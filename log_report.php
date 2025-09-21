<?php
require_once __DIR__ . '/configs/DB.php';

if (!isset($_COOKIE['user_data'])) { header('Location: login.php'); exit; }
$user = json_decode($_COOKIE['user_data'], true);

// ตรวจสอบสิทธิ์: เฉพาะ Counter เท่านั้นที่ดูหน้านี้ได้
if ($user['role'] !== 'counter') {
    // Redirect ไปหน้า dashboard ของตัวเอง
    header('Location: index.php');
    exit;
}

$pdo = getPDOConnection();

// จัดการการกรองข้อมูลตามวันที่
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// เพิ่มเวลาสิ้นสุดของวันเข้าไปใน end_date เพื่อให้ค้นหาข้อมูลของวันนั้นได้ครบถ้วน
$end_date_for_query = $end_date . ' 23:59:59';

$sql = "SELECT * FROM patient_logs WHERE created_at BETWEEN :start_date AND :end_date ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date_for_query]);
$logs = $stmt->fetchAll();

include 'layout/_header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-file-earmark-text"></i> รายงาน Log การดำเนินงาน</h3>
        <a href="index.php" class="btn btn-secondary">กลับหน้า Dashboard</a>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">กรองข้อมูลตามวันที่</h5>
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="start_date" class="form-label">วันที่เริ่มต้น</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="col-md-5">
                    <label for="end_date" class="form-label">วันที่สิ้นสุด</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">ค้นหา</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive mt-4">
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>วัน-เวลา</th>
                    <th>ชื่อคนไข้</th>
                    <th>รายละเอียด</th>
                    <th>ผู้ดำเนินการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="4" class="text-center">ไม่พบข้อมูล Log ในช่วงวันที่ที่เลือก</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($log['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($log['action_description']); ?></td>
                            <td><?php echo htmlspecialchars($log['performed_by_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include 'layout/_footer.php';
?>
