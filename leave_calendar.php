<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$user = current_user();
$role = $user['role'] ?? '';
$calendarScopeLabel = $role === 'lecturer'
    ? 'แสดงเฉพาะวันลาของฉันที่อนุมัติแล้ว'
    : 'แสดงวันลาที่อนุมัติแล้วตามสิทธิ์การเข้าถึงข้อมูล';

$pageTitle = 'ปฏิทินการลา';
require __DIR__ . '/includes/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<section class="rounded bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="section-kicker">Leave Calendar</div>
            <h2 class="text-xl font-semibold text-slate-900">ปฏิทินการลา</h2>
            <p class="mt-1 text-sm text-slate-500"><?= e($calendarScopeLabel) ?></p>
        </div>
        <a class="btn btn-primary" href="leave_create.php"><i data-lucide="file-plus-2" class="h-4 w-4"></i>ยื่นใบลา</a>
    </div>
    <div id="calendar"></div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        locale: 'th',
        height: 'auto',
        events: 'calendar_events.php',
        eventClick: function(info) {
            window.location.href = 'leave_view.php?id=' + info.event.id;
        }
    });
    calendar.render();
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
