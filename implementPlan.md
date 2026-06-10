# แผนการพัฒนาระบบใบลาออนไลน์และใบรับรองเวลาปฏิบัติงาน

## 1. เป้าหมายของระบบ

พัฒนาระบบสำหรับคณะเทคโนโลยีสารสนเทศ มหาวิทยาลัยสยาม เพื่อจัดการงานหลัก 2 ส่วน

- ระบบยื่นใบลาออนไลน์ตามแบบฟอร์มใบลา มหาวิทยาลัยสยาม ปี 2568
- ระบบยื่นใบรับรองเวลาปฏิบัติงานตามแบบฟอร์มใบรับรองการปฏิบัติงาน มหาวิทยาลัยสยาม ปี 2568

ระบบต้องใช้งานได้จริงบน Web Hosting ทั่วไป เช่น Apache + PHP + MySQL โดยใช้ Pure PHP แบบ Procedural Programming ไม่ใช้ OOP, ไม่ใช้ MVC และไม่ใช้ Framework

## 2. ขอบเขตที่ต้องทำ

### 2.1 ระบบผู้ใช้และสิทธิ์

- Login / Logout ด้วย session
- บังคับเปลี่ยนรหัสผ่านหลัง login ครั้งแรก
- จัดการผู้ใช้ตามบทบาท
- Import / Export รายชื่อบุคลากร
- แยกสิทธิ์การเข้าถึงทุกหน้าและทุก action

บทบาทหลัก

- `admin`
- `dean`
- `vice_dean`
- `assistant_dean`
- `head_department`
- `lecturer`
- `staff`
- `hr`

### 2.2 ระบบใบลา

- สร้างใบลา
- บันทึกแบบร่าง
- แก้ไขใบลาที่ยังเป็น Draft
- ส่งใบลาเข้าสู่กระบวนการอนุมัติ
- ยกเลิกใบลาที่ยัง Pending
- อนุมัติ / ไม่อนุมัติใบลา
- บันทึกข้อมูล HR หลังอนุมัติ
- แนบเอกสารประกอบ เช่น ใบรับรองแพทย์
- พิมพ์ใบลาแบบ HTML A4 ให้สอดคล้องกับ PDF ฟอร์มใบลาแนบ
- Export รายงานเป็น CSV
- แสดงใบลาที่อนุมัติแล้วในปฏิทิน

### 2.3 ระบบใบรับรองเวลาปฏิบัติงาน

- สร้างคำขอใบรับรองเวลาปฏิบัติงาน
- แก้ไข / ยกเลิกตามสถานะ
- ส่งอนุมัติตามลำดับผู้บังคับบัญชา
- HR บันทึกผลหลังอนุมัติ
- แนบเอกสารประกอบ
- พิมพ์ใบรับรองแบบ HTML A4 ให้สอดคล้องกับ PDF ฟอร์มใบรับรองการปฏิบัติงานแนบ
- Export รายงานเป็น CSV

### 2.4 Dashboard และรายงาน

- Dashboard แสดงจำนวนใบลาตามสถานะ
- Dashboard แสดงใบรับรองเวลาปฏิบัติงานตามสถานะ
- รายงานสรุปการลาทั้งหมด
- รายงานแยกตามบุคลากร
- รายงานแยกตามภาควิชา
- รายงานแยกตามประเภทการลา
- รายงานสำหรับ HR
- ปฏิทินการลาของบุคลากรโดยใช้ FullCalendar.js

## 3. Technology Stack

### Frontend

- Pure PHP render HTML
- Tailwind CSS ผ่าน CDN
- Lucide Icons ผ่าน CDN
- SweetAlert2 ผ่าน CDN
- FullCalendar.js ผ่าน CDN
- JavaScript พื้นฐานเท่าที่จำเป็น
- Responsive Design รองรับ Desktop, Tablet และ Mobile

### Backend

- Pure PHP แบบ Procedural Programming
- MySQL
- PDO พร้อม prepared statement
- PHP session สำหรับ authentication
- ไม่มี Framework
- ไม่มี OOP
- ไม่มี MVC

### Export / Print

- CSV export ด้วย PHP header และ stream output
- Print form เป็น HTML A4 พร้อม CSS print
- หากต้องการ PDF จริงภายหลัง ให้เพิ่มทางเลือก export ผ่าน browser print-to-PDF หรือพิจารณาไลบรารีที่เหมาะกับ hosting ในขั้นต่อไป

## 4. แนวทาง UI

