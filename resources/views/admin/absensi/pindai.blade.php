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

        .badge-pulang {
            background: #e0e7ff;
            color: #4338ca;
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
        <div class="camera-container" id="camera-container">

            <!-- Video Kamera Klien (Webcam Laptop / Kamera HP Skenario 2) -->
            <video id="webcam-video" 
                   autoplay 
                   playsinline 
                   muted 
                   class="video-feed"
                   style="transform: scaleX(-1);">
            </video>

            <!-- Canvas Tersembunyi untuk Snapshot Frame AI -->
            <canvas id="capture-canvas" class="d-none"></canvas>

            <!-- Fallback Stream OpenCV Server (jika perangkat tidak mendukung kamera langsung) -->
            <img id="opencv-feed" 
                 src="{{ $streamUrl ?? '/scanner/video_feed' }}" 
                 alt="Kamera Presensi Siswa" 
                 class="video-feed d-none">

            <!-- Tampilan Saat Kamera Tidak Aktif / Belum Diizinkan -->
            <div class="offline-box d-none" id="camera-offline-box">
                <i class="bx bx-camera-off offline-icon"></i>
                <div class="offline-title" id="offline-title">Kamera Sedang Tidak Aktif</div>
                <div class="offline-desc" id="offline-desc">
                    Silakan izinkan akses kamera pada browser perangkat Anda untuk mulai memindai.
                </div>
                <button type="button" class="btn-segarkan" onclick="mulaiKameraBrowser()">
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

                <button type="button" 
                        class="btn-kamera-pilihan active" 
                        id="btn-kamera-depan" 
                        onclick="gantiModeKamera('user')">
                    Kamera Depan
                </button>
                <button type="button" 
                        class="btn-kamera-pilihan" 
                        id="btn-kamera-belakang" 
                        onclick="gantiModeKamera('environment')">
                    Kamera Belakang
                </button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size: 11px;" id="kamera-status-badge">
                    <i class="bx bx-broadcast me-1"></i> Kamera HP Aktif
                </span>
            </div>
        </div>

    </div>

    <!-- Script Jam Digital & Pembaruan Status Detail Presensi Real-Time -->
    <script>
        // 1. Jam Digital
        setInterval(() => {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WITA';
            const el = document.getElementById('jam-digital');
            if (el) el.textContent = timeStr;
        }, 1000);

        const prosesPindaiUrl = "{{ route('admin.attendance.proses-pindai') }}";

        let currentFacingMode = 'user'; // 'user' (depan/selfie) atau 'environment' (belakang)
        let localStream = null;
        let isProcessing = false;
        let isShowingSuccess = false;
        let lastScanNisn = '';
        let lastScanTime = 0;
        let hideNotifTimer = null;
        let scanLoopTimer = null;

        const webcamVideo = document.getElementById('webcam-video');
        const captureCanvas = document.getElementById('capture-canvas');
        const cameraOfflineBox = document.getElementById('camera-offline-box');
        const offlineTitle = document.getElementById('offline-title');
        const offlineDesc = document.getElementById('offline-desc');

        const notifLive = document.getElementById('notif-live');
        const liveIconBox = document.getElementById('live-icon-box');
        const liveTitle = document.getElementById('live-title');
        const liveDesc = document.getElementById('live-desc');

        const notifSukses = document.getElementById('notif-sukses');
        const notifNama = document.getElementById('notif-nama');
        const notifDetail = document.getElementById('notif-detail');
        const notifStatusBadge = document.getElementById('notif-status-badge');
        const notifJam = document.getElementById('notif-jam');

        const btnKameraDepan = document.getElementById('btn-kamera-depan');
        const btnKameraBelakang = document.getElementById('btn-kamera-belakang');
        const kameraStatusBadge = document.getElementById('kamera-status-badge');

        // 2. Inisialisasi Kamera Browser (HTML5 WebRTC)
        async function mulaiKameraBrowser() {
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
                localStream = null;
            }

            // Cek dukungan navigator.mediaDevices
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                tampilkanErrorKamera(
                    'Peramban Tidak Mendukung Kamera',
                    'Browser Anda tidak mengizinkan akses kamera langsung. Silakan gunakan Chrome/Safari terbaru dan pastikan menggunakan protokol HTTPS.'
                );
                return;
            }

            // Cek keamanan protokol (HTTPS / localhost)
            const isSecure = window.isSecureContext || ['localhost', '127.0.0.1'].includes(window.location.hostname);
            if (!isSecure) {
                tampilkanErrorKamera(
                    'Koneksi Aman (HTTPS) Diperlukan',
                    'Browser HP memblokir kamera jika website belum menggunakan HTTPS. Akses halaman ini melalui domain ber-SSL (HTTPS) di VPS Anda.'
                );
                return;
            }

            const constraints = {
                audio: false,
                video: {
                    facingMode: { ideal: currentFacingMode },
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }
            };

            try {
                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                localStream = stream;
                webcamVideo.srcObject = stream;
                webcamVideo.classList.remove('d-none');
                if (cameraOfflineBox) cameraOfflineBox.classList.add('d-none');

                // Mirror kamera depan agar natural seperti cermin di HP
                if (currentFacingMode === 'user') {
                    webcamVideo.style.transform = 'scaleX(-1)';
                } else {
                    webcamVideo.style.transform = 'none';
                }

                if (kameraStatusBadge) {
                    kameraStatusBadge.innerHTML = '<i class="bx bx-broadcast me-1"></i> Kamera HP Aktif';
                    kameraStatusBadge.className = 'badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2';
                }

                // Jalankan loop pemindaian frame
                mulaiLoopPemindaian();

            } catch (err) {
                console.error('Gagal mengakses kamera:', err);
                let judul = 'Kamera Tidak Dapat Diakses';
                let pesan = 'Pastikan izin kamera sudah diberikan di pengaturan browser HP Anda.';

                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    judul = 'Izin Kamera Ditolak';
                    pesan = 'Ketuk ikon gembok di sebelah alamat web browser Anda, lalu izinkan akses Kamera.';
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    judul = 'Kamera Tidak Ditemukan';
                    pesan = 'Tidak ditemukan perangkat kamera yang dapat digunakan pada perangkat ini.';
                }

                tampilkanErrorKamera(judul, pesan);
            }
        }

        function tampilkanErrorKamera(judul, pesan) {
            if (webcamVideo) webcamVideo.classList.add('d-none');
            if (cameraOfflineBox) {
                cameraOfflineBox.classList.remove('d-none');
                if (offlineTitle) offlineTitle.textContent = judul;
                if (offlineDesc) offlineDesc.textContent = pesan;
            }
            if (kameraStatusBadge) {
                kameraStatusBadge.innerHTML = '<i class="bx bx-x-circle me-1"></i> Kamera Offline';
                kameraStatusBadge.className = 'badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2';
            }
        }

        // 3. Ganti Mode Kamera Depan / Belakang
        function gantiModeKamera(mode) {
            if (currentFacingMode === mode) return;
            currentFacingMode = mode;

            if (mode === 'user') {
                if (btnKameraDepan) btnKameraDepan.classList.add('active');
                if (btnKameraBelakang) btnKameraBelakang.classList.remove('active');
            } else {
                if (btnKameraBelakang) btnKameraBelakang.classList.add('active');
                if (btnKameraDepan) btnKameraDepan.classList.remove('active');
            }

            mulaiKameraBrowser();
        }

        // 4. Loop Pengambilan Frame & Pengiriman ke AI
        function mulaiLoopPemindaian() {
            if (scanLoopTimer) clearInterval(scanLoopTimer);
            // Kirim frame setiap 700ms saat tidak sedang memproses
            scanLoopTimer = setInterval(kirimFrameKeAI, 700);
        }

        async function kirimFrameKeAI() {
            if (isProcessing || isShowingSuccess || !localStream) return;
            if (webcamVideo.readyState !== webcamVideo.HAVE_ENOUGH_DATA) return;

            isProcessing = true;

            try {
                // Gambar frame video ke canvas offscreen
                const vw = webcamVideo.videoWidth || 640;
                const vh = webcamVideo.videoHeight || 480;

                // Target dimensi optimal untuk efisiensi jaringan & komputasi DeepFace (480x360)
                const targetWidth = 480;
                const targetHeight = Math.round((vh / vw) * targetWidth);

                captureCanvas.width = targetWidth;
                captureCanvas.height = targetHeight;
                const ctx = captureCanvas.getContext('2d');
                ctx.drawImage(webcamVideo, 0, 0, targetWidth, targetHeight);

                // Ekstrak JPEG Base64
                const frameBase64 = captureCanvas.toDataURL('image/jpeg', 0.75);

                // Perbarui status UI menjadi memproses
                perbaruiStatusLive('memproses', 'Memproses Wajah...', 'Sedang mengidentifikasi data siswa di server AI...');

                const response = await fetch(prosesPindaiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ gambar: frameBase64 })
                });

                if (response.ok) {
                    const data = await response.json();
                    tanganiHasilScan(data);
                } else {
                    perbaruiStatusLive('standby', 'Arahkan Wajah ke Kamera', 'Posisikan wajah Anda di dalam frame untuk presensi otomatis');
                }

            } catch (err) {
                console.warn('Gagal memproses frame:', err);
                perbaruiStatusLive('standby', 'Arahkan Wajah ke Kamera', 'Posisikan wajah Anda di dalam frame untuk presensi otomatis');
            } finally {
                isProcessing = false;
            }
        }

        // 5. Penanganan Respon Presensi dari Server
        function tanganiHasilScan(data) {
            const st = data.status;

            if (st === 'sukses' || st === 'sudah_absen') {
                const siswa = data.siswa || {};
                const nisn = siswa.nisn || '';
                const now = Date.now();

                // Cegah pengulangan jika siswa yang sama baru saja terdeteksi dalam 6 detik
                if (nisn === lastScanNisn && (now - lastScanTime) < 6000) {
                    return;
                }

                lastScanNisn = nisn;
                lastScanTime = now;

                // Tampilkan kartu sukses / status absen siswa
                tampilkanDetailPresensi({
                    nama: siswa.nama,
                    kelas: siswa.kelas,
                    nisn: siswa.nisn,
                    status_absen: (st === 'sudah_absen') ? 'SUDAH ABSEN' : (siswa.status || 'HADIR'),
                    timestamp: siswa.jam || ''
                });

                // Getaran feedback di smartphone jika didukung
                if (navigator.vibrate) {
                    navigator.vibrate(st === 'sukses' ? [80, 40, 80] : [120]);
                }

            } else if (st === 'tidak_dikenali') {
                perbaruiStatusLive('tidak_dikenali', 'Wajah Tidak Dikenali', data.pesan || 'Wajah tidak cocok dengan data siswa terdaftar.');
            } else if (st === 'tidak_ada_wajah') {
                perbaruiStatusLive('standby', 'Arahkan Wajah ke Kamera', 'Posisikan wajah Anda di dalam frame untuk presensi otomatis');
            } else if (st === 'error_ai') {
                perbaruiStatusLive('memproses_lama', 'Layanan AI Menyiapkan Model', data.pesan || 'Sedang menghubungkan ke mesin pengenalan wajah...');
            }
        }

        function perbaruiStatusLive(st, pesan, detail) {
            if (!notifLive || isShowingSuccess) return;

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
            } else if (rawStatus.includes('ALPA')) {
                statusLabel = 'ALPA';
                badgeClass = 'badge-alpa';
                borderColor = '#dc2626';
                iconHtml = '<i class="bx bx-alarm-exclamation"></i>';
                iconBg = '#fee2e2';
                iconColor = '#dc2626';
            } else if (rawStatus.includes('DILUAR')) {
                statusLabel = 'DILUAR WAKTU ABSEN';
                badgeClass = 'badge-diluar-waktu';
                borderColor = '#dc2626';
                iconHtml = '<i class="bx bx-alarm-exclamation"></i>';
                iconBg = '#fee2e2';
                iconColor = '#dc2626';
            } else if (rawStatus.includes('SUDAH')) {
                statusLabel = 'SUDAH ABSEN';
                badgeClass = 'badge-sudah-absen';
                borderColor = '#0284c7';
                iconHtml = '<i class="bx bx-check-double"></i>';
                iconBg = '#e0f2fe';
                iconColor = '#0284c7';
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

            // Tampilkan detail siswa selama 5 detik, lalu kembali ke status live standby
            hideNotifTimer = setTimeout(() => {
                isShowingSuccess = false;
                if (notifSukses) notifSukses.classList.add('d-none');
                if (notifLive) notifLive.classList.remove('d-none');
                perbaruiStatusLive('standby', 'Arahkan Wajah ke Kamera', 'Posisikan wajah Anda di dalam frame untuk presensi otomatis');
            }, 5000);
        }

        // 6. Jalankan Kamera Saat Halaman Selesai Dimuat
        document.addEventListener('DOMContentLoaded', () => {
            mulaiKameraBrowser();
        });
    </script>
</body>
</html>
