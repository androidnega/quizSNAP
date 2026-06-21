{{-- Copy error logs to clipboard --}}
<script>
(function () {
    'use strict';

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function toast(msg) {
        if (window.showToast) { window.showToast(msg); return; }
        alert(msg);
    }

    function copyText(text) {
        if (!text) { toast('Nothing to copy.'); return Promise.resolve(false); }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text).then(function () { toast('Copied to clipboard.'); return true; });
        }
        var ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); toast('Copied to clipboard.'); return Promise.resolve(true); }
        catch (e) { toast('Copy failed.'); return Promise.resolve(false); }
        finally { document.body.removeChild(ta); }
    }

    function fetchExport(url) {
        return fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-copy-error]');
        if (!btn) return;
        e.preventDefault();
        var id = btn.getAttribute('data-copy-error');
        var url = id === 'all'
            ? btn.getAttribute('data-export-url') || ''
            : '/dashboard/monitoring/errors/' + encodeURIComponent(id) + '/export';
        fetchExport(url).then(function (data) { copyText(data.text || ''); });
    });

    var copySelected = document.getElementById('copy-selected-errors');
    if (copySelected) {
        copySelected.addEventListener('click', function () {
            var ids = Array.from(document.querySelectorAll('.error-log-checkbox:checked')).map(function (el) { return el.value; });
            if (!ids.length) { toast('Select at least one error.'); return; }
            var url = copySelected.getAttribute('data-export-url') + '?ids=' + ids.join(',');
            fetchExport(url).then(function (data) { copyText(data.text || ''); });
        });
    }

    var selectAll = document.getElementById('select-all-errors');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.error-log-checkbox').forEach(function (cb) { cb.checked = selectAll.checked; });
        });
    }
})();
</script>
