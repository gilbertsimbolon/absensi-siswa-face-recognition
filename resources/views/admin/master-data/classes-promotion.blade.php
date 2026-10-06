@extends('layouts.admin.app')

@section('title', 'Kenaikan Kelas Massal | SMAN 2 Tondano')

@section('content')
    <div class="d-flex flex-column flex-grow-1 h-100 overflow-y-auto content-scrollable pe-1 pb-4" style="min-height: 0;">
        <!-- Notifikasi -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="icon-base bx bx-check-circle me-1"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center mb-1">
                    <i class="icon-base bx bx-error-circle me-1"></i>
                    <strong>Terjadi kesalahan:</strong>
                </div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <a href="{{ route('admin.classes.index') }}" class="btn btn-sm btn-icon btn-outline-secondary">
                        <i class="icon-base bx bx-arrow-back"></i>
                    </a>
                    <h4 class="fw-bold mb-0">Kenaikan Kelas Massal</h4>
                </div>
                <span class="text-muted small">
                    Proses kenaikan jenjang kelas secara massal antar tahun ajaran dengan preservasi histori data penempatan dan absensi.
                </span>
            </div>
            <div>
                <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary">
                    <i class="icon-base bx bx-left-arrow-alt me-1"></i> Kembali ke Data Kelas
                </a>
            </div>
        </div>

        <!-- Filter & Pemilihan Tahun Ajaran / Kelas Asal -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.classes.promotion.preview') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tahun Ajaran Aktif Saat Ini</label>
                            <input type="text" class="form-control bg-lighter text-dark fw-bold" readonly
                                value="{{ $activeYear ? $activeYear->name . ' (' . $activeYear->semester . ')' : 'Belum Ditentukan' }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tahun Ajaran Tujuan (Baru) <span class="text-danger">*</span></label>
                            <select name="target_academic_year_id" class="form-select" required onchange="this.form.submit()">
                                @foreach ($academicYears as $year)
                                    @if ($year->id != $activeYear?->id)
                                        <option value="{{ $year->id }}" {{ $targetAcademicYear && $targetAcademicYear->id == $year->id ? 'selected' : '' }}>
                                            {{ $year->name }} ({{ $year->semester }})
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Filter Kelas Asal</label>
                            <select name="source_class_id" class="form-select" onchange="this.form.submit()">
                                <option value="all" {{ request('source_class_id') == 'all' || !request('source_class_id') ? 'selected' : '' }}>
                                    -- Semua Kelas --
                                </option>
                                @foreach ($classes as $cls)
                                    <option value="{{ $cls->id }}" {{ request('source_class_id') == $cls->id ? 'selected' : '' }}>
                                        [{{ $cls->grade_level }}] {{ $cls->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100" title="Terapkan Filter">
                                <i class="icon-base bx bx-filter-alt"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panduan & Peringatan Histori Data -->
        <div class="alert alert-primary d-flex align-items-center mb-4" role="alert">
            <i class="icon-base bx bx-info-circle fs-4 me-2 flex-shrink-0"></i>
            <div>
                <strong>Aturan Otomatis Kenaikan Kelas:</strong>
                <ul class="mb-0 ps-3 small mt-1">
                    <li><strong>Kelas X</strong> dipersiapkan naik ke kelas <strong>XI</strong>.</li>
                    <li><strong>Kelas XI</strong> dipersiapkan naik ke kelas <strong>XII</strong>.</li>
                    <li><strong>Kelas XII</strong> secara otomatis diset dengan status <strong>Lulus</strong> (tidak dipindahkan ke kelas lain).</li>
                    <li>Histori absensi dan kelas siswa pada tahun ajaran lama tetap <strong>100% tersimpan aman</strong> di database.</li>
                </ul>
            </div>
        </div>

        <!-- Form Preview & Eksekusi Kenaikan Kelas Massal -->
        @if ($previewData->isEmpty())
            <div class="card">
                <div class="card-body text-center py-5 text-muted">
                    <i class="icon-base bx bx-user-x display-4 text-secondary mb-2"></i>
                    <h5>Tidak Ada Siswa yang Ditemukan</h5>
                    <p class="mb-0">Tidak ada siswa aktif pada filter kelas yang dipilih.</p>
                </div>
            </div>
        @else
            <form id="formKenaikanKelas" method="POST" action="{{ route('admin.classes.promotion.process') }}">
                @csrf
                <input type="hidden" name="target_academic_year_id" value="{{ $targetAcademicYear?->id }}">

                <div class="card">
                    <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                        <div>
                            <h5 class="mb-0 fw-bold">Preview Kenaikan Kelas</h5>
                            <span class="text-muted small">Total: {{ $previewData->count() }} siswa ditemukan. Periksa status dan kelas tujuan sebelum mengeksekusi.</span>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="set_target_as_active" id="setTargetAsActive" value="1" checked>
                            <label class="form-check-label small fw-semibold" for="setTargetAsActive">
                                Jadikan Tahun Ajaran Tujuan sebagai Aktif
                            </label>
                        </div>
                    </div>

                    <div class="table-responsive text-nowrap">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 40px;">
                                        <input type="checkbox" class="form-check-input" id="checkAllPromotion" checked
                                            onclick="document.querySelectorAll('.promote-checkbox').forEach(c => c.checked = this.checked)">
                                    </th>
                                    <th class="text-center" style="width: 50px;">No.</th>
                                    <th style="width: 130px;">NISN</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas Saat Ini</th>
                                    <th style="width: 170px;">Status Kenaikan</th>
                                    <th style="width: 250px;">Kelas Tujuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($previewData as $index => $item)
                                    @php
                                        $s = $item['student'];
                                        $isGrade12 = $item['current_grade'] === 'XII';
                                    @endphp
                                    <tr>
                                        <!-- Checkbox Pilih Siswa -->
                                        <td class="text-center">
                                            <input type="checkbox" name="promotions[{{ $index }}][selected]" value="1"
                                                class="form-check-input promote-checkbox" checked>
                                            <input type="hidden" name="promotions[{{ $index }}][student_id]" value="{{ $s->id }}">
                                        </td>
                                        <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                        <td><span class="fw-semibold">{{ $s->nisn }}</span></td>
                                        <td><strong>{{ $s->name }}</strong></td>
                                        <td>
                                            <span class="badge bg-label-secondary">
                                                {{ $item['current_grade'] }} - {{ $item['current_class_name'] }}
                                            </span>
                                        </td>
                                        <!-- Pilihan Status Kenaikan -->
                                        <td>
                                            @if ($isGrade12)
                                                <input type="hidden" name="promotions[{{ $index }}][action]" value="lulus">
                                                <span class="badge bg-success fs-7">
                                                    <i class="icon-base bx bx-check-double me-1"></i> Lulus
                                                </span>
                                            @else
                                                <select name="promotions[{{ $index }}][action]" class="form-select form-select-sm action-select"
                                                    data-target-select="target-class-{{ $index }}">
                                                    <option value="naik" {{ $item['default_action'] === 'naik' ? 'selected' : '' }}>Naik Kelas</option>
                                                    <option value="tinggal" {{ $item['default_action'] === 'tinggal' ? 'selected' : '' }}>Tinggal Kelas</option>
                                                    <option value="lulus">Lulus</option>
                                                </select>
                                            @endif
                                        </td>
                                        <!-- Pilihan Kelas Tujuan -->
                                        <td>
                                            @if ($isGrade12)
                                                <span class="text-muted small fst-italic">- (Alumni / Lulus)</span>
                                                <input type="hidden" name="promotions[{{ $index }}][target_class_id]" value="">
                                            @else
                                                <select name="promotions[{{ $index }}][target_class_id]" id="target-class-{{ $index }}"
                                                    class="form-select form-select-sm target-class-select">
                                                    @if ($item['current_grade'] === 'X')
                                                        <optgroup label="Pilihan Kelas XI (Rekomendasi)">
                                                            @foreach ($classesByGrade['XI'] as $c)
                                                                <option value="{{ $c->id }}" {{ $c->id == $item['recommended_target_class_id'] ? 'selected' : '' }}>
                                                                    {{ $c->name }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                        <optgroup label="Kelas Lainnya">
                                                            @foreach ($classes as $c)
                                                                @if ($c->grade_level !== 'XI')
                                                                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->grade_level }})</option>
                                                                @endif
                                                            @endforeach
                                                        </optgroup>
                                                    @elseif ($item['current_grade'] === 'XI')
                                                        <optgroup label="Pilihan Kelas XII (Rekomendasi)">
                                                            @foreach ($classesByGrade['XII'] as $c)
                                                                <option value="{{ $c->id }}" {{ $c->id == $item['recommended_target_class_id'] ? 'selected' : '' }}>
                                                                    {{ $c->name }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                        <optgroup label="Kelas Lainnya">
                                                            @foreach ($classes as $c)
                                                                @if ($c->grade_level !== 'XII')
                                                                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->grade_level }})</option>
                                                                @endif
                                                            @endforeach
                                                        </optgroup>
                                                    @else
                                                        @foreach ($classes as $c)
                                                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->grade_level }})</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer & Tombol Aksi dengan Konfirmasi -->
                    <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 bg-light">
                        <div class="text-muted small">
                            <i class="icon-base bx bx-shield-quarter text-success me-1"></i>
                            Histori penempatan lama akan diarsipkan otomatis ke database.
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.classes.index') }}" class="btn btn-secondary">
                                Batal
                            </a>
                            <!-- Tombol Trigger Modal Konfirmasi (Butir 9: Jangan langsung mengeksekusi tanpa konfirmasi Admin) -->
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiKenaikan">
                                <i class="icon-base bx bx-check-circle me-1"></i> Jalankan Kenaikan Kelas Massal
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Konfirmasi Eksekusi Kenaikan Kelas (Butir 9) -->
                <div class="modal fade" id="modalKonfirmasiKenaikan" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title text-warning d-flex align-items-center gap-2">
                                    <i class="icon-base bx bx-error-circle fs-3"></i> Konfirmasi Kenaikan Kelas
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-3">
                                    Apakah Anda yakin ingin mengeksekusi proses kenaikan kelas ini?
                                </p>
                                <div class="p-3 rounded bg-lighter mb-3">
                                    <div class="d-flex justify-content-between mb-1 small">
                                        <span class="text-muted">Tahun Ajaran Tujuan:</span>
                                        <strong class="text-dark">{{ $targetAcademicYear?->name }} ({{ $targetAcademicYear?->semester }})</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 small">
                                        <span class="text-muted">Total Siswa Diproses:</span>
                                        <strong class="text-dark">{{ $previewData->count() }} Siswa</strong>
                                    </div>
                                </div>
                                <div class="alert alert-warning py-2 small mb-0">
                                    <i class="icon-base bx bx-info-circle me-1"></i>
                                    Data absensi tahun ajaran sebelumnya tidak akan terpengaruh karena kelas historis telah tersimpan.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kembali Periksa</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="icon-base bx bx-check me-1"></i> Ya, Eksekusi Sekarang
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @endif

    </div>

    <!-- Script Interaktif untuk Dropdown Status -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.action-select').forEach(function(select) {
                select.addEventListener('change', function() {
                    const targetSelectId = this.dataset.targetSelect;
                    const targetSelect = document.getElementById(targetSelectId);
                    if (targetSelect) {
                        if (this.value === 'lulus') {
                            targetSelect.disabled = true;
                        } else if (this.value === 'tinggal') {
                            targetSelect.disabled = true;
                        } else {
                            targetSelect.disabled = false;
                        }
                    }
                });
            });
        });
    </script>
@endsection

