document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide) {
        window.lucide.createIcons();
    }

    document.querySelectorAll('.attendance-form').forEach(function (form) {
        var typeInputs = form.querySelectorAll('input[name="request_type"]');
        var sections = form.querySelectorAll('[data-section]');
        var reasonInputs = form.querySelectorAll('input[name="reason_type"]');
        var otherReason = form.querySelector('[data-other-reason]');

        function syncSections() {
            var selected = form.querySelector('input[name="request_type"]:checked');
            var type = selected ? selected.value : 'time_record';
            sections.forEach(function (section) {
                var active = section.getAttribute('data-section') === type;
                section.classList.toggle('is-disabled', !active);
                section.querySelectorAll('.request-group-controls input, .request-group-controls select, .request-group-controls textarea').forEach(function (control) {
                    control.disabled = !active;
                });
                section.querySelectorAll('input[name="request_type"]').forEach(function (control) {
                    control.disabled = false;
                });
            });
            syncOtherReason();
        }

        function syncOtherReason() {
            var selected = form.querySelector('input[name="reason_type"]:checked');
            if (otherReason) {
                var enabled = selected && selected.value === 'other' && !selected.disabled;
                otherReason.classList.toggle('hidden', !enabled);
                otherReason.querySelectorAll('input, select, textarea').forEach(function (control) {
                    control.disabled = !enabled;
                });
            }
        }

        typeInputs.forEach(function (input) {
            input.addEventListener('change', syncSections);
        });
        reasonInputs.forEach(function (input) {
            input.addEventListener('change', syncOtherReason);
        });
        syncSections();
        syncOtherReason();
    });
});
