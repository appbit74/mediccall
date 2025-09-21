<?php
// 1. เรียกใช้ Autoloader ของ Composer
//    สคริปต์จะมองหาโฟลเดอร์ vendor ในระดับเดียวกันกับไฟล์ auth.php
require_once __DIR__ . '/vendor/autoload.php';

// 2. เรียกใช้ไฟล์ตั้งค่า
require_once 'configs/DB.php';

// 3. เริ่ม Session (ถ้ายังไม่เริ่ม)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 4. ตรวจสอบว่าเป็น POST request หรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// 5. รับค่า username และ password
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['err_message'] = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    header('Location: login.php');
    exit;
}

// 6. สร้างและกำหนดค่า HTTP_Request2
$request = new HTTP_Request2();
$request->setUrl(LOGIN_API_URL);
$request->setMethod(HTTP_Request2::METHOD_POST);
$request->setConfig(['follow_redirects' => true]);
$request->setHeader([
    'Content-Type' => 'application/x-www-form-urlencoded',
    'Cookie' => 'my_lang=th'
]);
$request->addPostParameter([
    'token' => LOGIN_API_TOKEN,
    'username' => $username,
    'password' => $password
]);

// 7. ส่ง Request และจัดการผลลัพธ์
try {
    $response = $request->send();

    if ($response->getStatus() == 200) {
        $apiData = json_decode($response->getBody(), true);

        if (isset($apiData['code']) && $apiData['code'] == '0' && isset($apiData['position'])) {
            // กำหนด Role ตาม 'position' (ปรับแก้ตัวเลขให้ตรงกับระบบของคุณ)
            switch ($apiData['position']) {
                case 4: $apiData['role'] = 'counter'; break;
                case 2: case 3: $apiData['role'] = 'therapist'; break;
                case 1: $apiData['role'] = 'doctor'; break;
                default:
                    $_SESSION['err_message'] = 'ชื่อผู้ใช้ของคุณไม่มีสิทธิ์ในการใช้ระบบ!';
                    header('Location: login.php');
                    exit;
            }

            // บันทึกข้อมูลลง Cookie อายุ 30 วัน
            $cookie_data = json_encode($apiData);
            setcookie('user_data', $cookie_data, time() + (86400 * 30), "/");

            header("Location: index.php");
            exit;

        } else {
            $_SESSION['err_message'] = $apiData['message'] ?? 'ข้อมูล API ไม่ถูกต้อง!';
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['err_message'] = 'ชื่อผู้ใช้ หรือ รหัสผ่าน ของคุณไม่ถูกต้อง!';
        header("Location: login.php");
        exit;
    }
} catch (HTTP_Request2_Exception $e) {
    $_SESSION['err_message'] = 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' . $e->getMessage();
    header('Location: login.php');
    exit;
}
?>

