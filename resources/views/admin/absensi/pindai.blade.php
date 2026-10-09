@extends('layouts.admin.app')

@section('title', 'Pindai Wajah Presensi Otomatis - SMAN 2 Tondano')

@section('content')
@if(! ($pythonStatus['online'] ?? false))
    <!-- Auto-refresh berkala murni HTML saat layanan Python sedang dalam proses inisialisasi -->
    <meta http-equiv="refresh" content="5">
@endif

<!-- Penanda aksesibilitas & verifikasi -->
<span class="visually-hidden">Pindai Wajah Presensi Otomatis</span>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 px-3 small mb-3" role="alert">
        <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@elseif(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show py-2 px-3 small mb-3" role="alert">
        <i class="bx bx-error-circle me-1"></i> {{ session('warning') }}
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-3">
    <!-- Kolom 1: Kamera OpenCV (Tampilan Utama) -->
    <div class="col-lg-7 col-md-12">
        <div class="card border-0 shadow-sm overflow-hidden rounded-3" id="scanner-card">
            <!-- Header Card: Status Kamera dan Jam Server -->
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2 px-3">
                <span class="small fw-semibold d-flex align-items-center">
                    @if($pythonStatus['online'] ?? false)
                        <span class="spinner-grow spinner-grow-sm text-success me-2" role="status"></span>
                        <span>Kamera OpenCV Aktif (Sumber: {{ $pythonStatus['camera_source'] ?? 0 }})</span>
                    @else
                        <span class="spinner-border spinner-border-sm text-warning me-2" role="status"></span>
                        <span class="text-warning">Menyiapkan Layanan Python...</span>
                    @endif
                </span>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary small mono">{{ now()->format('H:i') }} WITA</span>
                    <a href="{{ url()->current() }}" class="btn btn-outline-light btn-sm py-0 px-2" style="font-size: 12px;" title="Segarkan Kamera">
                        <i class="bx bx-refresh me-1"></i> Refresh
                    </a>
                </div>
            </div>

            <!-- Viewfinder Video Feed OpenCV (MJPEG Native HTML Tanpa JavaScript) -->
            <div class="position-relative bg-black d-flex justify-content-center align-items-center" style="min-height: 440px; max-height: 500px; overflow: hidden;">
                @if($pythonStatus['online'] ?? false)
                    <!-- Browser merender stream MJPEG multipart/x-mixed-replace secara native -->
                    <img id="opencv-feed" 
                         src="http://{{ request()->getHost() }}:5000/video_feed" 
                         alt="Stream Kamera OpenCV SMAN 2 Tondano" 
                         class="w-100 h-100" 
                         style="object-fit: cover; max-height: 500px; display: block;">
                @else
                    <!-- Tampilan status saat layanan Python sedang dinyalakan otomatis -->
                    <div class="w-100 h-100 bg-dark text-white d-flex flex-column justify-content-center align-items-center p-4 text-center" style="min-height: 440px;">
                        <i class="bx bx-loader-alt bx-spin text-warning mb-2" style="font-size: 3rem;"></i>
                        <h6 class="fw-bold mb-1 text-white">Layanan Kamera Sedang Dinyalakan Otomatis</h6>
                        <p class="small text-white-50 mb-3" style="max-width: 440px;">
                            {{ $pythonStatus['message'] ?? 'Memuat modul OpenCV & model FaceNet di latar belakang. Halaman akan menyegarkan otomatis...' }}
                        </p>
                        <a href="{{ url()->current() }}" class="btn btn-primary btn-sm">
                            <i class="bx bx-refresh me-1"></i> Segarkan Sekarang
                        </a>
                    </div>
                @endif
            </div>

            <!-- Footer Card Pengaturan Sumber Kamera (HTML Form Murni Tanpa JavaScript) -->
            <div class="card-footer bg-light py-2 px-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="small fw-semibold text-dark"><i class="bx bx-camera me-1"></i> Kamera:</span>

                        <!-- Pilihan Kamera 0 (Laptop) -->
                        <form method="POST" action="{{ route('admin.attendance.pindai.kamera') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="sumber" value="0">
                            <button type="submit" class="btn btn-sm {{ ($pythonStatus['camera_source'] ?? 0) == 0 ? 'btn-success' : 'btn-outline-success' }} py-1 px-2">
                                <i class="bx bx-laptop me-1"></i> Laptop (0)
                            </button>
                        </form>

                        <!-- Pilihan Kamera 1 (iPhone USB / External) -->
                        <form method="POST" action="{{ route('admin.attendance.pindai.kamera') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="sumber" value="1">
                            <button type="submit" class="btn btn-sm {{ ($pythonStatus['camera_source'] ?? 0) == 1 ? 'btn-primary' : 'btn-outline-primary' }} py-1 px-2">
                                <i class="bx bxl-apple me-1"></i> iPhone USB (1)
                            </button>
                        </form>

                        <!-- Pilihan Kamera 2 (iPhone USB / Alternate) -->
                        <form method="POST" action="{{ route('admin.attendance.pindai.kamera') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="sumber" value="2">
                            <button type="submit" class="btn btn-sm {{ ($pythonStatus['camera_source'] ?? 0) == 2 ? 'btn-info text-white' : 'btn-outline-info' }} py-1 px-2">
                                <i class="bx bxl-apple me-1"></i> iPhone USB (2)
                            </button>
                        </form>
                    </div>

                    <!-- Input IP Camera WiFi HP -->
                    <form method="POST" action="{{ route('admin.attendance.pindai.kamera') }}" class="d-flex align-items-center gap-1">
                        @csrf
                        <input type="text" name="sumber" class="form-control form-control-sm" placeholder="URL IP Cam HP (http://...:8080/video)" style="width: 190px;" required>
                        <button type="submit" class="btn btn-sm btn-primary py-1 px-2">Pakai WiFi HP</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom 2: Status Deteksi Real-Time & Riwayat Presensi Hari Ini -->
    <div class="col-lg-5 col-md-12">
        <!-- Kartu 1: Deteksi Terakhir (Real-Time) -->
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark small">
                    <i class="bx bx-scan text-success me-1"></i> Status Deteksi Wajah
                </h6>
                <span class="badge bg-label-info small">HUD OpenCV Real-time</span>
            </div>
            <div class="card-body p-3 text-center">
                @if(!empty($pythonStatus['latest_result']))
                    @php $h = $pythonStatus['latest_result']; @endphp
                    <h4 class="fw-bold text-dark mb-1">{{ $h['nama'] ?? 'Siswa' }}</h4>
                    <p class="text-muted small mb-2">
                        {{ !empty($h['nisn']) ? 'NISN: ' . $h['nisn'] . ' • Akurasi: ' . ($h['akurasi'] ?? '-') : 'Wajah terdeteksi di kamera' }}
                    </p>
                    <span class="badge {{ ($h['status'] ?? '') === 'sukses' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-1 fs-6">
                        {{ $h['status_absen'] ?? ($h['status'] === 'sukses' ? 'TERCATAT' : 'MEMINDAI') }}
                    </span>
                @else
                    <h4 class="fw-bold text-dark mb-1">Memindai Wajah...</h4>
                    <p class="text-muted small mb-2">Nama & status kehadiran tertera langsung pada layar kamera.</p>
                    <span class="badge bg-secondary px-3 py-1 fs-6">SIAP MEMINDAI</span>
                @endif
            </div>
        </div>

        <!-- Kartu 2: Presensi Hari Ini -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark small">
                    <i class="bx bx-history text-primary me-1"></i> Presensi Hari Ini
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-label-primary small">{{ count($riwayatPindai) }} Tercatat</span>
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" title="Perbarui Daftar">
                        <i class="bx bx-refresh"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0" style="max-height: 350px; overflow-y: auto;">
                <ul class="list-group list-group-flush">
                    @forelse ($riwayatPindai as $item)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <div>
                                <h6 class="mb-0 fw-semibold fs-6 text-dark">{{ $item->student?->name ?? 'Siswa' }}</h6>
                                <small class="text-muted">{{ $item->student?->classes?->name ?? '-' }} • NISN: {{ $item->student?->nisn ?? '-' }}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge {{ $item->status === 'hadir' ? 'bg-label-success' : 'bg-label-warning' }} small">
                                    {{ $item->status_label }}
                                </span>
                                <small class="d-block text-muted" style="font-size: 11px;">
                                    {{ \Carbon\Carbon::parse($item->check_in_time)->format('H:i') }}
                                </small>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4 small">
                            Belum ada siswa yang melakukan presensi hari ini.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
