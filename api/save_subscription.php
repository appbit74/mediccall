<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../configs/DB.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_COOKIE['user_data'])) { http_response_code(401); echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit; }
$user = json_decode($_COOKIE['user_data'], true);
session_write_close();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST method required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['endpoint']) || !isset($data['keys']['p256dh']) || !isset($data['keys']['auth'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid subscription object']);
    exit;
}

$pdo = getPDOConnection();
$sql = "INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)"; // อัปเดต user_id ถ้า endpoint ซ้ำ

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user['id'],
        $data['endpoint'],
        $data['keys']['p256dh'],
        $data['keys']['auth']
    ]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
