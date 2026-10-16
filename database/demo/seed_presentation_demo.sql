-- SIM PBRI synthetic presentation/UAT dataset.
-- Import into a LOCAL development/test database with the SIM PBRI schema migrated.
-- Select the target database in MySQL/phpMyAdmin before running this file.
-- No existing rows are exported, updated, or deleted; no user accounts/passwords are included.
-- Requires exactly one college and one active lecturer + staff account assigned to it.
-- Run once. If preflight_result is STOP, this file performs no inserts.

SET @demo_marker := 'DEMO-PRESENTATION-20261016';
SET @college_count := (SELECT COUNT(*) FROM colleges);
SET @college_id := IF(@college_count = 1, (SELECT MIN(id) FROM colleges), NULL);
SET @lecturer_id := (SELECT id FROM users WHERE college_id = @college_id AND role = 'lecturer' AND is_active = 1 ORDER BY id LIMIT 1);
SET @staff_id := (SELECT id FROM users WHERE college_id = @college_id AND role = 'staff' AND is_active = 1 ORDER BY id LIMIT 1);
SET @duplicate_marker := (
  EXISTS(SELECT 1 FROM courses WHERE code = 'DEMO-20261016')
  OR EXISTS(SELECT 1 FROM scenarios WHERE name LIKE CONCAT(@demo_marker, ' | %'))
  OR EXISTS(SELECT 1 FROM sim_resources WHERE name LIKE CONCAT(@demo_marker, ' | %'))
  OR EXISTS(SELECT 1 FROM simulator_types WHERE name LIKE CONCAT(@demo_marker, ' | %'))
  OR EXISTS(SELECT 1 FROM simulator_assets WHERE asset_code = 'DEMO-20261016-001')
  OR EXISTS(SELECT 1 FROM bookings WHERE note LIKE CONCAT(@demo_marker, '%'))
);
SET @demo_ready := IF(@college_id IS NOT NULL AND @lecturer_id IS NOT NULL AND @staff_id IS NOT NULL AND NOT @duplicate_marker, 1, 0);

SELECT DATABASE() AS selected_database,
       IF(@college_id IS NOT NULL, 'PASS', 'STOP: expected exactly one college') AS college_check,
       IF(@lecturer_id IS NOT NULL, 'PASS', 'STOP: active lecturer missing') AS lecturer_check,
       IF(@staff_id IS NOT NULL, 'PASS', 'STOP: active staff missing') AS staff_check,
       IF(NOT @duplicate_marker, 'PASS', 'STOP: demo marker already exists') AS duplicate_check,
       IF(@demo_ready = 1, 'PASS: safe to insert', 'STOP: no rows will be inserted') AS preflight_result;

START TRANSACTION;

INSERT INTO sim_resources (college_id, name, kind, status, quantity_total, is_exclusive, location, description, building, floor, capacity, responsible_staff_user_id, created_at, updated_at)
SELECT @college_id, CONCAT(@demo_marker, ' | ห้องจำลองนำเสนอ'), 'room', 'ready', 1, 1, 'อาคารสาธิต ชั้น 1', CONCAT(@demo_marker, ' | synthetic demo room'), 'อาคารสาธิต', '1', 24, @staff_id, NOW(), NOW()
FROM DUAL WHERE @demo_ready = 1;
SET @room_id := IF(@demo_ready = 1, LAST_INSERT_ID(), NULL);

INSERT INTO sim_resources (college_id, name, kind, status, quantity_total, is_exclusive, location, description, created_at, updated_at)
SELECT @college_id, CONCAT(@demo_marker, ' | หุ่นจำลองฝึก'), 'equipment', 'ready', 2, 0, 'ห้องจำลองนำเสนอ', CONCAT(@demo_marker, ' | synthetic demo equipment'), NOW(), NOW()
FROM DUAL WHERE @demo_ready = 1;
SET @equipment_id := IF(@demo_ready = 1, LAST_INSERT_ID(), NULL);

