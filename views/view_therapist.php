<div id="therapist-dashboard">
    <!-- <<-- [เพิ่ม] ปุ่ม Reload -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Therapist Dashboard</h2>
        <button id="therapist-reload-btn" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-clockwise"></i> Reload
        </button>
    </div>
    <div class="row">
        <div class="col-md-4"><div class="card"><div class="card-header bg-primary text-white"><i class="bi bi-person-plus-fill"></i> คนไข้ใหม่ (จาก JERA)</div><div class="card-body" id="new-patients-col"></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-header bg-warning"><i class="bi bi-person-walking"></i> รอทำกายภาพ (ส่งจากเคาน์เตอร์)</div><div class="card-body" id="waiting-therapy-col"></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-header bg-success text-white"><i class="bi bi-check-circle-fill"></i> กำลังทำกายภาพ</div><div class="card-body" id="in-therapy-col"></div></div></div>
    </div>
</div>
<div class="modal fade" id="acceptCaseModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">รับงาน & กำหนดห้อง/แพทย์</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><p>คนไข้: <strong id="accept-case-patient-name"></strong></p><input type="hidden" id="accept-case-patient-id"><div class="mb-3"><label for="room-select" class="form-label">เลือกห้อง</label><select class="form-select" id="room-select"></select></div><div class="mb-3"><label for="doctor-select-therapist" class="form-label">กำหนดแพทย์ (ถ้ามี)</label><select class="form-select" id="doctor-select-therapist"></select></div></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button><button type="button" class="btn btn-primary" id="confirm-accept-case">ยืนยัน</button></div>
    </div></div>
</div>

