<?php
// กำหนดค่าการเชื่อมต่อฐานข้อมูล
define('DB_HOST', 'localhost');
define('DB_NAME', 'mediccall'); // <<-- ใส่ชื่อฐานข้อมูลของคุณ
define('DB_USER', 'root');      // <<-- ใส่ชื่อผู้ใช้
define('DB_PASS', '');          // <<-- ใส่รหัสผ่าน

// กำหนดค่า API สำหรับ Login
define('LOGIN_API_URL', 'https://salary.psrehabccenter.com/api.php/v1/user/login');
define('LOGIN_API_TOKEN', '3ccb9ac467ae7111449c0cdf30cff0c3198fa55a');

// กำหนดค่า JERA CLOUD API (สำหรับดึงคิว)
define('JERA_API_TOKEN_URL', 'https://ps.jeracloud.com/openapi/v1/token/');
define('JERA_API_CLINIC_URL', 'https://ps.jeracloud.com/openapi/v1/clinic/');
define('JERA_API_AUTH_BASIC', 'UUt3dkRNUFZoazRNQkJ4aU1FcnlWVVJkOU9pSktUU0xYa2xOeGJsdzpVYUhOYmhsVjE2Y3JKakp5MXJtUndQTUV4dHY2UW9EMWdoZTU2OVZya0ZUQ0Z3TjVDaTh5eTU2dWE4ZUVDZkNVbjFRNGpJNmRZNVZuaVRjRlBDbFhTSlUyTGh0WGpuWnpGbXQ4MkJ6cm1EZmJLc2ZodG1weG1EY2huQjhTNGtxWA==');


// ฟังก์ชันสำหรับจัดการการเชื่อมต่อ PDO
function getPDOConnection() {
    static $db = null;
    if ($db === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $db = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // ในระบบจริงควร log error แทนการ die()
            die('การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . $e->getMessage());
        }
    }
    return $db;
}

// เริ่ม Session เพื่อใช้แสดงข้อความ error ชั่วคราว
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

