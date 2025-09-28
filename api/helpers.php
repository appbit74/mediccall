<?php
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * ดึงรายชื่อสมาชิก (แพทย์/นักกายภาพ) จาก API ภายนอก
 * @param int $position ID ตำแหน่งที่ต้องการ (เช่น 1 สำหรับแพทย์)
 * @return array|string รายชื่อสมาชิกหรือค่าว่างหากเกิดข้อผิดพลาด
 */
function getMemberListByPosition($position) {
    $request = new HTTP_Request2();
    $request->setUrl('https://salary.psrehabccenter.com/api.php/v1/memberlist/list');
    $request->setMethod(HTTP_Request2::METHOD_POST);
    $request->setConfig(['follow_redirects' => true]);
    $request->setHeader(['Content-Type' => 'application/x-www-form-urlencoded', 'Cookie' => 'my_lang=th']);
    $request->addPostParameter(['token' => '3ccb9ac467ae7111449c0cdf30cff0c3198fa55a', 'position' => $position]);
    try {
        $response = $request->send();
        return ($response->getStatus() == 200) ? json_decode($response->getBody(), true) : '';
    } catch (HTTP_Request2_Exception $e) { 
        error_log("Failed to get member list: " . $e->getMessage());
        return ''; 
    }
}

/**
 * ฟังก์ชันหลักในการตรวจสอบและสั่งซิงค์ข้อมูลจาก Jera API
 * @param PDO $pdo Object การเชื่อมต่อฐานข้อมูล
 * @param bool $force บังคับให้ซิงค์ทันทีหรือไม่
 */
