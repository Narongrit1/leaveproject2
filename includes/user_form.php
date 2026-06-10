<?php $formUser = $editUser ?? ['personnel_code'=>'','full_name'=>'','position_name'=>'','phone'=>'','email'=>'','department_id'=>'','role'=>'lecturer','is_active'=>1]; ?>
<form method="post" class="rounded bg-white p-6 shadow-sm">
    <?= csrf_field() ?>
    <div class="grid gap-5 md:grid-cols-2">
        <div><label class="form-label">รหัสบุคลากร</label><input class="form-input" name="personnel_code" value="<?= e($formUser['personnel_code']) ?>"></div>
        <div><label class="form-label">ชื่อ-สกุล</label><input class="form-input" name="full_name" value="<?= e($formUser['full_name']) ?>" required></div>
        <div><label class="form-label">ตำแหน่ง</label><input class="form-input" name="position_name" value="<?= e($formUser['position_name']) ?>"></div>
        <div><label class="form-label">โทรศัพท์</label><input class="form-input" name="phone" value="<?= e($formUser['phone']) ?>"></div>
        <div><label class="form-label">Email</label><input class="form-input" type="email" name="email" value="<?= e($formUser['email']) ?>" required></div>
        <div><label class="form-label">สังกัด</label><select class="form-input" name="department_id"><option value="">ไม่ระบุ</option><?php foreach ($departments as $d): ?><option value="<?= (int)$d['id'] ?>" <?= (int)$formUser['department_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label">Role</label><select class="form-input" name="role"><?php foreach ($roles as $r): ?><option value="<?= e($r) ?>" <?= $formUser['role'] === $r ? 'selected' : '' ?>><?= e(role_label($r)) ?></option><?php endforeach; ?></select></div>
        <label class="flex items-center gap-2 pt-8 text-sm"><input type="checkbox" name="is_active" <?= !empty($formUser['is_active']) ? 'checked' : '' ?>> ใช้งาน</label>
        <?php if (isset($editUser)): ?><label class="flex items-center gap-2 text-sm md:col-span-2"><input type="checkbox" name="reset_password"> รีเซ็ตรหัสผ่านเป็น 123456</label><?php endif; ?>
    </div>
    <?php if (!empty($error)): ?><div class="mt-5 rounded bg-rose-50 p-3 text-sm text-rose-700"><?= e($error) ?></div><?php endif; ?>
    <div class="mt-6 flex gap-2"><button class="btn btn-primary" type="submit"><i data-lucide="save" class="h-4 w-4"></i>บันทึก</button><a class="btn btn-secondary" href="users_list.php">กลับ</a></div>
</form>

