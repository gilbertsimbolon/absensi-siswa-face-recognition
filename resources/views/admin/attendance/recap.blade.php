@extends('layouts.admin.app')

@section('title', 'Rekapitulasi Kehadiran | SMAN 2 Tondano')

@section('content')
    <style>
        .recap-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
            background-color: #ffffff;
        }
        .recap-table th, .recap-table td {
            border-right: 1px solid #e7e7e8;
            border-bottom: 1px solid #e7e7e8;
            vertical-align: middle;
            text-align: center;
            padding: 8px 10px;
            background-color: #ffffff;
        }
        .recap-table thead th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa !important;
            z-index: 5;
            font-weight: 600;
            color: #495057;
            border-top: 1px solid #e7e7e8;
        }
        /* Sticky Kolom NISN */
        .recap-table .sticky-col-nisn {
            position: sticky !important;
            left: 0 !important;
            width: 130px !important;
            min-width: 130px !important;
            max-width: 130px !important;
            text-align: left !important;
            padding-left: 14px !important;
            background-color: #ffffff !important;
            z-index: 10 !important;
        }
        /* Sticky Kolom Nama Siswa */
        .recap-table .sticky-col-name {
            position: sticky !important;
            left: 130px !important;
            width: 200px !important;
            min-width: 200px !important;
            max-width: 200px !important;
            text-align: left !important;
            padding-left: 12px !important;
            background-color: #ffffff !important;
            z-index: 10 !important;
            box-shadow: 3px 0 6px rgba(0, 0, 0, 0.08) !important;
        }
        /* Top-Left Corner: Header NISN & Header Nama Siswa (Sticky Top & Left) */
        .recap-table thead th.sticky-col-nisn {
            position: sticky !important;
            top: 0 !important;
            left: 0 !important;
            background-color: #f8f9fa !important;
            z-index: 30 !important;
        }
        .recap-table thead th.sticky-col-name {
            position: sticky !important;
            top: 0 !important;
            left: 130px !important;
            background-color: #f8f9fa !important;
            z-index: 30 !important;
            box-shadow: 3px 0 6px rgba(0, 0, 0, 0.08) !important;
        }
        .recap-table tbody tr:hover td.sticky-col-nisn,
        .recap-table tbody tr:hover td.sticky-col-name {
            background-color: #f5f5f9 !important;
        }
        .period-btn {
            padding: 4px 12px;
            font-size: 12px;
            border-radius: 4px;
            border: 1px solid #d9dee3;
            background: #fff;
            color: #566a7f;
            text-decoration: none;
        }
        .period-btn.active {
            background: #696cff;
            color: #fff;
            border-color: #696cff;
            font-weight: 600;
        }
        .col-today {
            background-color: #f4f5ff !important;
        }
    </style>

    <div class="d-flex flex-column flex-grow-1 h-100" style="min-height: 0; overflow: hidden;">
        <!-- Header Halaman -->
        <div class="d-flex justify-content-between align-items-center mb-2 flex-shrink-0">
            <div>
                <h4 class="fw-bold mb-0">Rekapitulasi Kehadiran</h4>
                <span class="text-muted small">
                    Kelas: <strong>{{ $selectedClass ? $selectedClass->name : '-' }}</strong> | 
                    @if ($period === 'weekly')
                        Minggu Berjalan: <strong>{{ $weekStart ? $weekStart->locale('id')->translatedFormat('d M Y') : '' }} s/d {{ $weekEnd ? $weekEnd->locale('id')->translatedFormat('d M Y') : '' }}</strong>
                    @elseif ($period === 'monthly')
                        Periode: <strong>{{ $monthNames[$selectedMonth] ?? '' }} {{ $selectedYear }}</strong>
                    @else
                        Periode: <strong>Tahun {{ $selectedYear }} (1 Tahun Penuh)</strong>
                    @endif
                </span>
            </div>
            <div class="d-flex gap-2">
                @if ($selectedClass)
                    <a href="{{ route('admin.attendance.print', ['class_id' => $selectedClass->id, 'period' => $period, 'date' => $selectedDate, 'month' => $selectedMonth, 'year' => $selectedYear]) }}" 
                       target="_blank" 
                       class="btn btn-outline-secondary btn-sm">
                        <i class="icon-base bx bx-printer me-1"></i> Cetak Rekap
                    </a>
                @endif
            </div>
        </div>

        <!-- Card Utama: Tab Kelas, Sub-bar Filter Periode & Tabel -->
        <div class="card flex-grow-1 d-flex flex-column shadow-sm mb-0" style="min-height: 0; overflow: hidden;">
            <!-- 1. Tab Navigasi Kelas -->
            <div class="card-header border-bottom p-0 flex-shrink-0">
                <div class="d-flex align-items-center px-3 pt-2">
                    <ul class="nav nav-tabs card-header-tabs m-0 flex-nowrap" role="tablist" style="overflow-x: auto;">
                        @forelse ($classes as $cls)
                            <li class="nav-item">
                                <a class="nav-link {{ $selectedClass && $selectedClass->id == $cls->id ? 'active fw-bold' : '' }}"
                                    href="{{ route('admin.attendance.recap', ['class_id' => $cls->id, 'period' => $period, 'date' => $selectedDate, 'month' => $selectedMonth, 'year' => $selectedYear]) }}">
                                    {{ $cls->name }}
                                </a>
                            </li>
                        @empty
                            <li class="nav-item">
                                <span class="nav-link text-muted">Belum ada kelas</span>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <!-- 2. Sub-bar Filter (Digeser ke Kanan, Keterangan Siswa Dihapus) -->
            <div class="d-flex flex-wrap justify-content-between align-items-center px-3 py-2 border-bottom flex-shrink-0 gap-2 bg-white">
                <div class="d-flex align-items-center text-muted small">
                    <span>Kelas: <strong class="text-dark">{{ $selectedClass ? $selectedClass->name : '-' }}</strong></span>
                    <span class="mx-2">•</span>
                    <span>Total Siswa: <strong class="text-dark">{{ $recapStudents->count() }}</strong></span>
                </div>

                <!-- Pilihan Mode Periode & Navigasi Tanggal (di sebelah kanan) -->
                <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                    <div class="d-inline-flex gap-1">
                        <a href="{{ route('admin.attendance.recap', ['class_id' => $selectedClass?->id, 'period' => 'weekly', 'date' => now()->format('Y-m-d')]) }}" 
                           class="period-btn {{ $period === 'weekly' ? 'active' : '' }}">
                            Minggu Ini
                        </a>
                        <a href="{{ route('admin.attendance.recap', ['class_id' => $selectedClass?->id, 'period' => 'monthly', 'month' => now()->month, 'year' => now()->year]) }}" 
                           class="period-btn {{ $period === 'monthly' ? 'active' : '' }}">
                            Bulanan
                        </a>
                        <a href="{{ route('admin.attendance.recap', ['class_id' => $selectedClass?->id, 'period' => 'yearly', 'year' => now()->year]) }}" 
                           class="period-btn {{ $period === 'yearly' ? 'active' : '' }}">
                            1 Tahun
                        </a>
                    </div>

                    <!-- Filter Spesifik per Mode -->
                    <form action="{{ route('admin.attendance.recap') }}" method="GET" class="d-flex align-items-center gap-2 m-0 ms-2">
                        <input type="hidden" name="class_id" value="{{ $selectedClass?->id }}">
                        <input type="hidden" name="period" value="{{ $period }}">

                        @if ($period === 'weekly')
                            <!-- Navigasi Minggu -->
                            @php
                                $prevWeek = \Carbon\Carbon::parse($selectedDate)->subWeek()->format('Y-m-d');
                                $nextWeek = \Carbon\Carbon::parse($selectedDate)->addWeek()->format('Y-m-d');
                            @endphp
                            <div class="d-flex align-items-center gap-1">
                                <a href="{{ route('admin.attendance.recap', ['class_id' => $selectedClass?->id, 'period' => 'weekly', 'date' => $prevWeek]) }}" 
                                   class="btn btn-sm btn-outline-secondary py-1 px-2" title="Minggu Sebelumnya">
                                    <i class="bx bx-chevron-left"></i>
                                </a>
                                <input type="date" name="date" class="form-control form-control-sm" style="width: 135px;" value="{{ $selectedDate }}" onchange="this.form.submit()">
                                <a href="{{ route('admin.attendance.recap', ['class_id' => $selectedClass?->id, 'period' => 'weekly', 'date' => $nextWeek]) }}" 
                                   class="btn btn-sm btn-outline-secondary py-1 px-2" title="Minggu Berikutnya">
                                    <i class="bx bx-chevron-right"></i>
                                </a>
                            </div>
                        @elseif ($period === 'monthly')
                            <div class="d-flex align-items-center gap-1">
                                <select name="month" class="form-select form-select-sm" style="width: 125px;" onchange="this.form.submit()">
                                    @foreach ($monthNames as $num => $name)
                                        <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                <select name="year" class="form-select form-select-sm" style="width: 85px;" onchange="this.form.submit()">
                                    @for ($y = now()->year - 2; $y <= now()->year + 1; $y++)
                                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        @else
                            <div class="d-flex align-items-center gap-1">
                                <select name="year" class="form-select form-select-sm" style="width: 95px;" onchange="this.form.submit()">
                                    @for ($y = now()->year - 2; $y <= now()->year + 1; $y++)
                                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            <!-- 3. Tabel Rekapitulasi (Scrollable) -->
            <div class="table-responsive flex-grow-1" style="min-height: 0; overflow: auto;">
                <table class="table mb-0 recap-table" style="{{ $period === 'monthly' ? 'min-width: 1700px;' : ($period === 'yearly' ? 'min-width: 1300px;' : 'min-width: 100%;') }}">
                    <thead>
                        <tr>
                            <!-- Kolom Sticky Kiri -->
                            <th class="sticky-col-nisn">NISN</th>
                            <th class="sticky-col-name">Nama Siswa</th>
                            <th style="width: 45px; min-width: 45px;">L/P</th>

                            @if ($period === 'weekly')
                                <!-- Kolom Hari Lengkap (Senin s/d Sabtu) -->
                                @foreach ($daysInfo as $day)
                                    <th style="min-width: 130px; width: 14%;" class="{{ $day['is_today'] ? 'col-today' : '' }}">
                                        <div class="fw-bold {{ $day['is_today'] ? 'text-primary' : 'text-dark' }}">
                                            {{ $day['day_name'] }}
                                        </div>
                                        <div class="small text-muted mt-1" style="font-weight: normal;">
                                            {{ $day['short_label'] }}
                                        </div>
                                    </th>
                                @endforeach
                            @elseif ($period === 'monthly')
                                <!-- Kolom Tanggal Bulanan (Nama Hari Lengkap di Baris Atas) -->
                                @foreach ($daysInfo as $day)
                                    <th style="min-width: 90px;" class="{{ $day['is_today'] ? 'col-today' : '' }}">
                                        <div class="fw-bold {{ $day['is_weekend'] ? 'text-danger' : ($day['is_today'] ? 'text-primary' : 'text-dark') }}">
                                            {{ $day['day_name'] }}
                                        </div>
                                        <div class="small text-muted mt-1" style="font-weight: normal;">
                                            {{ $day['day'] }} {{ $monthNames[$selectedMonth] ?? '' }}
                                        </div>
                                    </th>
                                @endforeach
                            @else
                                <!-- Kolom 12 Bulan (1 Tahun Penuh) -->
                                @foreach ($monthsInfo as $m)
                                    <th style="min-width: 80px;">
                                        <div class="fw-bold text-dark">{{ $m['name'] }}</div>
                                    </th>
                                @endforeach
                            @endif

                            <!-- Kolom Total Polos -->
                            <th style="width: 40px; min-width: 40px;" title="Total Hadir">H</th>
                            <th style="width: 40px; min-width: 40px;" title="Total Terlambat">T</th>
                            <th style="width: 40px; min-width: 40px;" title="Total Sakit">S</th>
                            <th style="width: 40px; min-width: 40px;" title="Total Izin">I</th>
                            <th style="width: 40px; min-width: 40px;" title="Total Alpa">A</th>
                            <th style="width: 60px; min-width: 60px;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recapStudents as $st)
                            <tr>
                                <!-- Kolom Sticky NISN & Nama Polos -->
                                <td class="sticky-col-nisn font-monospace text-muted small">
                                    {{ $st->nisn }}
                                </td>
                                <td class="sticky-col-name text-dark fw-medium text-truncate" title="{{ $st->name }}">
                                    {{ $st->name }}
                                </td>
                                <td class="text-muted">
                                    {{ $st->gender }}
                                </td>

                                @if ($period === 'weekly')
                                    <!-- Sel Presensi Mingguan Polos -->
                                    @foreach ($daysInfo as $day)
                                        @php
                                            $att = $st->matrix[$day['key']] ?? null;
                                        @endphp
                                        <td class="{{ $day['is_today'] ? 'col-today' : '' }}" style="font-size: 12px;">
                                            @if ($att)
                                                @if ($att->status === 'hadir')
                                                    <span class="text-success fw-medium">Hadir</span>
                                                    <div class="text-muted" style="font-size: 11px;">{{ substr($att->check_in_time, 0, 5) }}</div>
                                                @elseif ($att->status === 'terlambat')
                                                    <span class="text-warning fw-medium">Terlambat</span>
                                                    <div class="text-muted" style="font-size: 11px;">{{ substr($att->check_in_time, 0, 5) }}</div>
                                                @elseif ($att->status === 'sakit')
                                                    <span class="text-info fw-medium">Sakit</span>
                                                @elseif ($att->status === 'izin')
                                                    <span class="text-primary fw-medium">Izin</span>
                                                @elseif ($att->status === 'alpa')
                                                    <span class="text-danger fw-medium">Alpa</span>
                                                @endif
                                            @elseif ($day['is_past'])
                                                <span class="text-muted">-</span>
                                            @else
                                                <span class="text-muted opacity-50">•</span>
                                            @endif
                                        </td>
                                    @endforeach
                                @elseif ($period === 'monthly')
                                    <!-- Sel Presensi Bulanan Polos -->
                                    @foreach ($daysInfo as $day)
                                        @php
                                            $att = $st->matrix[$day['key']] ?? null;
                                        @endphp
                                        <td class="{{ $day['is_today'] ? 'col-today' : '' }}" style="font-size: 12px;">
                                            @if ($day['is_weekend'])
                                                <span class="text-muted" style="font-size: 11px;">Libur</span>
                                            @elseif ($att)
                                                @if ($att->status === 'hadir')
                                                    <span class="text-success fw-bold">H</span>
                                                @elseif ($att->status === 'terlambat')
                                                    <span class="text-warning fw-bold">T</span>
                                                @elseif ($att->status === 'sakit')
                                                    <span class="text-info fw-bold">S</span>
                                                @elseif ($att->status === 'izin')
                                                    <span class="text-primary fw-bold">I</span>
                                                @elseif ($att->status === 'alpa')
                                                    <span class="text-danger fw-bold">A</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                @else
                                    <!-- Sel Presensi Tahunan Polos (Hadir / Total per Bulan) -->
                                    @foreach ($monthsInfo as $m)
                                        @php
                                            $mCounts = $st->monthly_counts[$m['month_num']] ?? ['h' => 0, 't' => 0, 's' => 0, 'i' => 0, 'a' => 0, 'total' => 0];
                                            $mHadir = $mCounts['h'] + $mCounts['t'];
                                        @endphp
                                        <td style="font-size: 12px;">
                                            @if ($mCounts['total'] > 0)
                                                <div class="fw-semibold text-dark">{{ $mHadir }} <span class="text-muted fw-normal" style="font-size: 11px;">hari</span></div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                @endif

                                <!-- Rekap Total Angka Polos -->
                                <td class="fw-bold text-success">{{ $st->count_h }}</td>
                                <td class="fw-bold text-warning">{{ $st->count_t }}</td>
                                <td class="fw-bold text-info">{{ $st->count_s }}</td>
                                <td class="fw-bold text-primary">{{ $st->count_i }}</td>
                                <td class="fw-bold text-danger">{{ $st->count_a }}</td>
                                <td class="fw-bold text-dark">{{ $st->presence_percent }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="text-center py-5 text-muted">
                                    <i class="bx bx-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                                    @if (! $selectedClass)
                                        Silakan pilih kelas terlebih dahulu.
                                    @else
                                        Belum ada data siswa di kelas <strong>{{ $selectedClass->name }}</strong>.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 4. Footer Card: Keterangan Polos -->
            <div class="card-footer border-top py-2 px-3 bg-white flex-shrink-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-3 flex-wrap text-muted" style="font-size: 12px;">
                    <span class="fw-semibold text-dark">Keterangan:</span>
                    <span><strong class="text-success">H</strong> : Hadir</span>
                    <span><strong class="text-warning">T</strong> : Terlambat</span>
                    <span><strong class="text-info">S</strong> : Sakit</span>
                    <span><strong class="text-primary">I</strong> : Izin</span>
                    <span><strong class="text-danger">A</strong> : Alpa</span>
                </div>
                <div class="text-muted small">
                    Total Siswa: <strong class="text-dark">{{ $recapStudents->count() }}</strong>
                </div>
            </div>
        </div>
    </div>
@endsection
