@extends('layouts.admin.app')

@section('title', 'Data Siswa | SMAN 2 Tondano')

@section('content')
    <div class="mt-0">
        <!-- Notifikasi -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Master Data Siswa</h4>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahSiswa">
                <i class="icon-base bx bx-plus me-1"></i> Tambah Data Siswa
            </button>
        </div>

        <!-- Tabel Data Siswa -->
        <div class="card">
            <h5 class="card-header">Daftar Siswa</h5>
            <div class="table-responsive text-nowrap">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">No.</th>
                            <th class="text-center" style="width: 80px;">Foto Siswa</th>
                            <th>Nama Siswa</th>
                            <th>NISN</th>
                            <th>Kelas</th>
                            <th>L/P</th>
                            <th>WhatsApp Siswa</th>
                            <th>Orang Tua / Wali</th>
                            <th>WhatsApp Orang Tua</th>
                            <th class="text-center" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse ($students as $siswa)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td class="text-center">
                                    @php
                                        $primaryFace = $siswa->faces->first();
                                    @endphp
                                    @if ($primaryFace)
                                        <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#modalDetailFotoSiswa{{ $siswa->id }}" title="Lihat sampel foto">
                                            <img src="{{ asset('storage/' . $primaryFace->file_path) }}" alt="{{ $siswa->name }}"
                                                class="rounded-circle border" style="width: 40px; height: 40px; object-fit: cover;">
                                        </a>
                                    @else
                                        <span class="badge bg-label-warning p-1" style="font-size: 10px;">Belum Ada</span>
                                    @endif
                                </td>
                                <td><strong>{{ $siswa->name }}</strong></td>
                                <td><span class="badge bg-label-dark">{{ $siswa->nisn }}</span></td>
                                <td>
                                    @if ($siswa->classes)
                                        <span class="badge bg-label-primary">{{ $siswa->classes->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $siswa->gender }}</td>
                                <td>
                                    @if ($siswa->phone)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siswa->phone) }}" target="_blank" class="text-success text-decoration-none">
                                            <i class="icon-base bx bxl-whatsapp me-1"></i>{{ $siswa->phone }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $siswa->parent_name ?: '-' }}</td>
                                <td>
                                    @if ($siswa->parent_phone)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siswa->parent_phone) }}" target="_blank" class="text-success text-decoration-none">
                                            <i class="icon-base bx bxl-whatsapp me-1"></i>{{ $siswa->parent_phone }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center align-items-center gap-1">
                                        <!-- Tombol Detail Foto Modal -->
                                        <button type="button" class="btn btn-sm btn-icon p-1 text-secondary"
                                            data-bs-toggle="modal" data-bs-target="#modalDetailFotoSiswa{{ $siswa->id }}"
                                            data-bs-placement="top" title="Lihat Foto Wajah">
                                            <i class="icon-base bx bx-image fs-5"></i>
                                        </button>

                                        <!-- Tombol Edit Modal -->
                                        <button type="button" class="btn btn-sm btn-icon p-1 text-secondary"
                                            data-bs-toggle="modal" data-bs-target="#modalUbahSiswa{{ $siswa->id }}"
                                            data-bs-placement="top" title="Edit Data Siswa">
                                            <i class="icon-base bx bx-edit-alt fs-5"></i>
                                        </button>

                                        <!-- Tombol Hapus Modal -->
                                        <button type="button" class="btn btn-sm btn-icon p-1 text-secondary"
                                            data-bs-toggle="modal" data-bs-target="#modalHapusSiswa{{ $siswa->id }}"
                                            data-bs-placement="top" title="Hapus Siswa">
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
        </div>
    </div>

    <!-- Include Modal Tambah Siswa -->
    @include('admin.master-data.siswa.modal-tambah', ['classes' => $classes])

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endsection