- โทน Professional, Clean, Trustworthy
- สีหลักเป็นน้ำเงิน / ขาว / เทา ตามภาพลักษณ์องค์กร
- ใช้ Sidebar + Topbar สำหรับหน้าหลัง login
- ใช้ Card UI เฉพาะข้อมูลสรุป
- ใช้ Table UI สำหรับรายการใบลา ใบรับรอง และรายงาน
- ใช้ Form UI ที่อ่านง่าย กรอกง่าย
- เมนู sidebar ต้องเปลี่ยนตามสิทธิ์ผู้ใช้
- หน้าพิมพ์เอกสารต้องเน้นความตรงกับแบบฟอร์ม PDF มากกว่าความสวยแบบ dashboard

## 5. โครงสร้างโฟลเดอร์ที่จะสร้าง

```text
leave-system/
├── index.php
├── login.php
├── logout.php
├── dashboard.php
├── change_password.php
├── force_change_password.php
├── leave_create.php
├── leave_list.php
├── leave_view.php
├── leave_edit.php
├── leave_cancel.php
├── leave_approve.php
├── leave_reject.php
├── leave_history.php
├── leave_print.php
├── leave_hr_record.php
├── leave_calendar.php
├── calendar_events.php
├── attendance_create.php
├── attendance_list.php
├── attendance_view.php
├── attendance_edit.php
├── attendance_cancel.php
├── attendance_approve.php
├── attendance_reject.php
├── attendance_hr_record.php
├── attendance_print.php
├── attendance_history.php
├── users_list.php
├── users_create.php
├── users_edit.php
├── users_delete.php
├── users_import.php
├── users_export_csv.php
├── departments_list.php
├── departments_create.php
├── departments_edit.php
├── departments_delete.php
├── leave_types_list.php
├── leave_types_create.php
├── leave_types_edit.php
├── leave_types_delete.php
├── report_leave_summary.php
├── report_leave_individual.php
├── report_by_user.php
├── report_by_department.php
├── report_by_leave_type.php
├── report_hr_summary.php
├── export_leave_csv.php
├── export_attendance_csv.php
├── includes/
│   ├── config.php
│   ├── db.php
│   ├── auth.php
│   ├── functions.php
│   ├── header.php
│   ├── sidebar.php
│   ├── topbar.php
│   └── footer.php
├── uploads/
│   ├── leave_attachments/
│   └── attendance_attachments/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── database/
    ├── leave_system.sql
    └── seed.sql
```

หมายเหตุ: จะสร้างโครงสร้างนี้เมื่อได้รับคำสั่งเริ่มเขียนโปรแกรมเท่านั้น

## 6. Database Design

### 6.1 ตารางหลัก

- `departments`
- `users`
- `leave_types`
- `leave_balances`
- `leave_requests`
- `leave_approval_logs`
- `leave_attachments`
- `attendance_requests`
- `attendance_approval_logs`
- `attendance_attachments`
- `hr_records`
- `system_settings`

### 6.2 แนวทาง field สำคัญ

`users`

- `id`
- `personnel_code`
- `full_name`
- `position_name`
- `phone`
- `email`
- `password_hash`
- `department_id`
- `role`
- `must_change_password`
- `is_active`
- `created_at`
- `updated_at`

`leave_requests`

- `id`
- `request_no`
- `user_id`
- `leave_type_id`
- `start_date`
- `end_date`
- `total_days`
- `reason`
- `contact_during_leave`
- `status`
- `current_approver_role`
- `submitted_at`
- `approved_at`
- `rejected_at`
- `cancelled_at`
- `created_at`
- `updated_at`

`attendance_requests`

- `id`
- `request_no`
- `user_id`
- `work_date`
- `start_time`
- `end_time`
- `reason`
- `status`
- `current_approver_role`
- `submitted_at`
- `approved_at`
- `rejected_at`
- `cancelled_at`
- `created_at`
- `updated_at`

`leave_approval_logs` และ `attendance_approval_logs`

- `id`
- `request_id`
- `approver_id`
- `approver_role`
- `action`
- `comment`
- `created_at`

## 7. Seed Data

ต้องสร้างข้อมูลเริ่มต้นจากรายชื่อบุคลากรคณะ IT ปี 2568 ที่อยู่ในไฟล์แนบ โดยกำหนด

- username เป็น email
- password เริ่มต้นเป็น `123456`
- password ต้อง hash ด้วย `password_hash()`
- `must_change_password = 1`
- กำหนด role ตามข้อมูลแนบ เช่น dean, head_department, lecturer, staff
- สร้าง admin เริ่มต้นอย่างน้อย 1 รายการสำหรับติดตั้งระบบ

