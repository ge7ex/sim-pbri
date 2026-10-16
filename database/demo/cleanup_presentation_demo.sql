-- SIM PBRI synthetic presentation/UAT dataset cleanup.
-- Run only in the same LOCAL development/test database after reviewing the marker.
-- This removes only data carrying the fixed demo marker; it does not remove users.
SET @demo_marker := 'DEMO-PRESENTATION-20261016';
SET @course_id := (SELECT id FROM courses WHERE code = 'DEMO-20261016' LIMIT 1);
SET @scenario_id := (SELECT id FROM scenarios WHERE course_id = @course_id AND name = CONCAT(@demo_marker, ' | ฝึกประเมินผู้ป่วย') LIMIT 1);
SET @college_id := (SELECT college_id FROM courses WHERE id = @course_id LIMIT 1);
SET @room_id := (SELECT id FROM sim_resources WHERE college_id = @college_id AND name = CONCAT(@demo_marker, ' | ห้องจำลองนำเสนอ') LIMIT 1);
SET @equipment_id := (SELECT id FROM sim_resources WHERE college_id = @college_id AND name = CONCAT(@demo_marker, ' | หุ่นจำลองฝึก') LIMIT 1);
SET @simulator_type_id := (SELECT id FROM simulator_types WHERE college_id = @college_id AND name = CONCAT(@demo_marker, ' | หุ่นผู้ใหญ่') LIMIT 1);
SET @asset_id := (SELECT id FROM simulator_assets WHERE asset_code = 'DEMO-20261016-001' AND college_id = @college_id LIMIT 1);

START TRANSACTION;
DELETE FROM booking_status_transitions WHERE booking_id IN (SELECT id FROM bookings WHERE note LIKE CONCAT(@demo_marker, ' | คำขอตัวอย่าง %'));
DELETE FROM booking_participant_amendments WHERE booking_id IN (SELECT id FROM bookings WHERE note LIKE CONCAT(@demo_marker, ' | คำขอตัวอย่าง %'));
DELETE FROM booking_custom_equipment_requests WHERE booking_id IN (SELECT id FROM bookings WHERE note LIKE CONCAT(@demo_marker, ' | คำขอตัวอย่าง %'));
DELETE FROM booking_resource WHERE booking_id IN (SELECT id FROM bookings WHERE note LIKE CONCAT(@demo_marker, ' | คำขอตัวอย่าง %'));
DELETE FROM bookings WHERE note LIKE CONCAT(@demo_marker, ' | คำขอตัวอย่าง %');
DELETE FROM simulator_change_logs
WHERE college_id = @college_id
  AND ((subject_type = 'asset' AND subject_id = @asset_id)
       OR (subject_type = 'type' AND subject_id = @simulator_type_id))
  AND (CAST(before_values AS CHAR) LIKE CONCAT('%', @demo_marker, '%')
       OR CAST(after_values AS CHAR) LIKE CONCAT('%', @demo_marker, '%'));
DELETE FROM scenario_resource_templates WHERE scenario_id = @scenario_id AND sim_resource_id = @equipment_id;
DELETE FROM scenario_simulator_type WHERE scenario_id = @scenario_id AND simulator_type_id = @simulator_type_id;
DELETE FROM scenarios WHERE id = @scenario_id AND name = CONCAT(@demo_marker, ' | ฝึกประเมินผู้ป่วย');
DELETE FROM courses WHERE id = @course_id AND code = 'DEMO-20261016';
DELETE FROM simulator_maintenance_records WHERE simulator_asset_id = @asset_id;
DELETE FROM simulator_assets WHERE id = @asset_id AND asset_code = 'DEMO-20261016-001';
DELETE FROM simulator_types WHERE id = @simulator_type_id AND name = CONCAT(@demo_marker, ' | หุ่นผู้ใหญ่');
DELETE FROM sim_resource_image_changes WHERE sim_resource_id IN (@room_id, @equipment_id);
DELETE FROM sim_resources WHERE id IN (@room_id, @equipment_id) AND name LIKE CONCAT(@demo_marker, ' | %');
COMMIT;
