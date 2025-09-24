<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once __DIR__ . '/../configs/DB.php';
require_once __DIR__ . '/helpers.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_COOKIE['user_data'])) {
    echo "data: " . json_encode(['error' => 'Unauthorized']) . "\n\n";
    ob_flush();
    flush();
    exit;
}
$user = json_decode($_COOKIE['user_data'], true);
session_write_close();

function sendSseMessage($data) {
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

// ปิดการบีบอัดข้อมูล เพราะอาจรบกวนการทำงานของ SSE
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);

// สั่งให้ส่งข้อมูลออกไปทันทีที่ echo
@ini_set('implicit_flush', 1);
@ob_end_flush();
set_time_limit(0); // ป้องกันสคริปต์หมดเวลา

$pdo = getPDOConnection();
$last_hash = null;

while (true) {
    try{
        triggerJeraSyncIfNeeded($pdo);
        
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
            default:
                $response['error'] = 'Invalid role';
                break;
        }
    } catch (PDOException $e) {
        // Log error หรือพยายามเชื่อมต่อ DB ใหม่
        // หรือส่งข้อความ error กลับไปแล้ว exit
        sendSseMessage(['error' => 'Database connection failed. Please refresh.']);
        exit;
    }
    $current_hash = md5(json_encode($response));
    if ($current_hash !== $last_hash) {
        sendSseMessage($response);
        $last_hash = $current_hash;
    } else {
        // <<-- [แก้ไข] เพิ่มการส่ง comment เพื่อเป็น heartbeat -->>
        echo ": heartbeat\n\n";
        if (ob_get_level() > 0) { ob_flush(); }
        flush();
    }
    
    sleep(2);
}
?>