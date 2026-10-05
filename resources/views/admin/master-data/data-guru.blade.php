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
            <h4 class="fw-bold mb-0">Master Data Guru</h4>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahDataGuru">
                <i class="icon-base bx bx-plus me-1"></i> Tambah Data Guru
            </button>
        </div>

        <!-- Modal Tambah Guru -->
        <div class="modal fade" id="modalTambahDataGuru" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Tambah Data Guru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('admin.teacher.store') }}">
                        @csrf
                        <div class="modal-body">
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
            <h5 class="card-header">Daftar Guru</h5>
            <div class="table-responsive text-nowrap">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">No.</th>
                            <th>Nama</th>
                            <th>NIP</th>
                            <th>Email</th>
                            <th>Wali Kelas</th>
                            <th>No. Handphone</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse ($teachers as $teacher)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td><strong>{{ $teacher->user->name }}</strong></td>
                                <td><span class="badge bg-label-dark">{{ $teacher->nip }}</span></td>
                                <td>{{ $teacher->user->email }}</td>
                                <td>
                                    @if ($teacher->classes)
                                        <span class="badge bg-label-primary">{{ $teacher->classes->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($teacher->phone)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $teacher->phone) }}" target="_blank" class="text-success text-decoration-none">
                                            <i class="icon-base bx bxl-whatsapp me-1"></i>{{ $teacher->phone }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <!-- Tombol Edit Modal -->
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#modalEditGuru{{ $teacher->id }}">
                                            <i class="icon-base bx bx-edit-alt"></i>
                                        </button>

                                        <!-- Form Delete -->
                                        <form action="{{ route('admin.teacher.destroy', $teacher->id) }}" method="POST"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus data guru {{ $teacher->user->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="icon-base bx bx-trash"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Modal Edit Guru -->
                                    <div class="modal fade" id="modalEditGuru{{ $teacher->id }}" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content">
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
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                                    </div>
                                                </form>
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
        </div>
    </div>
@endsection
