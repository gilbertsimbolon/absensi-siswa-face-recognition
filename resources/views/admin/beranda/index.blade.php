@extends('layouts.admin.app')

@section('title', 'Beranda | SMA Negeri 2 Tondano')

@section('content')
    <div class="d-flex flex-column flex-grow-1 h-100" style="min-height: 0; overflow-y: auto; overflow-x: hidden;">

        <!-- 1. BARIS SALAM & JAM MASUK / BATAS ABSENSI -->
        <div class="card shadow-sm mb-3 border">
            <div class="card-body py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <!-- Ucapan Salam & Nama Pengguna -->
                <div>
                    <h4 class="fw-bold mb-1 text-dark">
                        {{ $greeting }}, {{ $userName }}!
                    </h4>
                    <p class="text-secondary small mb-0">
                        Selamat datang di Sistem Kehadiran Pintar (Face Recognition berbasis DeepFace) SMAN 2 Tondano.
                    </p>
                </div>

                <!-- Indikator Jam Masuk & Batas Absensi -->
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="d-flex align-items-center border rounded px-3 py-2 bg-white">
                        <i class="icon-base bx bx-time text-primary fs-4 me-2"></i>
                        <div>
                            <span class="d-block text-secondary" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                Jam Masuk
                            </span>
                            <span class="fw-bold text-dark fs-6">
                                {{ $jamMasuk }} WITA
                            </span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center border rounded px-3 py-2 bg-white">
                        <i class="icon-base bx bx-timer text-warning fs-4 me-2"></i>
                        <div>
                            <span class="d-block text-secondary" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                Batas Absensi
                            </span>
                            <span class="fw-bold text-dark fs-6">
                                {{ $batasAbsensi }} WITA
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. KARTU STATISTIK (JUMLAH SISWA, JUMLAH GURU, JUMLAH KELAS) -->
        <div class="row g-3 mb-3">
            <!-- Jumlah Siswa -->
            <div class="col-12 col-md-4">
                <div class="card shadow-sm h-100 border">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="me-3 d-flex align-items-center justify-content-center">
                            <i class="icon-base bx bx-user text-primary" style="font-size: 2.2rem;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-secondary small fw-semibold d-block">Jumlah Siswa</span>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($totalStudents) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jumlah Guru -->
            <div class="col-12 col-md-4">
                <div class="card shadow-sm h-100 border">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="me-3 d-flex align-items-center justify-content-center">
                            <i class="icon-base bx bx-id-card text-success" style="font-size: 2.2rem;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-secondary small fw-semibold d-block">Jumlah Guru</span>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($totalTeachers) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jumlah Kelas -->
            <div class="col-12 col-md-4">
                <div class="card shadow-sm h-100 border">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="me-3 d-flex align-items-center justify-content-center">
                            <i class="icon-base bx bx-door-open text-info" style="font-size: 2.2rem;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-secondary small fw-semibold d-block">Jumlah Kelas</span>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($totalClasses) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. GRAFIK KEHADIRAN & PERSENTASE KEHADIRAN -->
        <div class="row g-3 mb-3">
            <!-- Grafik Kehadiran (Tren 7 Hari) -->
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm h-100 border">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white border-bottom">
                        <h5 class="mb-0 fw-bold text-dark">Grafik Kehadiran</h5>
                        <span class="text-secondary small">7 Hari Terakhir</span>
                    </div>
                    <div class="card-body pt-3 pb-2">
                        <div id="chartGrafikKehadiran" style="min-height: 290px;"></div>
                    </div>
                </div>
            </div>

            <!-- Persentase Kehadiran -->
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm h-100 border">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white border-bottom">
                        <h5 class="mb-0 fw-bold text-dark">Persentase Kehadiran</h5>
                        <span class="text-secondary small">Hari Ini</span>
                    </div>
                    <div class="card-body pt-2 d-flex flex-column justify-content-between">
                        <!-- Chart Lingkaran Persentase -->
                        <div id="chartPersentaseKehadiran" style="min-height: 210px;"></div>

                        <!-- Rincian Data Presensi Hari Ini (Teks Bersih Tanpa Background) -->
                        <div class="d-flex flex-column gap-2 mt-2 pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                <span class="text-dark fw-medium">Hadir Tepat Waktu</span>
                                <span class="fw-bold text-success">{{ $hadirToday }} siswa</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                <span class="text-dark fw-medium">Terlambat</span>
                                <span class="fw-bold text-warning">{{ $terlambatToday }} siswa</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                <span class="text-dark fw-medium">Izin / Sakit</span>
                                <span class="fw-bold text-info">{{ $izinToday + $sakitToday }} siswa</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center py-1">
                                <span class="text-dark fw-medium">Alpa</span>
                                <span class="fw-bold text-danger">{{ $alpaToday }} siswa</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. AKTIVITAS ABSENSI -->
        <div class="card shadow-sm mb-3 border">
            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white border-bottom">
                <h5 class="mb-0 fw-bold text-dark">Aktivitas Absensi</h5>
                <a href="{{ route('admin.attendance.index') }}" class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-white border-bottom">
                            <tr>
                                <th style="width: 50px;" class="text-center text-dark fw-bold">No</th>
                                <th class="text-dark fw-bold">Siswa</th>
                                <th class="text-dark fw-bold">Kelas</th>
                                <th class="text-dark fw-bold">Waktu Presensi</th>
                                <th class="text-dark fw-bold">Metode</th>
                                <th class="text-center text-dark fw-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentAttendances as $index => $item)
                                @php
                                    $studentFace = $item->student?->faces?->first();
                                    $snapshot = $item->snapshot_path ? asset('storage/' . $item->snapshot_path) : null;
                                    $photoUrl = $snapshot ?? ($studentFace?->file_path ? asset('storage/' . $studentFace->file_path) : null);
                                    
                                    $statusTextColor = match ($item->status) {
                                        \App\Models\Attendance::STATUS_HADIR => 'text-success',
                                        \App\Models\Attendance::STATUS_TERLAMBAT => 'text-warning',
                                        \App\Models\Attendance::STATUS_ALPA => 'text-danger',
                                        \App\Models\Attendance::STATUS_SAKIT => 'text-primary',
                                        \App\Models\Attendance::STATUS_IZIN => 'text-info',
                                        default => 'text-dark',
                                    };
                                @endphp
                                <tr>
                                    <td class="text-center text-secondary small">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if ($photoUrl)
                                                <img src="{{ $photoUrl }}" alt="{{ $item->student?->name }}"
                                                    class="rounded-circle me-2 flex-shrink-0 border" width="34" height="34" style="object-fit: cover;">
                                            @else
                                                <div class="rounded-circle border border-primary text-primary fw-bold me-2 d-flex align-items-center justify-content-center flex-shrink-0 bg-white"
                                                    style="width: 34px; height: 34px; font-size: 13px;">
                                                    {{ strtoupper(substr($item->student?->name ?? 'S', 0, 1)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <span class="fw-semibold text-dark d-block text-truncate" style="max-width: 220px;">
                                                    {{ $item->student?->name ?? '-' }}
                                                </span>
                                                <small class="text-secondary font-monospace" style="font-size: 11px;">
                                                    NISN: {{ $item->student?->nisn ?? '-' }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark">
                                            {{ $item->student?->classes?->name ?? ($item->classes?->name ?? '-') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark d-block">
                                            {{ $item->check_in_time ? \Carbon\Carbon::parse($item->check_in_time)->format('H:i') : '-' }} WITA
                                        </span>
                                        <small class="text-secondary" style="font-size: 11px;">
                                            {{ $item->date ? \Carbon\Carbon::parse($item->date)->translatedFormat('d M Y') : '-' }}
                                        </small>
                                    </td>
                                    <td>
                                        @if ($item->method === \App\Models\Attendance::METHOD_FACE)
                                            <span class="text-dark fw-medium">
                                                <i class="icon-base bx bx-scan text-primary me-1"></i> Pindai Wajah
                                            </span>
                                            @if ($item->confidence_score)
                                                <small class="text-secondary d-block" style="font-size: 10.5px;">
                                                    Akurasi: {{ number_format($item->confidence_score, 2) }}%
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-dark fw-medium">
                                                <i class="icon-base bx bx-edit text-secondary me-1"></i> Manual
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold {{ $statusTextColor }}">
                                            {{ $item->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-secondary">
                                        <i class="icon-base bx bx-info-circle fs-3 d-block mb-1"></i>
                                        Belum ada aktivitas presensi yang tercatat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Script Inisialisasi Grafik ApexCharts -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Inisialisasi Grafik Kehadiran (Bar Chart 7 Hari)
            const elGrafikKehadiran = document.querySelector('#chartGrafikKehadiran');
            if (elGrafikKehadiran && typeof ApexCharts !== 'undefined') {
                const optionsGrafik = {
                    series: [
                        {
                            name: 'Hadir',
                            data: @json($chartHadir)
                        },
                        {
                            name: 'Terlambat',
                            data: @json($chartTerlambat)
                        },
                        {
                            name: 'Tidak Hadir',
                            data: @json($chartTidakHadir)
                        }
                    ],
                    chart: {
                        type: 'bar',
                        height: 290,
                        stacked: false,
                        toolbar: { show: false },
                        fontFamily: 'Public Sans, sans-serif'
                    },
                    colors: ['#16a34a', '#d97706', '#dc2626'],
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '45%',
                            borderRadius: 4
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        show: true,
                        width: 2,
                        colors: ['transparent']
                    },
                    xaxis: {
                        categories: @json($chartCategories),
                        labels: {
                            style: {
                                fontSize: '12px',
                                colors: '#334155'
                            }
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    yaxis: {
                        title: { text: undefined },
                        labels: {
                            style: {
                                fontSize: '12px',
                                colors: '#334155'
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right',
                        fontSize: '12px',
                        labels: {
                            colors: '#1e293b'
                        }
                    },
                    grid: {
                        borderColor: '#e2e8f0',
                        strokeDashArray: 4
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val + ' Siswa';
                            }
                        }
                    }
                };

                const chartGrafik = new ApexCharts(elGrafikKehadiran, optionsGrafik);
                chartGrafik.render();
            }

            // 2. Inisialisasi Persentase Kehadiran (RadialBar Chart)
            const elPersentase = document.querySelector('#chartPersentaseKehadiran');
            if (elPersentase && typeof ApexCharts !== 'undefined') {
                const optionsPersentase = {
                    series: [{{ $attendancePercentage }}],
                    chart: {
                        height: 230,
                        type: 'radialBar',
                        fontFamily: 'Public Sans, sans-serif'
                    },
                    plotOptions: {
                        radialBar: {
                            startAngle: -135,
                            endAngle: 135,
                            hollow: {
                                margin: 0,
                                size: '70%',
                                background: '#ffffff'
                            },
                            track: {
                                background: '#f8fafc',
                                strokeWidth: '100%'
                            },
                            dataLabels: {
                                name: {
                                    show: true,
                                    fontSize: '13px',
                                    fontWeight: 600,
                                    color: '#475569',
                                    offsetY: -10
                                },
                                value: {
                                    show: true,
                                    fontSize: '28px',
                                    fontWeight: 700,
                                    color: '#0f172a',
                                    offsetY: 4,
                                    formatter: function (val) {
                                        return val + '%';
                                    }
                                }
                            }
                        }
                    },
                    fill: {
                        type: 'solid'
                    },
                    colors: ['#16a34a'],
                    stroke: {
                        dashArray: 4
                    },
                    labels: ['Hadir Hari Ini']
                };

                const chartPersentase = new ApexCharts(elPersentase, optionsPersentase);
                chartPersentase.render();
            }
        });
    </script>
@endsection
