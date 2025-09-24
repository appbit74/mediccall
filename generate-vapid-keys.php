<?php

// generate-vapid-keys.php

// เรียกใช้ Autoloader ของ Composer
require __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\VAPID;

// เรียกใช้ฟังก์ชันสร้าง VAPID keys จาก library โดยตรง
$vapidKeys = VAPID::createVapidKeys();

echo "✅ VAPID Keys Generated Successfully!\n\n";
echo "========================================\n";
echo "Public Key:\n";
echo $vapidKeys['publicKey'] . "\n\n";
echo "Private Key:\n";
echo $vapidKeys['privateKey'] . "\n";
echo "========================================\n\n";
echo "💡 Please copy these keys and save them securely in your config file.\n";
echo "You can now delete this script file (generate-vapid-keys.php).\n";

?>