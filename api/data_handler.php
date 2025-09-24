<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../configs/DB.php';
require_once __DIR__ . '/helpers.php';

if (isset($_GET['action']) && $_GET['action'] === 'manual_sync') { triggerJeraSyncIfNeeded(getPDOConnection(), true); echo json_encode(['status' => 'success', 'message' => 'Manual sync completed.']); exit; }
if (!isset($_COOKIE['user_data'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
$user = json_decode($_COOKIE['user_data'], true);
$pdo = getPDOConnection();

// <<-- [แก้ไข] ย้ายการเรียก Sync มาไว้ที่นี่ เพื่อให้ถูกเรียกทุกครั้งที่ Polling -->>
triggerJeraSyncIfNeeded($pdo);

if (isset($_GET['get'])) {
    if ($_GET['get'] === 'doctors') {
        $doctorsData = getMemberListByPosition(1); $formattedDoctors = [];
        if (is_array($doctorsData) && !empty($doctorsData)) {
            foreach ($doctorsData as $id => $details) { if (isset($details['name'])) { $formattedDoctors[] = ['id' => $id, 'name' => $details['name']]; } }
        } echo json_encode($formattedDoctors); exit;
    }
    if ($_GET['get'] === 'rooms') {
        $sql_in_use = "SELECT DISTINCT assigned_room_id FROM patient_queue WHERE assigned_room_id IS NOT NULL AND status IN ('in_therapy', 'waiting_doctor')";
        $in_use_rooms = $pdo->query($sql_in_use)->fetchAll(PDO::FETCH_COLUMN);
        $all_rooms = $pdo->query("SELECT uuid, name FROM rooms WHERE is_active = 1 ORDER BY uuid ASC")->fetchAll(PDO::FETCH_ASSOC);
        $available_rooms = array_filter($all_rooms, fn($r) => !in_array($r['uuid'], $in_use_rooms));
        echo json_encode(array_values($available_rooms)); exit;
    }
}

$view = $_GET['view'] ?? ''; $response = [];
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
    default: http_response_code(400); $response['error'] = 'Invalid view'; break;
}
echo json_encode($response);
?>