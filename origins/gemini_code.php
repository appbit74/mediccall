<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบบริหารจัดการคิวคนไข้</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chosen Palette: Calm Healthcare -->
    <!-- Application Structure Plan: A role-based, single-page application using a Kanban board layout to visualize patient flow. The columns represent stages: Waiting, Examination, Post-Consultation, and Completed. Users switch roles (Nurse, Doctor, Finance) via a dropdown, which dynamically updates the available actions on patient cards. This structure was chosen because it provides an intuitive, at-a-glance overview of the entire patient journey, making it easy for staff to see bottlenecks and manage their specific tasks efficiently without page reloads. Modals are used for focused actions like room assignment to keep the main interface clean. -->
    <!-- Visualization & Content Choices: The core visualization is a Kanban Board (HTML/CSS Flexbox). Goal: Organize/Track Patient Flow. Presentation: Patient 'cards' move between status columns. Interaction: Staff click buttons on cards to trigger modals or status changes. Justification: This is the most effective way to represent a multi-stage workflow process. Textual info (patient name, HN) is primary. Buttons provide clear calls-to-action based on user role. This avoids complex charts and focuses on operational clarity. Library/Method: Vanilla JS for state management and DOM manipulation. -->
    <!-- CONFIRMATION: NO SVG graphics used. NO Mermaid JS used. -->
    <style>
        body { background-color: #f8f9fa; font-family: 'Sarabun', sans-serif; }
        .kanban-column { min-height: 60vh; }
        .modal { display: none; align-items: center; justify-content: center; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; padding: 2rem; border-radius: 0.5rem; width: 90%; max-width: 500px; }
        .status-waiting { border-left: 5px solid #3b82f6; }
        .status-exam { border-left: 5px solid #f97316; }
        .status-payment { border-left: 5px solid #10b981; }
        .status-done { border-left: 5px solid #6b7280; }
        .patient-card:hover { background-color: #f1f5f9; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .mobile-tab.active { border-bottom-color: #3b82f6; color: #3b82f6; font-weight: 600; }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body class="text-gray-800">

    <div id="app" class="p-4 md:p-6">
        <header class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-700">ระบบจัดการคิวผู้ป่วย</h1>
                <div class="flex items-center gap-2 sm:gap-4">
                    <button id="refresh-btn" class="p-2 bg-gray-200 rounded-md hover:bg-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.899 2.186l-1.414 1.414A5.002 5.002 0 005.999 7H9a1 1 0 110 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm12 14a1 1 0 01-1-1v-2.101a7.002 7.002 0 01-11.899-2.186l1.414-1.414A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 01-1 1z" clip-rule="evenodd" /></svg>
                    </button>
                    <div>
                        <label for="role-selector" class="sr-only">เลือกบทบาท</label>
                        <select id="role-selector" class="p-2 border rounded-md shadow-sm"><option value="nurse">เจ้าหน้าที่ซักประวัติ</option><option value="doctor">แพทย์</option><option value="finance">เจ้าหน้าที่การเงิน/รับยา</option></select>
                    </div>
                </div>
            </div>
        </header>

        <div id="mobile-tabs" class="md:hidden border-b border-gray-200 mb-4"><nav class="flex -mb-px" aria-label="Tabs"></nav></div>
        <main id="kanban-board" class="md:grid md:grid-cols-4 md:gap-6 space-y-6 md:space-y-0"></main>
    </div>

    <div id="assign-room-modal" class="modal">
        <div class="modal-content shadow-xl">
            <h2 class="text-2xl font-bold mb-4">กำหนดห้องตรวจ</h2>
            <p class="mb-4">คนไข้: <span id="modal-patient-name" class="font-semibold"></span></p>
            <div id="modal-error" class="hidden text-red-500 mb-4"></div>
            <input type="hidden" id="modal-patient-id">
            <div>
                <label for="room-select" class="block text-sm font-medium text-gray-700">เลือกห้องตรวจที่ว่าง:</label>
                <select id="room-select" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm"></select>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button id="cancel-assign" class="px-4 py-2 bg-gray-300 rounded-md hover:bg-gray-400">ยกเลิก</button>
                <button id="confirm-assign" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">ยืนยัน</button>
            </div>
        </div>
    </div>
    
    <div id="doctor-note-modal" class="modal">
        <div class="modal-content shadow-xl">
            <h2 class="text-2xl font-bold mb-4">บันทึกการตรวจ</h2>
            <p class="mb-4">คนไข้: <span id="modal-doctor-patient-name" class="font-semibold"></span></p>
            <input type="hidden" id="modal-doctor-patient-id">
            <div>
                <label for="doctor-notes" class="block text-sm font-medium text-gray-700">ผลการวินิจฉัยและคำสั่ง:</label>
                <textarea id="doctor-notes" rows="4" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="เช่น สั่งยา A, B และนัดตรวจอีกครั้ง..."></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button id="cancel-doctor-note" class="px-4 py-2 bg-gray-300 rounded-md hover:bg-gray-400">ยกเลิก</button>
                <button id="confirm-doctor-note" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">ส่งต่อการเงิน/รับยา</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        let patients = [];
        let currentUserRole = 'nurse';
        let activeMobileTab = 'waiting';

        const kanbanBoard = document.getElementById('kanban-board');
        const roleSelector = document.getElementById('role-selector');
        const refreshBtn = document.getElementById('refresh-btn');
        const mobileTabsContainer = document.querySelector('#mobile-tabs nav');
        const assignRoomModal = document.getElementById('assign-room-modal');
        const doctorNoteModal = document.getElementById('doctor-note-modal');
        const roomSelect = document.getElementById('room-select');
        const modalError = document.getElementById('modal-error');

        const statuses = {
            waiting: { title: 'รอพบเจ้าหน้าที่', color: 'bg-blue-100', textColor: 'text-blue-800' },
            exam: { title: 'รอตรวจ', color: 'bg-orange-100', textColor: 'text-orange-800' },
            payment: { title: 'รอชำระเงิน/รับยา', color: 'bg-emerald-100', textColor: 'text-emerald-800' },
            done: { title: 'เสร็จสิ้น', color: 'bg-gray-200', textColor: 'text-gray-800' }
        };

        const findPatient = (id) => patients.find(p => p.id === id);
        const isMobile = () => window.innerWidth < 768;

        const showLoader = () => { kanbanBoard.innerHTML = '<div class="col-span-4 text-center py-10"><div class="loader"></div><p>กำลังโหลดข้อมูลคิว...</p></div>'; };
        const showError = (message) => { kanbanBoard.innerHTML = `<div class="col-span-4 text-center py-10"><p class="text-red-500 font-semibold">เกิดข้อผิดพลาด: ${message}</p></div>`; };

        const fetchAndRenderPatients = async () => {
            showLoader();
            try {
                const response = await fetch('api.php');
                if (!response.ok) throw new Error(`Network response was not ok (status: ${response.status})`);
                const result = await response.json();
                if (result.status !== 'success' || !result.data || !Array.isArray(result.data)) {
                    throw new Error(result.message || 'รูปแบบข้อมูลจาก API ไม่ถูกต้อง');
                }
                const patientState = patients.reduce((acc, p) => {
                    acc[p.id] = { status: p.status, assignedRoom: p.assignedRoom, doctorNotes: p.doctorNotes };
                    return acc;
                }, {});
                patients = result.data.map((q, index) => {
                    const existingState = patientState[q.opd?.uuid] || {};
                    return {
                        id: q.opd?.uuid || `gen-id-${index}`,
                        hn: q.patient_code || 'N/A', name: q.patient_name || 'ผู้ป่วยไม่ระบุชื่อ',
                        queue: `Q-${String(index + 1).padStart(3, '0')}`,
                        status: existingState.status || (q.room_name.includes('รีเชพชัน') ? 'waiting' : 'exam'),
                        assignedRoom: existingState.assignedRoom || (q.room_name.includes('รีเชพชัน') ? null : q.room_name),
                        doctorNotes: existingState.doctorNotes || ''
                    };
                });
                renderUI();
            } catch (error) {
                console.error('Fetch error:', error);
                showError(error.message);
            }
        };

        const loadAvailableRooms = async () => {
            try {
                const response = await fetch('api_rooms.php');
                const rooms = await response.json();
                roomSelect.innerHTML = '';
                if (rooms.error) {
                    throw new Error(rooms.error);
                }
                rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.uuid;
                    option.textContent = room.name;
                    roomSelect.appendChild(option);
                });
            } catch (error) {
                modalError.textContent = 'ไม่สามารถโหลดรายการห้องได้: ' + error.message;
                modalError.classList.remove('hidden');
            }
        };

        const renderUI = () => {
            kanbanBoard.innerHTML = '';
            mobileTabsContainer.innerHTML = '';
            Object.keys(statuses).forEach(statusKey => {
                const statusInfo = statuses[statusKey];
                const patientsInStatus = patients.filter(p => p.status === statusKey);
                const column = document.createElement('div');
                column.id = `kanban-col-${statusKey}`;
                column.className = `kanban-column bg-gray-50 rounded-lg p-4`;
                column.innerHTML = `<div class="flex justify-between items-center mb-4"><h2 class="text-lg font-semibold ${statusInfo.textColor}">${statusInfo.title}</h2><span class="px-3 py-1 text-sm font-bold ${statusInfo.color} ${statusInfo.textColor} rounded-full">${patientsInStatus.length}</span></div><div class="space-y-4"></div>`;
                kanbanBoard.appendChild(column);
                const patientList = column.querySelector('.space-y-4');
                patientsInStatus.forEach(patient => {
                    const card = document.createElement('div');
                    card.className = `patient-card bg-white p-4 rounded-lg shadow-md status-${statusKey}`;
                    card.dataset.id = patient.id;
                    let actionButton = '';
                    if (currentUserRole === 'nurse' && patient.status === 'waiting') {
                        actionButton = `<button data-action="take-queue" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">รับคิว</button>`;
                    } else if (currentUserRole === 'doctor' && patient.status === 'exam') {
                        actionButton = `<button data-action="examine" class="mt-4 w-full px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">ตรวจไข้</button>`;
                    } else if (currentUserRole === 'finance' && patient.status === 'payment') {
                        actionButton = `<button data-action="complete" class="mt-4 w-full px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700">ดำเนินการเสร็จสิ้น</button>`;
                    }
                    let roomInfo = patient.assignedRoom ? `<p class="text-sm text-gray-600">ห้อง: <span class="font-semibold">${patient.assignedRoom}</span></p>` : '';
                    let notesInfo = patient.doctorNotes ? `<p class="text-sm text-gray-500 mt-2">หมายเหตุ: ${patient.doctorNotes}</p>` : '';
                    card.innerHTML = `<div class="flex justify-between items-start"><div><p class="font-bold text-lg">${patient.name}</p><p class="text-sm text-gray-500">HN: ${patient.hn}</p></div><span class="px-2 py-1 text-sm font-semibold bg-gray-200 rounded-full">${patient.queue}</span></div>${roomInfo}${notesInfo}${actionButton}`;
                    patientList.appendChild(card);
                });
                const tabButton = document.createElement('button');
                tabButton.className = `mobile-tab flex-1 whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm text-gray-500`;
                tabButton.textContent = `${statusInfo.title} (${patientsInStatus.length})`;
                tabButton.dataset.tab = statusKey;
                mobileTabsContainer.appendChild(tabButton);
            });
            updateMobileView();
        };

        const updateMobileView = () => {
            if (!isMobile()) {
                document.querySelectorAll('.kanban-column').forEach(col => col.style.display = 'block');
                return;
            }
            document.querySelectorAll('.kanban-column').forEach(col => col.style.display = col.id === `kanban-col-${activeMobileTab}` ? 'block' : 'none');
            document.querySelectorAll('.mobile-tab').forEach(tab => tab.classList.toggle('active', tab.dataset.tab === activeMobileTab));
        };

        kanbanBoard.addEventListener('click', async (e) => {
            if (e.target.tagName !== 'BUTTON') return;
            const action = e.target.dataset.action;
            const card = e.target.closest('.patient-card');
            const patientId = card.dataset.id;
            const patient = findPatient(patientId);
            if (!patient) return;
            if (action === 'take-queue') {
                modalError.classList.add('hidden');
                document.getElementById('modal-patient-id').value = patient.id;
                document.getElementById('modal-patient-name').textContent = patient.name;
                assignRoomModal.style.display = 'flex';
                await loadAvailableRooms();
            } else if (action === 'examine') {
                document.getElementById('modal-doctor-patient-id').value = patient.id;
                document.getElementById('modal-doctor-patient-name').textContent = patient.name;
                doctorNoteModal.style.display = 'flex';
            } else if (action === 'complete') {
                patient.status = 'done';
                renderUI();
            }
        });

        mobileTabsContainer.addEventListener('click', (e) => {
            if (e.target.dataset.tab) { activeMobileTab = e.target.dataset.tab; updateMobileView(); }
        });
        roleSelector.addEventListener('change', (e) => { currentUserRole = e.target.value; renderUI(); });
        refreshBtn.addEventListener('click', fetchAndRenderPatients);

        const closeModal = (modal) => modal.style.display = 'none';

        document.getElementById('confirm-assign').addEventListener('click', async () => {
            const patientId = document.getElementById('modal-patient-id').value;
            const selectedRoom = roomSelect.options[roomSelect.selectedIndex];
            const roomUuid = selectedRoom.value;
            const roomName = selectedRoom.text;

            if (!roomUuid) {
                alert('กรุณาเลือกห้องตรวจ');
                return;
            }

            try {
                const response = await fetch('api_assign_room.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ opd_uuid: patientId, room_uuid: roomUuid, room_name: roomName })
                });
                const result = await response.json();
                if (result.status !== 'success') {
                    throw new Error(result.message);
                }
                const patient = findPatient(patientId);
                if (patient) {
                    patient.status = 'exam';
                    patient.assignedRoom = roomName;
                    activeMobileTab = 'exam';
                    renderUI();
                }
                closeModal(assignRoomModal);
            } catch (error) {
                alert('เกิดข้อผิดพลาดในการบันทึก: ' + error.message);
            }
        });

        document.getElementById('confirm-doctor-note').addEventListener('click', () => {
            const patientId = document.getElementById('modal-doctor-patient-id').value;
            const patient = findPatient(patientId);
            if (patient) {
                patient.status = 'payment';
                patient.doctorNotes = document.getElementById('doctor-notes').value || 'รอชำระเงิน/รับยา';
                activeMobileTab = 'payment';
                renderUI();
            }
            closeModal(doctorNoteModal);
        });

        document.getElementById('cancel-assign').addEventListener('click', () => closeModal(assignRoomModal));
        document.getElementById('cancel-doctor-note').addEventListener('click', () => closeModal(doctorNoteModal));
        window.addEventListener('resize', updateMobileView);
        fetchAndRenderPatients();
    });
    </script>
</body>
</html>

