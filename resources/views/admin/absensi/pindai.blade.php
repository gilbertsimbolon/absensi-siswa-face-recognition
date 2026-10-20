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
            width: 92%;
            max-width: 520px;
            pointer-events: none;
        }

        /* Notifikasi Status Pemindaian Dinamis (Live Real-Time Alert) */
        .notif-live {
            padding: 12px 20px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            gap: 14px;
            width: 100%;
            text-align: left;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(8px);
        }

        .notif-live .icon-box {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .notif-live .live-title {
            font-size: 0.92rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.3;
        }

        .notif-live .live-desc {
            font-size: 0.78rem;
            margin: 2px 0 0 0;
            opacity: 0.95;
            line-height: 1.35;
        }

        /* 1. Standby: Dark Glassmorphic Modern (Bukan White Alert) */
        .notif-live.state-standby {
            background: rgba(15, 23, 42, 0.90);
            border: 1.5px solid rgba(56, 189, 248, 0.4);
            color: #f8fafc;
        }
        .notif-live.state-standby .icon-box {
            background: rgba(56, 189, 248, 0.18);
            color: #38bdf8;
        }

        /* 2. Memproses / Mendeteksi: Sky Blue Glow */
        .notif-live.state-memproses {
            background: rgba(2, 132, 199, 0.96);
            border: 1.5px solid #38bdf8;
            color: #ffffff;
            box-shadow: 0 10px 30px rgba(2, 132, 199, 0.5);
        }
        .notif-live.state-memproses .icon-box {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
        }

        /* 3. Memproses Lama / Petunjuk: Royal Indigo Alert */
        .notif-live.state-memproses-lama {
            background: rgba(30, 58, 138, 0.96);
            border: 1.5px solid #93c5fd;
            color: #ffffff;
            box-shadow: 0 10px 30px rgba(30, 58, 138, 0.5);
        }
        .notif-live.state-memproses-lama .icon-box {
            background: rgba(255, 255, 255, 0.22);
            color: #fde047;
        }

        /* 4. Peringatan / Akurasi Kurang: Amber Glow Alert */
        .notif-live.state-gagal-akurasi {
            background: rgba(217, 119, 6, 0.96);
            border: 1.5px solid #fde047;
            color: #ffffff;
            box-shadow: 0 10px 30px rgba(217, 119, 6, 0.5);
        }
        .notif-live.state-gagal-akurasi .icon-box {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
        }

        /* 5. Tidak Dikenali / Belum Terdaftar: Crimson Alert */
        .notif-live.state-tidak-dikenali {
            background: rgba(185, 28, 28, 0.96);
            border: 1.5px solid #fca5a5;
            color: #ffffff;
            box-shadow: 0 10px 30px rgba(185, 28, 28, 0.5);
        }
        .notif-live.state-tidak-dikenali .icon-box {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
        }

        /* 6. Peringatan Posisi / Jarak: Warning Gold */
        .notif-live.state-peringatan {
            background: rgba(180, 83, 9, 0.96);
            border: 1.5px solid #fde047;
            color: #ffffff;
            box-shadow: 0 10px 30px rgba(180, 83, 9, 0.5);
        }
        .notif-live.state-peringatan .icon-box {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
        }

        /* Kondisi Sukses Absen: Tampilan Solid & Elegan */
        .notif-sukses {
            background: #ffffff;
            border-left: 6px solid #16a34a;
            border-radius: 14px;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
            text-align: left;
            width: 100%;
        }

        .notif-sukses .icon-box {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #dcfce7;
            color: #16a34a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
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
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
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

        .badge-sudah-absen {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-diluar-waktu {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-alpa {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-tidak-dikenal {
            background: #fee2e2;
            color: #991b1b;
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
                <!-- Tampilan Kamera OpenCV Python Native -->
                <img id="opencv-feed" 
                     src="{{ $streamUrl ?? '/scanner/video_feed' }}" 
                     alt="Kamera Presensi Siswa" 
                     class="video-feed">

                <!-- Video Kamera Klien (Fallback tersembunyi) -->
                <video id="webcam-video" 
                       autoplay 
                       playsinline 
                       muted 
                       class="video-feed d-none"
                       style="transform: scaleX(-1);">
                </video>
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
                <!-- Alert Dinamis Real-Time (Aktif memandu siswa, memberi alasan jelas bila proses lama/gagal) -->
                <div class="notif-live state-standby" id="notif-live">
                    <div class="icon-box" id="live-icon-box">
                        <i class="bx bx-scan"></i>
                    </div>
                    <div class="notif-konten text-start">
                        <h4 class="live-title" id="live-title">Arahkan Wajah ke Kamera</h4>
                        <p class="live-desc" id="live-desc">Posisikan wajah Anda di dalam frame untuk presensi otomatis</p>
                    </div>
                </div>

                <!-- Kondisi Sukses / Status Absensi: Detail Lengkap Siswa -->
                <div class="notif-sukses d-none" id="notif-sukses">
                    <div class="icon-box" id="notif-icon-box">
                        <i class="bx bx-check"></i>
                    </div>
                    <div class="notif-konten text-start">
                        <h3 class="notif-nama-siswa" id="notif-nama">Nama Siswa</h3>
                        <p class="notif-keterangan" id="notif-detail">Kelas • NISN: 1234567890</p>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <div class="d-flex align-items-center gap-1 justify-content-end mb-1">
                            <span class="notif-badge-label badge-hadir" id="notif-status-badge">HADIR</span>
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
                    <button type="submit" class="btn-kamera-pilihan {{ ($pythonStatus['camera_source'] ?? 0) === 0 || ($pythonStatus['camera_source'] ?? 0) === '0' ? 'active' : '' }}">
                        <i class="bx bx-video me-1"></i> Kamera 1 (Internal)
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.attendance.pindai.kamera') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="sumber" value="1">
                    <button type="submit" class="btn-kamera-pilihan {{ ($pythonStatus['camera_source'] ?? null) === 1 || ($pythonStatus['camera_source'] ?? null) === '1' ? 'active' : '' }}">
                        <i class="bx bx-video-recording me-1"></i> Kamera 2 (Eksternal)
                    </button>
                </form>

                <button type="button" 
                        class="btn-kamera-pilihan {{ !is_numeric($pythonStatus['camera_source'] ?? 0) && !empty($pythonStatus['camera_source']) ? 'active' : '' }}" 
                        style="background: #e0f2fe; color: #0369a1; border-color: #bae6fd;"
                        onclick="dialogAturIpKamera()"
                        title="Atur URL Streaming IP Kamera / CCTV (RTSP atau HTTP)">
                    <i class="bx bx-cctv me-1"></i> IP Kamera / CCTV
                </button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ ($pythonStatus['online'] ?? false) ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' }} py-1 px-2" style="font-size: 11px;">
                    <i class="bx {{ ($pythonStatus['online'] ?? false) ? 'bx-broadcast' : 'bx-x-circle' }} me-1"></i>
                    {{ ($pythonStatus['online'] ?? false) ? 'Python OpenCV Live' : 'Server Offline' }}
                </span>
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
        let lastScanId = -1;
        let hideNotifTimer = null;
        let isShowingSuccess = false;

        const notifLive = document.getElementById('notif-live');
        const liveIconBox = document.getElementById('live-icon-box');
        const liveTitle = document.getElementById('live-title');
        const liveDesc = document.getElementById('live-desc');

        const notifSukses = document.getElementById('notif-sukses');
        const notifNama = document.getElementById('notif-nama');
        const notifDetail = document.getElementById('notif-detail');
        const notifStatusBadge = document.getElementById('notif-status-badge');
        const notifJam = document.getElementById('notif-jam');

        let isPolling = false;

        async function periksaStatusAbsensi() {
            if (isPolling) return;
            isPolling = true;

            try {
                const res = await fetch(statusPollUrl, { cache: 'no-store' });
                if (res.ok) {
                    const data = await res.json();
                    const h = data.latest_result || data.hasil_terakhir;

                    // 1. Cek apakah ada hasil presensi siswa yang sukses / tercatat
                    if (h && (h.status === 'sukses' || h.status === 'sudah_absen' || h.status === 'diluar_waktu' || h.status === 'alpa')) {
                        const scanId = h.scan_id !== undefined ? h.scan_id : 0;
                        const key = `${scanId}_${h.timestamp || ''}_${h.nisn || ''}_${h.status_absen || ''}`;
                        if (key !== lastResultKey) {
                            lastResultKey = key;
                            lastScanId = scanId;
                            tampilkanDetailPresensi(h);
                        }
                    }

                    // 2. Jika tidak sedang menampilkan kartu sukses, perbarui status alert pemindaian secara real-time
                    if (!isShowingSuccess) {
                        perbaruiStatusLive(data);
                    }
                }
            } catch (err) {
                // Abaikan kesalahan koneksi sesaat
            } finally {
                isPolling = false;
                setTimeout(periksaStatusAbsensi, 250);
            }
        }

        // Jalankan polling loop pertama
        periksaStatusAbsensi();

        function perbaruiStatusLive(data) {
            if (!notifLive || isShowingSuccess) return;

            const st = data.status_scanner || 'standby';
            const pesan = data.pesan_scanner || 'Arahkan Wajah ke Kamera';
            const detail = data.detail_scanner || 'Posisikan wajah Anda di dalam frame untuk presensi otomatis';

            let stateClass = 'state-standby';
            let iconHtml = '<i class="bx bx-scan"></i>';

            if (st === 'memproses') {
                stateClass = 'state-memproses';
                iconHtml = '<i class="bx bx-loader-alt bx-spin"></i>';
            } else if (st === 'memproses_lama') {
                stateClass = 'state-memproses-lama';
                iconHtml = '<i class="bx bx-time-five bx-tada"></i>';
            } else if (st === 'gagal_akurasi') {
                stateClass = 'state-gagal-akurasi';
                iconHtml = '<i class="bx bx-error-circle bx-burst"></i>';
            } else if (st === 'tidak_dikenali') {
                stateClass = 'state-tidak-dikenali';
                iconHtml = '<i class="bx bx-user-x"></i>';
            } else if (st === 'peringatan') {
                stateClass = 'state-peringatan';
                iconHtml = '<i class="bx bx-zoom-in"></i>';
            } else if (st === 'sukses') {
                // Biarkan kartu sukses yang menanganinya
                return;
            }

            notifLive.className = 'notif-live ' + stateClass;
            if (liveIconBox) liveIconBox.innerHTML = iconHtml;
            if (liveTitle) liveTitle.textContent = pesan;
            if (liveDesc) liveDesc.textContent = detail;

            notifLive.classList.remove('d-none');
            if (notifSukses) notifSukses.classList.add('d-none');
        }

        function tampilkanDetailPresensi(h) {
            if (hideNotifTimer) clearTimeout(hideNotifTimer);
            isShowingSuccess = true;

            if (notifNama) {
                notifNama.textContent = h.nama || 'Siswa';
            }
            if (notifDetail) {
                notifDetail.textContent = `${h.kelas || '-'} • NISN: ${h.nisn || '-'}`;
            }

            const rawStatus = (h.status_absen || 'HADIR').toUpperCase();
            let statusLabel = rawStatus;
            let badgeClass = 'badge-hadir';
            let borderColor = '#16a34a';
            let iconHtml = '<i class="bx bx-check"></i>';
            let iconBg = '#dcfce7';
            let iconColor = '#16a34a';

            if (rawStatus.includes('TERLAMBAT')) {
                statusLabel = 'TERLAMBAT';
                badgeClass = 'badge-terlambat';
                borderColor = '#ca8a04';
                iconHtml = '<i class="bx bx-time"></i>';
                iconBg = '#fef9c3';
                iconColor = '#ca8a04';
            } else if (rawStatus.includes('SUDAH')) {
                statusLabel = 'SUDAH ABSEN';
                badgeClass = 'badge-sudah-absen';
                borderColor = '#0284c7';
                iconHtml = '<i class="bx bx-check-double"></i>';
                iconBg = '#e0f2fe';
                iconColor = '#0284c7';
            } else if (rawStatus.includes('DILUAR') || rawStatus.includes('ALPA')) {
                statusLabel = 'ALPA';
                badgeClass = 'badge-alpa';
                borderColor = '#dc2626';
                iconHtml = '<i class="bx bx-x-circle"></i>';
                iconBg = '#fee2e2';
                iconColor = '#dc2626';
            }

            if (notifStatusBadge) {
                notifStatusBadge.textContent = statusLabel;
                notifStatusBadge.className = 'notif-badge-label ' + badgeClass;
            }

            if (notifSukses) {
                notifSukses.style.borderLeftColor = borderColor;
            }

            const iconBox = document.getElementById('notif-icon-box');
            if (iconBox) {
                iconBox.innerHTML = iconHtml;
                iconBox.style.background = iconBg;
                iconBox.style.color = iconColor;
            }

            if (notifJam) {
                notifJam.textContent = (h.timestamp || '') + ' WITA';
            }

            if (notifLive) notifLive.classList.add('d-none');
            if (notifSukses) notifSukses.classList.remove('d-none');

            // Tampilkan detail siswa selama 5 detik, lalu kembali ke status live scanner
            hideNotifTimer = setTimeout(() => {
                isShowingSuccess = false;
                if (notifSukses) notifSukses.classList.add('d-none');
                if (notifLive) notifLive.classList.remove('d-none');
            }, 5000);
        }

        // Dialog Konfigurasi URL IP Camera / CCTV RTSP
        function dialogAturIpKamera() {
            const urlSekarang = prompt(
                "Masukkan URL Streaming IP Camera / CCTV:\n\nContoh Aplikasi HP 'IP Webcam':\nhttp://192.168.1.15:8080/video\n\nContoh RTSP CCTV (Hikvision/Dahua/Tapo):\nrtsp://admin:password@192.168.1.100:554/stream1\n\nAtau masukkan '0' untuk kembali ke Kamera Internal Laptop:",
                "{{ is_string($pythonStatus['camera_source'] ?? null) ? $pythonStatus['camera_source'] : 'http://192.168.1.' }}"
            );

            if (!urlSekarang || urlSekarang.trim() === '') return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('admin.attendance.pindai.kamera') }}";

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = "{{ csrf_token() }}";
            form.appendChild(csrfInput);

            const sumberInput = document.createElement('input');
            sumberInput.type = 'hidden';
            sumberInput.name = 'sumber';
            sumberInput.value = urlSekarang.trim();
            form.appendChild(sumberInput);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
