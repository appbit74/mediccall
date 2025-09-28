<?php
// ตรวจสอบ cookie ถ้าไม่มีให้กลับไปหน้า login
if (!isset($_COOKIE['user_data'])) {
    header('Location: login.php');
    exit;
}
$user = json_decode($_COOKIE['user_data'], true);

// ส่วน Header
include 'layout/_header.php';

// แสดงหน้าจอตาม Role ของผู้ใช้
switch ($user['role']) {
    case 'counter':
        include 'views/view_counter.php';
        break;
    case 'therapist':
        include 'views/view_therapist.php';
        break;
    case 'doctor':
        include 'views/view_doctor.php';
        break;
    default:
        echo '<div class="alert alert-danger">ไม่พบ Role ของคุณในระบบ</div>';
        break;
}

// ส่วน Footer
include 'layout/_footer.php';
?>