function triggerJeraSyncIfNeeded($pdo, $force = false) {
    $sync_interval = 60; // Sync ทุก 60 วินาที
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'jera_last_sync_time' FOR UPDATE");
        $stmt->execute();
        $last_sync_time = (int)$stmt->fetchColumn();
        
        if ($force || (time() - $last_sync_time > $sync_interval)) {
            _syncJeraData($pdo);
            $stmt_update = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'jera_last_sync_time'");
            $stmt_update->execute([time()]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("JERA Sync Trigger Failed: " . $e->getMessage());
    }
}

/**
 * ดึงพยัญชนะตัวแรกของคำในภาษาไทย (ข้ามสระนำหน้า เช่น เ, แ, โ, ใ, ไ)
 * @param string $name คำที่ต้องการหาอักษรย่อ
 * @return string พยัญชนะตัวแรก
 */
function getThaiInitial($name) {
    if (empty($name)) {
        return '';
    }
    $leadingVowels = ['เ', 'แ', 'โ', 'ใ', 'ไ'];
    $firstChar = mb_substr($name, 0, 1, 'UTF-8');

    if (in_array($firstChar, $leadingVowels) && mb_strlen($name, 'UTF-8') > 1) {
        return mb_substr($name, 1, 1, 'UTF-8');
    }
    
    return $firstChar;
}

/**
 * Logic หลักในการซิงค์ข้อมูลคิวคนไข้จาก Jera API มายังฐานข้อมูล
 * @param PDO $pdo Object การเชื่อมต่อฐานข้อมูล
 */
function _syncJeraData($pdo) {
    $queueList = _fetchJeraQueue();
    if ($queueList === false) { 
        error_log("JERA API Sync Failed: Could not fetch queue list."); 
        return; 
    }

    if (is_array($queueList)) {
        try {
            $pdo->beginTransaction();
            $active_opd_uuids = [];

            $sql_insert = "INSERT INTO patient_queue (opd_uuid, patient_hn, patient_name, patient_short_name, status) 
                           VALUES (:opd_uuid, :hn, :name, :short_name, 'waiting_counter') 
                           ON DUPLICATE KEY UPDATE 
                               patient_name = VALUES(patient_name), 
                               patient_short_name = VALUES(patient_short_name)";
            $stmt = $pdo->prepare($sql_insert);

            foreach ($queueList as $p) {
                if (isset($p['opd']['uuid'], $p['patient_code'], $p['opd']['patient']['fname'], $p['opd']['patient']['lname'])) {
                    
                    $title = trim($p['opd']['patient']['title'] ?? '');
                    $fname = trim($p['opd']['patient']['fname']);
                    $lname = trim($p['opd']['patient']['lname']);

                    $full_name = $title . $fname . ' ' . $lname;
                    $fname_initial = getThaiInitial($fname);
                    $lname_initial = getThaiInitial($lname);
                    $short_name = $fname_initial . ' ' . $lname_initial . '.';

                    $stmt->execute([
                        ':opd_uuid' => $p['opd']['uuid'], 
                        ':hn' => $p['patient_code'], 
                        ':name' => $full_name,
                        ':short_name' => $short_name
                    ]);
                    
                    $active_opd_uuids[] = $p['opd']['uuid'];
                }
            }
            
            // เคลียร์คนไข้ที่สถานะ 'waiting_counter' แต่ไม่อยู่ใน Jera Queue แล้ว
            if (!empty($active_opd_uuids)) {
                $placeholders = implode(',', array_fill(0, count($active_opd_uuids), '?'));
                $pdo->prepare("DELETE FROM patient_queue WHERE status = 'waiting_counter' AND opd_uuid NOT IN ($placeholders)")->execute($active_opd_uuids);
            } else { 
                $pdo->query("DELETE FROM patient_queue WHERE status = 'waiting_counter'"); 
            }
            
            // เคลียร์คนไข้ที่อยู่ในกระบวนการอื่น แต่ไม่อยู่ใน Jera Queue แล้ว
            $in_process_statuses = "'waiting_therapy', 'in_therapy', 'waiting_doctor', 'payment_pending'";
            $stmt_in_process = $pdo->query("SELECT id, opd_uuid, patient_name FROM patient_queue WHERE status IN ($in_process_statuses)");
            $in_process_patients = $stmt_in_process->fetchAll();
            
            if (!empty($in_process_patients)) {
                $active_opd_map = array_flip($active_opd_uuids);
                $patients_to_complete_ids = [];
                foreach ($in_process_patients as $patient) {
                    if (!isset($active_opd_map[$patient['opd_uuid']])) {
                        $patients_to_complete_ids[] = $patient['id'];
                        $log_sql = "INSERT INTO patient_logs (patient_queue_id, patient_name, action_description, performed_by_id, performed_by_name) VALUES (?, ?, ?, ?, ?)";
                        $pdo->prepare($log_sql)->execute([$patient['id'], $patient['patient_name'], "เคลียร์คิวอัตโนมัติ (ไม่พบใน JERA)", "system", "System"]);
                    }
                }
                if (!empty($patients_to_complete_ids)) {
                    $placeholders = implode(',', array_fill(0, count($patients_to_complete_ids), '?'));
                    $pdo->prepare("UPDATE patient_queue SET status = 'completed' WHERE id IN ($placeholders)")->execute($patients_to_complete_ids);
                }
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("JERA Sync Failed within transaction: " . $e->getMessage());
        }
    }
}

/**
 * Wrapper function for Jera API calls.
 * @return array|false List of patients in queue or false on failure.
 */
function _fetchJeraQueue() {
    $token = _jera_getAccessToken();
    if (!$token) return false;
    $branchUUID = _jera_getBranchUUID($token);
    if (!$branchUUID) return false;
    return _jera_getQueueList($token, $branchUUID);
}

// ----- Jera API Specific Functions -----

// function _fetchJeraQueue() {
//     $accessToken = _jera_getAccessToken(); if ($accessToken === false) { return false; }
//     $branchUUID = _jera_getBranchUUID($accessToken); if ($branchUUID === false) { return false; }
//     return _jera_getQueueList($accessToken, $branchUUID);
// }
function _jera_getAccessToken() { $ch = curl_init(); curl_setopt_array($ch, [CURLOPT_URL => JERA_API_TOKEN_URL, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_POST => true, CURLOPT_POSTFIELDS => "grant_type=client_credentials", CURLOPT_HTTPHEADER => ["Authorization: Basic " . JERA_API_AUTH_BASIC, "Content-Type: application/x-www-form-urlencoded"]]); $res = curl_exec($ch); $err = curl_error($ch); curl_close($ch); if ($err) { error_log("cURL Error (Token): " . $err); return false; } $data = json_decode($res, true); return $data['access_token'] ?? false; }
function _jera_getBranchUUID($token) { $ch = curl_init(); curl_setopt_array($ch, [CURLOPT_URL => JERA_API_CLINIC_URL, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $token]]); $res = curl_exec($ch); $err = curl_error($ch); curl_close($ch); if ($err) { error_log("cURL Error (Branch): " . $err); return false; } $data = json_decode($res, true); return $data['branches'][0]['uuid'] ?? false; }
function _jera_getQueueList($token, $branchUUID) {
    $ch = curl_init(); curl_setopt_array($ch, [CURLOPT_URL => JERA_API_CLINIC_URL . "branch/" . $branchUUID . "/queue/", CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $token]]);
    $res = curl_exec($ch); $err = curl_error($ch); curl_close($ch); if ($err) { error_log("cURL Error (Queue): " . $err); return false; }
    $data = json_decode($res, true); if (!is_array($data)) { return false; }
    $today = date('Y-m-d'); $todays_queue = [];
    foreach ($data as $patient) { if (isset($patient['opd']['create_date']) && substr($patient['opd']['create_date'], 0, 10) === $today) { $todays_queue[] = $patient; } }
    return $todays_queue;
}