<?php
header('Content-Type: application/json');

// Include the database class
require 'DB.php';

$input = json_decode(file_get_contents('php://input'), true);
$opd_uuid = $input['opd_uuid'] ?? null;
$room_uuid = $input['room_uuid'] ?? null;

if (!$opd_uuid || !$room_uuid) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data.']);
    exit;
}

try {
    // Connect to the database
    $db = new DB();
    $pdo = $db->getConnection();

    // Prepare SQL to insert data
    $sql = "INSERT INTO opd_room_assignments (opd_uuid, room_uuid) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$opd_uuid, $room_uuid]);

    echo json_encode(['status' => 'success', 'message' => 'Room assigned and saved successfully.']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'General error: ' . $e->getMessage()]);
}

?>