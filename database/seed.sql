USE faculty_leave_system;
/*
INSERT INTO departments (id, name) VALUES
(1, 'คณะเทคโนโลยีสารสนเทศ'),
(2, 'ภาควิชาเทคโนโลยีสารสนเทศ'),
(3, 'ภาควิชาธุรกิจดิจิทัล'),
(4, 'ภาควิชาแอนิเมชันและสื่อสร้างสรรค์')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO leave_types (name, code, requires_attachment_days, advance_working_days, color) VALUES
('ลาป่วย', 'sick', 3, 0, '#ef4444'),
('ลากิจ', 'personal', NULL, 3, '#f59e0b'),
('ลาพักร้อน', 'vacation', NULL, 3, '#10b981'),
('ลาคลอด', 'maternity', NULL, 0, '#8b5cf6'),
('ลาอุปสมบท', 'ordination', NULL, 0, '#0ea5e9'),
('อื่น ๆ', 'other', NULL, 0, '#64748b')
ON DUPLICATE KEY UPDATE
name = VALUES(name),
requires_attachment_days = VALUES(requires_attachment_days),
advance_working_days = VALUES(advance_working_days),
color = VALUES(color);

INSERT INTO users (personnel_code, full_name, position_name, phone, email, password_hash, department_id, role, must_change_password, is_active) VALUES
('ADMIN', 'ผู้ดูแลระบบ', 'Admin', '-', 'admin@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 1, 'admin', 1, 1),
(NULL, 'ดร.เดชานุชิต กตัญญูทวีทิพย์', 'คณบดี', '081-550-6786', 'dechanuchit@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 1, 'dean', 1, 1),
('43126031', 'ดร.ณรงค์ฤทธิ์ สุคนธสิงห์', 'ผู้ช่วยคณบดีและหัวหน้าภาควิชา', '081-492-0993', 'narongrit@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 2, 'head_department', 1, 1),
('49126050', 'นายอรรณพ กางกั้น', 'อาจารย์ประจำ', '089-199-4734', 'unnopkk@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 2, 'lecturer', 1, 1),
('66129009', 'นายพงศ์พัฒน์ ฉายศิริพันธ์', 'อาจารย์ประจำ', '092-993-5495', 'pongphat.cha@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 2, 'lecturer', 1, 1),
('60126055', 'นางสาวศรัญธร มั่งมี', 'อาจารย์ประจำ', '084-526-1010', 'saranthon.mau@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 3, 'lecturer', 1, 1),
('40126010', 'ผศ.ดร.พิชญากร เลค', 'อาจารย์ประจำ', '089-031-7525', 'pitchayakorn@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 3, 'lecturer', 1, 1),
('44126035', 'นายนิวัฒน์ เตชะเกียรตินันท์', 'อาจารย์ประจำ', '085-954-5090', 'niwattec@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 3, 'lecturer', 1, 1),
('52330003', 'ดร.คมเดช บุญประเสริฐ', 'อาจารย์ประจำ', '090-989-6452', 'komdech.boo@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 3, 'lecturer', 1, 1),
('59130004', 'นายอรรถเศรษฐ์ ปรีดากรณ์', 'หัวหน้าภาควิชา', '086-349-3515', 'auttasead.pre@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 4, 'head_department', 1, 1),
('34153001', 'ดร.วิเศษฐ์ แสงดวงดี', 'อาจารย์ประจำ', '091-779-9905', 'vichet.sae@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 4, 'lecturer', 1, 1),
('65130009', 'นางสาวมนฤดี มิตรเจริญถาวร', 'อาจารย์ประจำ', '083-582-2459', 'monruedee.mit@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 4, 'lecturer', 1, 1),
('65130008', 'นายภัทรพล เกิดปรางค์', 'อาจารย์ประจำ', '064-189-5542', 'pattarapon.koe@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 4, 'lecturer', 1, 1),
('68130010', 'นายอมร พันธ์จิตวุฒิชัย', 'อาจารย์ประจำ', '082-590-8302', 'amorn.pan@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 4, 'lecturer', 1, 1),
('66131094', 'นางสาวชวัลลักษณ์ ศิริเกตุ', 'เลขานุการคณะ', '093-223-3314', 'chawanluk.sir@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 1, 'staff', 1, 1),
('HR', 'เจ้าหน้าที่ HR', 'HR', '-', 'hr@siam.edu', 'INITIAL_123456_REHASH_ON_LOGIN', 1, 'hr', 1, 1)
ON DUPLICATE KEY UPDATE
full_name = VALUES(full_name),
position_name = VALUES(position_name),
phone = VALUES(phone),
department_id = VALUES(department_id),
role = VALUES(role),
is_active = VALUES(is_active);

INSERT INTO leave_balances (user_id, leave_type_id, year_be, entitled_days, used_days)
SELECT u.id, lt.id, 2568,
  CASE lt.code
    WHEN 'sick' THEN 30
    WHEN 'personal' THEN 10
    WHEN 'vacation' THEN 10
    ELSE 0
  END,
  0
FROM users u
CROSS JOIN leave_types lt
WHERE 1=1
ON DUPLICATE KEY UPDATE entitled_days = VALUES(entitled_days);
*/
INSERT INTO system_settings (setting_key, setting_value) VALUES
('faculty_name', 'คณะเทคโนโลยีสารสนเทศ'),
('university_name', 'มหาวิทยาลัยสยาม'),
('academic_year_be', '2568')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO academic_years (year_be, title, is_active)
SELECT CAST(setting_value AS UNSIGNED), CONCAT('ปีการศึกษา ', setting_value), 1
FROM system_settings
WHERE setting_key = 'academic_year_be'
ON DUPLICATE KEY UPDATE title = VALUES(title), is_active = 1;

INSERT INTO leave_type_entitlements (academic_year_be, leave_type_id, entitled_days)
SELECT y.year_be, lt.id,
  CASE lt.code
    WHEN 'sick' THEN 30
    WHEN 'personal' THEN 10
    WHEN 'vacation' THEN 10
    ELSE 0
  END
FROM academic_years y
CROSS JOIN leave_types lt
ON DUPLICATE KEY UPDATE entitled_days = VALUES(entitled_days);
