<?php
// ตั้งค่าให้สคริปต์ทำงานได้จาก Command Line
// และป้องกันการรันผ่านเบราว์เซอร์โดยตรง
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

// กำหนด Path ไปยังไฟล์ที่จำเป็น
require_once __DIR__ . '/configs/DB.php';
require_once __DIR__ . '/api/helpers.php';

echo "Starting JERA API Sync at " . date('Y-m-d H:i:s') . "\n";

try {
    $pdo = getPDOConnection();
    syncWithJeraAPI($pdo);
    echo "Sync completed successfully.\n";
} catch (Exception $e) {
    // ในระบบจริง ควรเขียน Error ลงไฟล์ Log
    echo "An error occurred: " . $e->getMessage() . "\n";
    error_log("JERA Sync Cron Job Failed: " . $e->getMessage());
}
?>
