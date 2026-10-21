<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pindai Wajah Presensi Otomatis - SMAN 2 Tondano</title>

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

            <!-- Video Kamera Klien (Webcam / Kamera HP) -->
            <video id="webcam-video" 
                   autoplay 
                   playsinline 
                   muted 
                   class="video-feed"
                   style="transform: scaleX(-1);">
            </video>
            <canvas id="capture-canvas" class="d-none"></canvas>

            <!-- Tampilan Saat Kamera Tidak Aktif / Akses Ditolak -->
            <div class="offline-box d-none" id="camera-offline-box">
                <i class="bx bx-camera-off offline-icon"></i>
                <div class="offline-title" id="camera-offline-title">Kamera Belum Aktif</div>
                <div class="offline-desc" id="camera-offline-desc">
                    Pastikan Anda telah memberikan izin akses kamera di peramban (browser) Anda.
                </div>
                <button type="button" class="btn-segarkan" onclick="mulaiKamera()">
                    <i class="bx bx-refresh"></i> Aktifkan Kamera
                </button>
            </div>

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

                <button type="button" id="btn-cam-user" class="btn-kamera-pilihan active" onclick="setKameraFacing('user')">
                    <i class="bx bx-user me-1"></i> Kamera Depan
                </button>

                <button type="button" id="btn-cam-env" class="btn-kamera-pilihan" onclick="setKameraFacing('environment')">
                    <i class="bx bx-camera me-1"></i> Kamera Belakang
                </button>

                <button type="button" class="btn-kamera-pilihan" onclick="toggleKamera()" title="Ganti Kamera">
                    <i class="bx bx-refresh me-1"></i> Putar Kamera
                </button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size: 11px;" id="badge-status-kamera">
                    <i class="bx bx-video me-1"></i> Kamera Aktif
                </span>
                <span class="badge {{ ($pythonStatus['online'] ?? false) ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25' }} py-1 px-2" style="font-size: 11px;" id="badge-status-ai">
                    <i class="bx bx-chip me-1"></i> {{ ($pythonStatus['online'] ?? false) ? 'AI DeepFace Siap' : 'AI Backend Standby' }}
                </span>
            </div>
        </div>

    </div>

    <!-- Script Jam Digital, Kamera HTML5 & Penangkapan Frame Citra ke AI Python -->
    <script>
        // 1. Jam Digital
        setInterval(() => {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WITA';
            const el = document.getElementById('jam-digital');
            if (el) el.textContent = timeStr;
        }, 1000);

        // 2. Elemen DOM
        const videoEl = document.getElementById('webcam-video');
        const canvasEl = document.getElementById('capture-canvas');
        const offlineBox = document.getElementById('camera-offline-box');
        const offlineTitle = document.getElementById('camera-offline-title');
        const offlineDesc = document.getElementById('camera-offline-desc');

        const notifLive = document.getElementById('notif-live');
        const liveIconBox = document.getElementById('live-icon-box');
        const liveTitle = document.getElementById('live-title');
        const liveDesc = document.getElementById('live-desc');

        const notifSukses = document.getElementById('notif-sukses');
        const notifNama = document.getElementById('notif-nama');
        const notifDetail = document.getElementById('notif-detail');
        const notifStatusBadge = document.getElementById('notif-status-badge');
        const notifJam = document.getElementById('notif-jam');
        const notifIconBox = document.getElementById('notif-icon-box');

        const btnCamUser = document.getElementById('btn-cam-user');
        const btnCamEnv = document.getElementById('btn-cam-env');
        const badgeStatusKamera = document.getElementById('badge-status-kamera');

        const prosesPindaiUrl = "{{ route('admin.attendance.proses-pindai') }}";
        const csrfToken = "{{ csrf_token() }}";

        let currentStream = null;
        let currentFacingMode = 'user'; // 'user' (depan) atau 'environment' (belakang)
        let isProcessingFrame = false;
        let isCoolingDown = false;
        let cooldownTimer = null;
        let loopIntervalId = null;
        let lastScannedNisn = '';
        let lastScannedTime = 0;

        // 3. Audio Chime (Web Audio API Synthesizer - Ringan & Tanpa File Eksternal)
        function playChime(isSuccess = true) {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                const ctx = new AudioContext();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                if (isSuccess) {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
                    osc.frequency.setValueAtTime(880, ctx.currentTime + 0.12); // A5
                    gain.gain.setValueAtTime(0.25, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.4);
                } else {
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(320, ctx.currentTime);
                    gain.gain.setValueAtTime(0.2, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.25);
                }
            } catch (e) {
                // Abaikan jika audio tidak didukung
            }
        }

        // 4. Inisialisasi Kamera Klien (WebRTC getUserMedia)
        async function mulaiKamera() {
            if (currentStream) {
                currentStream.getTracks().forEach(track => track.stop());
                currentStream = null;
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                tampilkanErrorKamera("Browser Tidak Mendukung", "Browser Anda tidak mendukung akses kamera HTML5 atau koneksi tidak menggunakan HTTPS.");
                return;
            }

            try {
                offlineBox.classList.add('d-none');
                videoEl.classList.remove('d-none');

                const constraints = {
                    audio: false,
                    video: {
                        facingMode: { ideal: currentFacingMode },
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                };

                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                currentStream = stream;
                videoEl.srcObject = stream;

                // Cermin video jika kamera depan, normal jika kamera belakang
                videoEl.style.transform = (currentFacingMode === 'user') ? 'scaleX(-1)' : 'none';

                videoEl.onloadedmetadata = () => {
                    videoEl.play();
                    perbaruiTombolKamera();
                    mulaiSiklusPemindaian();
                };

                if (badgeStatusKamera) {
                    badgeStatusKamera.innerHTML = '<i class="bx bx-video me-1"></i> Kamera Aktif';
                    badgeStatusKamera.className = 'badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2';
                }
            } catch (err) {
                console.error("Gagal membuka kamera:", err);
                let pesan = "Pastikan izin akses kamera diberikan pada peramban Anda.";
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    pesan = "Akses kamera ditolak oleh pengguna atau browser. Harap izinkan akses kamera di ikon gembok bilah URL.";
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    pesan = "Kamera tidak ditemukan pada perangkat Anda.";
                }
                tampilkanErrorKamera("Izin Kamera Diperlukan", pesan);
            }
        }

        function tampilkanErrorKamera(judul, desc) {
            videoEl.classList.add('d-none');
            offlineBox.classList.remove('d-none');
            if (offlineTitle) offlineTitle.textContent = judul;
            if (offlineDesc) offlineDesc.textContent = desc;
            if (badgeStatusKamera) {
                badgeStatusKamera.innerHTML = '<i class="bx bx-video-off me-1"></i> Kamera Terhenti';
                badgeStatusKamera.className = 'badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2';
            }
        }

        function setKameraFacing(mode) {
            if (currentFacingMode === mode) return;
            currentFacingMode = mode;
            mulaiKamera();
        }

        function toggleKamera() {
            currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
            mulaiKamera();
        }

        function perbaruiTombolKamera() {
            if (btnCamUser) {
                btnCamUser.classList.toggle('active', currentFacingMode === 'user');
            }
            if (btnCamEnv) {
                btnCamEnv.classList.toggle('active', currentFacingMode === 'environment');
            }
        }

        // 5. Siklus Penangkapan Citra & Pengiriman ke Backend Python
        function mulaiSiklusPemindaian() {
            if (loopIntervalId) clearInterval(loopIntervalId);
            loopIntervalId = setInterval(tangkapDanProsesFrame, 800);
        }

        async function tangkapDanProsesFrame() {
            // Jangan memproses jika sedang ada request aktif, sedang jeda sukses, atau video belum siap
            if (isProcessingFrame || isCoolingDown || !videoEl || videoEl.readyState !== 4) {
                return;
            }

            const videoW = videoEl.videoWidth;
            const videoH = videoEl.videoHeight;
            if (!videoW || !videoH) return;

            isProcessingFrame = true;

            try {
                // Skala frame citra optimal untuk pengiriman cepat ke backend (640x480)
                const targetW = 640;
                const targetH = Math.round((videoH / videoW) * targetW);
                canvasEl.width = targetW;
                canvasEl.height = targetH;

                const ctx = canvasEl.getContext('2d');
                ctx.drawImage(videoEl, 0, 0, targetW, targetH);

                // Ekstrak JPEG Base64
                const base64Image = canvasEl.toDataURL('image/jpeg', 0.82);

                // Kirim ke Laravel -> diteruskan ke Python DeepFace
                const response = await fetch(prosesPindaiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        gambar: base64Image
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    tanganiResponPresensi(data);
                } else {
                    if (response.status === 502 || response.status === 503) {
                        perbaruiNotifLive('state-peringatan', '<i class="bx bx-chip bx-spin"></i>', 'Server AI Sedang Memuat', 'Menghubungkan ke layanan FaceNet di server...');
                    }
                }
            } catch (err) {
                // Abaikan kesalahan koneksi sementara
            } finally {
                isProcessingFrame = false;
            }
        }

        // 6. Tangani Respon Backend
        function tanganiResponPresensi(data) {
            if (isCoolingDown) return;

            const st = data.status;

            if (st === 'sukses' || st === 'sudah_absen') {
                const siswa = data.siswa || {};
                const nowSec = Date.now() / 1000;

                // Hindari duplikasi rekaman berulang untuk siswa yang sama berturut-turut dalam 10 detik
                if (siswa.nisn === lastScannedNisn && (nowSec - lastScannedTime) < 10) {
                    return;
                }

                lastScannedNisn = siswa.nisn;
                lastScannedTime = nowSec;

                playChime(true);
                tampilkanDetailSukses(siswa, st, data.pesan);
            } else if (st === 'tidak_ada_wajah') {
                perbaruiNotifLive('state-standby', '<i class="bx bx-scan"></i>', 'Arahkan Wajah ke Kamera', 'Posisikan wajah Anda di dalam frame untuk presensi otomatis');
            } else if (st === 'tidak_dikenali') {
                perbaruiNotifLive('state-tidak-dikenali', '<i class="bx bx-user-x"></i>', 'Wajah Belum Terdaftar', data.pesan || 'Wajah tidak cocok dengan data siswa terdaftar');
            } else if (st === 'gagal_akurasi') {
                perbaruiNotifLive('state-gagal-akurasi', '<i class="bx bx-error-circle"></i>', 'Akurasi Wajah Kurang', data.pesan || 'Posisikan wajah lebih jelas tegak lurus ke kamera');
            }
        }

        function perbaruiNotifLive(stateClass, iconHtml, title, desc) {
            if (!notifLive || isCoolingDown) return;
            notifLive.className = 'notif-live ' + stateClass;
            if (liveIconBox) liveIconBox.innerHTML = iconHtml;
            if (liveTitle) liveTitle.textContent = title;
            if (liveDesc) liveDesc.textContent = desc;

            notifLive.classList.remove('d-none');
            if (notifSukses) notifSukses.classList.add('d-none');
        }

        function tampilkanDetailSukses(siswa, statusKode, pesanCustom) {
            if (cooldownTimer) clearTimeout(cooldownTimer);
            isCoolingDown = true;

            if (notifNama) notifNama.textContent = siswa.nama || 'Siswa';
            if (notifDetail) notifDetail.textContent = `${siswa.kelas || '-'} • NISN: ${siswa.nisn || '-'}`;

            const rawStatus = (siswa.status || 'HADIR').toUpperCase();
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
            } else if (rawStatus.includes('SUDAH') || statusKode === 'sudah_absen') {
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

            if (notifIconBox) {
                notifIconBox.innerHTML = iconHtml;
                notifIconBox.style.background = iconBg;
                notifIconBox.style.color = iconColor;
            }

            if (notifJam) {
                const jamStr = siswa.jam ? siswa.jam.substring(0, 5) : '';
                notifJam.textContent = (jamStr || new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })) + ' WITA';
            }

            if (notifLive) notifLive.classList.add('d-none');
            if (notifSukses) notifSukses.classList.remove('d-none');

            // Tampilkan kartu sukses selama 4.5 detik untuk siswa, lalu kembali ke standby
            cooldownTimer = setTimeout(() => {
                isCoolingDown = false;
                if (notifSukses) notifSukses.classList.add('d-none');
                if (notifLive) notifLive.classList.remove('d-none');
                perbaruiNotifLive('state-standby', '<i class="bx bx-scan"></i>', 'Arahkan Wajah ke Kamera', 'Posisikan wajah Anda di dalam frame untuk presensi otomatis');
            }, 4500);
        }

        // Jalankan inisialisasi kamera saat halaman dimuat
        document.addEventListener('DOMContentLoaded', () => {
            mulaiKamera();
        });
    </script>
</body>
</html>
