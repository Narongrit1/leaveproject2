# วิธีติดตั้งระบบ

1. สร้างฐานข้อมูล MySQL ชื่อ `faculty_leave_system`
2. Import `database/leave_system.sql`
3. Import `database/seed.sql`
4. แก้ไขค่าฐานข้อมูลใน `includes/config.php`
5. ตรวจว่าโฟลเดอร์ `uploads/leave_attachments/` และ `uploads/attendance_attachments/` เขียนไฟล์ได้
6. เปิดเว็บที่ `login.php`
7. Login ด้วยบัญชีเริ่มต้น

บัญชีเริ่มต้น

- Admin: `admin@siam.edu`
- HR: `hr@siam.edu`
- ผู้ใช้ seed อื่น ๆ ใช้ email ตาม `database/seed.sql`
- รหัสผ่านเริ่มต้นทุกบัญชี: `123456`

หลัง login ครั้งแรก ระบบจะบังคับเปลี่ยนรหัสผ่าน และจะบันทึกรหัสผ่านใหม่ด้วย `password_hash()`

หมายเหตุ: หน้า print ของใบลาและใบรับรองเวลาปฏิบัติงานเป็น HTML A4 สำหรับสั่งพิมพ์หรือ print-to-PDF จาก browser โดยอ้างอิงฟอร์ม PDF แนบของมหาวิทยาลัยสยาม
