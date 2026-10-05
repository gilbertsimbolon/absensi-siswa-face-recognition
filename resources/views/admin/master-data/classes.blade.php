@extends('layouts.admin.app')

@section('title', 'Data Kelas | SMAN 2 Tondano')

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
            <h4 class="fw-bold mb-0">Master Data Kelas</h4>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahKelas">
                <i class="icon-base bx bx-plus me-1"></i> Tambah Kelas
            </button>
        </div>

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
                                        <option value="{{ $teacher->id }}">{{ $teacher->user->name }} (NIP: {{ $teacher->nip }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-success">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabel Kelas -->
        <div class="card">
            <h5 class="card-header">Daftar Kelas</h5>
            <div class="table-responsive text-nowrap">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 60px;">No.</th>
                            <th>Tingkat</th>
                            <th>Nama Kelas</th>
                            <th>Wali Kelas</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse ($classes as $class)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="badge bg-label-info">{{ $class->grade_level }}</span>
                                </td>
                                <td><strong>{{ $class->name }}</strong></td>
                                <td>
                                    @if ($class->teacher)
                                        {{ $class->teacher->user->name }}
                                    @else
                                        <span class="text-muted font-italic">- Belum Diatur -</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <!-- Tombol Edit Modal -->
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#modalEditKelas{{ $class->id }}">
                                            <i class="icon-base bx bx-edit-alt"></i>
                                        </button>

                                        <!-- Form Delete -->
                                        <form action="{{ route('admin.classes.destroy', $class->id) }}" method="POST"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelas ini? Siswa di kelas ini juga akan terhapus.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="icon-base bx bx-trash"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Modal Edit Kelas -->
                                    <div class="modal fade" id="modalEditKelas{{ $class->id }}" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Data Kelas</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('admin.classes.update', $class->id) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Tingkat Kelas</label>
                                                            <select class="form-select" name="grade_level" required>
                                                                <option value="X" {{ $class->grade_level == 'X' ? 'selected' : '' }}>Kelas X (10)</option>
                                                                <option value="XI" {{ $class->grade_level == 'XI' ? 'selected' : '' }}>Kelas XI (11)</option>
                                                                <option value="XII" {{ $class->grade_level == 'XII' ? 'selected' : '' }}>Kelas XII (12)</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Nama Kelas</label>
                                                            <input type="text" class="form-control" name="name"
                                                                value="{{ $class->name }}" required />
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Wali Kelas (Opsional)</label>
                                                            <select class="form-select" name="teacher_id">
                                                                <option value="">-- Tanpa Wali Kelas --</option>
                                                                @foreach ($teachers as $teacher)
                                                                    <option value="{{ $teacher->id }}" {{ $class->teacher_id == $teacher->id ? 'selected' : '' }}>
                                                                        {{ $teacher->user->name }} (NIP: {{ $teacher->nip }})
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
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada data kelas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
