<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../configs/DB.php';
require_once __DIR__ . '/helpers.php'; // สำหรับ getMemberListByPosition

$pdo = getPDOConnection();

try {
    // 1. ดึงรายชื่อแพทย์ทั้งหมดจาก API มาเตรียมไว้
    $doctorsData = getMemberListByPosition(1);
    $allDoctors = [];
    if (is_array($doctorsData) && !empty($doctorsData)) {
        foreach ($doctorsData as $id => $details) {
            if (isset($details['name'])) {
                $allDoctors[$id] = [
                    'doctor_id' => $id,
                    'doctor_name' => $details['name'],
                    'color' => $details['color'] ?? '#6c757d',
                    'patients' => [] // สร้าง array ว่างสำหรับเก็บคนไข้
                ];
            }
        }
    }

    // 2. ดึงข้อมูลคนไข้ทั้งหมดที่อยู่ในสถานะเกี่ยวข้องกับแพทย์
    $sql = "SELECT patient_name, assigned_room_name, status, assigned_doctor_id 
            FROM patient_queue 
            WHERE status IN ('in_therapy', 'waiting_doctor') 
            AND assigned_doctor_id IS NOT NULL";
    
    $patients = $pdo->query($sql)->fetchAll();

    // 3. นำคนไข้ไปใส่ในคอลัมน์ของแพทย์ที่รับผิดชอบ
    foreach ($patients as $patient) {
        $doctorId = $patient['assigned_doctor_id'];
        if (isset($allDoctors[$doctorId])) {
            $allDoctors[$doctorId]['patients'][] = [
                'patient_name' => $patient['patient_name'],
                'room_name' => $patient['assigned_room_name'],
                'status' => $patient['status']
            ];
        }
    }

    // 4. [ใหม่] คัดกรองเอาเฉพาะแพทย์ที่มีคนไข้ (patients array không rỗng)
    $activeDoctors = array_filter($allDoctors, function($doctor) {
        return !empty($doctor['patients']);
    });

    // 5. คืนค่าข้อมูลเฉพาะแพทย์ที่มีคิวคนไข้
    echo json_encode(array_values($activeDoctors));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

