<?php
header('Content-Type: application/json');

require_once 'DB.php';

// ตรวจสอบว่าเป็น POST request หรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'ต้องใช้เมธอด POST เท่านั้น']);
    exit;
}

// รับข้อมูล JSON จาก request body
$input = json_decode(file_get_contents('php://input'), true);

$opd_uuid = $input['opd_uuid'] ?? null;
$room_uuid = $input['room_uuid'] ?? null;
$room_name = $input['room_name'] ?? null;

// ตรวจสอบข้อมูล
if (empty($opd_uuid) || empty($room_uuid) || empty($room_name)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    $db = new DB();
    $pdo = $db->getConnection();

    // ใช้ INSERT ... ON DUPLICATE KEY UPDATE เพื่อจัดการกรณีมีการจ่ายห้องให้คนไข้คนเดิมซ้ำ
    // จะทำการอัปเดตห้องใหม่แทนการเพิ่มข้อมูลซ้ำซ้อน
    $sql = "INSERT INTO opd_room_assignments (opd_uuid, room_uuid) VALUES (:opd_uuid, :room_uuid)
            ON DUPLICATE KEY UPDATE room_uuid = :room_uuid_update";
    
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':opd_uuid', $opd_uuid);
    $stmt->bindParam(':room_uuid', $room_uuid);
    $stmt->bindParam(':room_uuid_update', $room_uuid);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'กำหนดห้องตรวจสำเร็จ',
            'data' => [
                'opd_uuid' => $opd_uuid,
                'room_uuid' => $room_uuid,
                'room_name' => $room_name
            ]
        ]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถบันทึกข้อมูลได้']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
