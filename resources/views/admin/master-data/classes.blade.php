@extends('layouts.admin.app')

@section('title', 'Data Kelas | SMAN 2 Tondano')

@section('content')
    <div class="d-flex flex-column flex-grow-1 h-100" style="min-height: 0; overflow: hidden;">
        <!-- Notifikasi -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show flex-shrink-0" role="alert">
                <i class="icon-base bx bx-check-circle me-1"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show flex-shrink-0" role="alert">
                <i class="icon-base bx bx-error-circle me-1"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show flex-shrink-0" role="alert">
                <i class="icon-base bx bx-error-circle me-1"></i>
                Gagal
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Header Halaman -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-shrink-0">
            <div>
                <h4 class="fw-bold mb-0">Data Kelas</h4>
                <span class="text-muted small">Kelas: {{ $selectedClass ? $selectedClass->name : 'Semua Kelas' }} (Semester {{ $activeYear->semester }})</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if (auth()->user()?->hasRole('admin'))
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahKelas">
                        <i class="icon-base bx bx-plus me-1"></i> Tambah Data Kelas
                    </button>
                @endif
            </div>
        </div>

        <!-- Card Utama Berisi Tab Kelas & Tabel Siswa -->
        <div class="card flex-grow-1 d-flex flex-column shadow-sm mb-0" style="min-height: 0; overflow: hidden;">
            <!-- Tab Navigasi Kelas -->
            <div class="card-header border-bottom p-0 flex-shrink-0">
                <div class="d-flex align-items-center px-3 pt-2">
                    <ul class="nav nav-tabs card-header-tabs m-0 flex-nowrap" role="tablist" style="overflow-x: auto;">
                        @forelse ($classes as $cls)
                            <li class="nav-item">
                                <a class="nav-link {{ $selectedClass && $selectedClass->id == $cls->id ? 'active fw-bold' : '' }}"
                                    href="{{ route('admin.classes.index', ['class_id' => $cls->id]) }}">
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

            @if ($selectedClass)
                <!-- Bar Detail & Aksi Kelas Terpilih -->
                <div class="d-flex flex-wrap justify-content-between align-items-center px-4 py-2 border-bottom flex-shrink-0 gap-2 bg-white">
                    <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size: 13.5px;">
                        <!-- 1. Wali Kelas paling kiri (tanpa tombol edit disini, cukup di pengaturan) -->
                        <div class="d-flex align-items-center gap-1">
                            <span class="text-muted">Wali Kelas:</span>
                            <span class="fw-semibold text-dark">{{ $selectedClass->teacher ? $selectedClass->teacher->user->name : 'Belum ditentukan' }}</span>
                        </div>

                        <span class="text-muted opacity-50">•</span>

                        <!-- 2. Tingkat (tanpa teks berlatar belakang, ukuran font stabil) -->
                        <div class="d-flex align-items-center gap-1">
                            <span class="text-muted">Tingkat:</span>
                            <span class="fw-semibold text-dark">Kelas {{ $selectedClass->grade_level }}</span>
                        </div>

                        <span class="text-muted opacity-50">•</span>

                        <!-- 3. Jumlah Siswa (ukuran font stabil) -->
                        <div class="d-flex align-items-center gap-1">
                            <span class="text-muted">Jumlah Siswa:</span>
                            <span class="fw-semibold text-dark">{{ $students->count() }} Orang</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <!-- Fitur Urutkan Siswa -->
                        <div class="d-none d-md-flex align-items-center gap-1">
                            <select id="sortClassStudents" class="form-select form-select-sm" style="width: 155px;" onchange="sortClassStudentsTable(this.value)">
                                <option value="name_asc" selected>Nama (A - Z)</option>
                                <option value="name_desc">Nama (Z - A)</option>
                                <option value="nisn_asc">NISN (Terkecil)</option>
                                <option value="nisn_desc">NISN (Terbesar)</option>
                            </select>
                        </div>

                        <!-- Pencarian Siswa -->
                        <form method="GET" action="{{ route('admin.classes.index') }}" class="d-flex align-items-center m-0">
                            <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">
                            <div class="input-group input-group-sm" style="width: 180px;">
                                <input type="text" name="search_student" class="form-control" placeholder="Cari siswa..."
                                    value="{{ request('search_student') }}">
                                <button class="btn btn-secondary" type="submit" title="Cari">
                                    <i class="icon-base bx bx-search"></i>
                                </button>
                            </div>
                            @if (request('search_student'))
                                <a href="{{ route('admin.classes.index', ['class_id' => $selectedClass->id]) }}" class="btn btn-sm btn-secondary ms-1" title="Reset">
                                    <i class="icon-base bx bx-reset"></i>
                                </a>
                            @endif
                        </form>

                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahSiswa">
                            <i class="icon-base bx bx-user-plus me-1"></i> Masukkan Siswa
                        </button>

                        <a href="{{ route('admin.promotion.index', ['source_class_id' => $selectedClass->id]) }}" class="btn btn-sm btn-outline-primary" title="Kenaikan Kelas {{ $selectedClass->name }}">
                            <i class="icon-base bx bx-trending-up me-1"></i> Kenaikan Kelas
                        </a>

                        @if (auth()->user()?->hasRole('admin'))
                            <div class="d-inline-flex align-items-center gap-1 ms-1">
                                <button type="button" class="btn btn-sm btn-icon p-0 text-warning" data-bs-toggle="modal" data-bs-target="#modalEditKelas" title="Pengaturan Kelas">
                                    <i class="icon-base bx bx-cog fs-5"></i>
                                </button>

                                <form action="{{ route('admin.classes.destroy', $selectedClass->id) }}" method="POST" class="m-0"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelas {{ $selectedClass->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon p-0 text-danger" title="Hapus Kelas">
                                        <i class="icon-base bx bx-trash fs-5"></i>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Tabel Siswa di Kelas Terpilih -->
                <div class="table-responsive flex-grow-1" style="overflow-y: auto; min-height: 0;">
                    <table class="table table-striped align-middle mb-0 table-sticky-header">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 7%;">Foto</th>
                                <th style="width: 15%; cursor: pointer; user-select: none;" onclick="toggleClassStudentsSort('nisn')" title="Klik untuk mengurutkan NISN">
                                    NISN <i id="classSortIconNisn" class="bx bx-sort text-muted ms-1" style="font-size: 11px; vertical-align: middle;"></i>
                                </th>
                                <th style="width: 25%; cursor: pointer; user-select: none;" onclick="toggleClassStudentsSort('name')" title="Klik untuk mengurutkan Nama">
                                    Nama Siswa <i id="classSortIconName" class="bx bx-chevron-up text-primary ms-1" style="font-size: 11px; vertical-align: middle;"></i>
                                </th>
                                <th class="text-center" style="width: 6%;">L/P</th>
                                <th style="width: 17%;">No. WA Siswa</th>
                                <th style="width: 19%;">Nama Orang Tua</th>
                                <th class="text-center" style="width: 11%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="classStudentsTableBody" class="table-border-bottom-0">
                            @forelse ($students as $siswa)
                                @php
                                    $primaryFace = $siswa->faces->firstWhere('face_position', 'depan') ?? $siswa->faces->first();
                                @endphp
                                <tr data-name="{{ strtolower($siswa->name) }}" data-nisn="{{ $siswa->nisn }}">
                                    <td class="text-center text-nowrap">
                                        @if ($primaryFace)
                                            <img src="{{ asset('storage/' . $primaryFace->file_path) }}" alt="{{ $siswa->name }}"
                                                class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
                                        @else
                                            <div class="avatar avatar-sm">
                                                <span class="avatar-initial rounded-circle bg-label-secondary">
                                                    {{ strtoupper(substr($siswa->name, 0, 1)) }}
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $siswa->nisn }}</td>
                                    <td><strong>{{ $siswa->name }}</strong></td>
                                    <td class="text-center text-nowrap">
                                        <span class="badge {{ $siswa->gender == 'L' ? 'bg-label-info' : 'bg-label-danger' }}">
                                            {{ $siswa->gender }}
                                        </span>
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($siswa->phone)
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siswa->phone) }}" target="_blank" class="text-body text-decoration-none">
                                                <i class="icon-base bx bxl-whatsapp text-success me-1"></i>{{ $siswa->phone }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $siswa->parent_name ?: '-' }}</td>
                                    <td class="text-center text-nowrap">
                                        <form action="{{ route('admin.classes.students.remove', [$selectedClass->id, $siswa->id]) }}" method="POST"
                                            onsubmit="return confirm('Apakah Anda yakin ingin mengeluarkan {{ $siswa->name }} dari kelas ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-icon p-0 text-danger" title="Keluarkan dari kelas">
                                                <i class="icon-base bx bx-trash fs-6"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        Belum ada siswa yang terdaftar di kelas {{ $selectedClass->name }}. Silakan klik tombol <strong>Masukkan Siswa</strong> untuk menambahkan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="d-flex flex-column align-items-center justify-content-center flex-grow-1 py-5 text-muted">
                    <p class="mb-2">Belum ada kelas yang dibuat.</p>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahKelas">
                        <i class="icon-base bx bx-plus me-1"></i> Tambah Data Kelas Sekarang
                    </button>
                </div>
            @endif
        </div>

        <!-- ==================== MODALS ==================== -->

        @if (auth()->user()?->hasRole('admin'))
        <!-- Modal Tambah Kelas -->
        <div class="modal fade" id="modalTambahKelas" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Data Kelas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('admin.classes.store') }}">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="grade_level">Tingkat Kelas</label>
                                <select class="form-select" id="grade_level" name="grade_level" required>
                                    <option value="" disabled selected>Pilih Tingkat</option>
                                    <option value="X">Kelas X (10)</option>
                                    <option value="XI">Kelas XI (11)</option>
                                    <option value="XII">Kelas XII (12)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="name">Nama Kelas</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    placeholder="Contoh: X MIPA 1 atau XI IPS 2" required />
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="teacher_id">Wali Kelas (Opsional)</label>
                                <select class="form-select" id="teacher_id" name="teacher_id">
                                    <option value="">-- Tanpa Wali Kelas --</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->user->name }} (NIP: {{ $teacher->nip ?: '-' }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-success">Simpan Data Kelas</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        @if ($selectedClass)
            @if (auth()->user()?->hasRole('admin'))
            <!-- Modal Edit Kelas -->
            <div class="modal fade" id="modalEditKelas" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Pengaturan Data Kelas</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="{{ route('admin.classes.update', $selectedClass->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Tingkat Kelas</label>
                                    <select class="form-select" name="grade_level" required>
                                        <option value="X" {{ $selectedClass->grade_level == 'X' ? 'selected' : '' }}>Kelas X (10)</option>
                                        <option value="XI" {{ $selectedClass->grade_level == 'XI' ? 'selected' : '' }}>Kelas XI (11)</option>
                                        <option value="XII" {{ $selectedClass->grade_level == 'XII' ? 'selected' : '' }}>Kelas XII (12)</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nama Kelas</label>
                                    <input type="text" class="form-control" name="name"
                                        value="{{ $selectedClass->name }}" required />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Wali Kelas</label>
                                    <select class="form-select" name="teacher_id">
                                        <option value="">-- Tanpa Wali Kelas --</option>
                                        @foreach ($teachers as $teacher)
                                            <option value="{{ $teacher->id }}" {{ $selectedClass->teacher_id == $teacher->id ? 'selected' : '' }}>
                                                {{ $teacher->user->name }} (NIP: {{ $teacher->nip ?: '-' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            <!-- Modal Tambah Siswa ke Kelas -->
            <div class="modal fade" id="modalTambahSiswa" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Masukkan Siswa ke {{ $selectedClass->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="{{ route('admin.classes.students.add', $selectedClass->id) }}">
                            @csrf
                            <div class="modal-body">
                                @if ($availableStudents->isEmpty())
                                    <div class="alert alert-info py-2 mb-0">
                                        Semua siswa aktif sudah terdaftar di kelas ini atau tidak ada data siswa lainnya.
                                    </div>
                                @else
                                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                        <table class="table table-sm table-hover align-middle">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th style="width: 40px;" class="text-center">
                                                        <input type="checkbox" class="form-check-input" id="checkAllStudents"
                                                            onclick="document.querySelectorAll('.student-checkbox').forEach(c => c.checked = this.checked)">
                                                    </th>
                                                    <th>NISN</th>
                                                    <th>Nama Siswa</th>
                                                    <th>Kelas Saat Ini</th>
                                                    <th class="text-center">L/P</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($availableStudents as $avail)
                                                    <tr>
                                                        <td class="text-center">
                                                            <input type="checkbox" name="student_ids[]" value="{{ $avail->id }}"
                                                                class="form-check-input student-checkbox">
                                                        </td>
                                                        <td>{{ $avail->nisn }}</td>
                                                        <td><strong>{{ $avail->name }}</strong></td>
                                                        <td>
                                                            @if ($avail->classes)
                                                                <span class="badge bg-label-secondary">{{ $avail->classes->name }}</span>
                                                            @else
                                                                <span class="badge bg-label-warning">Belum ada kelas</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge {{ $avail->gender == 'L' ? 'bg-label-info' : 'bg-label-danger' }}">
                                                                {{ $avail->gender }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                <button type="submit" class="btn btn-primary" {{ $availableStudents->isEmpty() ? 'disabled' : '' }}>
                                    Tambahkan Siswa
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Script Interaktif untuk Fitur Urutkan Data Siswa Kelas -->
    <script>
        let classCurrentSortField = 'name';
        let classCurrentSortOrder = 'asc';

        function sortClassStudentsTable(criteria) {
            const tbody = document.getElementById('classStudentsTableBody');
            if (!tbody) return;

            const parts = criteria.split('_');
            const field = parts[0];
            const order = parts[1] || 'asc';
            classCurrentSortField = field;
            classCurrentSortOrder = order;

            const rows = Array.from(tbody.querySelectorAll('tr[data-name]'));
            if (rows.length === 0) return;

            rows.sort((a, b) => {
                const valA = field === 'name' ? (a.dataset.name || '') : (a.dataset.nisn || '');
                const valB = field === 'name' ? (b.dataset.name || '') : (b.dataset.nisn || '');

                if (field === 'nisn') {
                    return order === 'asc'
                        ? valA.localeCompare(valB, undefined, { numeric: true })
                        : valB.localeCompare(valA, undefined, { numeric: true });
                } else {
                    return order === 'asc'
                        ? valA.localeCompare(valB)
                        : valB.localeCompare(valA);
                }
            });

            rows.forEach(r => tbody.appendChild(r));

            const sortSelect = document.getElementById('sortClassStudents');
            if (sortSelect && sortSelect.value !== criteria) {
                sortSelect.value = criteria;
            }

            updateClassSortIcons();
        }

        function toggleClassStudentsSort(field) {
            if (classCurrentSortField === field) {
                classCurrentSortOrder = classCurrentSortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                classCurrentSortField = field;
                classCurrentSortOrder = 'asc';
            }
            sortClassStudentsTable(`${classCurrentSortField}_${classCurrentSortOrder}`);
        }

        function updateClassSortIcons() {
            const iconName = document.getElementById('classSortIconName');
            const iconNisn = document.getElementById('classSortIconNisn');

            if (iconName) {
                if (classCurrentSortField === 'name') {
                    iconName.className = `bx ${classCurrentSortOrder === 'asc' ? 'bx-chevron-up' : 'bx-chevron-down'} text-primary ms-1`;
                } else {
                    iconName.className = 'bx bx-sort text-muted ms-1';
                }
                iconName.style.fontSize = '11px';
                iconName.style.verticalAlign = 'middle';
            }

            if (iconNisn) {
                if (classCurrentSortField === 'nisn') {
                    iconNisn.className = `bx ${classCurrentSortOrder === 'asc' ? 'bx-chevron-up' : 'bx-chevron-down'} text-primary ms-1`;
                } else {
                    iconNisn.className = 'bx bx-sort text-muted ms-1';
                }
                iconNisn.style.fontSize = '11px';
                iconNisn.style.verticalAlign = 'middle';
            }
        }
    </script>
@endsection

