@extends('layouts.admin.app')

@section('title', 'Data Siswa | SMAN 2 Tondano')

@section('content')
    <div class="d-flex flex-column flex-grow-1 h-100" style="min-height: 0; overflow: hidden;">
        <!-- Notifikasi -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show flex-shrink-0" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show flex-shrink-0" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-3 flex-shrink-0">
            <h4 class="fw-bold mb-0">Data Siswa</h4>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahSiswa">
                <i class="icon-base bx bx-plus me-1"></i> Tambah Data Siswa
            </button>
        </div>

        <!-- Tabel Data Siswa -->
        <div class="card flex-grow-1 d-flex flex-column shadow-sm mb-0" style="min-height: 0; overflow: hidden;">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 py-3 flex-shrink-0">
                <h5 class="mb-0">Daftar Siswa</h5>

                <!-- Filter & Pencarian -->
                <form method="GET" action="{{ route('admin.student.index') }}" class="d-flex align-items-center gap-2 m-0 flex-nowrap">
                    <!-- Filter Kelas -->
                    <select name="class_id" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                        <option value="">Semua Kelas</option>
                        @foreach ($classes as $kelas)
                            <option value="{{ $kelas->id }}" {{ request('class_id') == $kelas->id ? 'selected' : '' }}>
                                {{ $kelas->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Input Search -->
                    <div class="input-group input-group-sm" style="width: 220px;">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama / NISN..."
                            value="{{ request('search') }}">
                        <button class="btn btn-secondary" type="submit" title="Cari">
                            <i class="icon-base bx bx-search"></i>
                        </button>
                    </div>

                    @if (request()->hasAny(['search', 'class_id']) && (request('search') || request('class_id')))
                        <a href="{{ route('admin.student.index') }}" class="btn btn-sm btn-secondary" title="Reset Filter">
                            <i class="icon-base bx bx-reset"></i>
                        </a>
                    @endif
                </form>
            </div>

            <div class="table-responsive flex-grow-1" style="overflow-y: auto; min-height: 0;">
                <table class="table table-striped align-middle mb-0 table-sticky-header">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 4%;">No.</th>
                            <th class="text-center" style="width: 6%;">Foto</th>
                            <th style="width: 11%;">NISN</th>
                            <th style="width: 18%;">Nama Siswa</th>
                            <th style="width: 10%;">Kelas</th>
                            <th class="text-center" style="width: 5%;">L/P</th>
                            <th style="width: 12%;">No. WA Siswa</th>
                            <th style="width: 14%;">Orang Tua</th>
                            <th style="width: 12%;">No. WA Ortu</th>
                            <th class="text-center" style="width: 8%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse ($students as $siswa)
                            <tr>
                                <td class="text-center text-nowrap">{{ $students->firstItem() + $loop->index }}</td>
                                <td class="text-center text-nowrap">
                                    @php
                                        $primaryFace = $siswa->faces->first();
                                    @endphp
                                    @if ($primaryFace)
                                        <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#modalDetailFotoSiswa{{ $siswa->id }}" title="Lihat Foto Wajah">
                                            <img src="{{ asset('storage/' . $primaryFace->file_path) }}" alt="{{ $siswa->name }}"
                                                class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $siswa->nisn }}</td>
                                <td><strong>{{ $siswa->name }}</strong></td>
                                <td class="text-nowrap">{{ $siswa->classes ? $siswa->classes->name : '-' }}</td>
                                <td class="text-center text-nowrap">
                                    <span class="badge {{ $siswa->gender == 'L' ? 'bg-label-info' : 'bg-label-danger' }}">
                                        {{ $siswa->gender }}
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    @if ($siswa->phone)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siswa->phone) }}" target="_blank" class="text-body text-decoration-none">
                                            {{ $siswa->phone }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $siswa->parent_name ?: '-' }}</td>
                                <td class="text-nowrap">
                                    @if ($siswa->parent_phone)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siswa->parent_phone) }}" target="_blank" class="text-body text-decoration-none">
                                            {{ $siswa->parent_phone }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <!-- Tombol Detail Foto Modal (Hijau) -->
                                        <button type="button" class="btn btn-sm btn-icon p-0 text-success"
                                            data-bs-toggle="modal" data-bs-target="#modalDetailFotoSiswa{{ $siswa->id }}"
                                            title="Detail Foto">
                                            <i class="icon-base bx bx-image fs-5"></i>
                                        </button>

                                        <!-- Tombol Edit Modal (Kuning) -->
                                        <button type="button" class="btn btn-sm btn-icon p-0 text-warning"
                                            data-bs-toggle="modal" data-bs-target="#modalUbahSiswa{{ $siswa->id }}"
                                            title="Edit Data Siswa">
                                            <i class="icon-base bx bx-edit-alt fs-5"></i>
                                        </button>

                                        <!-- Tombol Hapus Modal (Merah) -->
                                        <button type="button" class="btn btn-sm btn-icon p-0 text-danger"
                                            data-bs-toggle="modal" data-bs-target="#modalHapusSiswa{{ $siswa->id }}"
                                            title="Hapus Siswa">
                                            <i class="icon-base bx bx-trash fs-5"></i>
                                        </button>
                                    </div>

                                    <!-- Include Modal Detail Foto Siswa -->
                                    @include('admin.master-data.siswa.modal-detail-foto', ['siswa' => $siswa])

                                    <!-- Include Modal Edit Siswa -->
                                    @include('admin.master-data.siswa.modal-ubah', ['siswa' => $siswa, 'classes' => $classes])

                                    <!-- Include Modal Hapus Siswa -->
                                    @include('admin.master-data.siswa.modal-hapus', ['siswa' => $siswa])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">Belum ada data siswa.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center py-2 gap-2 flex-shrink-0">
                <small class="text-muted">
                    Menampilkan {{ $students->firstItem() ?? 0 }} sampai {{ $students->lastItem() ?? 0 }} dari {{ $students->total() }} siswa
                </small>
                <div>
                    {{ $students->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modal Tambah Siswa -->
    @include('admin.master-data.siswa.modal-tambah', ['classes' => $classes])
@endsection
