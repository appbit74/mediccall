<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../configs/DB.php';
require_once __DIR__ . '/push_notification.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST method required']);
    exit;
}
if (!isset($_COOKIE['user_data'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = json_decode($_COOKIE['user_data'], true);
$pdo = getPDOConnection();
$action = $_POST['action'] ?? '';
$patient_id = $_POST['patient_id'] ?? 0;

if (empty($action) || empty($patient_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing action or patient ID']);
    exit;
}

function createLog($pdo, $patient_queue_id, $patient_name, $action_description, $user)
{
    $sql = "INSERT INTO patient_logs (patient_queue_id, patient_name, action_description, performed_by_id, performed_by_name) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$patient_queue_id, $patient_name, $action_description, $user['id'] ?? 'system', $user['name'] ?? 'System']);
}

try {
    // -- ดึงข้อมูลคนไข้เพื่อใช้สร้างข้อความแจ้งเตือน --
    $stmt_patient_info = $pdo->prepare("SELECT patient_name FROM patient_queue WHERE id = ?");
    $stmt_patient_info->execute([$patient_id]);
    $patient = $stmt_patient_info->fetch(PDO::FETCH_ASSOC);
    if (!$patient) {
        throw new Exception("Patient not found in queue.");
    }
    $patient_name = $patient['patient_name'];

    // -- ประกาศตัวแปรสำหรับข้อความแจ้งเตือน --
    $title = '';
    $body = '';
    $action_by = '';

    switch ($action) {
        case 'process_patient':
            $sql = "UPDATE patient_queue SET status = 'waiting_therapy' WHERE id = ? AND status = 'waiting_counter'";
            $pdo->prepare($sql)->execute([$patient_id]);
            createLog($pdo, $patient_id, $patient_name, "เคาน์เตอร์ส่งต่อคนไข้", $user);
            $title = 'คนไข้ใหม่ (กายภาพ)';
            $body = "คุณ $patient_name ถูกส่งต่อเข้ารับบริการกายภาพ";
            $action_by = 'counter';
            break;

        case 'assign_doctor':
            $doctor_name = $_POST['doctor_name'] ?? 'N/A';
            $sql = "UPDATE patient_queue SET assigned_doctor_id = ?, assigned_doctor_name = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$_POST['doctor_id'], $doctor_name, $patient_id]);
            createLog($pdo, $patient_id, $patient_name, "เคาน์เตอร์กำหนดแพทย์: " . $doctor_name, $user);
            $title = 'กำหนดแพทย์ให้คนไข้';
            $body = "แพทย์ $doctor_name ได้รับมอบหมายให้ดูแลคุณ $patient_name";
            $action_by = 'conuter';
            break;

        case 'assign_room':
            $room_name = $_POST['room_name'] ?? 'N/A';
            $doctor_name = $_POST['doctor_name'] ?? '';
            $params = [
                ':room_id' => $_POST['room_id'], 
                ':room_name' => $room_name, 
                ':therapist_id' => $user['id'], 
                ':therapist_name' => $user['name'], 
                ':doctor_id' => empty($_POST['doctor_id']) ? null : $_POST['doctor_id'], 
                ':doctor_name' => empty($doctor_name) ? null : $doctor_name, 
                ':patient_id' => $patient_id
            ];
            $sql = "UPDATE patient_queue SET status = 'in_therapy', assigned_room_id = :room_id, assigned_room_name = :room_name, 
                           assigned_therapist_id = :therapist_id, assigned_therapist_name = :therapist_name, assigned_doctor_id = :doctor_id, 
                           assigned_doctor_name = :doctor_name 
                    WHERE id = :patient_id AND status IN ('waiting_therapy', 'waiting_counter')";
            if (!$pdo->prepare($sql)->execute($params)) {
                throw new Exception("ไม่สามารถอัปเดตฐานข้อมูลได้");
            }
            $log_msg = "นักกายภาพรับงานเข้าห้อง " . $room_name . (empty($doctor_name) ? "" : " และกำหนดแพทย์: " . $doctor_name);
            createLog($pdo, $patient_id, $patient_name, $log_msg, $user);
            $title = 'คนไข้เข้ารับบริการ';
            $body = "คุณ $patient_name กำลังรับบริการที่ห้อง $room_name โดยคุณ $therapist_name";
            $action_by = 'therapist';
            break;

        case 'notify_doctor':
            $sql = "UPDATE patient_queue SET status = 'waiting_doctor' WHERE id = ? AND status = 'in_therapy'";
            $pdo->prepare($sql)->execute([$patient_id]);
            createLog($pdo, $patient_id, $patient_name, "นักกายภาพแจ้งแพทย์", $user);
            $title = 'แจ้งเตือนตรวจคนไข้';
            $body = "คุณ $patient_name พร้อมเข้ารับการตรวจจากแพทย์แล้ว";
            $action_by = 'therapist';
            break;

        case 'finish_consult':
            $sql = "UPDATE patient_queue SET status = 'payment_pending' 
                    WHERE id = ? AND status IN ('waiting_doctor', 'in_therapy', 'waiting_therapy')";
            $pdo->prepare($sql)->execute([$patient_id]);
            createLog($pdo, $patient_id, $patient_name, "แพทย์ตรวจเสร็จสิ้น", $user);
            /* $title = 'รอชำระเงิน';
            $body = "คุณ $patient_name ตรวจเสร็จสิ้นและกำลังรอชำระเงินที่เคาน์เตอร์"; */
            // ไม่ต้องส่ง Notification เพราะเป็นขั้นตอนสุดท้าย
            break;

        case 'complete_payment':
            $sql = "UPDATE patient_queue SET status = 'completed' 
                    WHERE id = ? AND status IN ('payment_pending', 'waiting_doctor', 'in_therapy', 'waiting_therapy')";
            $pdo->prepare($sql)->execute([$patient_id]);
            createLog($pdo, $patient_id, $patient_name, "เคาน์เตอร์รับชำระเงินเรียบร้อย (Override)", $user);
            // ไม่ต้องส่ง Notification เพราะเป็นขั้นตอนสุดท้าย
            break;

        case 'send_back_to_therapy':
            
            $stmt_patient = $pdo->prepare("SELECT assigned_therapist_id FROM patient_queue WHERE id = ?");
            $stmt_patient->execute([$patient_id]);
            $patient = $stmt_patient->fetch();
            if ($patient && !empty($patient['assigned_therapist_id'])) {
                $sql = "UPDATE patient_queue SET status = 'in_therapy' WHERE id = ?";
                $pdo->prepare($sql)->execute([$patient_id]);
                createLog($pdo, $patient_id, $patient_name, "แพทย์ส่งกลับไปให้นักกายภาพ (คนเดิม)", $user);
            } else {
                $sql = "UPDATE patient_queue SET status = 'waiting_therapy' WHERE id = ?";
                $pdo->prepare($sql)->execute([$patient_id]);
                createLog($pdo, $patient_id, $patient_name, "แพทย์ส่งกลับไปรอทำกายภาพ (คิวกลาง)", $user);
            }
            $title = 'ส่งกลับทำกายภาพ';
            $body = "คุณ $patient_name ถูกส่งกลับมาเพื่อทำกายภาพเพิ่มเติม";
            $action_by = 'therapist';

            break;

        case 'therapist_finish_work':
            $sql = "UPDATE patient_queue SET status = 'payment_pending' WHERE id = ? AND status = 'in_therapy'";
            $pdo->prepare($sql)->execute([$patient_id]);
            createLog($pdo, $patient_id, $patient_name, "นักกายภาพจบงาน (ส่งชำระเงิน)", $user);
            /* $title = 'รอชำระเงิน';
            $body = "คุณ $patient_name ทำกายภาพเสร็จสิ้นและกำลังรอชำระเงิน"; */
            // ไม่ต้องส่ง Notification เพราะเป็นขั้นตอนสุดท้าย
            break;

        default:
            throw new Exception("Invalid action provided");
    }

    // -- ตรวจสอบว่ามีข้อความที่ต้องส่งหรือไม่ ก่อนเรียกใช้ฟังก์ชัน --
    if (!empty($title)) {
        pushNotifications($pdo, $title, $body, $patient_id, $user);
    }

    echo json_encode(['status' => 'success', 'message' => 'Action completed successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
