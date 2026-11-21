/*
 * Perilaku form dan tabel untuk semua halaman admin (tanpa build, memakai plugin bawaan Metronic):
 * - input tanggal  -> pemilih tanggal flatpickr (tampil "21 Nov 2026", terkirim "2026-11-21")
 * - dropdown panjang -> select2 dengan pencarian
 * - input jumlah uang (data-money) -> format ribuan otomatis "1.500.000"
 * - tabel lebar -> bisa digeser ke samping di layar kecil
 * Tambahkan class "no-ui" pada elemen untuk mengecualikannya.
 */
(function () {
    'use strict';
    var $ = window.jQuery;

    var localeId = {
        weekdays: { shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'], longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] },
        months: {
            shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        },
        firstDayOfWeek: 1, rangeSeparator: ' s/d ', time_24hr: true,
    };

    function initDates(root) {
        if (!window.flatpickr) return;
        root.querySelectorAll('input[type="date"]:not(.no-ui), input[data-ui-date]:not(.no-ui)').forEach(function (el) {
            if (el._flatpickr) return;
            el.type = 'text';
            window.flatpickr(el, {
                locale: localeId, dateFormat: 'Y-m-d', altInput: true, altFormat: 'j M Y', allowInput: true,
                disableMobile: true,
                onReady: function (_, __, fp) {
                    // Tombol hapus kecil agar filter tanggal bisa dikosongkan.
                    var clear = document.createElement('button');
                    clear.type = 'button'; clear.className = 'ui-date-clear'; clear.innerHTML = '&times;'; clear.title = 'Kosongkan';
                    clear.addEventListener('click', function () { fp.clear(); });
                    var wrap = document.createElement('div');
                    wrap.className = 'ui-date-wrap';
                    fp.altInput.parentNode.insertBefore(wrap, fp.altInput);
                    wrap.appendChild(fp.altInput);
                    wrap.appendChild(clear);
                },
            });
        });
    }

    function initSelects(root) {
        if (!$ || !$.fn.select2) return;
        $(root).find('select.form-select').each(function () {
            var el = this;
            if (el.classList.contains('no-ui') || el.dataset.control === 'select2' || $(el).hasClass('select2-hidden-accessible')) return;
            if (!el.multiple && el.options.length <= 12) return; // daftar pendek (mis. bulan) tetap dropdown biasa
            var modal = $(el).closest('.modal');
            var placeholder = el.dataset.placeholder || (el.options[0] && el.options[0].value === '' ? el.options[0].text : null);
            $(el).select2({
                width: '100%',
                dropdownParent: modal.length ? modal : $(document.body),
                placeholder: placeholder || undefined,
                allowClear: !!placeholder && !el.required,
                minimumResultsForSearch: 6,
                language: { noResults: function () { return 'Tidak ditemukan'; }, searching: function () { return 'Mencari…'; } },
            });
        });
    }

    function formatMoney(v) {
        var n = Number(v);
        if (v === '' || v === null || v === undefined || !isFinite(n)) return '';
        return n.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    function initMoney(root) {
        root.querySelectorAll('input[data-money]:not(.no-ui)').forEach(function (el) {
            if (el._uiMoney) return;
            el._uiMoney = true;
            el.setAttribute('inputmode', 'decimal');
            el.addEventListener('input', function () {
                // Simpan posisi kursor relatif terhadap jumlah digit di kanannya.
                var raw = el.value;
                var parts = raw.replace(/[^\d,]/g, '').split(',');
                var intPart = parts[0].replace(/^0+(?=\d)/, '');
                var right = raw.length - (el.selectionStart || raw.length);
                var out = intPart ? Number(intPart).toLocaleString('id-ID') : '';
                if (parts.length > 1) out += ',' + parts.slice(1).join('').slice(0, 2);
                el.value = out;
                var pos = Math.max(0, out.length - right);
                try { el.setSelectionRange(pos, pos); } catch (e) {}
            });
            if (el.value) el.value = formatMoney(el.value);
        });
    }

    function wrapTables(root) {
        root.querySelectorAll('table.table').forEach(function (t) {
            if (t.closest('.table-responsive, .ui-scroll, .dataTables_scroll, .ui-drawer') || t.classList.contains('no-ui')) return;
            var w = document.createElement('div');
            w.className = 'table-responsive';
            t.parentNode.insertBefore(w, t);
            w.appendChild(t);
        });
    }

    window.UI = {
        init: function (root) {
            root = root || document;
            wrapTables(root); initDates(root); initSelects(root); initMoney(root);
        },
        /** Isi input uang dari angka mentah (mis. "120000000.00"). */
        setMoney: function (el, v) { el.value = formatMoney(v); },
        /** Ambil angka dari input uang, mis. "1.500.000,5" -> 1500000.5 */
        money: function (el) { var s = String(el.value || '').replace(/\./g, '').replace(',', '.'); return s === '' ? '' : Number(s); },
        formatMoney: formatMoney,
    };

    document.addEventListener('DOMContentLoaded', function () { window.UI.init(document); });
    // Modal yang isinya baru dibuat lewat JS ikut diproses saat dibuka.
    document.addEventListener('shown.bs.modal', function (e) { window.UI.init(e.target); });
})();
