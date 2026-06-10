CREATE DATABASE IF NOT EXISTS faculty_leave_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE faculty_leave_system;

CREATE TABLE departments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  personnel_code VARCHAR(50) NULL,
  full_name VARCHAR(255) NOT NULL,
  position_name VARCHAR(255) NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  department_id INT UNSIGNED NULL,
  role ENUM('admin','dean','vice_dean','assistant_dean','head_department','lecturer','staff','hr') NOT NULL DEFAULT 'lecturer',
  must_change_password TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leave_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  code VARCHAR(50) NOT NULL UNIQUE,
  requires_attachment_days DECIMAL(5,2) NULL,
  advance_working_days INT UNSIGNED NOT NULL DEFAULT 0,
  color VARCHAR(20) NOT NULL DEFAULT '#2563eb',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leave_balances (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  leave_type_id INT UNSIGNED NOT NULL,
  year_be INT UNSIGNED NOT NULL,
  entitled_days DECIMAL(6,2) NOT NULL DEFAULT 0,
  used_days DECIMAL(6,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_leave_balance (user_id, leave_type_id, year_be),
  CONSTRAINT fk_leave_balances_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_leave_balances_type FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE academic_years (
  year_be INT UNSIGNED PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  starts_on DATE NULL,
  ends_on DATE NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leave_type_entitlements (
  academic_year_be INT UNSIGNED NOT NULL,
  leave_type_id INT UNSIGNED NOT NULL,
  entitled_days DECIMAL(6,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (academic_year_be, leave_type_id),
  CONSTRAINT fk_leave_entitlements_year FOREIGN KEY (academic_year_be) REFERENCES academic_years(year_be) ON DELETE CASCADE,
  CONSTRAINT fk_leave_entitlements_type FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leave_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_no VARCHAR(50) NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  leave_type_id INT UNSIGNED NOT NULL,
  academic_year_be INT UNSIGNED NOT NULL DEFAULT 2568,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  total_days DECIMAL(6,2) NOT NULL,
  reason TEXT NOT NULL,
  contact_during_leave VARCHAR(255) NULL,
  status ENUM('draft','pending_head','pending_dean','approved','rejected','cancelled','hr_recorded') NOT NULL DEFAULT 'draft',
  current_approver_role VARCHAR(50) NULL,
  submitted_at DATETIME NULL,
  approved_at DATETIME NULL,
  rejected_at DATETIME NULL,
  cancelled_at DATETIME NULL,
  hr_recorded_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_leave_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_leave_requests_type FOREIGN KEY (leave_type_id) REFERENCES leave_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leave_approval_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  leave_request_id INT UNSIGNED NOT NULL,
  approver_id INT UNSIGNED NOT NULL,
  approver_role VARCHAR(50) NOT NULL,
  action ENUM('submitted','approved','rejected','cancelled','hr_recorded') NOT NULL,
  comment TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_leave_logs_request FOREIGN KEY (leave_request_id) REFERENCES leave_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_leave_logs_approver FOREIGN KEY (approver_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leave_attachments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  leave_request_id INT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  mime_type VARCHAR(100) NULL,
  file_size INT UNSIGNED NOT NULL DEFAULT 0,
  uploaded_by INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_leave_attachments_request FOREIGN KEY (leave_request_id) REFERENCES leave_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_leave_attachments_user FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendance_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_no VARCHAR(50) NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  academic_year_be INT UNSIGNED NOT NULL DEFAULT 2568,
  request_type ENUM('time_record','workday_swap') NOT NULL DEFAULT 'time_record',
  work_date DATE NULL,
  absent_date DATE NULL,
  makeup_date DATE NULL,
  reason_type VARCHAR(50) NULL,
  other_reason VARCHAR(255) NULL,
  start_time TIME NULL,
  end_time TIME NULL,
  reason TEXT NOT NULL,
  status ENUM('draft','pending_head','pending_dean','approved','rejected','cancelled','hr_recorded') NOT NULL DEFAULT 'draft',
  current_approver_role VARCHAR(50) NULL,
  submitted_at DATETIME NULL,
  approved_at DATETIME NULL,
  rejected_at DATETIME NULL,
  cancelled_at DATETIME NULL,
  hr_recorded_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_attendance_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendance_approval_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attendance_request_id INT UNSIGNED NOT NULL,
  approver_id INT UNSIGNED NOT NULL,
  approver_role VARCHAR(50) NOT NULL,
  action ENUM('submitted','approved','rejected','cancelled','hr_recorded') NOT NULL,
  comment TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_attendance_logs_request FOREIGN KEY (attendance_request_id) REFERENCES attendance_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_attendance_logs_approver FOREIGN KEY (approver_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendance_attachments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attendance_request_id INT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  mime_type VARCHAR(100) NULL,
  file_size INT UNSIGNED NOT NULL DEFAULT 0,
  uploaded_by INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_attendance_attachments_request FOREIGN KEY (attendance_request_id) REFERENCES attendance_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_attendance_attachments_user FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hr_records (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  record_type ENUM('leave','attendance') NOT NULL,
  request_id INT UNSIGNED NOT NULL,
  recorded_by INT UNSIGNED NOT NULL,
  note TEXT NULL,
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_hr_records_user FOREIGN KEY (recorded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NULL,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
