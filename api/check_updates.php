<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../configs/DB.php';
require_once __DIR__ . '/helpers.php';

$user = json_decode($_COOKIE['user_data'], true);
$pdo = getPDOConnection();

// รับค่า timestamp ล่าสุดที่ client รู้จัก
$lastSyncTime = $_GET['lastSyncTime'] ?? '1970-01-01 00:00:00';

triggerJeraSyncIfNeeded($pdo);
        
    $data = [];
    $view = $user['role'] ?? '';
    switch($view) {
        case 'counter':
            $data['new_patients'] = $pdo->query("SELECT * FROM patient_queue WHERE status = 'waiting_counter' ORDER BY created_at DESC")->fetchAll();
            $data['in_process_patients'] = $pdo->query("SELECT * FROM patient_queue WHERE status IN ('waiting_therapy', 'in_therapy', 'waiting_doctor') ORDER BY last_updated_at DESC")->fetchAll();
            $data['payment_pending'] = $pdo->query("SELECT * FROM patient_queue WHERE status = 'payment_pending' ORDER BY last_updated_at DESC")->fetchAll();
            break;
        case 'therapist':
            $data['new_patients'] = $pdo->query("SELECT * FROM patient_queue WHERE status = 'waiting_counter' ORDER BY created_at DESC")->fetchAll();
            $data['waiting_therapy'] = $pdo->query("SELECT * FROM patient_queue WHERE status = 'waiting_therapy' ORDER BY last_updated_at DESC")->fetchAll();
            $data['in_therapy'] = $pdo->query("SELECT * FROM patient_queue WHERE status = 'in_therapy' ORDER BY last_updated_at DESC")->fetchAll();
            break;
        case 'doctor':
            $doctor_id = $user['id'] ?? '0';
            $sql = "SELECT * FROM patient_queue WHERE status IN ('waiting_therapy', 'in_therapy', 'waiting_doctor') AND assigned_doctor_id = :doctor_id ORDER BY last_updated_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['doctor_id' => $doctor_id]);
            $data['my_patients'] = $stmt->fetchAll();
            break;
        default:
            $data['error'] = 'Invalid role';
            break;
    }


// สร้าง object เพื่อส่งกลับไปให้ JavaScript
$response = [
    'updates' => $data,
    'newSyncTime' => gmdate('Y-m-d H:i:s') // ใช้เวลามาตรฐาน UTC
];

echo json_encode($response);