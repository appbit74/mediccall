<?php
// ตั้งค่าให้แสดง error ทั้งหมดเพื่อการ debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/configs/DB.php';
require_once __DIR__ . '/api/helpers.php';

echo "===== JERA API Connection Test =====\n\n";

// --- Step 1: Get Access Token ---
echo "Step 1: Fetching Access Token...\n";
$accessToken = _jera_getAccessToken();
if ($accessToken === false) {
    die("FAILED: Could not get Access Token. Please check API credentials and connection.\n");
}
echo "SUCCESS! Token received: " . substr($accessToken, 0, 10) . "...\n\n";


// --- Step 2: Get Branch UUID ---
echo "Step 2: Fetching Branch UUID...\n";
$branchUUID = _jera_getBranchUUID($accessToken);
if ($branchUUID === false) {
    die("FAILED: Could not get Branch UUID. The Access Token might be invalid.\n");
}
echo "SUCCESS! Branch UUID received: " . $branchUUID . "\n\n";


// --- Step 3: Get Queue List (Today Only) ---
echo "Step 3: Fetching today's patient queue...\n";
$queueList = _jera_getQueueList($accessToken, $branchUUID);
if ($queueList === false) {
    die("FAILED: Could not get Queue List.\n");
}
echo "SUCCESS! Found " . count($queueList) . " patient(s) in today's queue.\n\n";


// --- Final Result ---
echo "===== Final Data to be Synced =====\n";
print_r($queueList);

echo "\n\n===== Test Completed =====";
?>
