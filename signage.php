<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ตั้งค่าให้รีเฟรชหน้าทุก 15 นาที เพื่อป้องกันปัญหา memory leak จากการเปิดนานๆ -->
    <meta http-equiv="refresh" content="900">
    <title>สถานะห้องตรวจ - PS Rehab Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="css/signage.css" rel="stylesheet">
</head>
<body>
    <header class="signage-header">
        <img src="https://salary.psrehabccenter.com/datas/images/logo.png" alt="Logo">
        <h1 class="header-title">PS Medical Infomation System</h1> 
        <div id="clock"></div>
    </header>

    <main id="signage-board" class="container-fluid">
        <!-- คอลัมน์ของแพทย์จะถูกสร้างขึ้นที่นี่โดย Javascript -->
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="js/signage.js"></script>
</body>
</html>