INSERT INTO courses (college_id, code, name, created_at, updated_at)
SELECT @college_id, 'DEMO-20261016', CONCAT(@demo_marker, ' | รายวิชาสาธิต'), NOW(), NOW()
FROM DUAL WHERE @demo_ready = 1;
SET @course_id := IF(@demo_ready = 1, LAST_INSERT_ID(), NULL);

INSERT INTO scenarios (course_id, name, description, is_active, created_at, updated_at)
SELECT @course_id, CONCAT(@demo_marker, ' | ฝึกประเมินผู้ป่วย'), CONCAT(@demo_marker, ' | synthetic demo scenario'), 1, NOW(), NOW()
FROM DUAL WHERE @demo_ready = 1;
SET @scenario_id := IF(@demo_ready = 1, LAST_INSERT_ID(), NULL);

INSERT INTO simulator_types (college_id, name, description, is_active, created_at, updated_at)
SELECT @college_id, CONCAT(@demo_marker, ' | หุ่นผู้ใหญ่'), CONCAT(@demo_marker, ' | synthetic demo type'), 1, NOW(), NOW()
FROM DUAL WHERE @demo_ready = 1;
SET @simulator_type_id := IF(@demo_ready = 1, LAST_INSERT_ID(), NULL);

INSERT INTO simulator_assets (college_id, simulator_type_id, asset_name, asset_code, purchase_year, status, location, description, created_at, updated_at)
SELECT @college_id, @simulator_type_id, CONCAT(@demo_marker, ' | หุ่นผู้ใหญ่หมายเลข 1'), 'DEMO-20261016-001', 2025, 'active', 'ห้องจำลองนำเสนอ', CONCAT(@demo_marker, ' | synthetic demo asset'), NOW(), NOW()
FROM DUAL WHERE @demo_ready = 1;
SET @asset_id := IF(@demo_ready = 1, LAST_INSERT_ID(), NULL);

INSERT INTO scenario_resource_templates (scenario_id, sim_resource_id, quantity, created_at, updated_at)
SELECT @scenario_id, @equipment_id, 1, NOW(), NOW() FROM DUAL WHERE @demo_ready = 1;
INSERT INTO scenario_simulator_type (scenario_id, simulator_type_id, created_at, updated_at)
SELECT @scenario_id, @simulator_type_id, NOW(), NOW() FROM DUAL WHERE @demo_ready = 1;

-- Pending request
INSERT INTO bookings (college_id, requested_by_user_id, course_id, scenario_id, simulator_asset_id, requester_name, starts_at, ends_at, participant_count, note, status, created_at, updated_at)
SELECT @college_id, @lecturer_id, @course_id, @scenario_id, @asset_id, 'ผู้สาธิต SIM PBRI', TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), '09:00:00'), TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), '11:00:00'), 15, CONCAT(@demo_marker, ' | คำขอตัวอย่าง pending'), 'pending', NOW(), NOW()
FROM DUAL WHERE @demo_ready = 1;
SET @booking_pending_id := IF(@demo_ready = 1, LAST_INSERT_ID(), NULL);

-- Approved request
INSERT INTO bookings (college_id, requested_by_user_id, course_id, scenario_id, simulator_asset_id, requester_name, starts_at, ends_at, participant_count, note, status, reviewed_by_user_id, reviewed_at, created_at, updated_at)
SELECT @college_id, @lecturer_id, @course_id, @scenario_id, @asset_id, 'ผู้สาธิต SIM PBRI', TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 4 DAY), '10:00:00'), TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 4 DAY), '12:00:00'), 15, CONCAT(@demo_marker, ' | คำขอตัวอย่าง approved'), 'approved', @staff_id, NOW(), NOW(), NOW()
FROM DUAL WHERE @demo_ready = 1;
SET @booking_approved_id := IF(@demo_ready = 1, LAST_INSERT_ID(), NULL);

