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
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="white-space: normal;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Tambah Data Guru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('admin.teacher.store') }}">
                        @csrf
                        <div class="modal-body text-start">
                            <div class="d-flex align-items-center mb-3">
                                <span class="text-uppercase small fw-semibold text-muted me-2">Akun & Identitas</span>
                                <hr class="flex-grow-1 m-0">
                            </div>

                            <!-- Input Nama -->
                            <div class="mb-3">
                                <label class="form-label" for="name">Nama Lengkap</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base bx bx-user"></i></span>
                                    <input type="text" class="form-control" id="name" name="name"
                                        placeholder="Nama beserta gelar" value="{{ old('name') }}" required />
                                </div>
                            </div>

                            <!-- Input NIP -->
                            <div class="mb-3">
                                <label class="form-label" for="nip">Nomor Induk Pegawai (NIP)</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base bx bx-id-card"></i></span>
                                    <input type="text" class="form-control" id="nip" name="nip"
                                        placeholder="19840606..." value="{{ old('nip') }}" required />
                                </div>
                            </div>

                            <!-- Input Email -->
                            <div class="mb-3">
                                <label class="form-label" for="email">Email</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base bx bx-envelope"></i></span>
                                    <input type="email" id="email" class="form-control" name="email"
                                        placeholder="nama@smandutdo.com" value="{{ old('email') }}" required />
                                </div>
                            </div>

                            <!-- Input Password -->
                            <div class="mb-3">
                                <label class="form-label" for="password">Password</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base bx bx-key"></i></span>
                                    <input type="password" id="password" class="form-control" name="password"
                                        placeholder="Minimal 8 karakter" required />
                                </div>
                            </div>

                            <!-- Input No. Handphone -->
                            <div class="mb-3">
                                <label class="form-label" for="phone">No. Telp / WhatsApp</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base bx bx-phone"></i></span>
                                    <input type="text" id="phone" name="phone" class="form-control"
                                        placeholder="0812 3456 7890" value="{{ old('phone') }}" required />
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
                            <th class="text-center" style="width: 5%;">No.</th>
                            <th style="width: 25%;">Nama</th>
                            <th style="width: 15%;">NIP</th>
                            <th style="width: 20%;">Email</th>
                            <th style="width: 15%;">Wali Kelas</th>
                            <th style="width: 12%;">No. Handphone</th>
                            <th class="text-center" style="width: 8%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse ($teachers as $teacher)
                            <tr>
                                <td class="text-center text-nowrap">{{ $teachers->firstItem() + $loop->index }}</td>
                                <td><strong>{{ $teacher->user->name }}</strong></td>
                                <td class="text-nowrap">{{ $teacher->nip }}</td>
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

                                    <!-- Modal Edit Guru -->
                                    <div class="modal fade" id="modalEditGuru{{ $teacher->id }}" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content" style="white-space: normal;">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Data Guru</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('admin.teacher.update', $teacher->id) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Nama Lengkap</label>
                                                            <input type="text" class="form-control" name="name"
                                                                value="{{ $teacher->user->name }}" required />
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">NIP</label>
                                                            <input type="text" class="form-control" name="nip"
                                                                value="{{ $teacher->nip }}" required />
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Email</label>
                                                            <input type="email" class="form-control" name="email"
                                                                value="{{ $teacher->user->email }}" required />
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Password Baru (Kosongkan jika tidak diubah)</label>
                                                            <input type="password" class="form-control" name="password"
                                                                placeholder="Kosongkan jika tetap" />
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">No. Handphone</label>
                                                            <input type="text" class="form-control" name="phone"
                                                                value="{{ $teacher->phone }}" required />
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
                                    <div class="modal fade" id="modalHapusGuru{{ $teacher->id }}" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content" style="white-space: normal;">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Hapus Data Guru</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-center pt-2">
                                                    <!-- 1. Ikon / Avatar Guru di Bagian Atas -->
                                                    <div class="mb-3">
                                                        <div class="rounded-circle border d-inline-flex align-items-center justify-content-center" style="width: 85px; height: 85px;">
                                                            <i class="icon-base bx bx-user fs-1 text-muted"></i>
                                                        </div>
                                                    </div>

                                                    <!-- 2. Detail Guru di Bawah Avatar -->
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
                                <td colspan="7" class="text-center text-muted py-4">Belum ada data guru.</td>
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
