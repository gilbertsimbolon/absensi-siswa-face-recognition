<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pindai Wajah Presensi Otomatis - SMAN 2 Tondano</title>

    @if(! ($pythonStatus['online'] ?? false))
        <!-- Muat ulang otomatis saat kamera sedang disiapkan -->
        <meta http-equiv="refresh" content="5">
    @endif

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background-color: #ffffff;
            color: #1e293b;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Pembungkus Halaman */
        .scanner-page {
            width: 100%;
            max-width: 920px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* Bar Navigasi Atas */
        .scanner-nav {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px 0 10px 0;
            min-height: 60px;
        }

        .btn-kembali {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-kembali:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .sekolah-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .sekolah-logo {
            height: 48px;
            width: auto;
            margin-bottom: 4px;
        }

        .sekolah-nama {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            line-height: 1.2;
        }

        .sekolah-sub {
            font-size: 0.78rem;
            color: #64748b;
            margin: 2px 0 0 0;
        }

        .jam-badge {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #0284c7;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* 1 Frame Kamera Presensi */
        .camera-container {
            width: 100%;
            position: relative;
            background: #000000;
            border: 3.5px solid #0284c7;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            aspect-ratio: 16 / 10;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-height: 750px) {
            .camera-container {
                max-height: 75vh;
            }
        }

        /* Video Feed MJPEG */
        .video-feed {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            background: #000;
        }

        /* Notifikasi Status Absensi di Dalam Frame Kamera */
        .notification-overlay {
            position: absolute;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 40;
            width: 90%;
            max-width: 480px;
            pointer-events: none;
        }

        /* Kondisi Standby */
        .notif-standby {
            background: rgba(255, 255, 255, 0.95);
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .titik-biru {
            width: 8px;
            height: 8px;
            background-color: #0284c7;
            border-radius: 50%;
        }

        /* Kondisi Sukses Absen */
        .notif-sukses {
            background: rgba(255, 255, 255, 0.98);
            border-left: 5px solid #16a34a;
            border-radius: 12px;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            text-align: left;
        }

        .notif-sukses .icon-box {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dcfce7;
            color: #16a34a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .notif-warning {
            background: rgba(255, 255, 255, 0.98);
            border-left: 5px solid #ca8a04;
            border-radius: 12px;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            text-align: left;
        }

        .notif-warning .icon-box {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fef9c3;
            color: #ca8a04;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .notif-konten {
            flex-grow: 1;
            min-width: 0;
        }

        .notif-nama-siswa {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .notif-keterangan {
            font-size: 0.8rem;
            color: #64748b;
            margin: 2px 0 0 0;
        }

        .notif-badge-label {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
            text-align: center;
        }

        .badge-hadir {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-terlambat {
            background: #fef9c3;
            color: #854d0e;
        }

        .badge-alpa {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-tidak-dikenal {
            background: #fee2e2;
            color: #991b1b;
        }

        .notif-badge-akurasi {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            flex-shrink: 0;
            text-align: center;
        }

        /* Tampilan Layar Offline */
        .offline-box {
            position: absolute;
            inset: 0;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
            z-index: 25;
        }

        .offline-icon {
            font-size: 3rem;
            color: #0284c7;
            margin-bottom: 0.5rem;
        }

        .offline-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .offline-desc {
            font-size: 0.85rem;
            color: #64748b;
            max-width: 400px;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .btn-segarkan {
            background: #0284c7;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-segarkan:hover {
            background: #0369a1;
            color: #ffffff;
        }

        /* Pilihan Kamera di Bawah Frame */
        .camera-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2px 4px;
        }

        .camera-options {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .camera-options-label {
            font-size: 0.82rem;
            color: #64748b;
            font-weight: 600;
        }

        .btn-kamera-pilihan {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #475569;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-kamera-pilihan:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .btn-kamera-pilihan.active {
            background: #0284c7;
            border-color: #0284c7;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <!-- Penanda aksesibilitas & verifikasi -->
    <span class="visually-hidden">Pindai Wajah Presensi Otomatis</span>

    <div class="scanner-page">

        <!-- Bar Navigasi Atas -->
        <div class="scanner-nav">
            <a href="{{ route('admin.dashboard.index') }}" class="btn-kembali">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>

            <div class="sekolah-info">
                <img src="{{ asset('img/logo.png') }}" alt="Logo Sekolah" class="sekolah-logo">
                <h1 class="sekolah-nama">SMAN 2 Tondano</h1>
                <p class="sekolah-sub">Presensi Wajah Siswa</p>
            </div>

            <div class="jam-badge" id="jam-digital">
                {{ now()->format('H:i') }} WITA
            </div>
        </div>

        <!-- 1 Frame Kamera Presensi -->
        <div class="camera-container">

            @if($pythonStatus['online'] ?? false)
                <!-- Tampilan Kamera -->
                <img id="opencv-feed" 
                     src="{{ $streamUrl ?? '/scanner/video_feed' }}" 
                     alt="Kamera Presensi Siswa" 
                     class="video-feed">
            @else
                <!-- Tampilan Saat Kamera Tidak Aktif -->
                <div class="offline-box">
                    <i class="bx bx-camera-off offline-icon"></i>
                    <div class="offline-title">Kamera Sedang Tidak Aktif</div>
                    <div class="offline-desc">
                        Layanan kamera belum dijalankan di komputer sekolah.
                    </div>
                    <a href="{{ url()->current() }}" class="btn-segarkan">
                        <i class="bx bx-refresh"></i> Segarkan Halaman
                    </a>
                </div>
            @endif

            <!-- Kartu Detail Informasi Presensi Siswa di Bawah Frame Kamera -->
            <div class="notification-overlay text-center" id="notif-container">
                <!-- Kondisi Standby (Tenang, tanpa animasi kedip) -->
                <div class="notif-standby" id="notif-standby">
                    <i class="bx bx-scan text-primary fs-5 me-1"></i>
                    <span class="fw-semibold">Arahkan wajah ke kamera untuk presensi</span>
                </div>

                <!-- Kondisi Sukses: Detail Lengkap Siswa -->
                <div class="notif-sukses d-none" id="notif-sukses">
                    <div class="icon-box">
                        <i class="bx bx-check"></i>
                    </div>
                    <div class="notif-konten text-start">
                        <h3 class="notif-nama-siswa" id="notif-nama">Nama Siswa</h3>
                        <p class="notif-keterangan" id="notif-detail">Kelas • NISN: 1234567890</p>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <div class="d-flex align-items-center gap-1 justify-content-end mb-1">
                            <span class="notif-badge-label badge-hadir" id="notif-status-badge">HADIR</span>
                            <span class="notif-badge-akurasi" id="notif-akurasi-badge">Akurasi: 95%</span>
                        </div>
                        <small class="text-muted d-block" style="font-size: 11px;" id="notif-jam">07:15 WITA</small>
                    </div>
                </div>
            </div>

        </div>

        <!-- Pilihan Kamera di Bawah Frame -->
        <div class="camera-footer">
            <div class="camera-options">
                <span class="camera-options-label"><i class="bx bx-camera me-1"></i> Pilihan Kamera:</span>

                <form method="POST" action="{{ route('admin.attendance.pindai.kamera') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="sumber" value="0">
                    <button type="submit" class="btn-kamera-pilihan {{ ($pythonStatus['camera_source'] ?? 0) == 0 ? 'active' : '' }}">
                        Laptop
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.attendance.pindai.kamera') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="sumber" value="1">
                    <button type="submit" class="btn-kamera-pilihan {{ ($pythonStatus['camera_source'] ?? 0) == 1 ? 'active' : '' }}">
                        iPhone USB
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.attendance.pindai.kamera') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="sumber" value="2">
                    <button type="submit" class="btn-kamera-pilihan {{ ($pythonStatus['camera_source'] ?? 0) == 2 ? 'active' : '' }}">
                        Kamera Tambahan (USB)
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- Script Jam Digital & Pembaruan Status Detail Presensi Real-Time -->
    <script>
        // Jam Digital
        setInterval(() => {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WITA';
            const el = document.getElementById('jam-digital');
            if (el) el.textContent = timeStr;
        }, 1000);

        const statusPollUrl = "{{ $statusPollUrl ?? 'http://127.0.0.1:5000/status-scanner' }}";
        let lastResultKey = '';
        let hideNotifTimer = null;

        const notifStandby = document.getElementById('notif-standby');
        const notifSukses = document.getElementById('notif-sukses');
        const notifNama = document.getElementById('notif-nama');
        const notifDetail = document.getElementById('notif-detail');
        const notifStatusBadge = document.getElementById('notif-status-badge');
        const notifAkurasiBadge = document.getElementById('notif-akurasi-badge');
        const notifJam = document.getElementById('notif-jam');

        async function periksaStatusAbsensi() {
            try {
                const res = await fetch(statusPollUrl);
                if (!res.ok) return;
                const data = await res.json();
                const h = data.latest_result || data.hasil_terakhir;
                if (h && h.status === 'sukses') {
                    const key = `${h.timestamp || ''}_${h.nisn || ''}`;
                    if (key !== lastResultKey) {
                        lastResultKey = key;
                        tampilkanDetailPresensi(h);
                    }
                }
            } catch (err) {}
        }

        setInterval(periksaStatusAbsensi, 400);

        function tampilkanDetailPresensi(h) {
            if (hideNotifTimer) clearTimeout(hideNotifTimer);

            // Format akurasi dengan format (00.00%)
            let akurasiRaw = h.akurasi_num;
            if (akurasiRaw === undefined || akurasiRaw === null) {
                akurasiRaw = parseFloat(String(h.akurasi || '0').replace(/[^0-9.]/g, '')) || 0;
            }
            const akurasiFormatted = `(${Number(akurasiRaw).toFixed(2)}%)`;

            if (notifNama) {
                const namaSiswa = h.nama || 'Siswa';
                notifNama.innerHTML = `${namaSiswa} <span class="ms-1 text-primary fw-semibold" style="font-size: 0.9rem;">${akurasiFormatted}</span>`;
            }
            if (notifDetail) notifDetail.textContent = `${h.kelas || '-'} • NISN: ${h.nisn || '-'}`;

            const statusLabel = h.status_absen || 'HADIR';
            if (notifStatusBadge) {
                notifStatusBadge.textContent = statusLabel;
                let badgeClass = 'badge-hadir';
                const lower = statusLabel.toLowerCase();
                if (lower.includes('terlambat')) {
                    badgeClass = 'badge-terlambat';
                } else if (lower.includes('alpa')) {
                    badgeClass = 'badge-alpa';
                }
                notifStatusBadge.className = 'notif-badge-label ' + badgeClass;
            }

            if (notifAkurasiBadge) {
                notifAkurasiBadge.textContent = 'Akurasi: ' + akurasiFormatted;
            }

            if (notifJam) notifJam.textContent = (h.timestamp || '') + ' WITA';

            if (notifStandby) notifStandby.classList.add('d-none');
            if (notifSukses) notifSukses.classList.remove('d-none');

            // Tampilkan detail siswa selama 6 detik stabil tanpa kedip
            hideNotifTimer = setTimeout(() => {
                if (notifSukses) notifSukses.classList.add('d-none');
                if (notifStandby) notifStandby.classList.remove('d-none');
            }, 6000);
        }
    </script>
</body>
</html>
