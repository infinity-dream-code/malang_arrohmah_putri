<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Presensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-main: linear-gradient(135deg, #faf8fc 0%, #f3eff9 50%, #ebe5f5 100%);
            --card-bg: #ffffff;
            --card-shadow: 0 4px 20px rgba(124, 58, 237, 0.08);
            --card-radius: 16px;
            --accent: #6d28d9;
            --accent-light: #8b5cf6;
            --text: #1e1b2e;
            --text-muted: #64748b;
            --green-bg: #d1fae5;
            --yellow-bg: #fef3c7;
            --red-bg: #fee2e2;
            --gray-bg: #f1f5f9;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: var(--bg-main); display: flex; justify-content: center; }
        .app { width: 100%; max-width: 600px; min-height: 100vh; color: var(--text); position: relative; }
        .drawer-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); opacity: 0; pointer-events: none; transition: opacity 0.25s; backdrop-filter: blur(2px); z-index: 50; }
        .drawer-backdrop.open { opacity: 1; pointer-events: auto; }
        .drawer { position: fixed; top: 0; left: 0; width: 280px; height: 100%; background: linear-gradient(180deg, #1e1b4b 0%, #0f0d1e 100%); color: #e2e8f0; transform: translateX(-100%); transition: transform 0.25s; padding: 24px 20px; display: flex; flex-direction: column; z-index: 51; border-right: 1px solid rgba(255,255,255,0.08); box-shadow: 8px 0 32px rgba(0,0,0,0.2); }
        .drawer.open { transform: translateX(0); }
        .drawer-header { display: flex; align-items: center; margin-bottom: 20px; }
        .drawer-logo { width: 48px; height: 48px; border-radius: 12px; overflow: hidden; margin-right: 12px; flex-shrink: 0; }
        .drawer-logo img { width: 100%; height: 100%; object-fit: contain; }
        .drawer-user-name { font-size: 0.95rem; font-weight: 600; color: #f8fafc; }
        .drawer-user-role { font-size: 0.78rem; color: #94a3b8; }
        .drawer-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 16px 0; }
        .drawer-menu { list-style: none; padding: 0; margin: 0; flex: 1; }
        .drawer-menu-label { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.12em; color: #64748b; margin-bottom: 10px; padding-left: 14px; font-weight: 600; }
        .drawer-item { margin-bottom: 4px; }
        .drawer-link { display: flex; align-items: center; padding: 12px 14px; border-radius: 12px; color: #cbd5e1; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.2s; }
        .drawer-link span.icon { width: 24px; display: inline-flex; justify-content: center; align-items: center; margin-right: 12px; color: #94a3b8; }
        .drawer-link:hover { background: rgba(255,255,255,0.08); color: #f8fafc; }
        .drawer-link.active { background: linear-gradient(135deg, rgba(124,58,237,0.3) 0%, rgba(109,40,217,0.2) 100%); color: #e9d5ff; }
        .drawer-link.active span.icon { color: #c4b5fd; }
        .drawer-footer { font-size: 0.75rem; color: #64748b; margin-top: auto; padding-top: 16px; }

        .header { display: flex; align-items: center; padding: 20px 20px 16px; }
        .burger { width: 36px; height: 36px; margin-right: 12px; border: none; border-radius: 10px; background: rgba(109, 40, 217, 0.08); padding: 0; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; }
        .burger span { display: block; width: 18px; height: 2.5px; border-radius: 2px; background: var(--accent); }
        .title { font-size: 1.35rem; font-weight: 700; color: var(--text); letter-spacing: -0.02em; flex: 1; }
        .btn-refresh { width: 40px; height: 40px; border: none; border-radius: 10px; background: rgba(109, 40, 217, 0.1); color: var(--accent); cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .btn-refresh:hover { background: rgba(109, 40, 217, 0.2); }

        .content { padding: 0 20px 32px; }
        .filter-card { background: var(--card-bg); border-radius: var(--card-radius); padding: 18px 20px; margin-bottom: 16px; box-shadow: var(--card-shadow); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; }
        .filter-left { display: flex; flex-direction: column; gap: 2px; }
        .date-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); }
        .date-value { font-size: 1.1rem; font-weight: 700; color: var(--text); }
        .btn-date { border: none; border-radius: 12px; padding: 12px 18px; background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%); color: #fff; display: inline-flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 14px rgba(109, 40, 217, 0.35); }
        .btn-date:hover { transform: translateY(-1px); }
        .btn-date i { font-size: 1rem; opacity: 0.9; }

        .search-wrap { margin-bottom: 16px; }
        .search-input { width: 100%; padding: 14px 18px 14px 44px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 0.95rem; font-family: inherit; background: var(--card-bg); box-shadow: var(--card-shadow); }
        .search-input::placeholder { color: var(--text-muted); }
        .search-wrap { position: relative; }
        .search-wrap i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1rem; }

        .section-title { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); margin-bottom: 12px; }
        .list { display: flex; flex-direction: column; gap: 16px; }
        .card { background: var(--card-bg); border-radius: var(--card-radius); padding: 20px; box-shadow: var(--card-shadow); border: 1px solid rgba(109, 40, 217, 0.06); }
        .card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .card-title { font-size: 1rem; font-weight: 700; color: var(--text); line-height: 1.35; }
        .card-sub { font-size: 0.82rem; color: var(--text-muted); margin-top: 4px; }
        .chip-date { font-size: 0.75rem; font-weight: 600; padding: 6px 12px; border-radius: 10px; background: var(--gray-bg); color: var(--text-muted); white-space: nowrap; }

        .sholat-list { display: flex; flex-direction: column; gap: 8px; }
        .sholat-row { display: flex; align-items: stretch; gap: 10px; }
        .sholat-item { flex: 1; border-radius: 12px; padding: 12px 14px; min-height: 56px; }
        .sholat-label { font-size: 0.8rem; font-weight: 700; margin-bottom: 4px; }
        .sholat-sub { font-size: 0.72rem; line-height: 1.4; opacity: 0.95; }
        .sholat-sholat { background: var(--green-bg); color: #065f46; }
        .sholat-izin { background: var(--yellow-bg); color: #92400e; }
        .sholat-alpa { background: var(--red-bg); color: #991b1b; }
        .sholat-belum { background: var(--gray-bg); color: #64748b; }
        .sholat-sakit { background: #fce7f3; color: #9d174d; }
        .btn-edit { width: 44px; min-width: 44px; height: 56px; border: none; border-radius: 12px; background: rgba(109, 40, 217, 0.1); color: var(--accent); cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .btn-edit:hover { background: rgba(109, 40, 217, 0.2); }
        .btn-edit:disabled { opacity: 0.5; cursor: not-allowed; }

        .empty { text-align: center; padding: 48px 24px; font-size: 0.95rem; color: var(--text-muted); background: var(--card-bg); border-radius: var(--card-radius); box-shadow: var(--card-shadow); }
        .empty i { font-size: 2.5rem; opacity: 0.4; margin-bottom: 12px; display: block; }
        .error { margin-bottom: 20px; padding: 14px 16px; font-size: 0.9rem; border-radius: 12px; background: var(--red-bg); color: #991b1b; display: flex; align-items: center; gap: 10px; }
        .success { margin-bottom: 20px; padding: 14px 16px; font-size: 0.9rem; border-radius: 12px; background: var(--green-bg); color: #065f46; display: flex; align-items: center; gap: 10px; }

        .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); opacity: 0; pointer-events: none; transition: opacity 0.2s; z-index: 100; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .modal-backdrop.open { opacity: 1; pointer-events: auto; }
        .modal { background: var(--card-bg); border-radius: 16px; padding: 24px; max-width: 360px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.2); transform: scale(0.95); transition: transform 0.2s; }
        .modal-backdrop.open .modal { transform: scale(1); }
        .modal-title { font-size: 1.1rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
        .modal-sub { font-size: 0.88rem; color: var(--text-muted); margin-bottom: 20px; }
        .modal-options { display: flex; flex-direction: column; gap: 8px; }
        .modal-opt { display: block; width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; color: var(--text); font-size: 0.95rem; font-weight: 500; font-family: inherit; cursor: pointer; text-align: left; transition: all 0.15s; }
        .modal-opt:hover { background: #f8fafc; border-color: var(--accent); }
        .modal-opt.alpa { border-left: 4px solid #dc2626; }
        .modal-opt.izin { border-left: 4px solid #d97706; }
        .modal-opt.sholat { border-left: 4px solid #059669; }
        .modal-opt.haid { border-left: 4px solid #059669; }
        .modal-opt.sakit { border-left: 4px solid #db2777; }
        .modal-close { margin-top: 16px; width: 100%; padding: 10px; border: none; border-radius: 10px; background: var(--gray-bg); color: var(--text-muted); font-size: 0.9rem; cursor: pointer; }
        .modal-close:hover { background: #e2e8f0; }

        @media (min-width: 960px) {
            body { justify-content: flex-start; }
            .app { max-width: 1024px; margin-left: 280px; }
            .content { max-width: 560px; margin: 48px auto 24px; margin-left: max(0, calc(50vw - 560px)); padding: 0 24px 48px; }
            .drawer { position: fixed; transform: translateX(0); }
            .drawer-backdrop:not(.modal-open) { display: none; }
        }
        .content-wrap { position: relative; min-height: 200px; }
        .page-loader {
            position: absolute;
            inset: 0;
            background: var(--bg-main);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            border-radius: var(--card-radius);
        }
        .page-loader.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .page-loader .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(109, 40, 217, 0.2);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        .page-loader .text {
            margin-top: 16px;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
<div class="app">
    <div class="header">
        <button class="burger" id="drawerToggle" type="button" aria-label="Menu"><span></span><span></span><span></span></button>
        <div class="title">Kelola Presensi</div>
        <a href="{{ route('presensi.kelola', ['tanggal' => $tanggal]) }}" class="btn-refresh" title="Refresh"><i class="fas fa-rotate-right"></i></a>
    </div>

    <div class="content content-wrap">
        <div class="page-loader" id="pageLoader">
            <div class="spinner"></div>
            <span class="text">Memuat data...</span>
        </div>
        @if(session('error'))
            <div class="error"><i class="fas fa-circle-exclamation"></i>{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="success"><i class="fas fa-check-circle"></i>{{ session('success') }}</div>
        @endif
        @if($error)
            <div class="error"><i class="fas fa-circle-exclamation"></i>{{ $error }}</div>
        @endif

        <form method="GET" action="{{ route('presensi.kelola') }}">
            <div class="filter-card">
                <div class="filter-left">
                    <span class="date-label">Tanggal</span>
                    <span class="date-value">{{ $tanggal === $today ? 'Hari ini' : $tanggal }}</span>
                </div>
                <input type="date" name="tanggal" value="{{ $tanggal }}" max="{{ $today }}" style="display:none;" id="tanggalPicker">
                <button type="button" class="btn-date" onclick="document.getElementById('tanggalPicker').showPicker && document.getElementById('tanggalPicker').showPicker();">
                    <i class="fas fa-calendar-days"></i> Pilih tanggal
                </button>
            </div>
        </form>

        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" class="search-input" id="searchInput" placeholder="Cari nama atau NIS" autocomplete="off">
        </div>

        <div class="section-title">Siswa</div>

        <div class="list" id="studentList"></div>
    </div>
</div>

<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal">
        <div class="modal-title" id="modalTitle">Edit Presensi - Sholat 1</div>
        <div class="modal-sub" id="modalSub">-</div>
        <form method="POST" action="{{ route('presensi.kelola.update') }}" id="editForm">
            @csrf
            <input type="hidden" name="id" id="editId">
            <input type="hidden" name="session" id="editSession">
            <input type="hidden" name="status" id="editStatus">
            <input type="hidden" name="tanggal" value="{{ $tanggal }}">
            <div class="modal-options">
                <button type="button" class="modal-opt alpa" data-status="Alpa">Alpa</button>
                <button type="button" class="modal-opt izin" data-status="Izin">Izin</button>
                <button type="button" class="modal-opt sholat" data-status="Sholat">Sholat</button>
                <button type="button" class="modal-opt haid" data-status="Haid">Haid</button>
                <button type="button" class="modal-opt sakit" data-status="Sakit">Sakit</button>
            </div>
            <button type="button" class="modal-close" id="modalClose">Tutup</button>
        </form>
    </div>
</div>

<div class="drawer-backdrop" id="drawerBackdrop"></div>
<aside class="drawer" id="drawer">
    <div class="drawer-header">
        <div class="drawer-logo"><img src="{{ asset('logo.png') }}" alt="Logo"></div>
        <div>
            <div class="drawer-user-name">{{ session('user.username', '-') }}</div>
            <div class="drawer-user-role">User</div>
        </div>
    </div>
    <div class="drawer-divider"></div>
    <div class="drawer-menu-label">Presensi Sholat</div>
    <ul class="drawer-menu">
        <li class="drawer-item"><a href="{{ route('presensi-sholat.qr') }}" class="drawer-link"><span class="icon"><i class="fas fa-qrcode"></i></span><span>Presensi Sholat</span></a></li>
        <li class="drawer-item"><a href="{{ route('presensi-haid.qr') }}" class="drawer-link"><span class="icon"><i class="fas fa-qrcode"></i></span><span>Presensi Haid</span></a></li>
        <li class="drawer-item"><a href="{{ route('presensi.log-marifah') }}" class="drawer-link"><span class="icon"><i class="fas fa-rectangle-list"></i></span><span>Log Marifah</span></a></li>
        <li class="drawer-item"><a href="{{ route('presensi.log-presensi') }}" class="drawer-link"><span class="icon"><i class="fas fa-square-check"></i></span><span>Log Presensi</span></a></li>
        <li class="drawer-item"><a href="{{ route('presensi.kelola') }}" class="drawer-link active"><span class="icon"><i class="fas fa-arrows-rotate"></i></span><span>Kelola Presensi</span></a></li>
        <li class="drawer-item"><a href="{{ route('presensi.rekap-sholat') }}" class="drawer-link"><span class="icon"><i class="fas fa-chart-simple"></i></span><span>Rekap Sholat</span></a></li>
        <li class="drawer-item"><a href="{{ route('presensi.account.ganti-password') }}" class="drawer-link"><span class="icon"><i class="fas fa-gear"></i></span><span>Account Controls</span></a></li>
        <li class="drawer-item">
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="drawer-link" style="width: 100%; text-align: left; border: none; background: transparent; color: inherit; cursor: pointer; font-size: inherit; font-family: inherit; padding: 12px 14px;">
                    <span class="icon"><i class="fas fa-right-from-bracket"></i></span><span>Log Out</span>
                </button>
            </form>
        </li>
    </ul>
    <div class="drawer-footer">App Ver : 1.2.1</div>
</aside>

<script>
(function () {
    var currentTanggal = '{{ $tanggal }}';
    var today = '{{ $today }}';
    var dataUrl = '{{ route("presensi.kelola.data") }}';

    function showLoader() {
        var loader = document.getElementById('pageLoader');
        if (loader) loader.classList.remove('hidden');
    }
    function hideLoader() {
        var loader = document.getElementById('pageLoader');
        if (loader) loader.classList.add('hidden');
    }
    function updateDateDisplay(t) {
        var el = document.querySelector('.date-value');
        if (el) el.textContent = t === today ? 'Hari ini' : t;
    }
    function loadData(tanggal) {
        showLoader();
        fetch(dataUrl + '?tanggal=' + encodeURIComponent(tanggal))
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var list = document.getElementById('studentList');
                if (list) list.innerHTML = html;
                currentTanggal = tanggal;
                updateDateDisplay(tanggal);
                history.replaceState(null, '', '?tanggal=' + encodeURIComponent(tanggal));
            })
            .catch(function () {
                var list = document.getElementById('studentList');
                if (list) list.innerHTML = '<div class="error"><i class="fas fa-circle-exclamation"></i>Gagal memuat data. Silakan refresh halaman.</div>';
            })
            .finally(hideLoader);
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadData(currentTanggal);

        var picker = document.getElementById('tanggalPicker');
        if (picker) {
            picker.addEventListener('change', function () {
                var t = this.value;
                if (t) loadData(t);
            });
        }

        var refreshBtn = document.querySelector('.btn-refresh');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function (e) {
                e.preventDefault();
                loadData(currentTanggal);
            });
            refreshBtn.href = '#';
        }

        var searchInput = document.getElementById('searchInput');
        var searchTimeout;
        function doFilter() {
            var q = searchInput.value.trim().toLowerCase();
            var cards = document.querySelectorAll('.card-student');
            var len = cards.length;
            for (var i = 0; i < len; i++) {
                var card = cards[i];
                var show = q === '' || (card.dataset.search || '').indexOf(q) >= 0;
                card.style.display = show ? '' : 'none';
            }
        }
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(doFilter, 120);
            });
        }

        var modal = document.getElementById('modalBackdrop');
        var modalTitle = document.getElementById('modalTitle');
        var modalSub = document.getElementById('modalSub');
        var editForm = document.getElementById('editForm');
        var editId = document.getElementById('editId');
        var editSession = document.getElementById('editSession');
        var editStatus = document.getElementById('editStatus');
        document.querySelectorAll('.modal-opt[data-status]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                editStatus.value = this.dataset.status;
                editForm.submit();
            });
        });
        document.getElementById('studentList').addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-edit-presensi');
            if (btn) {
                e.preventDefault();
                editId.value = btn.dataset.id || '';
                editSession.value = btn.dataset.session || '';
                modalTitle.textContent = 'Edit Presensi - Sholat ' + (btn.dataset.session || '');
                modalSub.textContent = (btn.dataset.name || '') + (btn.dataset.unit ? ' - ' + btn.dataset.unit : '');
                document.getElementById('editForm').querySelector('input[name="tanggal"]').value = currentTanggal;
                modal.classList.add('open');
            }
        });
        document.getElementById('modalClose').addEventListener('click', function () { modal.classList.remove('open'); });
        modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.remove('open'); });

        var toggleBtn = document.getElementById('drawerToggle');
        var backdrop = document.getElementById('drawerBackdrop');
        var drawer = document.getElementById('drawer');
        function closeDrawer() { drawer.classList.remove('open'); backdrop.classList.remove('open'); }
        toggleBtn.addEventListener('click', function () {
            if (drawer.classList.contains('open')) closeDrawer();
            else { drawer.classList.add('open'); backdrop.classList.add('open'); }
        });
        backdrop.addEventListener('click', closeDrawer);
    });
})();
</script>
</body>
</html>
