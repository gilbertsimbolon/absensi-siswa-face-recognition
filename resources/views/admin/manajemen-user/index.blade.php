@extends('layouts.admin.app')

@section('title', 'Manajemen User | SMAN 2 Tondano')

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
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Header Halaman -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-shrink-0">
            <div>
                <h4 class="fw-bold mb-0">Manajemen User</h4>
                <span class="text-muted small">Kelola data akun pengguna, email, dan hak akses</span>
            </div>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                <i class="icon-base bx bx-plus me-1"></i> Tambah User
            </button>
        </div>

        <!-- Card Tabel User -->
        <div class="card flex-grow-1 d-flex flex-column shadow-sm mb-0" style="min-height: 0; overflow: hidden;">
            <div class="card-header border-bottom py-3 px-4 flex-shrink-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <span class="fw-semibold text-dark">Daftar Pengguna Sistem</span>
                </div>

                <!-- Form Pencarian & Filter -->
                <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex align-items-center gap-2 m-0 flex-wrap">
                    @if (request('role'))
                        <input type="hidden" name="role" value="{{ request('role') }}">
                    @endif

                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" name="cari" class="form-control" placeholder="Cari nama atau email..."
                            value="{{ request('cari', request('search')) }}">
                        <button class="btn btn-secondary" type="submit" title="Cari">
                            <i class="icon-base bx bx-search"></i>
                        </button>
                    </div>

                    @if (request()->hasAny(['cari', 'search', 'role']) && (request('cari') || request('search') || request('role')))
                        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                            <i class="icon-base bx bx-reset"></i>
                        </a>
                    @endif
                </form>
            </div>

            <div class="table-responsive flex-grow-1" style="overflow-y: auto; min-height: 0;">
                <table class="table table-striped align-middle mb-0 table-sticky-header">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 5%;">No.</th>
                            <th style="width: 35%;">Nama</th>
                            <th style="width: 30%;">Email</th>
                            <th class="text-center" style="width: 15%;">Role</th>
                            <th class="text-center" style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse ($users as $user)
                            <tr>
                                <td class="text-center text-nowrap">{{ $users->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm me-2">
                                            @if ($user->teacher && $user->teacher->faces->isNotEmpty())
                                                <img src="{{ asset('storage/' . $user->teacher->faces->first()->file_path) }}" alt="{{ $user->name }}" class="rounded-circle" style="object-fit: cover;">
                                            @else
                                                <span class="avatar-initial rounded-circle bg-label-primary fw-bold">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </span>
                                            @endif
                                        </div>
                                        <div>
                                            <strong class="d-block text-dark">{{ $user->name }}</strong>
                                            @if ($user->teacher)
                                                <small class="text-muted">Guru &bull; NIP: {{ $user->teacher->nip }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-body">{{ $user->email }}</span>
                                </td>
                                <td class="text-center text-nowrap">
                                    @foreach ($user->roles as $role)
                                        @php
                                            $badgeClass = match ($role->name) {
                                                'admin' => 'bg-label-danger',
                                                'teacher' => 'bg-label-success',
                                                'headmaster' => 'bg-label-warning',
                                                default => 'bg-label-secondary',
                                            };
                                            $roleLabel = match ($role->name) {
                                                'admin' => 'Administrator',
                                                'teacher' => 'Guru',
                                                'headmaster' => 'Kepala Sekolah',
                                                'student' => 'Siswa',
                                                default => ucfirst($role->name),
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $roleLabel }}</span>
                                    @endforeach
                                    @if ($user->roles->isEmpty())
                                        <span class="badge bg-label-secondary">User</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <!-- Tombol Edit User -->
                                        <button type="button" class="btn btn-sm btn-icon p-0 text-warning"
                                            data-bs-toggle="modal" data-bs-target="#modalEditUser{{ $user->id }}"
                                            title="Edit User">
                                            <i class="icon-base bx bx-edit-alt fs-5"></i>
                                        </button>

                                        <!-- Tombol Hapus User -->
                                        @if (auth()->id() !== $user->id)
                                            <button type="button" class="btn btn-sm btn-icon p-0 text-danger"
                                                data-bs-toggle="modal" data-bs-target="#modalHapusUser{{ $user->id }}"
                                                title="Hapus User">
                                                <i class="icon-base bx bx-trash fs-5"></i>
                                            </button>
                                        @else
                                            <span class="d-inline-block text-muted" title="Akun Anda yang sedang aktif">
                                                <i class="icon-base bx bx-lock-alt fs-5 opacity-50"></i>
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Modal Edit User -->
                                    <div class="modal fade" id="modalEditUser{{ $user->id }}" data-bs-backdrop="static" tabindex="-1" aria-labelledby="judulEditUser{{ $user->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content" style="white-space: normal;">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="judulEditUser{{ $user->id }}">Edit User</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label" for="edit_name_{{ $user->id }}">Nama Lengkap</label>
                                                            <div class="input-group input-group-merge">
                                                                <span class="input-group-text"><i class="icon-base bx bx-user"></i></span>
                                                                <input type="text" class="form-control" id="edit_name_{{ $user->id }}" name="name"
                                                                    value="{{ old('name', $user->name) }}" required />
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="edit_email_{{ $user->id }}">Email</label>
                                                            <div class="input-group input-group-merge">
                                                                <span class="input-group-text"><i class="icon-base bx bx-envelope"></i></span>
                                                                <input type="email" class="form-control" id="edit_email_{{ $user->id }}" name="email"
                                                                    value="{{ old('email', $user->email) }}" required />
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="edit_password_{{ $user->id }}">Password Baru (Opsional)</label>
                                                            <div class="input-group input-group-merge">
                                                                <span class="input-group-text"><i class="icon-base bx bx-key"></i></span>
                                                                <input type="password" class="form-control" id="edit_password_{{ $user->id }}" name="password"
                                                                    placeholder="Kosongkan jika tidak ingin mengubah password" minlength="8" />
                                                            </div>
                                                            <small class="text-muted">Biarkan kosong jika tetap menggunakan password lama.</small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="edit_role_{{ $user->id }}">Role / Hak Akses</label>
                                                            <select class="form-select" id="edit_role_{{ $user->id }}" name="role" required>
                                                                @foreach ($roles as $role)
                                                                    <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                                                        {{ $role->name === 'admin' ? 'Administrator' : ($role->name === 'teacher' ? 'Guru' : ucfirst($role->name)) }}
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

                                    <!-- Modal Hapus User -->
                                    <div class="modal fade" id="modalHapusUser{{ $user->id }}" data-bs-backdrop="static" tabindex="-1" aria-labelledby="judulHapusUser{{ $user->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content" style="white-space: normal;">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="judulHapusUser{{ $user->id }}">Konfirmasi Hapus User</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="modal-body">
                                                        <p class="mb-2">Apakah Anda yakin ingin menghapus akun user berikut?</p>
                                                        <div class="alert alert-warning py-2 mb-2">
                                                            <strong>{{ $user->name }}</strong><br>
                                                            <small class="text-muted">{{ $user->email }}</small>
                                                        </div>
                                                        <small class="text-danger">Tindakan ini tidak dapat dibatalkan.</small>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-danger">Hapus User</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="icon-base bx bx-user-x fs-1 d-block mb-1 opacity-50"></i>
                                    Tidak ada data user yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($users->hasPages())
                <div class="card-footer border-top py-2 px-4 flex-shrink-0 bg-white">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">
                            Menampilkan {{ $users->firstItem() }} - {{ $users->lastItem() }} dari {{ $users->total() }} data user
                        </small>
                        {{ $users->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Tambah User -->
    <div class="modal fade" id="modalTambahUser" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="judulTambahUser" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="white-space: normal;">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulTambahUser">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="modal-body">
                        <!-- Pilihan Sumber Nama dari Guru -->
                        <div class="mb-3">
                            <label class="form-label" for="pilih_guru">Ambil Nama dari Data Guru (Opsional)</label>
                            <select class="form-select" id="pilih_guru" name="teacher_id" onchange="isiOtomatisDariGuru(this)">
                                <option value="">-- Ketik Nama Manual atau Pilih Guru --</option>
                                @foreach ($teachers as $guru)
                                    <option value="{{ $guru->id }}"
                                        data-nama="{{ $guru->user ? $guru->user->name : '' }}"
                                        data-email="{{ $guru->user ? $guru->user->email : '' }}">
                                        {{ $guru->user ? $guru->user->name : 'Guru #' . $guru->nip }} (NIP: {{ $guru->nip }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Pilih guru untuk mengisi nama dan email secara otomatis.</small>
                        </div>

                        <!-- Input Nama -->
                        <div class="mb-3">
                            <label class="form-label" for="tambah_name">Nama Lengkap</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-user"></i></span>
                                <input type="text" class="form-control" id="tambah_name" name="name"
                                    placeholder="Nama Lengkap User" value="{{ old('name') }}" required />
                            </div>
                        </div>

                        <!-- Input Email -->
                        <div class="mb-3">
                            <label class="form-label" for="tambah_email">Email</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-envelope"></i></span>
                                <input type="email" class="form-control" id="tambah_email" name="email"
                                    placeholder="nama@smandutdo.com" value="{{ old('email') }}" required />
                            </div>
                        </div>

                        <!-- Input Password -->
                        <div class="mb-3">
                            <label class="form-label" for="tambah_password">Password</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-key"></i></span>
                                <input type="password" class="form-control" id="tambah_password" name="password"
                                    placeholder="Minimal 8 karakter" minlength="8" required />
                            </div>
                        </div>

                        <!-- Input Role -->
                        <div class="mb-3">
                            <label class="form-label" for="tambah_role">Role / Hak Akses</label>
                            <select class="form-select" id="tambah_role" name="role" required>
                                <option value="teacher" selected>Guru</option>
                                <option value="admin">Administrator</option>
                                <option value="headmaster">Kepala Sekolah</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Script Pengisian Otomatis dari Pilihan Guru -->
    <script>
        function isiOtomatisDariGuru(selectElem) {
            const selectedOption = selectElem.options[selectElem.selectedIndex];
            const nama = selectedOption.getAttribute('data-nama');
            const email = selectedOption.getAttribute('data-email');

            if (nama) {
                document.getElementById('tambah_name').value = nama;
            }
            if (email) {
                document.getElementById('tambah_email').value = email;
            }
        }
    </script>
@endsection