ข้อมูลภาควิชาที่ต้องรองรับอย่างน้อย

- คณะเทคโนโลยีสารสนเทศ
- ภาควิชาเทคโนโลยีสารสนเทศ
- ภาควิชาธุรกิจดิจิทัล
- ภาควิชาแอนิเมชันและสื่อสร้างสรรค์

## 8. Workflow สถานะ

### 8.1 สถานะใบลา

- `draft`
- `pending_head`
- `pending_dean`
- `approved`
- `rejected`
- `cancelled`
- `hr_recorded`

### 8.2 สถานะใบรับรองเวลาปฏิบัติงาน

- `draft`
- `pending_head`
- `pending_dean`
- `approved`
- `rejected`
- `cancelled`
- `hr_recorded`

### 8.3 ลำดับอนุมัติเบื้องต้น

- lecturer ส่งถึง head_department ก่อน
- head_department ส่งถึง dean หรือขั้นที่กำหนด
- staff ส่งถึงผู้บริหาร/ผู้รับผิดชอบตามสิทธิ์
- dean เห็นรายการที่ถึงขั้นสุดท้ายหรือรายการทั้งหมดตามสิทธิ์
- admin และ hr เห็นข้อมูลทั้งหมดตามสิทธิ์ที่กำหนด

รายละเอียดลำดับอนุมัติจะทำเป็นฟังก์ชันกลางใน `includes/functions.php` เพื่อให้ปรับได้ง่ายโดยยังคงเป็น procedural code

## 9. Business Rules

- ผู้ใช้ 1 คนมี role หลัก 1 role
- อาจารย์และหัวหน้าภาคต้องผูกกับภาควิชา
- คณบดีและรองคณบดีไม่จำเป็นต้องผูกกับภาควิชา
- เจ้าหน้าที่สังกัดคณะ
- ผู้ใช้ทั่วไปเห็นเฉพาะใบลาหรือใบรับรองของตนเอง
- หัวหน้าภาคเห็นรายการของบุคลากรในภาควิชาตนเอง
- คณบดีเห็นรายการที่รออนุมัติขั้นสุดท้ายและรายงานภาพรวม
- admin และ hr เห็นข้อมูลทั้งหมดตามสิทธิ์
- ใบลาที่ approved, rejected หรือ hr_recorded แล้วแก้ไขไม่ได้
- ใบลาที่ draft แก้ไขได้
- ใบลาที่ pending ยกเลิกได้โดยเจ้าของใบลา
- ระบบต้องคำนวณจำนวนวันลาจาก `start_date` ถึง `end_date`
- ห้ามเลือกวันสิ้นสุดก่อนวันเริ่มต้น
- ลาป่วยตั้งแต่ 3 วันขึ้นไปต้องแนบใบรับรองแพทย์
- ลากิจหรือลาพักร้อนต้องยื่นล่วงหน้าอย่างน้อย 3 วันทำการ
- ใบลาที่ approved หรือ hr_recorded ต้องแสดงในปฏิทิน
- ใบรับรองเวลาปฏิบัติงานต้องอนุมัติก่อน HR บันทึก

## 10. Security Requirements

- ทุก query ต้องใช้ prepared statement
- password ต้องเข้ารหัสด้วย `password_hash()`
- ตรวจ session ทุกหน้าหลัง login
- ตรวจ role ก่อนเข้าเมนูและก่อนทำ action
- ป้องกัน XSS ด้วย `htmlspecialchars()`
- ใช้ CSRF token สำหรับ form สำคัญ
- จำกัดไฟล์ upload เฉพาะ PDF, JPG, JPEG, PNG
- จำกัดขนาดไฟล์ upload ไม่เกิน 5MB
- เปลี่ยนชื่อไฟล์ upload ก่อนบันทึก
- ตรวจสิทธิ์ก่อนเปิดไฟล์แนบ
- ไม่แสดง database error แบบละเอียดต่อผู้ใช้ทั่วไป
- เพิ่ม helper function สำหรับ redirect พร้อม flash message
- validate input ทั้งฝั่ง client และ server

## 11. แผนการ Implement

### Phase 1: เตรียมโครงสร้างและฐานข้อมูล

- สร้างโครงสร้างโฟลเดอร์
- สร้าง `database/leave_system.sql`
- สร้าง `database/seed.sql`
- สร้าง `includes/config.php`
- สร้าง `includes/db.php`
- สร้าง helper function พื้นฐาน
- สร้างระบบ session และ CSRF

