<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

function sendPushNotification($pdo, $target_user_id, $title, $body) {
    if (!defined('VAPID_PUBLIC_KEY') || !defined('VAPID_PRIVATE_KEY')) {
        error_log("VAPID keys are not defined.");
        return;
    }
    
    // 1. ค้นหา Subscriptions ของ User เป้าหมาย
    $stmt = $pdo->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
    $stmt->execute([$target_user_id]);
    $subscriptions = $stmt->fetchAll();

    if (empty($subscriptions)) {
        return; // ไม่มีอุปกรณ์ให้ส่ง
    }

    $auth = [
        'VAPID' => [
            'subject' => 'mailto:your-email@example.com', // แก้เป็นอีเมลของคุณ
            'publicKey' => VAPID_PUBLIC_KEY,
            'privateKey' => VAPID_PRIVATE_KEY,
        ],
    ];

    $webPush = new WebPush($auth);
    $payload = json_encode(['title' => $title, 'body' => $body]);

    // 2. ส่ง Notification ไปยังทุกอุปกรณ์ของ User
    foreach ($subscriptions as $sub) {
        $subscription = Subscription::create([
            'endpoint' => $sub['endpoint'],
            'publicKey' => $sub['p256dh'],
            'authToken' => $sub['auth'],
        ]);
        $webPush->queueNotification($subscription, $payload);
    }
    
    // 3. ส่ง Notification ทั้งหมดที่อยู่ในคิว
    foreach ($webPush->flush() as $report) {
        $endpoint = $report->getRequest()->getUri()->__toString();
        if (!$report->isSuccess()) {
            error_log("[x] Message failed to sent for endpoint '{$endpoint}': {$report->getReason()}");
            // ถ้า endpoint หมดอายุ ควรลบออกจากฐานข้อมูล
            if ($report->isSubscriptionExpired()) {
                $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?")->execute([$endpoint]);
            }
        }
    }
}
?>
