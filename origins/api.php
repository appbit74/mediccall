<?php
header('Content-Type: application/json');

// 1. Authen เพื่อรับ access_token
function getAccessToken() {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://ps.jeracloud.com/openapi/v1/token/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_HTTPHEADER => [
            "Authorization: Basic UUt3dkRNUFZoazRNQkJ4aU1FcnlWVVJkOU9pSktUU0xYa2xOeGJsdzpVYUhOYmhsVjE2Y3JKakp5MXJtUndQTUV4dHY2UW9EMWdoZTU2OVZya0ZUQ0Z3TjVDaTh5eTU2dWE4ZUVDZkNVbjFRNGpJNmRZNVZuaVRjRlBDbFhTSlUyTGh0WGpuWnpGbXQ4MkJ6cm1EZmJLc2ZodG1weG1EY2huQjhTNGtxWA==",
            "Content-Type: application/x-www-form-urlencoded"
        ],
    ]);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
        return ['error' => 'cURL Error #1: ' . $err];
    }
    $data = json_decode($response, true);
    return $data['access_token'] ?? ['error' => 'Invalid access token response'];
}

// 2. นำ access_token มาทำการเรียกข้อมูล สาขา
function getBranchUUID($token) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://ps.jeracloud.com/openapi/v1/clinic/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $token
        ],
    ]);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
        return ['error' => 'cURL Error #2: ' . $err];
    }
    $data = json_decode($response, true);
    return $data['branches'][0]['uuid'] ?? ['error' => 'Invalid branch UUID response'];
}

// 3. ดึงรายการ ทั้งหมดจาก queue
function getQueueList($token, $branchUUID) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://ps.jeracloud.com/openapi/v1/clinic/branch/" . $branchUUID . "/queue/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $token
        ],
    ]);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
        return ['error' => 'cURL Error #3: ' . $err];
    }
    $data = json_decode($response, true);
    return $data ?? ['error' => 'Invalid queue list response'];
}

// Main logic to execute the functions
$accessToken = getAccessToken();
if (isset($accessToken['error'])) {
    echo json_encode(['status' => 'error', 'message' => $accessToken['error']]);
    exit;
}

$branchUUID = getBranchUUID($accessToken);
if (isset($branchUUID['error'])) {
    echo json_encode(['status' => 'error', 'message' => $branchUUID['error']]);
    exit;
}

$queueList = getQueueList($accessToken, $branchUUID);
if (isset($queueList['error'])) {
    echo json_encode(['status' => 'error', 'message' => $queueList['error']]);
    exit;
}

// Return the final data as JSON
echo json_encode(['status' => 'success', 'data' => $queueList]);
?>