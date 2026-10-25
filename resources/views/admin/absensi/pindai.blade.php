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
            min-height: 100dvh;
            margin: 0;
            padding: 1rem;
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4px 0 8px 0;
            width: 100%;
            gap: 12px;
        }

        .btn-kembali {
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
            white-space: nowrap;
            flex-shrink: 0;
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
            flex-grow: 1;
            min-width: 0;
        }

        .sekolah-logo {
            height: 44px;
            width: auto;
            margin-bottom: 2px;
            object-fit: contain;
        }

        .sekolah-nama {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sekolah-sub {
            font-size: 0.78rem;
            color: #64748b;
            margin: 2px 0 0 0;
            white-space: nowrap;
        }

        .jam-badge {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #0284c7;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
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

        /* Canvas Overlay Bounding Box Wajah (Khas Python OpenCV - Border Putih) */
        .face-box-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 30;
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

        /* Tombol Putar Balik Kamera di Bawah Frame */
        .camera-footer {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 8px 4px 4px 4px;
        }

        .btn-putar-kamera {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            color: #0284c7;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.55rem;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            transition: all 0.25s ease;
        }

        .btn-putar-kamera:hover {
            background: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
            transform: rotate(180deg);
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }

        .btn-putar-kamera:active {
            transform: rotate(360deg) scale(0.95);
        }

        /* Tombol Putar Kamera Floating (Khusus Mode Lanskap / Mobile) */
        .btn-putar-kamera-floating {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 45;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.90);
            border: 1.5px solid rgba(2, 132, 199, 0.4);
            color: #0284c7;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(6px);
            transition: all 0.25s ease;
        }

        .btn-putar-kamera-floating:hover {
            background: #0284c7;
            color: #ffffff;
            transform: rotate(180deg);
        }

        .btn-putar-kamera-floating:active {
            transform: rotate(360deg) scale(0.92);
        }

        /* Banner Saran Rotasi Lanskap untuk Layar HP */
        .rotate-prompt {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1050;
            width: calc(100% - 20px);
            max-width: 440px;
            animation: slideDownPrompt 0.4s ease;
        }

        .rotate-prompt-box {
            background: rgba(15, 23, 42, 0.95);
            color: #f8fafc;
            border: 1.5px solid rgba(56, 189, 248, 0.45);
            padding: 9px 14px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(8px);
        }

        .rotate-icon {
            font-size: 1.5rem;
            color: #38bdf8;
            flex-shrink: 0;
            animation: animRotatePhone 2.5s infinite ease-in-out;
        }

        .rotate-text {
            font-size: 0.78rem;
            line-height: 1.3;
            color: #f1f5f9;
        }

        .btn-close-prompt {
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 1.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2px 6px;
            cursor: pointer;
            flex-shrink: 0;
            transition: color 0.2s;
        }

        .btn-close-prompt:hover {
            color: #ffffff;
        }

        @keyframes animRotatePhone {
            0%, 20% { transform: rotate(0deg); }
            50%, 70% { transform: rotate(-90deg); }
            90%, 100% { transform: rotate(0deg); }
        }

        @keyframes slideDownPrompt {
            from { opacity: 0; transform: translate(-50%, -12px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }

        /* ========================================================
           1. RESPONSIVITAS MOBILE (HP POTRAIT - HURUF & ELEMEN LEBIH KECIL)
           ======================================================== */
        @media (max-width: 768px) {
            body {
                padding: 0.6rem 0.6rem;
                justify-content: flex-start;
            }

            .scanner-page {
                gap: 8px;
            }

            .scanner-nav {
                padding: 2px 0 6px 0;
                gap: 8px;
            }

            .btn-kembali {
                padding: 4px 10px;
                font-size: 0.74rem;
                border-radius: 6px;
                gap: 4px;
            }

            .sekolah-logo {
                height: 32px;
                margin-bottom: 1px;
            }

            .sekolah-nama {
                font-size: 0.86rem;
            }

            .sekolah-sub {
                font-size: 0.68rem;
            }

            .jam-badge {
                padding: 4px 10px;
                font-size: 0.74rem;
                border-radius: 6px;
            }

            .camera-container {
                border-width: 2.5px;
                border-radius: 12px;
                aspect-ratio: 4 / 3;
            }

            .notification-overlay {
                bottom: 12px;
                width: 94%;
            }

            .notif-live {
                padding: 8px 12px;
                gap: 10px;
                border-radius: 10px;
            }

            .notif-live .icon-box {
                width: 32px;
                height: 32px;
                font-size: 1.15rem;
            }

            .notif-live .live-title {
                font-size: 0.8rem;
            }

            .notif-live .live-desc {
                font-size: 0.68rem;
            }

            .notif-sukses {
                padding: 8px 12px;
                gap: 10px;
                border-radius: 10px;
            }

            .notif-sukses .icon-box {
                width: 32px;
                height: 32px;
                font-size: 1.15rem;
            }

            .notif-nama-siswa {
                font-size: 0.85rem;
            }

            .notif-keterangan {
                font-size: 0.7rem;
            }

            .notif-badge-label {
                padding: 3px 8px;
                font-size: 0.7rem;
            }

            #notif-jam {
                font-size: 10px !important;
            }

            .camera-footer {
                padding: 4px;
            }

            .btn-putar-kamera {
                width: 40px;
                height: 40px;
                font-size: 1.35rem;
            }
        }

        /* ========================================================
           2. RESPONSIVITAS MOBILE MODE LANSKAP (MENYAMPING SEPERTI LAPTOP)
           ======================================================== */
        @media (orientation: landscape) and (max-height: 580px) {
            body {
                padding: 4px 10px !important;
                min-height: 100dvh;
                height: 100dvh;
                justify-content: flex-start !important;
                overflow: hidden;
            }

            .rotate-prompt {
                display: none !important;
            }

            .scanner-page {
                height: 100%;
                gap: 4px !important;
                max-width: 100% !important;
                justify-content: space-between;
            }

            .scanner-nav {
                padding: 0 !important;
                min-height: auto;
                gap: 8px;
            }

            .btn-kembali {
                padding: 2px 8px !important;
                font-size: 0.7rem !important;
            }

            .sekolah-logo {
                height: 22px !important;
                margin-bottom: 0 !important;
            }

            .sekolah-nama {
                font-size: 0.78rem !important;
            }

            .sekolah-sub {
                display: none !important;
            }

            .jam-badge {
                padding: 2px 8px !important;
                font-size: 0.7rem !important;
            }

            .camera-container {
                flex-grow: 1 !important;
                height: calc(100dvh - 56px) !important;
                max-height: calc(100dvh - 56px) !important;
                aspect-ratio: auto !important;
                border-width: 2.5px !important;
                border-radius: 10px !important;
            }

            /* Di mode lanskap HP, sembunyikan footer bawah agar kamera maksimal tinggi, gunakan tombol floating */
            .camera-footer {
                display: none !important;
            }

            .btn-putar-kamera-floating {
                display: inline-flex !important;
                top: 8px !important;
                right: 8px !important;
                width: 34px !important;
                height: 34px !important;
                font-size: 1.15rem !important;
            }

            .notification-overlay {
                bottom: 8px !important;
                width: 82% !important;
                max-width: 420px !important;
            }

            .notif-live {
                padding: 6px 10px !important;
                gap: 8px !important;
                border-radius: 8px !important;
            }

            .notif-live .icon-box {
                width: 28px !important;
                height: 28px !important;
                font-size: 1.05rem !important;
            }

            .notif-live .live-title {
                font-size: 0.74rem !important;
            }

            .notif-live .live-desc {
                font-size: 0.64rem !important;
            }

            .notif-sukses {
                padding: 6px 10px !important;
                gap: 8px !important;
                border-radius: 8px !important;
            }

            .notif-sukses .icon-box {
                width: 28px !important;
                height: 28px !important;
                font-size: 1.05rem !important;
            }

            .notif-nama-siswa {
                font-size: 0.78rem !important;
            }

            .notif-keterangan {
                font-size: 0.64rem !important;
            }

            .notif-badge-label {
                padding: 2px 6px !important;
                font-size: 0.64rem !important;
            }

            #notif-jam {
                font-size: 9px !important;
            }
        }
    </style>
</head>
<body>

    <!-- Penanda aksesibilitas & verifikasi -->
    <span class="visually-hidden">Pindai Wajah Presensi Otomatis</span>

    <!-- Banner Saran Rotasi Lanskap untuk Layar HP -->
    <div class="rotate-prompt d-none" id="rotate-prompt">
        <div class="rotate-prompt-box">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-mobile-landscape rotate-icon"></i>
                <div class="rotate-text">
                    <strong>Putar Layar ke Lanskap (Menyamping)</strong>
                    <small class="d-block opacity-75">Untuk tampilan penuh seperti laptop</small>
                </div>
            </div>
            <button type="button" class="btn-close-prompt" onclick="tutupRotatePrompt()" aria-label="Tutup">
                <i class="bx bx-x"></i>
            </button>
        </div>
    </div>

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

            <!-- Tombol Putar Kamera Floating (Khusus Mode Lanskap / Layar Kecil) -->
            <button type="button" class="btn-putar-kamera-floating" onclick="toggleKamera()" title="Ganti Kamera Depan/Belakang" aria-label="Ganti Kamera">
                <i class="bx bx-sync"></i>
            </button>

            <!-- Video Kamera Klien (Webcam / Kamera HP) -->
            <video id="webcam-video" 
                   autoplay 
                   playsinline 
                   muted 
                   class="video-feed"
                   style="transform: scaleX(-1);">
            </video>
            <!-- Canvas Overlay Bounding Box Wajah (Khas Python OpenCV - Border Putih) -->
            <canvas id="face-box-canvas" class="face-box-canvas" style="transform: scaleX(-1);"></canvas>
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

        <!-- Tombol Putar Balik Kamera (Hanya Icon) -->
        <div class="camera-footer">
            <button type="button" class="btn-putar-kamera" id="btn-toggle-kamera" onclick="toggleKamera()" title="Putar Balik Kamera (Depan / Belakang)" aria-label="Putar Kamera">
                <i class="bx bx-sync"></i>
            </button>
        </div>

    </div>

    <!-- Script Jam Digital, Kamera HTML5 & Penangkapan Frame Citra ke Python DeepFace -->
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
        const boxCanvas = document.getElementById('face-box-canvas');
        const boxCtx = boxCanvas ? boxCanvas.getContext('2d') : null;
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
        const btnToggleKamera = document.getElementById('btn-toggle-kamera');

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

        // Data & Status Bounding Box Wajah (Khas Python OpenCV)
        let activeFaceBox = null;
        let lastFaceDetectedTime = 0;
        let isBoxRenderLoopStarted = false;
        let localFaceDetector = null;

        if ('FaceDetector' in window) {
            try {
                localFaceDetector = new FaceDetector({ fastMode: true, maxDetectedFaces: 1 });
            } catch (e) {
                localFaceDetector = null;
            }
        }

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
                if (boxCanvas) {
                    boxCanvas.style.transform = (currentFacingMode === 'user') ? 'scaleX(-1)' : 'none';
                }

                videoEl.onloadedmetadata = () => {
                    videoEl.play();
                    perbaruiTombolKamera();
                    mulaiSiklusPemindaian();
                    if (!isBoxRenderLoopStarted) {
                        isBoxRenderLoopStarted = true;
                        renderFaceBoundingBox();
                        if (localFaceDetector) {
                            mulaiDeteksiWajahLokal();
                        }
                    }
                };
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
            if (boxCanvas) {
                boxCanvas.classList.add('d-none');
                activeFaceBox = null;
            }
            offlineBox.classList.remove('d-none');
            if (offlineTitle) offlineTitle.textContent = judul;
            if (offlineDesc) offlineDesc.textContent = desc;
        }

        function toggleKamera() {
            currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
            if (boxCanvas) {
                boxCanvas.style.transform = (currentFacingMode === 'user') ? 'scaleX(-1)' : 'none';
            }
            mulaiKamera();
        }

        function perbaruiTombolKamera() {
            if (btnToggleKamera) {
                btnToggleKamera.setAttribute('title', currentFacingMode === 'user' ? 'Putar ke Kamera Belakang' : 'Putar ke Kamera Depan');
            }
        }

        // 5. Siklus Penangkapan Citra & Pengiriman ke Backend Python Secepat Kilat
        function mulaiSiklusPemindaian() {
            if (loopIntervalId) clearTimeout(loopIntervalId);
            jadwalkanFrameBerikutnya(80);
        }

        function jadwalkanFrameBerikutnya(delayMs = 150) {
            if (isCoolingDown) return;
            if (loopIntervalId) clearTimeout(loopIntervalId);
            loopIntervalId = setTimeout(tangkapDanProsesFrame, delayMs);
        }

        async function tangkapDanProsesFrame() {
            // Jangan memproses jika sedang ada request aktif, sedang jeda sukses, atau video belum siap
            if (isProcessingFrame || isCoolingDown || !videoEl || videoEl.readyState < 2) {
                if (!isCoolingDown) jadwalkanFrameBerikutnya(200);
                return;
            }

            const videoW = videoEl.videoWidth;
            const videoH = videoEl.videoHeight;
            if (!videoW || !videoH) {
                jadwalkanFrameBerikutnya(200);
                return;
            }

            isProcessingFrame = true;

            try {
                // Resolusi optimal 400px (~12-15 KB), sangat cepat dikirim (< 5 ms) dan diproses FaceNet instan
                const targetW = 400;
                const targetH = Math.round((videoH / videoW) * targetW);
                canvasEl.width = targetW;
                canvasEl.height = targetH;

                const ctx = canvasEl.getContext('2d', { willReadFrequently: true });
                ctx.drawImage(videoEl, 0, 0, targetW, targetH);

                // Ekstrak JPEG Base64 ringan
                const base64Image = canvasEl.toDataURL('image/jpeg', 0.70);

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
                        perbaruiNotifLive('state-peringatan', '<i class="bx bx-chip bx-spin"></i>', 'Server Sedang Memuat', 'Menghubungkan ke layanan FaceNet di server...');
                    }
                }
            } catch (err) {
                // Abaikan kesalahan koneksi sementara
            } finally {
                isProcessingFrame = false;
                if (!isCoolingDown) {
                    jadwalkanFrameBerikutnya(150); // Scan berlanjut setiap 150ms untuk respons instan
                }
            }
        }

        // 6. Tangani Respon Backend
        function tanganiResponPresensi(data) {
            if (isCoolingDown) return;

            // Perbarui bounding box wajah jika terdeteksi oleh backend Python
            if (data.wajah && data.wajah.w > 0) {
                activeFaceBox = data.wajah;
                lastFaceDetectedTime = Date.now();
            } else if (data.status === 'tidak_ada_wajah') {
                activeFaceBox = null;
            }

            const st = data.status;

            if (st === 'sukses' || st === 'sudah_absen' || st === 'diluar_waktu') {
                const siswa = data.siswa || {};
                const nowSec = Date.now() / 1000;

                // Hindari duplikasi rekaman berulang untuk siswa yang sama berturut-turut dalam 8 detik
                if (siswa.nisn === lastScannedNisn && (nowSec - lastScannedTime) < 8) {
                    return;
                }

                lastScannedNisn = siswa.nisn;
                lastScannedTime = nowSec;

                playChime(true);
                tampilkanDetailSukses(siswa, st, data.pesan);
            } else if (st === 'tidak_ada_wajah') {
                perbaruiNotifLive('state-standby', '<i class="bx bx-scan"></i>', 'Arahkan Wajah ke Kamera', 'Posisikan wajah Anda di dalam frame untuk presensi otomatis');
            } else if (st === 'tidak_dikenali' || st === 'tidak_ditemukan') {
                tampilkanBelumTerdaftar(data.pesan || 'Data belum terdaftar. Silahkan hubungi administrator.');
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

        function tampilkanBelumTerdaftar(pesanCustom = 'Silahkan hubungi administrator') {
            if (cooldownTimer) clearTimeout(cooldownTimer);
            isCoolingDown = true;
            activeFaceBox = null;

            playChime(false);

            if (notifNama) notifNama.textContent = 'Data Belum Terdaftar';
            if (notifDetail) notifDetail.textContent = 'Silahkan hubungi administrator';

            if (notifStatusBadge) {
                notifStatusBadge.textContent = 'BELUM TERDAFTAR';
                notifStatusBadge.className = 'notif-badge-label badge-tidak-dikenal';
            }

            if (notifSukses) {
                notifSukses.style.borderLeftColor = '#dc2626';
            }

            if (notifIconBox) {
                notifIconBox.innerHTML = '<i class="bx bx-user-x"></i>';
                notifIconBox.style.background = '#fee2e2';
                notifIconBox.style.color = '#dc2626';
            }

            if (notifJam) {
                const waktuTeks = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WITA';
                notifJam.textContent = waktuTeks;
            }

            if (notifLive) notifLive.classList.add('d-none');
            if (notifSukses) notifSukses.classList.remove('d-none');

            // Tahan tampilan selama 3.5 detik agar terbaca dengan jelas oleh pengguna
            cooldownTimer = setTimeout(() => {
                isCoolingDown = false;
                if (notifSukses) notifSukses.classList.add('d-none');
                if (notifLive) notifLive.classList.remove('d-none');
                perbaruiNotifLive('state-standby', '<i class="bx bx-scan"></i>', 'Arahkan Wajah ke Kamera', 'Posisikan wajah Anda di dalam frame untuk presensi otomatis');
                jadwalkanFrameBerikutnya(80);
            }, 3500);
        }

        function tampilkanDetailSukses(siswa, statusKode, pesanCustom) {
            if (cooldownTimer) clearTimeout(cooldownTimer);
            isCoolingDown = true;
            activeFaceBox = null;

            if (notifNama) notifNama.textContent = siswa.nama || 'Siswa';

            const rawStatus = (siswa.status || 'HADIR').toUpperCase();
            let statusLabel = rawStatus;
            let badgeClass = 'badge-hadir';
            let borderColor = '#16a34a';
            let iconHtml = '<i class="bx bx-check"></i>';
            let iconBg = '#dcfce7';
            let iconColor = '#16a34a';

            if (rawStatus.includes('ALPA')) {
                statusLabel = 'ALPA';
                badgeClass = 'badge-alpa';
                borderColor = '#dc2626';
                iconHtml = '<i class="bx bx-x-circle"></i>';
                iconBg = '#fee2e2';
                iconColor = '#dc2626';
            } else if (rawStatus.includes('TERLAMBAT')) {
                statusLabel = 'TERLAMBAT';
                badgeClass = 'badge-terlambat';
                borderColor = '#ca8a04';
                iconHtml = '<i class="bx bx-time"></i>';
                iconBg = '#fef9c3';
                iconColor = '#ca8a04';
            } else if (rawStatus.includes('HADIR')) {
                statusLabel = 'HADIR';
                badgeClass = 'badge-hadir';
                borderColor = '#16a34a';
                iconHtml = '<i class="bx bx-check"></i>';
                iconBg = '#dcfce7';
                iconColor = '#16a34a';
            } else if (rawStatus.includes('BELUM') || rawStatus.includes('DILUAR')) {
                statusLabel = 'BELUM DIBUKA';
                badgeClass = 'badge-diluar-waktu';
                borderColor = '#64748b';
                iconHtml = '<i class="bx bx-time-five"></i>';
                iconBg = '#f1f5f9';
                iconColor = '#475569';
            } else {
                statusLabel = rawStatus;
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

            // Keterangan: Hanya menampilkan Kelas & NISN tanpa mengulang nama siswa
            if (notifDetail) {
                notifDetail.textContent = `${siswa.kelas || '-'} • NISN: ${siswa.nisn || '-'}`;
            }

            // Waktu di bawah/samping badge: Hanya jam tanpa teks sudah absen
            if (notifJam) {
                const jamStr = siswa.jam ? siswa.jam.substring(0, 5) : '';
                const waktuTeks = (jamStr || new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })) + ' WITA';
                notifJam.textContent = waktuTeks;
            }

            if (notifLive) notifLive.classList.add('d-none');
            if (notifSukses) notifSukses.classList.remove('d-none');

            // Tampilkan kartu status selama 3.5 detik untuk siswa, lalu kembali ke standby & lanjut memindai instan
            cooldownTimer = setTimeout(() => {
                isCoolingDown = false;
                if (notifSukses) notifSukses.classList.add('d-none');
                if (notifLive) notifLive.classList.remove('d-none');
                perbaruiNotifLive('state-standby', '<i class="bx bx-scan"></i>', 'Arahkan Wajah ke Kamera', 'Posisikan wajah Anda di dalam frame untuk presensi otomatis');
                jadwalkanFrameBerikutnya(80);
            }, 3500);
        }

        // 7. Render Visual Bounding Box Wajah (Khas Python OpenCV - Border Putih)
        let smoothedBox = null;

        function renderFaceBoundingBox() {
            if (!boxCanvas || !boxCtx || !videoEl) {
                requestAnimationFrame(renderFaceBoundingBox);
                return;
            }

            const container = videoEl.parentElement;
            const displayW = container ? container.clientWidth : videoEl.clientWidth;
            const displayH = container ? container.clientHeight : videoEl.clientHeight;

            if (displayW && displayH && (boxCanvas.width !== displayW || boxCanvas.height !== displayH)) {
                boxCanvas.width = displayW;
                boxCanvas.height = displayH;
            }

            boxCtx.clearRect(0, 0, boxCanvas.width, boxCanvas.height);

            // Jika sedang jeda sukses atau wajah tidak terdeteksi lebih dari 450ms, kosongkan
            const now = Date.now();
            if (isCoolingDown || !activeFaceBox || (now - lastFaceDetectedTime > 450)) {
                smoothedBox = null;
                requestAnimationFrame(renderFaceBoundingBox);
                return;
            }

            const videoW = videoEl.videoWidth || activeFaceBox.frame_w || 400;
            const videoH = videoEl.videoHeight || activeFaceBox.frame_h || 300;

            if (!videoW || !videoH || !displayW || !displayH) {
                requestAnimationFrame(renderFaceBoundingBox);
                return;
            }

            // Hitung skala & offset penyesuaian CSS object-fit: cover
            const scale = Math.max(displayW / videoW, displayH / videoH);
            const renderedW = videoW * scale;
            const renderedH = videoH * scale;
            const offsetX = (displayW - renderedW) / 2;
            const offsetY = (displayH - renderedH) / 2;

            // Koordinat relatif dari frame pemrosesan Python
            const frameW = activeFaceBox.frame_w || videoW;
            const frameH = activeFaceBox.frame_h || videoH;
            const relX = activeFaceBox.x / frameW;
            const relY = activeFaceBox.y / frameH;
            const relW = activeFaceBox.w / frameW;
            const relH = activeFaceBox.h / frameH;

            const targetX = offsetX + (relX * renderedW);
            const targetY = offsetY + (relY * renderedH);
            const targetW = relW * renderedW;
            const targetH = relH * renderedH;

            // Smooth Interpolation (LERP) agar pergerakan kotak wajah sangat halus dan stabil
            if (!smoothedBox) {
                smoothedBox = { x: targetX, y: targetY, w: targetW, h: targetH };
            } else {
                const lerpFactor = 0.40;
                smoothedBox.x += (targetX - smoothedBox.x) * lerpFactor;
                smoothedBox.y += (targetY - smoothedBox.y) * lerpFactor;
                smoothedBox.w += (targetW - smoothedBox.w) * lerpFactor;
                smoothedBox.h += (targetH - smoothedBox.h) * lerpFactor;
            }

            gambarKotakWajahOpenCv(boxCtx, smoothedBox.x, smoothedBox.y, smoothedBox.w, smoothedBox.h);

            requestAnimationFrame(renderFaceBoundingBox);
        }

        function gambarKotakWajahOpenCv(ctx, x, y, w, h) {
            if (w <= 0 || h <= 0) return;

            ctx.save();

            // Efek bayangan halus agar kotak putih terlihat kontras di latar belakang terang maupun gelap
            ctx.shadowColor = 'rgba(0, 0, 0, 0.45)';
            ctx.shadowBlur = 5;

            // Garis tipis kotak pembungkus (Pure White Border khas OpenCV)
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 1.8;
            ctx.strokeRect(x, y, w, h);

            // Aksen sudut tebal khas Python OpenCV (Corner Brackets)
            const cornerLen = Math.min(w, h) * 0.22;
            ctx.lineWidth = 3.8;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            ctx.beginPath();
            // Pojok Kiri Atas
            ctx.moveTo(x, y + cornerLen);
            ctx.lineTo(x, y);
            ctx.lineTo(x + cornerLen, y);

            // Pojok Kanan Atas
            ctx.moveTo(x + w - cornerLen, y);
            ctx.lineTo(x + w, y);
            ctx.lineTo(x + w, y + cornerLen);

            // Pojok Kanan Bawah
            ctx.moveTo(x + w, y + h - cornerLen);
            ctx.lineTo(x + w, y + h);
            ctx.lineTo(x + w - cornerLen, y + h);

            // Pojok Kiri Bawah
            ctx.moveTo(x + cornerLen, y + h);
            ctx.lineTo(x, y + h);
            ctx.lineTo(x, y + h - cornerLen);
            ctx.stroke();

            ctx.restore();
        }

        async function mulaiDeteksiWajahLokal() {
            if (!localFaceDetector || !videoEl) return;

            const loop = async () => {
                if (!isCoolingDown && videoEl.readyState >= 2) {
                    try {
                        const faces = await localFaceDetector.detect(videoEl);
                        if (faces && faces.length > 0) {
                            const b = faces[0].boundingBox;
                            activeFaceBox = {
                                x: b.x,
                                y: b.y,
                                w: b.width,
                                h: b.height,
                                frame_w: videoEl.videoWidth,
                                frame_h: videoEl.videoHeight
                            };
                            lastFaceDetectedTime = Date.now();
                        }
                    } catch (e) {
                        // Abaikan error deteksi lokal
                    }
                }
                setTimeout(loop, 60);
            };
            loop();
        }

        // 8. Deteksi Orientasi Mobile & Rekomendasi Mode Lanskap
        function periksaOrientasi() {
            const promptEl = document.getElementById('rotate-prompt');
            if (!promptEl) return;

            const isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
                                   (window.innerWidth <= 768 && window.innerHeight > window.innerWidth);
            const isPortrait = window.innerHeight > window.innerWidth;

            if (isMobileDevice && isPortrait && window.innerWidth <= 768) {
                if (sessionStorage.getItem('rotate_prompt_dismissed') !== 'true') {
                    promptEl.classList.remove('d-none');
                }
            } else {
                promptEl.classList.add('d-none');
            }
        }

        function tutupRotatePrompt() {
            const promptEl = document.getElementById('rotate-prompt');
            if (promptEl) {
                promptEl.classList.add('d-none');
            }
            sessionStorage.setItem('rotate_prompt_dismissed', 'true');
        }

        window.addEventListener('resize', periksaOrientasi);
        window.addEventListener('orientationchange', periksaOrientasi);

        // Jalankan inisialisasi kamera & cek orientasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', () => {
            mulaiKamera();
            periksaOrientasi();
        });
    </script>
</body>
</html>
