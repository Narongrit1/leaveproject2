USE faculty_leave_system;

CREATE TABLE IF NOT EXISTS academic_years (
  year_be INT UNSIGNED PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  starts_on DATE NULL,
  ends_on DATE NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leave_type_entitlements (
  academic_year_be INT UNSIGNED NOT NULL,
  leave_type_id INT UNSIGNED NOT NULL,
  entitled_days DECIMAL(6,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (academic_year_be, leave_type_id),
  CONSTRAINT fk_leave_entitlements_year FOREIGN KEY (academic_year_be) REFERENCES academic_years(year_be) ON DELETE CASCADE,
  CONSTRAINT fk_leave_entitlements_type FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO academic_years (year_be, title, is_active)
SELECT CAST(setting_value AS UNSIGNED), CONCAT('ปีการศึกษา ', setting_value), 1
FROM system_settings
WHERE setting_key = 'academic_year_be'
ON DUPLICATE KEY UPDATE title = VALUES(title), is_active = 1;

INSERT INTO leave_type_entitlements (academic_year_be, leave_type_id, entitled_days)
SELECT y.year_be, lt.id, COALESCE(MAX(lb.entitled_days), 0)
FROM academic_years y
CROSS JOIN leave_types lt
LEFT JOIN leave_balances lb ON lb.year_be = y.year_be AND lb.leave_type_id = lt.id
GROUP BY y.year_be, lt.id
ON DUPLICATE KEY UPDATE entitled_days = VALUES(entitled_days);
