$(document).ready(function() {
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const dateString = now.toLocaleDateString('th-TH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        $('#clock').html(`<div>${timeString}</div><div>${dateString}</div>`);
    }

    function loadSignageData() {
        $.getJSON('api/signage_data.php', function(doctors) {
            const board = $('#signage-board').empty();
            if (!doctors || doctors.length === 0) {
                board.html('<div class="col-12 text-center mt-5"><h1>ไม่มีคนไข้อยู่ในคิวตรวจ</h1></div>');
                return;
            }

            doctors.forEach(doctor => {
                const column = $(`
                    <div class="doctor-column">
                        <div class="doctor-header" style="background-color: ${doctor.color};">
                            <h3><i class="bi bi-person-fill"></i> ${doctor.doctor_name}</h3>
                        </div>
                        <div class="patient-list"></div>
                    </div>
                `);

                const patientList = column.find('.patient-list');
                
                if (doctor.patients.length > 0) {
                    doctor.patients.forEach(patient => {
                        
                        // <<-- [แก้ไข] ปรับโครงสร้าง HTML ของ patientCard -->>
                        const callStatusDiv = patient.call_status 
                            ? `<div class="call-status">${patient.call_status}</div>` 
                            : '';
                        
                        const patientCard = $(`
                            <div class="patient-card">
                                <div class="patient-card-top-row">
                                    <div class="patient-name">${patient.patient_name}</div>
                                    ${callStatusDiv}
                                </div>
                                <div class="patient-card-bottom-row">
                                    <div class="room-name"><i class="bi bi-door-open-fill"></i> ${patient.room_name}</div>
                                </div>
                            </div>
                        `);
                        // <<-- สิ้นสุดส่วนที่แก้ไข -->>

                        if (patient.status === 'waiting_doctor') {
                            patientCard.addClass('status-waiting-doctor flashing-alert');
                        } else if (patient.status === 'in_therapy') {
                            patientCard.addClass('status-in-therapy');
                        }
                        
                        patientList.append(patientCard);
                    });
                } else {
                    patientList.html('<div class="no-patient">ไม่มีคนไข้</div>');
                }
                board.append(column);
            });
        }).fail(function() {
            $('#signage-board').html('<div class="col-12 text-center mt-5"><h1>ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้</h1></div>');
        });
    }

    updateClock();
    setInterval(updateClock, 1000);
    loadSignageData();
    setInterval(loadSignageData, 5000);
});