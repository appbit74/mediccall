<?php
header('Content-Type: application/json');

require_once 'DB.php';

try {
    // 1. Connect to the database
    $db = new DB();
    $pdo = $db->getConnection();

    // 2. Get a list of rooms that are currently in use
    $sql_in_use = "SELECT DISTINCT room_uuid FROM opd_room_assignments";
    $stmt_in_use = $pdo->prepare($sql_in_use);
    $stmt_in_use->execute();
    $in_use_rooms = $stmt_in_use->fetchAll(PDO::FETCH_COLUMN);

    // 3. Get all active rooms from the `rooms` table
    $sql_all_rooms = "SELECT uuid, name FROM rooms WHERE is_active = 1 ORDER BY id ASC";
    $stmt_all_rooms = $pdo->prepare($sql_all_rooms);
    $stmt_all_rooms->execute();
    $all_rooms = $stmt_all_rooms->fetchAll(PDO::FETCH_ASSOC);

    // 4. Filter out rooms that are in use
    $available_rooms = array_filter($all_rooms, function($room) use ($in_use_rooms) {
        return !in_array($room['uuid'], $in_use_rooms);
    });

    // 5. Add an empty option as the first element for user selection
    array_unshift($available_rooms, ['uuid' => '', 'name' => '-- เลือกห้องตรวจ --']);

    // 6. Return the data as a JSON response
    echo json_encode(array_values($available_rooms));

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => 'General error: ' . $e->getMessage()]);
}

?>