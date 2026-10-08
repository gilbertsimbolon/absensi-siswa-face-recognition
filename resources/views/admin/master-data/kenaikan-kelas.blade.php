@extends('layouts.admin.app')

@section('title', 'Kenaikan Kelas | SMAN 2 Tondano')

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

        @php
            $currentClassTitle = $selectedSourceClass ? $selectedSourceClass->name : '-';
        @endphp

        <!-- Header Halaman -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-shrink-0">
            <div>
                <h4 class="fw-bold mb-0">Kenaikan Kelas</h4>
                <span class="text-muted small">Kelas: {{ $currentClassTitle }} (Semester {{ $activeYear ? $activeYear->semester : '-' }})</span>
            </div>
            <div>
                @if ($selectedSourceClass)
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiKenaikan" {{ $previewData->isEmpty() ? 'disabled' : '' }}>
                        <i class="icon-base bx bx-trending-up me-1"></i> Jalankan Kenaikan Kelas ({{ $selectedSourceClass->name }})
                    </button>
                @endif
            </div>
        </div>

        <!-- Card Utama: Tab Kelas & Tabel Siswa Kenaikan Kelas (Per Kelas) -->
        <div class="card flex-grow-1 d-flex flex-column shadow-sm mb-0" style="min-height: 0; overflow: hidden;">
            <!-- Tab Navigasi Kelas (Per Kelas) -->
            <div class="card-header border-bottom p-0 flex-shrink-0">
                <div class="d-flex align-items-center px-3 pt-2">
                    <ul class="nav nav-tabs card-header-tabs m-0 flex-nowrap" role="tablist" style="overflow-x: auto;">
                        @forelse ($classes as $cls)
                            <li class="nav-item">
                                <a class="nav-link {{ $sourceClassId == $cls->id ? 'active fw-bold' : '' }}"
                                    href="{{ route('admin.promotion.index', ['kelas_asal' => $cls->id]) }}">
                                    {{ $cls->name }}
                                </a>
                            </li>
                        @empty
                            <li class="nav-item">
                                <span class="nav-link text-muted">Tidak ada kelas yang dapat diakses</span>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <!-- Sub-Bar Detail Total Siswa & Fitur Urutkan -->
            <div class="d-flex flex-wrap justify-content-between align-items-center px-4 py-2 border-bottom flex-shrink-0 bg-white gap-2" style="font-size: 13.5px;">
                <div class="d-flex align-items-center gap-1">
                    <span class="text-muted">Total Siswa ({{ $selectedSourceClass?->name }}):</span>
                    <span class="fw-semibold text-dark">{{ $previewData->count() }} Orang</span>
                </div>

                @if ($previewData->isNotEmpty())
                    <div class="d-flex align-items-center gap-2">
                        <label for="sortPromotion" class="text-muted small mb-0 fw-medium text-nowrap">
                            <i class="icon-base bx bx-sort-alt-2 me-1"></i>Urutkan:
                        </label>
                        <select id="sortPromotion" class="form-select form-select-sm" style="width: 175px;" onchange="sortPromotionTable(this.value)">
                            <option value="name_asc" selected>Nama Siswa (A - Z)</option>
                            <option value="name_desc">Nama Siswa (Z - A)</option>
                            <option value="nisn_asc">NISN (Terkecil)</option>
                            <option value="nisn_desc">NISN (Terbesar)</option>
                        </select>
                    </div>
                @endif
            </div>

            <!-- Area Tabel & Konten -->
            @if ($previewData->isEmpty())
                <div class="d-flex flex-column align-items-center justify-content-center flex-grow-1 py-5 text-muted">
                    <i class="icon-base bx bx-user-x display-4 text-secondary mb-2"></i>
                    <h5 class="text-muted">Tidak Ada Siswa</h5>
                    <p class="mb-0 small">Tidak ditemukan siswa aktif pada kelas {{ $selectedSourceClass?->name ?? '' }}.</p>
                </div>
            @else
                <form id="formKenaikanKelas" method="POST" action="{{ route('admin.promotion.process') }}" class="d-flex flex-column flex-grow-1" style="min-height: 0; overflow: hidden;">
                    @csrf
                    <input type="hidden" name="source_class_id" value="{{ $selectedSourceClass?->id }}">
                    <input type="hidden" name="target_academic_year_id" value="{{ $targetAcademicYear?->id }}">

                    <div class="table-responsive flex-grow-1" style="overflow-y: auto;">
                        <table class="table table-striped table-hover align-middle table-sticky-header">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 40px;">
                                        <input type="checkbox" class="form-check-input" id="checkAllPromotion" checked
                                            onclick="document.querySelectorAll('.promote-checkbox').forEach(c => c.checked = this.checked); updateSelectedCount();">
                                    </th>
                                    <th style="width: 140px; cursor: pointer; user-select: none;" onclick="togglePromotionSort('nisn')" title="Klik untuk mengurutkan berdasarkan NISN">
                                        NISN <i id="sortIconNisn" class="bx bx-sort text-muted ms-1" style="font-size: 11px; vertical-align: middle;"></i>
                                    </th>
                                    <th style="cursor: pointer; user-select: none;" onclick="togglePromotionSort('name')" title="Klik untuk mengurutkan berdasarkan Nama">
                                        Nama Siswa <i id="sortIconName" class="bx bx-chevron-up text-primary ms-1" style="font-size: 11px; vertical-align: middle;"></i>
                                    </th>
                                    <th>Kelas Saat Ini</th>
                                    <th style="width: 170px;">Status Kenaikan</th>
                                    <th style="width: 250px;">Kelas Tujuan</th>
                                </tr>
                            </thead>
                            <tbody id="promotionTableBody">
                                @foreach ($previewData as $index => $item)
                                    @php
                                        $s = $item['student'];
                                        $isGrade12 = $item['current_grade'] === 'XII';
                                    @endphp
                                    <tr data-name="{{ strtolower($s->name) }}" data-nisn="{{ $s->nisn }}">
                                        <!-- Checkbox Pilih Siswa -->
                                        <td class="text-center">
                                            <input type="checkbox" name="promotions[{{ $index }}][selected]" value="1"
                                                class="form-check-input promote-checkbox" checked>
                                            <input type="hidden" name="promotions[{{ $index }}][student_id]" value="{{ $s->id }}">
                                        </td>
                                        <td><span class="fw-semibold">{{ $s->nisn }}</span></td>
                                        <td><strong>{{ $s->name }}</strong></td>
                                        <td>
                                            <span class="badge bg-label-secondary">
                                                {{ $item['current_class_name'] }}
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

                    <!-- Footer Card (Tanpa tombol ganda, cukup tombol di header atas) -->
                    <div class="card-footer d-flex align-items-center bg-white flex-shrink-0 py-2">
                        <div class="text-muted small">
                            <i class="icon-base bx bx-shield-quarter text-success me-1"></i>
                            Histori penempatan siswa lama tersimpan aman di database.
                        </div>
                    </div>
                </form>
            @endif
        </div>

        <!-- Modal Konfirmasi Eksekusi Kenaikan Kelas -->
        <div class="modal fade" id="modalKonfirmasiKenaikan" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title d-flex align-items-center gap-2">
                            <i class="icon-base bx bx-help-circle text-primary fs-4"></i> Konfirmasi Kenaikan Kelas
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            Apakah Anda yakin ingin memproses kenaikan kelas untuk siswa yang dipilih?
                        </p>
                        <div class="p-3 rounded bg-light mb-3" style="font-size: 13.5px;">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Kelas yang Diproses:</span>
                                <strong class="text-dark">{{ $selectedSourceClass?->name ?? '-' }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Total Siswa Diproses:</span>
                                <strong class="text-dark" id="modalTotalSelectedSiswa">{{ $previewData->count() }} Siswa</strong>
                            </div>
                        </div>
                        <div class="alert alert-info py-2 small mb-0">
                            <i class="icon-base bx bx-info-circle me-1"></i>
                            Histori absensi dan penempatan kelas tahun sebelumnya tetap tersimpan utuh di database.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" onclick="submitPromotionForm();">
                            <i class="icon-base bx bx-check me-1"></i> Ya, Jalankan Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Interaktif untuk Dropdown Status, Dynamic Counter, Auto Scroll Tab, & Fitur Urutkan -->
    <script>
        let currentSortField = 'name';
        let currentSortOrder = 'asc';

        function sortPromotionTable(criteria) {
            const tbody = document.getElementById('promotionTableBody');
            if (!tbody) return;

            const parts = criteria.split('_');
            const field = parts[0];
            const order = parts[1] || 'asc';
            currentSortField = field;
            currentSortOrder = order;

            const rows = Array.from(tbody.querySelectorAll('tr'));
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

            const sortSelect = document.getElementById('sortPromotion');
            if (sortSelect && sortSelect.value !== criteria) {
                sortSelect.value = criteria;
            }

            updateSortIcons();
        }

        function togglePromotionSort(field) {
            if (currentSortField === field) {
                currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortField = field;
                currentSortOrder = 'asc';
            }
            sortPromotionTable(`${currentSortField}_${currentSortOrder}`);
        }

        function updateSortIcons() {
            const iconName = document.getElementById('sortIconName');
            const iconNisn = document.getElementById('sortIconNisn');

            if (iconName) {
                if (currentSortField === 'name') {
                    iconName.className = `bx ${currentSortOrder === 'asc' ? 'bx-chevron-up' : 'bx-chevron-down'} text-primary ms-1`;
                } else {
                    iconName.className = 'bx bx-sort text-muted ms-1';
                }
                iconName.style.fontSize = '11px';
                iconName.style.verticalAlign = 'middle';
            }

            if (iconNisn) {
                if (currentSortField === 'nisn') {
                    iconNisn.className = `bx ${currentSortOrder === 'asc' ? 'bx-chevron-up' : 'bx-chevron-down'} text-primary ms-1`;
                } else {
                    iconNisn.className = 'bx bx-sort text-muted ms-1';
                }
                iconNisn.style.fontSize = '11px';
                iconNisn.style.verticalAlign = 'middle';
            }
        }

        function updateSelectedCount() {
            const checkedBoxes = document.querySelectorAll('.promote-checkbox:checked');
            const countSpan = document.getElementById('modalTotalSelectedSiswa');
            if (countSpan) {
                countSpan.textContent = checkedBoxes.length + ' Siswa';
            }
        }

        function submitPromotionForm() {
            const form = document.getElementById('formKenaikanKelas');
            if (!form) return;

            const checkedBoxes = document.querySelectorAll('.promote-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Silakan pilih minimal 1 siswa untuk diproses kenaikan kelasnya.');
                return;
            }

            form.submit();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Auto scroll ke tab aktif
            const activeTab = document.querySelector('.card-header-tabs .nav-link.active');
            if (activeTab) {
                activeTab.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });
            }

            // Update modal count when modal is shown
            const modal = document.getElementById('modalKonfirmasiKenaikan');
            if (modal) {
                modal.addEventListener('show.bs.modal', updateSelectedCount);
            }

            // Listener checkbox per siswa
            document.querySelectorAll('.promote-checkbox').forEach(function(cb) {
                cb.addEventListener('change', updateSelectedCount);
            });

            // Dropdown aksi disable/enable target class
            document.querySelectorAll('.action-select').forEach(function(select) {
                select.addEventListener('change', function() {
                    const targetSelectId = this.dataset.targetSelect;
                    const targetSelect = document.getElementById(targetSelectId);
                    if (targetSelect) {
                        if (this.value === 'lulus' || this.value === 'tinggal') {
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

