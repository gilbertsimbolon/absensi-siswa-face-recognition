@extends('layouts.admin.app')

@section('title', 'Absensi Harian | SMAN 2 Tondano')

@section('content')
    <div class="d-flex flex-column flex-grow-1 h-100" style="min-height: 0; overflow: hidden;">
        <!-- Notifikasi Ringkas -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show flex-shrink-0 py-2 mb-2" role="alert">
                <i class="icon-base bx bx-check-circle me-1"></i>
                {{ session('success') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show flex-shrink-0 py-2 mb-2" role="alert">
                <i class="icon-base bx bx-error-circle me-1"></i>
                {{ session('error') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Header Halaman -->
        <div class="d-flex justify-content-between align-items-center mb-2 flex-shrink-0">
            <div>
                <h4 class="fw-bold mb-0">Absensi Harian</h4>
                <span class="text-muted small">
                    Kelas: <strong>{{ $selectedClass ? $selectedClass->name : '-' }}</strong> | 
                    Hari: <strong>{{ \Carbon\Carbon::parse($selectedDate)->locale('id')->translatedFormat('l, d F Y') }}</strong>
                </span>
            </div>
            <div class="d-flex gap-2">
                @if ($selectedClass)
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBulkMark">
                        <i class="icon-base bx bx-check-double me-1"></i> Tandai Massal
                    </button>
                @endif
                <a href="{{ route('admin.attendance.recap', ['kelas' => $selectedClass?->id]) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="icon-base bx bx-bar-chart-alt-2 me-1"></i> Rekap Kehadiran
                </a>
            </div>
        </div>

        <!-- Card Utama: Tab Kelas, Sub-bar Filter & Tabel Presensi Siswa -->
        <div class="card flex-grow-1 d-flex flex-column shadow-sm mb-0" style="min-height: 0; overflow: hidden;">
            <!-- 1. Tab Navigasi Kelas -->
            <div class="card-header border-bottom p-0 flex-shrink-0">
                <div class="d-flex align-items-center px-3 pt-2">
                    <ul class="nav nav-tabs card-header-tabs m-0 flex-nowrap" role="tablist" style="overflow-x: auto;">
                        @forelse ($classes as $cls)
                            <li class="nav-item">
                                <a class="nav-link {{ $selectedClass && $selectedClass->id == $cls->id ? 'active fw-bold' : '' }}"
                                    href="{{ route('admin.attendance.index', ['kelas' => $cls->id, 'tanggal' => $selectedDate, 'status' => $statusFilter, 'urutkan' => $sortBy, 'arah' => $sortDir]) }}">
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

            <!-- 2. Sub-bar Filter Terintegrasi & Ringkasan Teks Polos -->
            <div class="d-flex flex-wrap justify-content-between align-items-center px-3 py-2 border-bottom flex-shrink-0 gap-2 bg-white">
                <!-- Filter Form -->
                <form id="filterForm" action="{{ route('admin.attendance.index') }}" method="GET" class="d-flex align-items-center gap-2 m-0 flex-wrap">
                    <input type="hidden" name="kelas" value="{{ $selectedClass?->id }}">
                    <input type="hidden" name="urutkan" id="sortByInput" value="{{ $sortBy }}">
                    <input type="hidden" name="arah" id="sortDirInput" value="{{ $sortDir }}">

                    <div class="d-flex align-items-center gap-1">
                        <label class="form-label small mb-0 text-muted fw-semibold">Tanggal:</label>
                        <input type="date" name="tanggal" class="form-control form-control-sm" style="width: 135px;" value="{{ $selectedDate }}" onchange="document.getElementById('filterForm').submit();">
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <label class="form-label small mb-0 text-muted fw-semibold">Status:</label>
                        <select name="status" class="form-select form-select-sm" style="width: 125px;" onchange="document.getElementById('filterForm').submit();">
                            <option value="semua" {{ in_array($statusFilter, ['semua', 'all']) ? 'selected' : '' }}>Semua</option>
                            <option value="hadir" {{ $statusFilter === 'hadir' ? 'selected' : '' }}>Hadir</option>
                            <option value="terlambat" {{ $statusFilter === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                            <option value="sakit" {{ $statusFilter === 'sakit' ? 'selected' : '' }}>Sakit</option>
                            <option value="izin" {{ $statusFilter === 'izin' ? 'selected' : '' }}>Izin</option>
                            <option value="alpa" {{ $statusFilter === 'alpa' ? 'selected' : '' }}>Alpa</option>
                            <option value="belum" {{ $statusFilter === 'belum' ? 'selected' : '' }}>Belum Absen</option>
                        </select>
                    </div>
                </form>

                <!-- Ringkasan Teks Polos (Tanpa Background) -->
                <div class="d-flex align-items-center gap-3 text-secondary small flex-wrap">
                    <span>Total: <strong class="text-dark">{{ $summary['total'] }}</strong> Siswa</span>
                    <span class="text-muted">•</span>
                    <span>Hadir: <strong class="text-success">{{ $summary['hadir'] }}</strong></span>
                    <span class="text-muted">•</span>
                    <span>Terlambat: <strong class="text-warning">{{ $summary['terlambat'] }}</strong></span>
                    <span class="text-muted">•</span>
                    <span>Sakit: <strong class="text-info">{{ $summary['sakit'] }}</strong></span>
                    <span class="text-muted">•</span>
                    <span>Izin: <strong class="text-primary">{{ $summary['izin'] }}</strong></span>
                    <span class="text-muted">•</span>
                    <span>Alpa: <strong class="text-danger">{{ $summary['alpa'] }}</strong></span>
                    <span class="text-muted">•</span>
                    <span>Belum: <strong class="text-muted">{{ $summary['belum'] }}</strong></span>
                </div>
            </div>

            <!-- 3. Tabel Siswa (Scrollable Vertikal) -->
            <div class="table-responsive flex-grow-1" style="min-height: 0; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light sticky-top bg-light" style="z-index: 2;">
                        <tr>
                            <!-- Sorting NISN -->
                            <th style="width: 15%; cursor: pointer; user-select: none;" onclick="toggleDailySort('nisn')" title="Klik untuk mengurutkan NISN">
                                <div class="d-flex align-items-center gap-1">
                                    <span>NISN</span>
                                    <span class="d-inline-flex flex-column justify-content-center text-muted" style="line-height: 0.7;">
                                        <i class="bx bxs-chevron-up {{ $sortBy === 'nisn' && $sortDir === 'asc' ? 'text-primary' : 'opacity-25' }}" style="font-size: 11px;"></i>
                                        <i class="bx bxs-chevron-down {{ $sortBy === 'nisn' && $sortDir === 'desc' ? 'text-primary' : 'opacity-25' }}" style="font-size: 11px; margin-top: -3px;"></i>
                                    </span>
                                </div>
                            </th>

                            <!-- Sorting Nama Siswa -->
                            <th style="width: 25%; cursor: pointer; user-select: none;" onclick="toggleDailySort('nama')" title="Klik untuk mengurutkan Nama">
                                <div class="d-flex align-items-center gap-1">
                                    <span>Nama Siswa</span>
                                    <span class="d-inline-flex flex-column justify-content-center text-muted" style="line-height: 0.7;">
                                        <i class="bx bxs-chevron-up {{ in_array($sortBy, ['nama', 'name']) && $sortDir === 'asc' ? 'text-primary' : 'opacity-25' }}" style="font-size: 11px;"></i>
                                        <i class="bx bxs-chevron-down {{ in_array($sortBy, ['nama', 'name']) && $sortDir === 'desc' ? 'text-primary' : 'opacity-25' }}" style="font-size: 11px; margin-top: -3px;"></i>
                                    </span>
                                </div>
                            </th>

                            <th style="width: 8%;">L/P</th>
                            <th style="width: 12%;">Jam Masuk</th>
                            <th style="width: 14%;">Status</th>
                            <th style="width: 12%;">Metode</th>
                            <th style="width: 14%;">Keterangan</th>
                            <th style="width: 10%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($studentsData as $st)
                            @php
                                $att = $st->attendance;
                            @endphp
                            <tr>
                                <td class="font-monospace text-muted">{{ $st->nisn }}</td>
                                <td class="fw-medium text-dark">{{ $st->name }}</td>
                                <td class="text-muted">{{ $st->gender }}</td>
                                <td>
                                    @if ($att && $att->check_in_time)
                                        <span class="font-monospace text-dark">{{ substr($att->check_in_time, 0, 5) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($att)
                                        @if ($att->status === 'hadir')
                                            <span class="text-success fw-medium">Hadir</span>
                                        @elseif ($att->status === 'terlambat')
                                            <span class="text-warning fw-medium">Terlambat</span>
                                        @elseif ($att->status === 'sakit')
                                            <span class="text-info fw-medium">Sakit</span>
                                        @elseif ($att->status === 'izin')
                                            <span class="text-primary fw-medium">Izin</span>
                                        @elseif ($att->status === 'alpa')
                                            <span class="text-danger fw-medium">Alpa</span>
                                        @endif
                                    @else
                                        <span class="text-muted">Belum Absen</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($att)
                                        @if ($att->method === 'face_recognition')
                                            <span class="text-dark small"><i class="bx bx-camera me-1 text-muted"></i>Face AI</span>
                                        @else
                                            <span class="text-muted small"><i class="bx bx-edit me-1"></i>Manual</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block text-muted small" style="max-width: 150px;" title="{{ $att?->notes ?? '-' }}">
                                        {{ $att?->notes ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Tombol Catat / Ubah Presensi -->
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditPresensi{{ $st->id }}" 
                                                title="Ubah / Catat Presensi">
                                            <i class="bx bx-edit-alt"></i>
                                        </button>

                                        @if ($att)
                                            <!-- Tombol Reset / Hapus Presensi -->
                                            <form action="{{ route('admin.attendance.destroy', $att->id) }}" method="POST" onsubmit="return confirm('Reset presensi siswa ini?');" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Hapus Presensi">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    <!-- Modal Catat / Edit Presensi Per Siswa -->
                                    <div class="modal fade text-start" id="modalEditPresensi{{ $st->id }}" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form action="{{ route('admin.attendance.store') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="student_id" value="{{ $st->id }}">
                                                    <input type="hidden" name="date" value="{{ $selectedDate }}">

                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Presensi Siswa</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small text-muted">Nama Siswa</label>
                                                            <div class="fw-bold fs-6">{{ $st->name }} ({{ $st->nisn }})</div>
                                                            <div class="small text-muted">Kelas: {{ $selectedClass?->name }} | Tanggal: {{ $selectedDate }}</div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Status Presensi <span class="text-danger">*</span></label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="hadir" {{ ($att?->status ?? 'hadir') === 'hadir' ? 'selected' : '' }}>Hadir</option>
                                                                <option value="terlambat" {{ $att?->status === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                                                                <option value="sakit" {{ $att?->status === 'sakit' ? 'selected' : '' }}>Sakit</option>
                                                                <option value="izin" {{ $att?->status === 'izin' ? 'selected' : '' }}>Izin</option>
                                                                <option value="alpa" {{ $att?->status === 'alpa' ? 'selected' : '' }}>Alpa</option>
                                                            </select>
                                                        </div>

                                                        <div class="row g-2 mb-3">
                                                            <div class="col-6">
                                                                <label class="form-label fw-semibold small">Jam Masuk</label>
                                                                <input type="time" name="check_in_time" class="form-control" 
                                                                       value="{{ $att && $att->check_in_time ? substr($att->check_in_time, 0, 5) : now()->format('H:i') }}">
                                                            </div>
                                                            <div class="col-6">
                                                                <label class="form-label fw-semibold small">Jam Pulang (Opsional)</label>
                                                                <input type="time" name="check_out_time" class="form-control" 
                                                                       value="{{ $att && $att->check_out_time ? substr($att->check_out_time, 0, 5) : '' }}">
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small">Catatan / Alasan</label>
                                                            <textarea name="notes" rows="2" class="form-control" placeholder="Contoh: Surat dokter sakit demam">{{ $att?->notes ?? '' }}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary">Simpan Presensi</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bx bx-user-x fs-1 d-block mb-2 text-secondary"></i>
                                    @if (! $selectedClass)
                                        Silakan pilih kelas terlebih dahulu.
                                    @else
                                        Tidak ada siswa di kelas <strong>{{ $selectedClass->name }}</strong> yang sesuai dengan filter.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 4. Footer Card: Info Jumlah -->
            <div class="card-footer border-top py-2 px-3 bg-white flex-shrink-0 d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    Menampilkan <strong>{{ $studentsData->count() }}</strong> siswa di kelas <strong>{{ $selectedClass?->name ?? '-' }}</strong>
                </span>
                <span class="text-muted small">
                    Tahun Ajaran: <strong>{{ $activeYear ? $activeYear->name . ' - Semester ' . $activeYear->semester : '-' }}</strong>
                </span>
            </div>
        </div>
    </div>

    <!-- Modal Tandai Massal Siswa yang Belum Absen -->
    @if ($selectedClass)
        <div class="modal fade" id="modalBulkMark" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('admin.attendance.bulk') }}" method="POST">
                        @csrf
                        <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">
                        <input type="hidden" name="date" value="{{ $selectedDate }}">

                        <div class="modal-header">
                            <h5 class="modal-title">Tandai Presensi Massal</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">
                                Tandai seluruh siswa di kelas <strong>{{ $selectedClass->name }}</strong> yang <strong>belum memiliki catatan presensi</strong> pada tanggal <strong>{{ $selectedDate }}</strong>:
                            </p>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tandai Sebagai:</label>
                                <select name="target_status" class="form-select" required>
                                    <option value="alpa">Alpa (Tanpa Keterangan)</option>
                                    <option value="hadir">Hadir</option>
                                </select>
                            </div>
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="bx bx-info-circle me-1"></i> Siswa yang sudah memiliki catatan presensi (Hadir/Terlambat/Sakit/Izin) pada tanggal ini tidak akan diubah.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Terapkan Massal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script>
        function toggleDailySort(column) {
            const currentSortBy = '{{ in_array($sortBy, ["nama", "name"]) ? "nama" : $sortBy }}';
            const currentSortDir = '{{ $sortDir }}';
            let newDir = 'asc';

            if (currentSortBy === column) {
                newDir = currentSortDir === 'asc' ? 'desc' : 'asc';
            }

            document.getElementById('sortByInput').value = column;
            document.getElementById('sortDirInput').value = newDir;
            document.getElementById('filterForm').submit();
        }
    </script>
@endsection
