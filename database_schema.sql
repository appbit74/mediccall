--
-- โครงสร้างตาราง `patient_queue`
--
CREATE TABLE `patient_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opd_uuid` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_hn` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('waiting_counter','waiting_therapy','in_therapy','waiting_doctor','completed','payment_pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'waiting_counter',
  `assigned_therapist_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_therapist_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_doctor_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_doctor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_room_id` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_room_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `opd_uuid` (`opd_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- โครงสร้างตาราง `rooms`
--
CREATE TABLE `rooms` (
  `uuid` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- โครงสร้างตาราง `patient_logs`
--
CREATE TABLE `patient_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_queue_id` int(11) NOT NULL,
  `patient_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `performed_by_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `performed_by_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ตาราง `system_settings`
--
CREATE TABLE `system_settings` (
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- เพิ่มข้อมูลเริ่มต้น
--
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES ('jera_last_sync_time', '0');
TRUNCATE TABLE `rooms`;
INSERT INTO `rooms` (`uuid`, `name`, `is_active`) VALUES
('room-uuid-01', 'Room 1A', 1), ('room-uuid-02', 'Room 1B', 1),
('room-uuid-03', 'Room 2', 1), ('room-uuid-04', 'Room 3', 1),
('room-uuid-05', 'Room 4', 1), ('room-uuid-06', 'Room 5', 1),
('room-uuid-07', 'Room 6A', 1), ('room-uuid-08', 'Room 6B', 1),
('room-uuid-09', 'Room 7A', 1), ('room-uuid-10', 'Room 7B', 1),
('room-uuid-11', 'Room 8', 1), ('room-uuid-12', 'Room 9', 1),
('room-uuid-13', 'Room 10', 1), ('room-uuid-14', 'Room 11', 1),
('room-uuid-15', 'Room 12', 1);