-- Rejected request
INSERT INTO bookings (college_id, requested_by_user_id, course_id, scenario_id, simulator_asset_id, requester_name, starts_at, ends_at, participant_count, note, status, reviewed_by_user_id, reviewed_at, review_reason, created_at, updated_at)
SELECT @college_id, @lecturer_id, @course_id, @scenario_id, @asset_id, 'ผู้สาธิต SIM PBRI', TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY), '13:00:00'), TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY), '15:00:00'), 15, CONCAT(@demo_marker, ' | คำขอตัวอย่าง rejected'), 'rejected', @staff_id, NOW(), 'เหตุผลตัวอย่างสำหรับสาธิต', NOW(), NOW()
FROM DUAL WHERE @demo_ready = 1;
SET @booking_rejected_id := IF(@demo_ready = 1, LAST_INSERT_ID(), NULL);

-- Link the room and recommended equipment to each seeded booking.
INSERT INTO booking_resource (booking_id, sim_resource_id, quantity, is_auto_recommended, resource_name_snapshot, resource_location_snapshot, room_building_snapshot, room_floor_snapshot, room_capacity_snapshot, created_at, updated_at)
SELECT b.id, r.id, 1, FALSE, r.name, r.location, r.building, r.floor, r.capacity, NOW(), NOW()
FROM bookings b JOIN sim_resources r ON r.id = @room_id
WHERE @demo_ready = 1 AND b.id IN (@booking_pending_id, @booking_approved_id, @booking_rejected_id);
INSERT INTO booking_resource (booking_id, sim_resource_id, quantity, is_auto_recommended, resource_name_snapshot, resource_location_snapshot, room_building_snapshot, room_floor_snapshot, room_capacity_snapshot, created_at, updated_at)
SELECT b.id, e.id, 1, TRUE, e.name, e.location, NULL, NULL, NULL, NOW(), NOW()
FROM bookings b JOIN sim_resources e ON e.id = @equipment_id
WHERE @demo_ready = 1 AND b.id IN (@booking_pending_id, @booking_approved_id, @booking_rejected_id);

INSERT INTO booking_status_transitions (booking_id, from_status, to_status, actor_user_id, reason, created_at)
SELECT @booking_pending_id, NULL, 'pending', @lecturer_id, @demo_marker, NOW() FROM DUAL WHERE @demo_ready = 1;
INSERT INTO booking_status_transitions (booking_id, from_status, to_status, actor_user_id, reason, created_at)
SELECT @booking_approved_id, NULL, 'pending', @lecturer_id, @demo_marker, NOW() FROM DUAL WHERE @demo_ready = 1;
INSERT INTO booking_status_transitions (booking_id, from_status, to_status, actor_user_id, reason, created_at)
SELECT @booking_approved_id, 'pending', 'approved', @staff_id, CONCAT(@demo_marker, ' | approved'), NOW() FROM DUAL WHERE @demo_ready = 1;
INSERT INTO booking_status_transitions (booking_id, from_status, to_status, actor_user_id, reason, created_at)
SELECT @booking_rejected_id, NULL, 'pending', @lecturer_id, @demo_marker, NOW() FROM DUAL WHERE @demo_ready = 1;
INSERT INTO booking_status_transitions (booking_id, from_status, to_status, actor_user_id, reason, created_at)
SELECT @booking_rejected_id, 'pending', 'rejected', @staff_id, CONCAT(@demo_marker, ' | rejected'), NOW() FROM DUAL WHERE @demo_ready = 1;

COMMIT;

SELECT @demo_ready AS imported, @college_id AS college_id,
       @room_id AS room_id, @equipment_id AS equipment_id,
       @course_id AS course_id, @scenario_id AS scenario_id,
       @simulator_type_id AS simulator_type_id, @asset_id AS asset_id,
       @booking_pending_id AS pending_booking_id,
       @booking_approved_id AS approved_booking_id,
       @booking_rejected_id AS rejected_booking_id;
