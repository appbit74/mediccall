<?php
// ตั้งค่า header สำหรับ SSE ก่อนเสมอ
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once __DIR__ . '/../configs/DB.php';
require_once __DIR__ . '/helpers.php';

// <<-- [แก้ไข] จัดการ Session อย่างถูกต้อง -->>
// 1. เริ่ม Session เพื่ออ่านข้อมูล
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. ตรวจสอบ Cookie และดึงข้อมูลผู้ใช้
if (!isset($_COOKIE['user_data'])) {
    echo "data: " . json_encode(['error' => 'Unauthorized']) . "\n\n";
    ob_flush(); flush();
    exit;
}
$user = json_decode($_COOKIE['user_data'], true);

// 3. (สำคัญที่สุด) ปิด Session ทันทีเพื่อปลดล็อคไฟล์
session_write_close(); 

// 4. หลังจากนี้ สคริปต์สามารถทำงานได้ยาวนานโดยไม่กระทบกับส่วนอื่น
function sendSseMessage($data) {
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level() > 0) { ob_flush(); }
    flush();
}

set_time_limit(0);
@ini_set('zlib.output_compression', 0);
if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', 1); }

$pdo = getPDOConnection();
$last_hash = null;
$sync_interval = 10; // Sync JERA ทุก 10 วินาที
$last_sync_time = 0;

// วนลูปทำงานหลัก
while (true) {
    $current_time = time();
    if (($current_time - $last_sync_time) >= $sync_interval) {
        triggerJeraSyncIfNeeded($pdo);
        $last_sync_time = $current_time;
    }
    
    $response = [];
    $view = $user['role'] ?? '';
    switch($view) {
        case 'counter':
            $response['new_patients'] = $pdo->query("SELECT * FROM patient_queue WHERE status = 'waiting_counter' ORDER BY created_at DESC")->fetchAll();
            $response['in_process_patients'] = $pdo->query("SELECT * FROM patient_queue WHERE status IN ('waiting_therapy', 'in_therapy', 'waiting_doctor') ORDER BY last_updated_at DESC")->fetchAll();
            $response['payment_pending'] = $pdo->query("SELECT * FROM patient_queue WHERE status = 'payment_pending' ORDER BY last_updated_at DESC")->fetchAll();
            break;
        case 'therapist':
            $response['new_patients'] = $pdo->query("SELECT * FROM patient_queue WHERE status = 'waiting_counter' ORDER BY created_at DESC")->fetchAll();
            $response['waiting_therapy'] = $pdo->query("SELECT * FROM patient_queue WHERE status = 'waiting_therapy' ORDER BY last_updated_at DESC")->fetchAll();
            $response['in_therapy'] = $pdo->query("SELECT * FROM patient_queue WHERE status = 'in_therapy' ORDER BY last_updated_at DESC")->fetchAll();
            break;
        case 'doctor':
            $doctor_id = $user['id'] ?? '0';
            $sql = "SELECT * FROM patient_queue WHERE status IN ('waiting_therapy', 'in_therapy', 'waiting_doctor') AND assigned_doctor_id = :doctor_id ORDER BY last_updated_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['doctor_id' => $doctor_id]);
            $response['my_patients'] = $stmt->fetchAll();
            break;
        default: $response['error'] = 'Invalid role'; break;
    }

    $current_hash = md5(json_encode($response));
    if ($current_hash !== $last_hash) {
        sendSseMessage($response);
        $last_hash = $current_hash;
    }
    
    sleep(2);
}
?>

