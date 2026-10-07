<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Kehadiran - {{ $selectedClass->name }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #111;
            background: #fff;
        }
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm 10mm 10mm 10mm;
            }
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 0;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
        .header-kop {
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .table-matrix th, .table-matrix td {
            padding: 5px 4px !important;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #333 !important;
            font-size: 10px;
        }
        .signature-box {
            margin-top: 30px;
        }
    </style>
</head>
<body class="p-4">
    <!-- Tombol Cetak -->
    <div class="no-print mb-3 text-end">
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            Cetak Dokumen
        </button>
        <button onclick="window.close()" class="btn btn-secondary btn-sm">
            Tutup
        </button>
    </div>

    <!-- Kop Surat Resmi -->
    <div class="header-kop d-flex align-items-center justify-content-between">
        <div style="width: 70px;">
            <img src="{{ asset('img/logo.png') }}" alt="Logo" style="width: 60px; height: auto;">
        </div>
        <div class="text-center flex-grow-1">
            <h5 class="mb-0 fw-bold text-uppercase">PEMERINTAH PROVINSI SULAWESI UTARA</h5>
            <h4 class="mb-0 fw-bold text-uppercase">DINAS PENDIDIKAN DAERAH</h4>
            <h5 class="mb-0 fw-bold text-uppercase">SMA NEGERI 2 TONDANO</h5>
            <p class="mb-0 small text-muted">Jl. Kampus UNIMA, Tonsewer, Tondano Selatan, Kabupaten Minahasa, Sulawesi Utara</p>
        </div>
        <div style="width: 70px;"></div>
    </div>

    <!-- Judul Dokumen -->
    <div class="text-center mb-3">
        <h6 class="fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px;">
            REKAPITULASI KEHADIRAN SISWA
            @if ($period === 'weekly')
                (MINGGUAN)
            @elseif ($period === 'monthly')
                (BULANAN)
            @else
                (TAHUNAN)
            @endif
        </h6>
        <div class="small">
            Kelas: <strong>{{ $selectedClass->name }}</strong> &nbsp;|&nbsp; 
            Wali Kelas: <strong>{{ $selectedClass->teacher?->user?->name ?? '-' }}</strong> &nbsp;|&nbsp; 
            @if ($period === 'weekly')
                Periode: <strong>{{ $weekStart ? $weekStart->locale('id')->translatedFormat('d M Y') : '' }} s/d {{ $weekEnd ? $weekEnd->locale('id')->translatedFormat('d M Y') : '' }}</strong>
            @elseif ($period === 'monthly')
                Periode: <strong>{{ $monthNames[$selectedMonth] ?? '' }} {{ $selectedYear }}</strong>
            @else
                Periode: <strong>Tahun {{ $selectedYear }} (1 Tahun Penuh)</strong>
            @endif
            &nbsp;|&nbsp; Tahun Ajaran: <strong>{{ $activeYear?->name }} (Semester {{ $activeYear?->semester }})</strong>
        </div>
    </div>

    <!-- Tabel Matriks Kehadiran -->
    <table class="table table-bordered table-matrix w-100">
        <thead>
            <tr style="background-color: #f7f7f7;">
                <th rowspan="2" style="width: 30px;">No</th>
                <th rowspan="2" style="width: 90px;">NISN</th>
                <th rowspan="2" class="text-start ps-2" style="min-width: 170px;">Nama Siswa</th>
                <th rowspan="2" style="width: 30px;">L/P</th>

                @if ($period === 'yearly')
                    <th colspan="{{ count($monthsInfo) }}">Bulan</th>
                @else
                    <th colspan="{{ count($daysInfo) }}">Tanggal</th>
                @endif

                <th colspan="6">Rekapitulasi Kehadiran</th>
            </tr>
            <tr style="background-color: #f7f7f7;">
                @if ($period === 'weekly')
                    @foreach ($daysInfo as $day)
                        <th style="min-width: 80px;">
                            <div class="fw-bold">{{ $day['day_name'] }}</div>
                            <div style="font-size: 9px; font-weight: normal;">{{ $day['short_label'] }}</div>
                        </th>
                    @endforeach
                @elseif ($period === 'monthly')
                    @foreach ($daysInfo as $day)
                        <th style="min-width: 28px;">
                            <div style="font-size: 8px;">{{ $day['day_name'] }}</div>
                            <div class="fw-bold">{{ $day['day'] }}</div>
                        </th>
                    @endforeach
                @else
                    @foreach ($monthsInfo as $m)
                        <th style="min-width: 60px;">{{ $m['name'] }}</th>
                    @endforeach
                @endif

                <th style="width: 30px;" title="Hadir">H</th>
                <th style="width: 30px;" title="Terlambat">T</th>
                <th style="width: 30px;" title="Sakit">S</th>
                <th style="width: 30px;" title="Izin">I</th>
                <th style="width: 30px;" title="Alpa">A</th>
                <th style="width: 45px;" title="Persentase">%</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recapStudents as $index => $st)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="font-monospace">{{ $st->nisn }}</td>
                    <td class="text-start ps-2 fw-semibold">{{ $st->name }}</td>
                    <td>{{ $st->gender }}</td>

                    @if ($period === 'weekly')
                        @foreach ($daysInfo as $day)
                            @php
                                $att = $st->matrix[$day['key']] ?? null;
                            @endphp
                            <td>
                                @if ($att)
                                    @if ($att->status === 'hadir')
                                        H ({{ substr($att->check_in_time, 0, 5) }})
                                    @elseif ($att->status === 'terlambat')
                                        T ({{ substr($att->check_in_time, 0, 5) }})
                                    @elseif ($att->status === 'sakit')
                                        Sakit
                                    @elseif ($att->status === 'izin')
                                        Izin
                                    @elseif ($att->status === 'alpa')
                                        Alpa
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                    @elseif ($period === 'monthly')
                        @foreach ($daysInfo as $day)
                            @php
                                $att = $st->matrix[$day['key']] ?? null;
                            @endphp
                            <td>
                                @if ($day['is_weekend'])
                                    -
                                @elseif ($att)
                                    @if ($att->status === 'hadir')
                                        H
                                    @elseif ($att->status === 'terlambat')
                                        T
                                    @elseif ($att->status === 'sakit')
                                        S
                                    @elseif ($att->status === 'izin')
                                        I
                                    @elseif ($att->status === 'alpa')
                                        A
                                    @endif
                                @else
                                    .
                                @endif
                            </td>
                        @endforeach
                    @else
                        @foreach ($monthsInfo as $m)
                            @php
                                $mCounts = $st->monthly_counts[$m['month_num']] ?? ['h' => 0, 't' => 0, 's' => 0, 'i' => 0, 'a' => 0, 'total' => 0];
                                $mHadir = $mCounts['h'] + $mCounts['t'];
                            @endphp
                            <td>
                                {{ $mCounts['total'] > 0 ? $mHadir : '-' }}
                            </td>
                        @endforeach
                    @endif

                    <td class="fw-bold">{{ $st->count_h }}</td>
                    <td>{{ $st->count_t }}</td>
                    <td>{{ $st->count_s }}</td>
                    <td>{{ $st->count_i }}</td>
                    <td>{{ $st->count_a }}</td>
                    <td class="fw-bold">{{ $st->presence_percent }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" class="py-3 text-muted">Belum ada data siswa di kelas ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Keterangan & Tanda Tangan -->
    <div class="row signature-box">
        <div class="col-6 small">
            <p class="mb-1 fw-bold">Keterangan Singkatan:</p>
            <ul class="list-unstyled mb-0 ps-1" style="font-size: 10px; line-height: 1.5;">
                <li><strong>H</strong> : Hadir Tepat Waktu</li>
                <li><strong>T</strong> : Terlambat</li>
                <li><strong>S</strong> : Sakit</li>
                <li><strong>I</strong> : Izin</li>
                <li><strong>A</strong> : Alpa</li>
            </ul>
        </div>
        <div class="col-6 text-end">
            <div class="d-inline-block text-center" style="min-width: 220px;">
                <p class="mb-1">Tondano, {{ now()->translatedFormat('d F Y') }}</p>
                <p class="mb-5">Wali Kelas,</p>
                <p class="fw-bold mb-0 text-decoration-underline">{{ $selectedClass->teacher?->user?->name ?? '................................................' }}</p>
                <p class="small text-muted mb-0">NIP. {{ $selectedClass->teacher?->nip ?? '.................................' }}</p>
            </div>
        </div>
    </div>
</body>
</html>
