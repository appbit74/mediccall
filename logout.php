<?php
// ลบ cookie โดยกำหนดเวลาในอดีต
setcookie('user_data', '', time() - 3600, "/");
header('Location: login.php');
exit;
?>
