<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['admin']);
set_flash('warning', 'ไม่ลบภาควิชาที่มีข้อมูลผูกอยู่ เพื่อรักษาประวัติระบบ');
redirect_to('departments_list.php');

