<?php
// ตรวจสอบว่ามี cookie การ login อยู่หรือไม่ ถ้ามีให้ redirect ไปหน้า dashboard
if (isset($_COOKIE['user_data'])) {
    header('Location: index.php');
    exit;
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$error_message = '';
if (isset($_SESSION['err_message'])) {
    $error_message = $_SESSION['err_message'];
    unset($_SESSION['err_message']); // แสดงข้อความแค่ครั้งเดียว
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PS Rehab Center Queue System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f6;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .login-header {
            background-color: #00854a; /* Green from website */
            color: white;
            padding: 2rem;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            text-align: center;
        }
        .logo {
            max-width: 250px;
            margin-bottom: 1rem;
        }
        .btn-login {
            background-color: #00854a;
            border-color: #00854a;
            font-weight: bold;
            padding: 0.75rem;
        }
        .btn-login:hover {
            background-color: #006a3b;
            border-color: #006a3b;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="col-md-5 col-lg-4">
            <div class="card login-card">
                <div class="login-header">
                    <img src="https://salary.psrehabccenter.com/datas/images/logo.png" alt="PS Rehab Center Logo" class="logo">
                    <h4>ระบบจัดการคิวผู้ป่วย</h4>
                </div>
                <div class="card-body p-4 p-md-5">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    <form action="auth.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">รหัสผ่าน</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-login">เข้าสู่ระบบ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