ผลลัพธ์: มีฐานข้อมูลและไฟล์แกนกลางพร้อมใช้งาน

### Phase 2: Authentication และ Layout

- สร้าง `login.php`
- สร้าง `logout.php`
- สร้าง `force_change_password.php`
- สร้าง `change_password.php`
- สร้าง layout กลาง `header.php`, `sidebar.php`, `topbar.php`, `footer.php`
- สร้าง `dashboard.php` เบื้องต้น
- ทำเมนูตาม role

ผลลัพธ์: login ได้จริงและเข้าหน้า dashboard ตามสิทธิ์

### Phase 3: User / Department / Leave Type Management

- สร้าง CRUD ผู้ใช้
- สร้าง import รายชื่อบุคลากร
- สร้าง export รายชื่อผู้ใช้ CSV
- สร้าง CRUD ภาควิชา
- สร้าง CRUD ประเภทการลา
- สร้างระบบสิทธิ์วันลาเริ่มต้น

ผลลัพธ์: admin จัดการข้อมูลตั้งต้นได้

### Phase 4: ระบบใบลา

- สร้าง `leave_create.php`
- สร้าง `leave_list.php`
- สร้าง `leave_view.php`
- สร้าง `leave_edit.php`
- สร้าง `leave_cancel.php`
- สร้าง upload attachment
- สร้าง validation ตาม business rules
- สร้าง approval/reject workflow
- สร้าง approval logs

ผลลัพธ์: ผู้ใช้ยื่นใบลาและผู้อนุมัติจัดการคำขอได้

### Phase 5: Print / Export ใบลา

- สร้าง `leave_print.php`
- จัด CSS print เป็น A4
- เทียบ layout กับ PDF ฟอร์มใบลาแนบ
- สร้าง `leave_hr_record.php`
- สร้าง `export_leave_csv.php`

ผลลัพธ์: พิมพ์ใบลาและ export รายงานได้

### Phase 6: ระบบใบรับรองเวลาปฏิบัติงาน

- สร้าง `attendance_create.php`
- สร้าง `attendance_list.php`
- สร้าง `attendance_view.php`
- สร้าง `attendance_edit.php`
- สร้าง `attendance_cancel.php`
- สร้าง upload attachment
- สร้าง approval/reject workflow
- สร้าง approval logs

ผลลัพธ์: ผู้ใช้ยื่นใบรับรองเวลาปฏิบัติงานได้

### Phase 7: Print / Export ใบรับรองเวลาปฏิบัติงาน

- สร้าง `attendance_print.php`
- จัด CSS print เป็น A4
- เทียบ layout กับ PDF ฟอร์มใบรับรองการปฏิบัติงานแนบ
- สร้าง `attendance_hr_record.php`
- สร้าง `export_attendance_csv.php`

ผลลัพธ์: พิมพ์ใบรับรองและ export รายงานได้

### Phase 8: Calendar และ Reports

- สร้าง `leave_calendar.php`
- สร้าง `calendar_events.php`
- ใช้ FullCalendar.js ผ่าน CDN
- สร้างรายงานสรุปทั้งหมด
- สร้างรายงานรายบุคคล
- สร้างรายงานตามภาควิชา
- สร้างรายงานตามประเภทการลา
- สร้างรายงาน HR

ผลลัพธ์: ผู้บริหารและ HR ดูข้อมูลภาพรวมได้

### Phase 9: Hardening และ Testing

- ตรวจ permission ทุกหน้า
- ตรวจ CSRF ทุก form สำคัญ
- ตรวจ upload validation
- ตรวจ SQL prepared statement
- ทดสอบ workflow ตาม role
- ทดสอบ responsive layout
- ทดสอบ print A4
- ทดสอบ CSV export ภาษาไทย
- ทดสอบติดตั้งบน local PHP/MySQL

ผลลัพธ์: ระบบพร้อม deploy บน hosting จริง

## 12. รายการไฟล์สำคัญที่จะต้องเขียน

ชุดแกนระบบ

- `includes/config.php`
- `includes/db.php`
- `includes/auth.php`
- `includes/functions.php`
- `includes/header.php`
- `includes/sidebar.php`
- `includes/topbar.php`
- `includes/footer.php`

ชุด authentication

- `index.php`
- `login.php`
- `logout.php`
- `dashboard.php`
- `change_password.php`
- `force_change_password.php`

ชุดใบลา

