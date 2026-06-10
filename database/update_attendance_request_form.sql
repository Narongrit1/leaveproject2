USE faculty_leave_system;

ALTER TABLE attendance_requests
  ADD COLUMN IF NOT EXISTS request_type ENUM('time_record','workday_swap') NOT NULL DEFAULT 'time_record' AFTER academic_year_be,
  ADD COLUMN IF NOT EXISTS absent_date DATE NULL AFTER work_date,
  ADD COLUMN IF NOT EXISTS makeup_date DATE NULL AFTER absent_date,
  ADD COLUMN IF NOT EXISTS reason_type VARCHAR(50) NULL AFTER makeup_date,
  ADD COLUMN IF NOT EXISTS other_reason VARCHAR(255) NULL AFTER reason_type;

ALTER TABLE attendance_requests
  MODIFY work_date DATE NULL,
  MODIFY start_time TIME NULL,
  MODIFY end_time TIME NULL;

UPDATE attendance_requests
SET request_type = COALESCE(request_type, 'time_record')
WHERE request_type IS NULL OR request_type = '';

UPDATE attendance_requests
SET reason_type = 'other', other_reason = reason
WHERE request_type = 'time_record' AND (reason_type IS NULL OR reason_type = '');
