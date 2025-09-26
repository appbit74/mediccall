<?php if (!isset($user)) { die('Access Denied'); } ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PS Medical Information System</title>
    <!-- <<-- [เพิ่ม] PWA Manifest & Theme Color -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#00854a">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="css/style.css" rel="stylesheet">
    <link rel="shortcut icon" href="assets/icons/icon-192.png" type="image/x-icon">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="JERA Queue">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
</head>
<body>
<!-- <<-- [ใหม่] Loading Animation Overlay -->
<div id="loading-overlay">
    <div class="spinner"></div>
</div>

<audio id="notification-sound" src="assets/sounds/notification.mp3" preload="auto"></audio>
<audio id="payment-sound" src="assets/sounds/payment.mp3" preload="auto"></audio>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <img src="https://salary.psrehabccenter.com/datas/images/logo.png" alt="Logo" height="40" class="d-none d-sm-inline"> 
            PS Medical Information System
        </a>
        <div class="d-flex align-items-center">
            <a href="signage.php" target="_blank" class="btn btn-info btn-sm me-3">
                <i class="bi bi-tv"></i> Open Signage
            </a>
            <span class="navbar-text me-3">
                สวัสดี, <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars(ucfirst($user['role'])); ?>)
            </span>
            <a href="logout.php" class="btn btn-outline-light">ออกจากระบบ</a>
        </div>
    </div>
</nav>
<div class="container-fluid mt-4">

