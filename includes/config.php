<?php
declare(strict_types=1);

define('APP_NAME', 'Faculty Leave System');
define('APP_TITLE', 'ระบบใบลาออนไลน์และใบรับรองเวลาปฏิบัติงาน');
define('APP_TIMEZONE', 'Asia/Bangkok');

define('DB_HOST', 'localhost');
define('DB_NAME', 'faculty_leave_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('UPLOAD_MAX_BYTES', 5 * 1024 * 1024);
define('BASE_PATH', dirname(__DIR__));
define('LEAVE_UPLOAD_DIR', BASE_PATH . '/uploads/leave_attachments');
define('ATTENDANCE_UPLOAD_DIR', BASE_PATH . '/uploads/attendance_attachments');

date_default_timezone_set(APP_TIMEZONE);

