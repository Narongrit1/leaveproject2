<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['admin']);
set_flash('warning', 'ไม่ลบประเภทการลาที่มีประวัติผูกอยู่ เพื่อรักษาข้อมูลย้อนหลัง');
redirect_to('leave_types_list.php');

