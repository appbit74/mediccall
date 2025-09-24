<?php
// api/save_subscription.php
header('Content-Type: application/json');
require_once __DIR__ . '/../configs/DB.php';
// อาจจะต้องมี logic ดึง user id จาก cookie
$user = json_decode($_COOKIE['user_data'], true);
$pdo = getPDOConnection();


$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['endpoint'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid subscription data']);
    exit;
}
// echo json_encode(['status' => 'step1 pass','data_p256dh' => $data['keys']['p256dh']]);

try {
    // สร้าง placeholder ชื่อใหม่สำหรับส่วน UPDATE
    $sql = "INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) 
            VALUES (:user_id, :endpoint, :p256dh, :auth)
            ON DUPLICATE KEY UPDATE 
                p256dh = :update_p256dh, 
                auth = :update_auth";
    
    $stmt = $pdo->prepare($sql);
    
    // เพิ่มค่าสำหรับ placeholder ใหม่เข้าไปใน array (ใช้ค่าเดียวกัน)
    $stmt->execute([
        ':user_id'       => $user['id'] ?? '0',
        ':endpoint'      => $data['endpoint'],
        ':p256dh'        => $data['keys']['p256dh'],
        ':auth'          => $data['keys']['auth'],
        ':update_p256dh' => $data['keys']['p256dh'], // ค่าเดียวกับ :p256dh
        ':update_auth'   => $data['keys']['auth']    // ค่าเดียวกับ :auth
    ]);
    
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>