USE faculty_leave_system;

ALTER TABLE leave_requests
  ADD COLUMN IF NOT EXISTS academic_year_be INT UNSIGNED NULL AFTER leave_type_id;

ALTER TABLE attendance_requests
  ADD COLUMN IF NOT EXISTS academic_year_be INT UNSIGNED NULL AFTER user_id;

UPDATE leave_requests
SET academic_year_be = COALESCE(
  (SELECT CAST(setting_value AS UNSIGNED) FROM system_settings WHERE setting_key = 'academic_year_be'),
  2568
)
WHERE academic_year_be IS NULL OR academic_year_be = 0;

UPDATE attendance_requests
SET academic_year_be = COALESCE(
  (SELECT CAST(setting_value AS UNSIGNED) FROM system_settings WHERE setting_key = 'academic_year_be'),
  2568
)
WHERE academic_year_be IS NULL OR academic_year_be = 0;

ALTER TABLE leave_requests
  MODIFY academic_year_be INT UNSIGNED NOT NULL DEFAULT 2568;

ALTER TABLE attendance_requests
  MODIFY academic_year_be INT UNSIGNED NOT NULL DEFAULT 2568;
