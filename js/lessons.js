// Lesson list helpers: master checkbox for bulk selection.

(function() {
    'use strict';

    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'cj-select-all') {
            document.querySelectorAll('input[name="selectedlessons[]"]').forEach(function(cb) {
                cb.checked = e.target.checked;
            });
        }
    });
})();
