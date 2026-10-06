@extends('layouts.admin.app')

@section('title', 'Data Guru | SMAN 2 Tondano')

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
            <h4 class="fw-bold mb-0">Data Guru</h4>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahDataGuru">
                <i class="icon-base bx bx-plus me-1"></i> Tambah Data Guru
            </button>
        </div>

        <!-- Modal Tambah Guru -->
        <div class="modal fade" id="modalTambahDataGuru" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content" style="white-space: normal;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Tambah Data Guru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('admin.teacher.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body text-start">
                            <div class="d-flex align-items-center mb-3">
                                <span class="text-uppercase small fw-semibold text-muted me-2">Akun & Identitas</span>
                                <hr class="flex-grow-1 m-0">
                            </div>

                            <div class="row">
                                <!-- Input Nama -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="tambah_name">Nama Lengkap</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="icon-base bx bx-user"></i></span>
                                        <input type="text" class="form-control" id="tambah_name" name="name"
                                            placeholder="Nama beserta gelar" value="{{ old('name') }}" required />
                                    </div>
                                </div>

                                <!-- Input NIP -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="tambah_nip">Nomor Induk Pegawai (NIP)</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="icon-base bx bx-id-card"></i></span>
                                        <input type="text" class="form-control" id="tambah_nip" name="nip"
                                            placeholder="19840606..." value="{{ old('nip') }}" required />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Input Email -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="tambah_email">Email</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="icon-base bx bx-envelope"></i></span>
                                        <input type="email" id="tambah_email" class="form-control" name="email"
                                            placeholder="nama@smandutdo.com" value="{{ old('email') }}" required />
                                    </div>
                                </div>

                                <!-- Input Password -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="tambah_password">Password</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="icon-base bx bx-key"></i></span>
                                        <input type="password" id="tambah_password" class="form-control" name="password"
                                            placeholder="Minimal 8 karakter" required />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Input No. Handphone -->
                                <div class="col-12 mb-3">
                                    <label class="form-label" for="tambah_phone">No. Telp / WhatsApp</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="icon-base bx bx-phone"></i></span>
                                        <input type="text" id="tambah_phone" name="phone" class="form-control"
                                            placeholder="0812 3456 7890" value="{{ old('phone') }}" required />
                                    </div>
                                </div>
                            </div>

                            <!-- Garis Pemisah Sampel Foto Wajah -->
                            <div class="d-flex align-items-center mb-3 mt-2">
                                <span class="text-uppercase small fw-semibold text-muted me-2">Sampel Foto Wajah (3 Sudut Pengambilan)</span>
                                <hr class="flex-grow-1 m-0">
                            </div>

                            <div class="row">
                                <!-- Foto Tampak Depan -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">1. Tampak Depan</label>
                                    <input type="file" class="form-control form-control-sm" name="photo_depan" accept="image/*" />
                                    <small class="text-muted d-block mt-1">Wajah menghadap lurus ke kamera</small>
                                </div>

                                <!-- Foto Serong Kanan -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">2. Serong Kanan</label>
                                    <input type="file" class="form-control form-control-sm" name="photo_kanan" accept="image/*" />
                                    <small class="text-muted d-block mt-1">Wajah condong ke arah kanan</small>
                                </div>

                                <!-- Foto Serong Kiri -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">3. Serong Kiri</label>
                                    <input type="file" class="form-control form-control-sm" name="photo_kiri" accept="image/*" />
                                    <small class="text-muted d-block mt-1">Wajah condong ke arah kiri</small>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-success">Simpan Data Guru</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabel Data Guru -->
        <div class="card">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 py-3">
                <h5 class="mb-0">Daftar Guru</h5>

                <!-- Filter & Pencarian -->
                <form method="GET" action="{{ route('admin.teacher.index') }}" class="d-flex align-items-center gap-2 m-0 flex-nowrap">
                    <div class="input-group input-group-sm" style="width: 240px;">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama, NIP, atau email..."
                            value="{{ request('search') }}">
                        <button class="btn btn-secondary" type="submit" title="Cari">
                            <i class="icon-base bx bx-search"></i>
                        </button>
                    </div>

                    @if (request()->filled('search'))
                        <a href="{{ route('admin.teacher.index') }}" class="btn btn-sm btn-secondary" title="Reset Filter">
                            <i class="icon-base bx bx-reset"></i>
                        </a>
                    @endif
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 4%;">No.</th>
                            <th class="text-center" style="width: 6%;">Foto</th>
                            <th style="width: 14%;">NIP</th>
                            <th style="width: 20%;">Nama</th>
                            <th style="width: 18%;">Email</th>
                            <th style="width: 12%;">Wali Kelas</th>
                            <th style="width: 14%;">No. Handphone</th>
                            <th class="text-center" style="width: 12%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse ($teachers as $teacher)
                            <tr>
                                <td class="text-center text-nowrap">{{ $teachers->firstItem() + $loop->index }}</td>
                                <td class="text-center text-nowrap">
                                    @php
                                        $primaryFace = $teacher->faces->first();
                                    @endphp
                                    @if ($primaryFace)
                                        <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#modalDetailFotoGuru{{ $teacher->id }}" title="Lihat Foto Wajah">
                                            <img src="{{ asset('storage/' . $primaryFace->file_path) }}" alt="{{ $teacher->user->name }}"
                                                class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $teacher->nip }}</td>
                                <td><strong>{{ $teacher->user->name }}</strong></td>
                                <td class="text-nowrap">{{ $teacher->user->email }}</td>
                                <td class="text-nowrap">{{ $teacher->classes ? $teacher->classes->name : '-' }}</td>
                                <td class="text-nowrap">
                                    @if ($teacher->phone)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $teacher->phone) }}" target="_blank" class="text-body text-decoration-none">
                                            {{ $teacher->phone }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <!-- Tombol Detail Foto Modal (Hijau) -->
                                        <button type="button" class="btn btn-sm btn-icon p-0 text-success"
                                            data-bs-toggle="modal" data-bs-target="#modalDetailFotoGuru{{ $teacher->id }}"
                                            title="Detail Foto">
                                            <i class="icon-base bx bx-image fs-5"></i>
                                        </button>

                                        <!-- Tombol Edit Modal (Kuning) -->
                                        <button type="button" class="btn btn-sm btn-icon p-0 text-warning"
                                            data-bs-toggle="modal" data-bs-target="#modalEditGuru{{ $teacher->id }}"
                                            title="Edit Data Guru">
                                            <i class="icon-base bx bx-edit-alt fs-5"></i>
                                        </button>

                                        <!-- Tombol Hapus Modal (Merah) -->
                                        <button type="button" class="btn btn-sm btn-icon p-0 text-danger"
                                            data-bs-toggle="modal" data-bs-target="#modalHapusGuru{{ $teacher->id }}"
                                            title="Hapus Guru">
                                            <i class="icon-base bx bx-trash fs-5"></i>
                                        </button>
                                    </div>

                                    <!-- Modal Detail Foto Guru -->
                                    <div class="modal fade" id="modalDetailFotoGuru{{ $teacher->id }}" data-bs-backdrop="static" tabindex="-1" aria-labelledby="judulDetailFotoGuru{{ $teacher->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content" style="white-space: normal;">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="judulDetailFotoGuru{{ $teacher->id }}">
                                                        Foto Wajah Guru - {{ $teacher->user->name }}
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    @if ($teacher->faces->count() > 0)
                                                        <div class="row">
                                                            @foreach ($teacher->faces as $face)
                                                                <div class="col-md-4 mb-3 text-center">
                                                                    <a href="{{ asset('storage/' . $face->file_path) }}" target="_blank" class="d-block mb-2">
                                                                        <img src="{{ asset('storage/' . $face->file_path) }}" alt="{{ $face->label }}" class="img-fluid rounded" style="max-height: 220px; object-fit: cover;">
                                                                    </a>
                                                                    <span class="d-block text-muted small">{{ $face->label ?: 'Sampel #' . $loop->iteration }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <div class="text-center py-4 text-muted">
                                                            <p class="mb-0">Belum ada sampel foto wajah untuk guru ini.</p>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Edit Guru -->
                                    <div class="modal fade" id="modalEditGuru{{ $teacher->id }}" data-bs-backdrop="static" tabindex="-1" aria-labelledby="judulEditGuru{{ $teacher->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg text-start">
                                            <div class="modal-content" style="white-space: normal;">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="judulEditGuru{{ $teacher->id }}">Edit Data Guru</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('admin.teacher.update', $teacher->id) }}" enctype="multipart/form-data">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="d-flex align-items-center mb-3">
                                                            <span class="text-uppercase small fw-semibold text-muted me-2">Akun & Identitas</span>
                                                            <hr class="flex-grow-1 m-0">
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Nama Lengkap</label>
                                                                <input type="text" class="form-control" name="name"
                                                                    value="{{ old('name', $teacher->user->name) }}" required />
                                                            </div>

                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">NIP</label>
                                                                <input type="text" class="form-control" name="nip"
                                                                    value="{{ old('nip', $teacher->nip) }}" required />
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Email</label>
                                                                <input type="email" class="form-control" name="email"
                                                                    value="{{ old('email', $teacher->user->email) }}" required />
                                                            </div>

                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Password Baru (Kosongkan jika tidak diubah)</label>
                                                                <input type="password" class="form-control" name="password"
                                                                    placeholder="Kosongkan jika tetap" />
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12 mb-3">
                                                                <label class="form-label">No. Handphone</label>
                                                                <input type="text" class="form-control" name="phone"
                                                                    value="{{ old('phone', $teacher->phone) }}" required />
                                                            </div>
                                                        </div>

                                                        <!-- Garis Pemisah Dataset Foto Wajah (3 Sampel) -->
                                                        <div class="d-flex align-items-center mb-3 mt-2">
                                                            <span class="text-uppercase small fw-semibold text-muted me-2">Pembaruan Sampel Foto Wajah (Opsional)</span>
                                                            <hr class="flex-grow-1 m-0">
                                                        </div>

                                                        @php
                                                            $faceDepan = $teacher->faces->firstWhere('label', 'Tampak Depan') ?? $teacher->faces->get(0);
                                                            $faceKanan = $teacher->faces->firstWhere('label', 'Serong Kanan') ?? $teacher->faces->get(1);
                                                            $faceKiri = $teacher->faces->firstWhere('label', 'Serong Kiri') ?? $teacher->faces->get(2);
                                                        @endphp

                                                        <div class="row">
                                                            <!-- Foto Tampak Depan -->
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label fw-semibold d-block">1. Tampak Depan</label>
                                                                <div class="mb-2 text-center">
                                                                    @if ($faceDepan)
                                                                        <img src="{{ asset('storage/' . $faceDepan->file_path) }}" alt="Tampak Depan"
                                                                            class="rounded border" style="width: 100px; height: 100px; object-fit: cover;">
                                                                    @else
                                                                        <div class="d-flex align-items-center justify-content-center rounded border bg-light mx-auto" style="width: 100px; height: 100px;">
                                                                            <span class="text-muted small">Belum ada</span>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <input type="file" class="form-control form-control-sm" name="photo_depan" accept="image/*" />
                                                                <small class="text-muted d-block mt-1 text-center">Biarkan kosong jika tetap</small>
                                                            </div>

                                                            <!-- Foto Serong Kanan -->
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label fw-semibold d-block">2. Serong Kanan</label>
                                                                <div class="mb-2 text-center">
                                                                    @if ($faceKanan)
                                                                        <img src="{{ asset('storage/' . $faceKanan->file_path) }}" alt="Serong Kanan"
                                                                            class="rounded border" style="width: 100px; height: 100px; object-fit: cover;">
                                                                    @else
                                                                        <div class="d-flex align-items-center justify-content-center rounded border bg-light mx-auto" style="width: 100px; height: 100px;">
                                                                            <span class="text-muted small">Belum ada</span>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <input type="file" class="form-control form-control-sm" name="photo_kanan" accept="image/*" />
                                                                <small class="text-muted d-block mt-1 text-center">Biarkan kosong jika tetap</small>
                                                            </div>

                                                            <!-- Foto Serong Kiri -->
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label fw-semibold d-block">3. Serong Kiri</label>
                                                                <div class="mb-2 text-center">
                                                                    @if ($faceKiri)
                                                                        <img src="{{ asset('storage/' . $faceKiri->file_path) }}" alt="Serong Kiri"
                                                                            class="rounded border" style="width: 100px; height: 100px; object-fit: cover;">
                                                                    @else
                                                                        <div class="d-flex align-items-center justify-content-center rounded border bg-light mx-auto" style="width: 100px; height: 100px;">
                                                                            <span class="text-muted small">Belum ada</span>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <input type="file" class="form-control form-control-sm" name="photo_kiri" accept="image/*" />
                                                                <small class="text-muted d-block mt-1 text-center">Biarkan kosong jika tetap</small>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                        <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Hapus Guru -->
                                    <div class="modal fade" id="modalHapusGuru{{ $teacher->id }}" data-bs-backdrop="static" tabindex="-1" aria-labelledby="judulHapusGuru{{ $teacher->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content" style="white-space: normal;">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="judulHapusGuru{{ $teacher->id }}">Hapus Data Guru</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-center pt-2">
                                                    <!-- 1. Foto Guru di Bagian Atas -->
                                                    <div class="mb-3">
                                                        @if ($primaryFace)
                                                            <img src="{{ asset('storage/' . $primaryFace->file_path) }}" alt="{{ $teacher->user->name }}"
                                                                class="rounded-circle border" style="width: 85px; height: 85px; object-fit: cover;">
                                                        @else
                                                            <div class="rounded-circle border d-inline-flex align-items-center justify-content-center" style="width: 85px; height: 85px;">
                                                                <i class="icon-base bx bx-user fs-1 text-muted"></i>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <!-- 2. Detail Guru di Bawah Foto -->
                                                    <h5 class="fw-bold mb-3 text-dark">{{ $teacher->user->name }}</h5>

                                                    <div class="text-start px-2 mb-3">
                                                        <table class="table table-sm table-borderless mb-0">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="text-muted p-1" style="width: 42%;">NIP</td>
                                                                    <td class="p-1 fw-semibold">: {{ $teacher->nip }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-muted p-1">Email</td>
                                                                    <td class="p-1 fw-semibold">: {{ $teacher->user->email }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-muted p-1">Wali Kelas</td>
                                                                    <td class="p-1 fw-semibold">: {{ $teacher->classes ? $teacher->classes->name : '-' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-muted p-1">No. Handphone</td>
                                                                    <td class="p-1 fw-semibold">: {{ $teacher->phone }}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <!-- 3. Alert Konfirmasi di Bagian Bawah -->
                                                    <div class="alert alert-danger d-flex align-items-center text-start gap-2 mb-0" role="alert">
                                                        <i class="icon-base bx bx-error-circle fs-4 text-danger flex-shrink-0"></i>
                                                        <div class="small">
                                                            Apakah Anda yakin ingin menghapus data guru ini? Seluruh akun dan data terkait akan dihapus secara permanen.
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    <form action="{{ route('admin.teacher.destroy', $teacher->id) }}" method="POST" class="d-inline m-0">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Belum ada data guru.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center py-3 gap-2">
                <small class="text-muted">
                    Menampilkan {{ $teachers->firstItem() ?? 0 }} sampai {{ $teachers->lastItem() ?? 0 }} dari {{ $teachers->total() }} guru
                </small>
                <div>
                    {{ $teachers->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
