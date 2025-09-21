<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการคิวผู้ป่วย (Queue List)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            margin-top: 20px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        /* Style for accepted queue row */
        .queue-accepted {
            background-color: #d4edda !important; /* Greenish background for accepted status */
        }
    </style>
</head>
<body>

    <div class="container">
        <h1 class="text-center mb-4 text-primary">รายการคิวผู้ป่วย 👨‍⚕️</h1>
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">ข้อมูลคิวปัจจุบัน</h5>
                        <button id="refreshBtn" class="btn btn-primary btn-sm">
                            <i class="fas fa-sync-alt"></i> รีเฟรชข้อมูล 🔃
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>คิวที่</th>
                                        <th class="d-none d-md-table-cell">รหัสผู้ป่วย</th>
                                        <th>OPD Code</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th class="d-none d-md-table-cell">เวลาที่เข้ามาติดต่อ</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody id="queue-table-body">
                                    <tr>
                                        <td colspan="6" class="text-center py-5">กำลังโหลดข้อมูล...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="roomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roomModalLabel">เลือกห้องตรวจ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>รับผู้ป่วย: <strong id="patientNameModal"></strong> เข้าห้องตรวจ</p>
                    <div class="mb-3">
                        <label for="roomSelect" class="form-label">เลือกห้อง:</label>
                        <select class="form-select" id="roomSelect"></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="saveRoomBtn">บันทึก</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>

    <script>
        $(document).ready(function() {
            let currentQueueData = [];
            let currentPatientUUID = null;

            function fetchQueueData() {
                if (currentQueueData.length === 0) {
                    const isMobile = window.matchMedia("(max-width: 767px)").matches;
                    const colspan = isMobile ? 4 : 6;
                    $('#queue-table-body').html(`<tr><td colspan="${colspan}" class="text-center py-5">กำลังโหลดข้อมูล...</td></tr>`);
                }
                
                $.ajax({
                    url: 'api.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.status === 'success') {
                            const sortedData = data.data.sort((a, b) => {
                                const dateA = new Date(a.start_wait);
                                const dateB = new Date(b.start_wait);
                                return dateA - dateB;
                            });
                            updateTable(sortedData);
                        } else {
                            const isMobile = window.matchMedia("(max-width: 767px)").matches;
                            const colspan = isMobile ? 4 : 6;
                            $('#queue-table-body').html(`<tr><td colspan="${colspan}" class="text-center text-danger py-5">เกิดข้อผิดพลาด: ${data.message}</td></tr>`);
                            console.error('API Error:', data.message);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        const isMobile = window.matchMedia("(max-width: 767px)").matches;
                        const colspan = isMobile ? 4 : 6;
                        $('#queue-table-body').html(`<tr><td colspan="${colspan}" class="text-center text-danger py-5">ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้</td></tr>`);
                        console.error('AJAX Error:', textStatus, errorThrown);
                    }
                });
            }

            function updateTable(newQueueData) {
                const tableBody = $('#queue-table-body');
                const isMobile = window.matchMedia("(max-width: 767px)").matches;
                const colspan = isMobile ? 4 : 6;
                
                if (newQueueData.length === 0) {
                    tableBody.html(`<tr><td colspan="${colspan}" class="text-center py-5">ไม่มีข้อมูลคิวในขณะนี้</td></tr>`);
                    currentQueueData = [];
                    return;
                }

                if (JSON.stringify(currentQueueData) !== JSON.stringify(newQueueData)) {
                    tableBody.empty(); 
                    let queueNumber = 1;
                    $.each(newQueueData, function(index, queue) {
                        const startWait = new Date(queue.start_wait);
                        const buddhistYear = startWait.getFullYear() + 543;
                        const formattedDate = `${startWait.getDate().toString().padStart(2, '0')}/${(startWait.getMonth() + 1).toString().padStart(2, '0')}/${buddhistYear}`;
                        const formattedTime = startWait.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
                        const contactDateTime = isNaN(startWait.getTime()) ? 'N/A' : `${formattedDate} ${formattedTime}`;

                        const opdCode = queue.opd.code || 'N/A';
                        
                        const acceptedClass = queue.room_name ? 'queue-accepted' : '';

                        const newRow = `
                            <tr class="${acceptedClass}">
                                <td>${queueNumber++}</td>
                                <td class="d-none d-md-table-cell">${queue.patient_code}</td>
                                <td>${opdCode}</td>
                                <td>${queue.patient_name}</td>
                                <td class="d-none d-md-table-cell">${contactDateTime}</td>
                                <td>
                                    <button class="btn btn-sm btn-success enter-room-btn" 
                                            data-patient-name="${queue.patient_name}"
                                            data-opd-uuid="${queue.opd.uuid}"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#roomModal">รับเข้าห้อง</button>
                                </td>
                            </tr>
                        `;
                        tableBody.append(newRow);
                    });
                }
                
                currentQueueData = newQueueData;
            }

            // Handle "รับเข้าห้อง" button click
            $(document).on('click', '.enter-room-btn', function() {
                const patientName = $(this).data('patient-name');
                currentPatientUUID = $(this).data('opd-uuid');

                // Update modal content
                $('#patientNameModal').text(patientName);
                
                // Fetch room data and populate select box
                $.ajax({
                    url: 'api_rooms.php', // You need to create this file
                    type: 'GET',
                    dataType: 'json',
                    success: function(rooms) {
                        const roomSelect = $('#roomSelect');
                        roomSelect.empty();
                        if (rooms.length > 0) {
                            $.each(rooms, function(index, room) {
                                roomSelect.append(`<option value="${room.uuid}">${room.name}</option>`);
                            });
                        } else {
                            roomSelect.append('<option value="">ไม่มีข้อมูลห้อง</option>');
                        }
                    }
                });
            });

            // Handle "บันทึก" button click inside the modal
            $('#saveRoomBtn').on('click', function() {
                const selectedRoomUUID = $('#roomSelect').val();
                if (!selectedRoomUUID) {
                    alert('กรุณาเลือกห้องตรวจ');
                    return;
                }

                // AJAX call to save room data
                $.ajax({
                    url: 'api_save_room.php', // You need to create this file
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        opd_uuid: currentPatientUUID,
                        room_uuid: selectedRoomUUID
                    }),
                    success: function(response) {
                        if (response.status === 'success') {
                            alert('บันทึกข้อมูลเรียบร้อยแล้ว');
                            $('#roomModal').modal('hide');
                            fetchQueueData(); // Refresh table to show new status
                        } else {
                            alert('เกิดข้อผิดพลาดในการบันทึก: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์เพื่อบันทึกข้อมูลได้');
                    }
                });
            });

            // Initial data fetch
            fetchQueueData();

            // Refresh button click handler
            $('#refreshBtn').on('click', function() {
                fetchQueueData();
            });

            // เปิดใช้งานการรีเฟรชอัตโนมัติทุก 30 วินาที
            setInterval(fetchQueueData, 30000); 
        });
    </script>

</body>
</html>