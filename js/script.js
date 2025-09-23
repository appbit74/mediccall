$(document).ready(function () {
    const loader = $("#loading-overlay");
    function showLoader() {
        loader.fadeIn(200);
    }
    function hideLoader() {
        loader.fadeOut(200);
    }

    let previousState = {
        counterPaymentIds: new Set(),
        therapistAllIds: new Set(),
        doctorWaitingIds: new Set(),
    };
    let userHasInteracted = false;
    $(document).one("click", () => {
        userHasInteracted = true;
    });

    const userRole = getUserRoleFromCookie();
    if (userRole) {
        startRealtimeUpdates(userRole);
    }

    function startRealtimeUpdates(role) {
        getInitialData(role);
        const eventSource = new EventSource(`api/stream.php`);
        eventSource.onmessage = function (event) {
            const data = JSON.parse(event.data);
            if (data.error) {
                eventSource.close();
                return;
            }
            updateView(role, data);
        };
        eventSource.onerror = (err) => {
            console.error("EventSource failed:", err);
            eventSource.close();
        };
    }

    function getInitialData(role) {
        showLoader();
        return $.getJSON(`api/data_handler.php?view=${role}`, (data) =>
            updateView(role, data)
        ).always(() => hideLoader());
    }

    function updateView(role, data) {
        switch (role) {
            case "counter":
                const currentPaymentIds = new Set(
                    (data.payment_pending || []).map((p) => p.id)
                );
                const newPaymentIds = new Set(
                    [...currentPaymentIds].filter(
                        (id) => !previousState.counterPaymentIds.has(id)
                    )
                );
                if (newPaymentIds.size > 0) playSound("payment-sound");
                previousState.counterPaymentIds = currentPaymentIds;
                populateColumn(
                    "#new-patients-col",
                    data.new_patients,
                    createCounterNewCard,
                    "ไม่มีคนไข้ใหม่"
                );
                populateColumn(
                    "#in-process-col",
                    data.in_process_patients,
                    createCounterInProcessCard,
                    "ไม่มีคนไข้ในกระบวนการ"
                );
                populateColumn(
                    "#payment-pending-col",
                    data.payment_pending,
                    createCounterPaymentCard,
                    "ไม่มีคนไข้รอชำระเงิน"
                );
                break;
            case "therapist":
                const allCurrentTherapistIds = new Set([
                    ...(data.new_patients || []).map((p) => p.id),
                    ...(data.waiting_therapy || []).map((p) => p.id),
                    ...(data.in_therapy || []).map((p) => p.id),
                ]);
                const newTherapistPatientIds = new Set(
                    [...allCurrentTherapistIds].filter(
                        (id) => !previousState.therapistAllIds.has(id)
                    )
                );
                if (newTherapistPatientIds.size > 0) playSound("notification-sound");
                previousState.therapistAllIds = allCurrentTherapistIds;
                populateColumn(
                    "#new-patients-col",
                    data.new_patients,
                    (p) => createTherapistWaitingCard(p, false),
                    "ไม่มีคนไข้ใหม่"
                );
                populateColumn(
                    "#waiting-therapy-col",
                    data.waiting_therapy,
                    (p) =>
                        createTherapistWaitingCard(p, newTherapistPatientIds.has(p.id)),
                    "ไม่มีคนไข้รอทำกายภาพ"
                );
                populateColumn(
                    "#in-therapy-col",
                    data.in_therapy,
                    (p) =>
                        createTherapistInTherapyCard(p, newTherapistPatientIds.has(p.id)),
                    "ไม่มีคนไข้กำลังทำกายภาพ"
                );
                break;
            case "doctor":
                const currentDoctorWaitingIds = new Set(
                    (data.my_patients || [])
                        .filter((p) => p.status === "waiting_doctor")
                        .map((p) => p.id)
                );
                const newDoctorPatientIds = new Set(
                    [...currentDoctorWaitingIds].filter(
                        (id) => !previousState.doctorWaitingIds.has(id)
                    )
                );
                if (newDoctorPatientIds.size > 0) playSound("notification-sound");
                previousState.doctorWaitingIds = currentDoctorWaitingIds;
                const list = $("#doctor-patients-list").empty();
                if (!data.my_patients || !data.my_patients.length) {
                    list.html('<div class="list-group-item">ไม่มีคนไข้ในคิวของคุณ</div>');
                    return;
                }
                $.each(data.my_patients, (i, p) => {
                    const isNew = newDoctorPatientIds.has(p.id);
                    list.append(createDoctorCard(p, isNew));
                });
                break;
        }
    }

    function getUserRoleFromCookie() {
        try {
            const c = document.cookie
                .split("; ")
                .find((r) => r.startsWith("user_data="));
            return JSON.parse(decodeURIComponent(c.split("=")[1])).role;
        } catch (e) {
            return null;
        }
    }
    function playSound(soundId) {
        if (!userHasInteracted) return;
        const soundElement = document.getElementById(soundId);
        if (soundElement) {
            soundElement
                .play()
                .catch((error) => console.error("Error playing sound:", error));
        }
    }
    function populateColumn(selector, data, cardCreator, emptyMsg) {
        const col = $(selector).empty();
        if (!data || data.length === 0) {
            col.html(`<p class="text-muted text-center">${emptyMsg}</p>`);
            return;
        }
        $.each(data, (i, item) => col.append(cardCreator(item)));
    }
    function handleAction(action, data, modalInstance = null) {
        data.action = action;
        $.post(
            "api/action_handler.php",
            data,
            (res) => {
                if (res.status === "success") {
                    if (userRole === "counter") getInitialData("counter");
                    if (userRole === "therapist") getInitialData("therapist");
                    if (userRole === "doctor") getInitialData("doctor");
                    if (modalInstance) modalInstance.hide();
                } else {
                    alert("เกิดข้อผิดพลาด: " + (res.message || "ไม่ทราบสาเหตุ"));
                }
            },
            "json"
        ).fail(() => alert("การเชื่อมต่อเซิร์ฟเวอร์ล้มเหลว"));
    }

    function createCounterNewCard(p) {
        return `<div class="card patient-card mb-2"><div class="card-body"><h5 class="card-title">${p.patient_name}</h5><p class="card-text">HN: ${p.patient_hn}</p><button class="btn btn-sm btn-primary btn-process-patient" data-id="${p.id}">ดำเนินการ</button></div></div>`;
    }
    function createCounterInProcessCard(p) {
        let statusText = "";
        switch (p.status) {
            case "waiting_therapy":
                statusText = "รอทำกายภาพ";
                break;
            case "in_therapy":
                statusText = "กำลังทำกายภาพ";
                break;
            case "waiting_doctor":
                statusText = "รอตรวจ";
                break;
            default:
                statusText = p.status;
        }
        let docInfo = p.assigned_doctor_name
            ? `<span class="badge bg-info">${p.assigned_doctor_name}</span>`
            : `<button class="btn btn-sm btn-outline-info btn-assign-doctor" data-id="${p.id}" data-name="${p.patient_name}">กำหนดแพทย์</button>`;
        return `<div class="card patient-card mb-2"><div class="card-body d-flex flex-column justify-content-between"><div><h5 class="card-title">${p.patient_name}</h5><p class="card-text">HN: ${p.patient_hn}</p><p class="card-text">สถานะ: <span class="badge bg-secondary">${statusText}</span></p><p>แพทย์: ${docInfo}</p></div><button class="btn btn-sm btn-success mt-2 btn-complete-payment" data-id="${p.id}">เก็บเงินเรียบร้อย</button></div></div>`;
    }
    function createCounterPaymentCard(p) {
        return `<div class="card patient-card mb-2 flashing"><div class="card-body"><h5 class="card-title">${p.patient_name}</h5><p class="card-text">HN: ${p.patient_hn}</p><button class="btn btn-sm btn-success btn-complete-payment" data-id="${p.id}">เก็บเงินเรียบร้อย</button></div></div>`;
    }
    function createTherapistWaitingCard(p, isNew = false) {
        const flashingClass = isNew ? "flashing-returned-alert" : "";
        return `<div class="card patient-card mb-2 ${flashingClass}"><div class="card-body"><h5 class="card-title">${p.patient_name}</h5><p class="card-text">HN: ${p.patient_hn}</p><p>แพทย์: ${p.assigned_doctor_name || "ยังไม่ระบุ"}</p><button class="btn btn-sm btn-warning btn-take-case" data-id="${p.id}" data-name="${p.patient_name}" data-doctor-id="${p.assigned_doctor_id || ""}">รับงาน</button></div></div>`;
    }
    function createTherapistInTherapyCard(p, isNew = false) {
        const flashingClass = isNew ? "flashing-returned-alert" : "";
        let docInfo = p.assigned_doctor_name
            ? `<span class="badge bg-info">${p.assigned_doctor_name}</span>`
            : "ยังไม่ระบุ";
        return `<div class="card patient-card mb-2 ${flashingClass}"><div class="card-body d-flex flex-column justify-content-between"><div><h5 class="card-title">${p.patient_name}</h5><p class="card-text">HN: ${p.patient_hn}</p><p>ห้อง: <span class="badge bg-secondary">${p.assigned_room_name}</span></p><p>นักกายภาพ: <span class="badge bg-primary">${p.assigned_therapist_name}</span></p><p>แพทย์: ${docInfo}</p></div><div class="mt-2 d-grid gap-2 d-sm-block"><button class="btn btn-sm btn-info btn-notify-doctor" data-id="${p.id}">แจ้งแพทย์</button><button class="btn btn-sm btn-success btn-therapist-finish-work" data-id="${p.id}">จบงาน (ส่งชำระเงิน)</button></div></div></div>`;
    }
    function createDoctorCard(p, isNew = false) {
        let statusText = "";
        switch (p.status) {
            case "waiting_therapy":
                statusText = "รอทำกายภาพ";
                break;
            case "in_therapy":
                statusText = "กำลังทำกายภาพ";
                break;
            case "waiting_doctor":
                statusText = "รอตรวจ";
                break;
            default:
                statusText = p.status;
        }
        let finishButton = `<button class="btn btn-sm btn-success btn-finish-consult" data-id="${p.id}">เสร็จสิ้นการตรวจ</button>`;
        let sendBackButton = `<button class="btn btn-sm btn-outline-secondary btn-send-back-to-therapy" data-id="${p.id}">ส่งกลับกายภาพ</button>`;
        let flashingClass = isNew ? "flashing-doctor-alert" : "";
        return `<div class="list-group-item ${flashingClass}"><div class="d-flex w-100 justify-content-between"><h5 class="mb-1">${p.patient_name} (HN: ${p.patient_hn})</h5><small>สถานะ: ${statusText}</small></div><p class="mb-1">ห้อง: ${p.assigned_room_name || "N/A"} | นักกายภาพ: ${p.assigned_therapist_name || "N/A"}</p><div class="mt-2">${finishButton}${sendBackButton}</div></div>`;
    }

    $("#manual-sync-btn").on("click", function () {
        showLoader();
        const btn = $(this);
        btn.prop("disabled", true);
        $.getJSON("api/data_handler.php?action=manual_sync").always(() => {
            hideLoader();
            btn.prop("disabled", false);
        });
    });
    $(document).on(
        "click",
        "#doctor-reload-btn, #therapist-reload-btn",
        function () {
            showLoader();
            const btn = $(this);
            const roleToReload = btn.attr("id").split("-")[0];
            btn.prop("disabled", true);
            getInitialData(roleToReload).always(() => btn.prop("disabled", false));
        }
    );
    $(document).on("click", ".btn-process-patient", function () {
        handleAction("process_patient", { patient_id: $(this).data("id") });
    });
    $(document).on("click", ".btn-notify-doctor", function () {
        handleAction("notify_doctor", { patient_id: $(this).data("id") });
    });
    $(document).on("click", ".btn-finish-consult", function () {
        handleAction("finish_consult", { patient_id: $(this).data("id") });
    });
    $(document).on("click", ".btn-complete-payment", function () {
        handleAction("complete_payment", { patient_id: $(this).data("id") });
    });
    $(document).on("click", ".btn-send-back-to-therapy", function () {
        handleAction("send_back_to_therapy", { patient_id: $(this).data("id") });
    });
    $(document).on("click", ".btn-therapist-finish-work", function () {
        if (confirm("ยืนยันการจบงาน และส่งต่อแผนกการเงิน?")) {
            handleAction("therapist_finish_work", { patient_id: $(this).data("id") });
        }
    });
    $(document).on("click", ".btn-assign-doctor", function () {
        $("#doctor-modal-patient-id").val($(this).data("id"));
        $("#doctor-modal-patient-name").text($(this).data("name"));
        $.getJSON("api/data_handler.php?get=doctors", (doctors) => {
            const sel = $("#doctor-select").empty();
            $.each(doctors, (i, d) =>
                sel.append(`<option value="${d.id}">${d.name}</option>`)
            );
        });
        new bootstrap.Modal("#assignDoctorModal").show();
    });
    $("#confirm-assign-doctor").on("click", function () {
        handleAction(
            "assign_doctor",
            {
                patient_id: $("#doctor-modal-patient-id").val(),
                doctor_id: $("#doctor-select").val(),
                doctor_name: $("#doctor-select option:selected").text(),
            },
            bootstrap.Modal.getInstance($("#assignDoctorModal"))
        );
    });
    $(document).on("click", ".btn-take-case", function () {
        const preAssignedDoctorId = $(this).data("doctor-id");
        $("#accept-case-patient-id").val($(this).data("id"));
        $("#accept-case-patient-name").text($(this).data("name"));
        $.getJSON("api/data_handler.php?get=rooms", (rooms) => {
            const sel = $("#room-select").empty();
            if (rooms.length > 0) {
                $.each(rooms, (i, r) =>
                    sel.append(`<option value="${r.uuid}">${r.name}</option>`)
                );
            } else {
                sel.append('<option value="">ไม่มีห้องว่าง</option>');
            }
        });
        $.getJSON("api/data_handler.php?get=doctors", (doctors) => {
            const sel = $("#doctor-select-therapist")
                .empty()
                .append('<option value="">-- ไม่กำหนดแพทย์ --</option>');
            $.each(doctors, (i, d) =>
                sel.append(`<option value="${d.id}">${d.name}</option>`)
            );
            if (preAssignedDoctorId) {
                sel.val(preAssignedDoctorId);
            }
        });
        new bootstrap.Modal("#acceptCaseModal").show();
    });
    $("#confirm-accept-case").on("click", function () {
        const roomId = $("#room-select").val();
        if (!roomId) {
            alert("กรุณาเลือกห้อง");
            return;
        }
        const doctorId = $("#doctor-select-therapist").val();
        const data = {
            patient_id: $("#accept-case-patient-id").val(),
            room_id: roomId,
            room_name: $("#room-select option:selected").text(),
            doctor_id: doctorId,
            doctor_name: doctorId
                ? $("#doctor-select-therapist option:selected").text()
                : "",
        };
        handleAction(
            "assign_room",
            data,
            bootstrap.Modal.getInstance($("#acceptCaseModal"))
        );
    });
});
