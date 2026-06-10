<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$pageTitle = 'ปฏิทินการลา';
require __DIR__ . '/includes/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<section class="rounded bg-white p-5 shadow-sm">
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