- `leave_create.php`
- `leave_list.php`
- `leave_view.php`
- `leave_edit.php`
- `leave_cancel.php`
- `leave_approve.php`
- `leave_reject.php`
- `leave_history.php`
- `leave_print.php`
- `leave_hr_record.php`

ชุดใบรับรองเวลาปฏิบัติงาน

- `attendance_create.php`
- `attendance_list.php`
- `attendance_view.php`
- `attendance_edit.php`
- `attendance_cancel.php`
- `attendance_approve.php`
- `attendance_reject.php`
- `attendance_history.php`
- `attendance_print.php`
- `attendance_hr_record.php`

ชุด admin และรายงาน

- `users_list.php`
- `users_create.php`
- `users_edit.php`
- `users_delete.php`
- `users_import.php`
- `users_export_csv.php`
- `departments_list.php`
- `leave_types_list.php`
- `leave_calendar.php`
- `calendar_events.php`
- `report_leave_summary.php`
- `report_leave_individual.php`
- `report_hr_summary.php`
- `export_leave_csv.php`
- `export_attendance_csv.php`

## 13. แนวทางตรวจรับ

- Login ด้วยผู้ใช้ seed ได้
- ผู้ใช้ถูกบังคับเปลี่ยนรหัสผ่านครั้งแรก
- lecturer ยื่นใบลาได้
- head_department เห็นใบลาของบุคลากรในภาคตนเอง
- dean เห็นรายการที่ต้องอนุมัติระดับคณบดี
- admin เห็นและจัดการข้อมูลทั้งหมด
- hr บันทึกข้อมูลหลังอนุมัติได้
- ใบลาป่วย 3 วันขึ้นไปต้องแนบไฟล์
- ลากิจ/ลาพักร้อนต้องยื่นล่วงหน้าอย่างน้อย 3 วันทำการ
- ปฏิทินแสดงเฉพาะใบลาที่ approved หรือ hr_recorded
- Print ใบลาเป็น A4 ได้และสอดคล้องกับฟอร์ม PDF แนบ
- Print ใบรับรองเวลาปฏิบัติงานเป็น A4 ได้และสอดคล้องกับฟอร์ม PDF แนบ
- Export CSV ภาษาไทยไม่เพี้ยน
- ผู้ใช้ทั่วไปเปิดข้อมูลของผู้อื่นไม่ได้

## 14. ขั้นตอนติดตั้งหลังพัฒนาเสร็จ

- สร้างฐานข้อมูล MySQL ชื่อ `faculty_leave_system`
- Import `database/leave_system.sql`
- Import `database/seed.sql`
- แก้ไขค่าฐานข้อมูลใน `includes/config.php`
- สร้างโฟลเดอร์ `uploads/leave_attachments/`
- สร้างโฟลเดอร์ `uploads/attendance_attachments/`
- ตั้ง permission ให้ `uploads/` เขียนไฟล์ได้
- เปิดเว็บผ่าน `login.php`
- Login ด้วย admin เริ่มต้น
- ตรวจรายชื่อบุคลากร seed
- ทดสอบยื่นใบลา
- ทดสอบยื่นใบรับรองเวลาปฏิบัติงาน
- ทดสอบอนุมัติและบันทึก HR
- ทดสอบพิมพ์เอกสารและ export CSV

## 15. ข้อสังเกตเกี่ยวกับ PDF ฟอร์มแนบ

ไฟล์ PDF แนบทั้ง 2 ไฟล์จะใช้เป็นต้นแบบสำหรับหน้า print/export ดังนี้

- `2568 ใบลา มหาวิทยาลัยสยาม.pdf` ใช้อ้างอิง layout ของ `leave_print.php`
- `2568 ใบรับรองการปฏิบัติงาน ม.สยาม.pdf` ใช้อ้างอิง layout ของ `attendance_print.php`

ตอนเริ่ม implement ต้องตรวจรายละเอียดช่องกรอกใน PDF อีกครั้ง และจัด HTML print ให้ตรงตำแหน่งสำคัญ เช่น หัวเอกสาร ข้อมูลผู้ยื่น วันที่ รายละเอียดคำขอ ลายเซ็น และช่องอนุมัติ

## 16. สถานะปัจจุบัน

จัดทำแผนเรียบร้อยแล้ว ยังไม่เริ่มเขียนโปรแกรมหรือสร้างโครงสร้างระบบจริง

เมื่อได้รับคำสั่งให้เริ่มเขียนโปรแกรม จึงจะเริ่มจาก Phase 1 ตามแผนนี้
