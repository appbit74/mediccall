<div id="counter-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Counter Dashboard</h2>
        <div>
            <a href="log_report.php" class="btn btn-info btn-sm">
                <i class="bi bi-file-earmark-text"></i> ดูรายงาน Log
            </a>
            <button id="manual-sync-btn" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-clockwise"></i> Sync JERA
            </button>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white"><i class="bi bi-person-plus-fill"></i> คนไข้ใหม่ (จาก JERA)</div>
                <div class="card-body" id="new-patients-col"></div>
            </div>
        </div>
        <!-- <<-- [แก้ไข] เปลี่ยนเป็นคอลัมน์ "กำลังดำเนินการ" -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white"><i class="bi bi-hourglass-split"></i> กำลังดำเนินการ</div>
                <div class="card-body" id="in-process-col"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white"><i class="bi bi-cash-coin"></i> พร้อมชำระเงิน</div>
                <div class="card-body" id="payment-pending-col"></div>
            </div>
        </div>
    </div>
</div>
<!-- Modal for Assigning Doctor (ไม่มีการเปลี่ยนแปลง) -->
<div class="modal fade" id="assignDoctorModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">กำหนดแพทย์</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><p>คนไข้: <strong id="doctor-modal-patient-name"></strong></p><input type="hidden" id="doctor-modal-patient-id"><div class="mb-3"><label for="doctor-select" class="form-label">เลือกแพทย์</label><select class="form-select" id="doctor-select"></select></div></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button><button type="button" class="btn btn-primary" id="confirm-assign-doctor">ยืนยัน</button></div>
    </div></div>
</div>

