<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// แก้ไข Signature ของฟังก์ชันให้รับเฉพาะ Parameter ที่ใช้งานจริง
function pushNotifications($pdo, $title, $body, $patient_queue_id, $user) {

    // --- ส่วนของ Logic การหาผู้รับที่แก้ไขใหม่ทั้งหมด ---

    // ถ้าผู้กระทำเป็น counter, ให้ส่งหาทุกคน (ตาม Logic เดิมของคุณ)
    if ($user['role'] === 'counter') {
        $stmt_subs = $pdo->query("SELECT * FROM push_subscriptions");
        $subscriptions = $stmt_subs->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // สำหรับ role อื่น ให้ส่งหาผู้ที่เกี่ยวข้องเท่านั้น

        // 1. ดึงข้อมูลแพทย์และนักกายภาพที่เกี่ยวข้องกับคนไข้
        $sql_patient = "SELECT assigned_doctor_id, assigned_therapist_id FROM patient_queue WHERE id = :patient_queue_id";
        $stmt_patient = $pdo->prepare($sql_patient);
        $stmt_patient->execute([':patient_queue_id' => $patient_queue_id]);
        $patient_roles = $stmt_patient->fetch(PDO::FETCH_ASSOC);

        if (!$patient_roles) { return; }

        // 2. รวบรวม ID ของผู้ที่อาจจะเกี่ยวข้องทั้งหมด
        $target_user_ids = [];
        if (!empty($patient_roles['assigned_doctor_id'])) {
            $target_user_ids[] = $patient_roles['assigned_doctor_id'];
        }
        if (!empty($patient_roles['assigned_therapist_id'])) {
            $target_user_ids[] = $patient_roles['assigned_therapist_id'];
        }
        
        // 3. ลบ ID ของคนที่กำลังกระทำออกจากลิสต์ (ไม่แจ้งเตือนตัวเอง)
        $current_user_id = $user['id'] ?? null;
        $target_user_ids = array_filter($target_user_ids, function($id) use ($current_user_id) {
            return $id != $current_user_id;
        });

        // 4. ทำให้รายชื่อไม่ซ้ำซ้อนและกรองค่าว่างออก
        $target_user_ids = array_values(array_unique(array_filter($target_user_ids)));

        if (empty($target_user_ids)) { return; }

        // 5. ดึง Subscriptions เฉพาะของ User ที่เกี่ยวข้อง
        $placeholders = implode(',', array_fill(0, count($target_user_ids), '?'));
        $sql_subs = "SELECT * FROM push_subscriptions WHERE user_id IN ($placeholders)";
        $stmt_subs = $pdo->prepare($sql_subs);
        $stmt_subs->execute($target_user_ids);
        $subscriptions = $stmt_subs->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- สิ้นสุดส่วน Logic ที่แก้ไข ---


    if (empty($subscriptions)) {
        return; // ไม่พบอุปกรณ์ที่ต้องส่งถึง
    }

    // ตั้งค่า VAPID keys
    $auth = [
        'VAPID' => [
            'subject' => 'mailto:appbit74@gmail.com',
            'publicKey' => 'BG7ecUqttn9OY62ggJTC6i-Gazp49hxDYuVEqKef3dJ57YsZkY-mo2oBr5wjGTOWOIWfnHWKdcpvS6GJfcc9mS8',
            'privateKey' => 'oGgaxVrGWf4DxO69vQU1QMxQhTDiuXsIGdpk6dBFfOc',
        ],
    ];
    $webPush = new WebPush($auth);

    $notificationPayload = json_encode([
        'title' => $title,
        'body' => $body,
        'vibrate' => [200, 100, 200]
    ]);
    
    foreach ($subscriptions as $sub) {
        $subscription = Subscription::create([
            'endpoint' => $sub['endpoint'], 'publicKey' => $sub['p256dh'], 'authToken' => $sub['auth'],
        ]);
        $webPush->queueNotification($subscription, $notificationPayload);
    }

    foreach ($webPush->flush() as $report) {
        $endpoint = $report->getRequest()->getUri()->__toString();
        if (!$report->isSuccess()) {
            error_log("Error sending to {$endpoint}: {$report->getReason()}");
        }
    }
}
?>