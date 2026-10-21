@extends('layouts.admin.app')

@section('title', 'Konfigurasi | SMAN 2 Tondano')

@section('content')
    <div class="d-flex flex-column flex-grow-1 h-100" style="min-height: 0; overflow-y: auto;">
        <!-- Notifikasi -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show flex-shrink-0" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show flex-shrink-0" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show flex-shrink-0" role="alert">
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
            <h4 class="fw-bold mb-0">Konfigurasi</h4>
        </div>

        <!-- 1. KARTU KONFIGURASI AKURASI PEMINDAIAN WAJAH -->
        <div class="card shadow-sm mb-4 flex-shrink-0">
            <div class="card-header py-3 border-bottom">
                <h5 class="mb-0">Akurasi Pemindaian Wajah</h5>
            </div>
            <div class="card-body py-3">
                <form action="{{ route('admin.settings.konfigurasi.accuracy') }}" method="POST">
                    @csrf
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-md-4 col-lg-3">
                            <label class="form-label fw-semibold" for="min_face_accuracy">Minimum Akurasi</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="30" max="99.99" class="form-control"
                                    id="min_face_accuracy" name="min_face_accuracy"
                                    value="{{ number_format($appSetting->min_face_accuracy ?? 70, 2, '.', '') }}" required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-5 col-lg-6">
                            <p class="text-muted small mb-0 pt-md-3">
                                Tingkat akurasi minimal agar wajah siswa berhasil diverifikasi saat pemindaian. Hasil di bawah nilai ini akan dianggap sebagai wajah tidak dikenal.
                            </p>
                        </div>

                        <div class="col-12 col-md-3 col-lg-3 text-md-end">
                            <div class="pt-md-3">
                                <button type="submit" class="btn btn-primary">Simpan Akurasi</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. KARTU ATURAN JAM KELAS & BATAS PRESENSI -->
        <div class="card shadow-sm mb-4 flex-grow-1 d-flex flex-column" style="min-height: 420px;">
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-shrink-0 border-bottom">
                <h5 class="mb-0">Daftar Aturan Jam Masuk & Batas Presensi</h5>
                @if (auth()->user()?->hasRole('admin'))
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAturSerentak">
                        Atur Serentak Semua Kelas
                    </button>
                @endif
            </div>

            <div class="table-responsive flex-grow-1" style="overflow-y: auto;">
                <table class="table table-striped align-middle mb-0 table-sticky-header">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 5%;">No.</th>
                            <th style="width: 22%;">Kelas</th>
                            <th style="width: 23%;">Wali Kelas</th>
                            <th class="text-center" style="width: 15%;">Mulai Presensi</th>
                            <th class="text-center" style="width: 15%;">Batas Hadir</th>
                            <th class="text-center" style="width: 15%;">Batas Terlambat</th>
                            <th class="text-center" style="width: 10%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classes as $index => $cls)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $cls->name }}</span>
                                    <small class="text-muted d-block">Tingkat {{ $cls->grade_level }}</small>
                                </td>
                                <td>
                                    {{ $cls->teacher ? $cls->teacher->user->name : '-' }}
                                </td>
                                <td class="text-center text-nowrap">
                                    {{ \Carbon\Carbon::parse($cls->jam_mulai ?? '07:00:00')->format('H:i') }} WITA
                                </td>
                                <td class="text-center text-nowrap">
                                    {{ \Carbon\Carbon::parse($cls->jam_masuk ?? '07:15:00')->format('H:i') }} WITA
                                </td>
                                <td class="text-center text-nowrap">
                                    {{ \Carbon\Carbon::parse($cls->batas_terlambat ?? '07:30:00')->format('H:i') }} WITA
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2"
                                        data-bs-toggle="modal" data-bs-target="#modalEditJam{{ $cls->id }}">
                                        Ubah
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Belum ada data kelas yang terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal Atur Serentak Seluruh Kelas -->
    <div class="modal fade" id="modalAturSerentak" tabindex="-1" aria-labelledby="modalAturSerentakLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAturSerentakLabel">Atur Serentak Jam Seluruh Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.settings.jam-kelas.update-all') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Pengaturan jam ini akan diterapkan serentak ke semua kelas.
                        </p>

                        <div class="mb-3">
                            <label class="form-label" for="all_jam_mulai">Jam Mulai Presensi <span class="text-danger">*</span></label>
                            <input type="time" name="jam_mulai" id="all_jam_mulai" class="form-control" value="07:00" required>
                            <small class="text-muted" style="font-size: 11px;">Waktu sistem mulai membuka presensi. Sebelum jam ini sistem menolak absen.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="all_jam_masuk">Batas Hadir (Jam Masuk) <span class="text-danger">*</span></label>
                            <input type="time" name="jam_masuk" id="all_jam_masuk" class="form-control" value="07:15" required>
                            <small class="text-muted" style="font-size: 11px;">Scan antara jam mulai s/d jam masuk ini berstatus <strong>HADIR</strong>.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="all_batas_terlambat">Batas Terlambat <span class="text-danger">*</span></label>
                            <input type="time" name="batas_terlambat" id="all_batas_terlambat" class="form-control" value="07:30" required>
                            <small class="text-muted" style="font-size: 11px;">Scan setelah jam masuk s/d batas ini berstatus <strong>TERLAMBAT</strong>. Lewat dari batas ini otomatis <strong>ALPA</strong>.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Terapkan ke Semua Kelas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Jam Per Kelas -->
    @foreach ($classes as $cls)
        <div class="modal fade" id="modalEditJam{{ $cls->id }}" tabindex="-1" aria-labelledby="modalEditJamLabel{{ $cls->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditJamLabel{{ $cls->id }}">Ubah Jam Kelas: {{ $cls->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('admin.settings.jam-kelas.update', $cls->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="jam_mulai_{{ $cls->id }}">Jam Mulai Presensi <span class="text-danger">*</span></label>
                                <input type="time" name="jam_mulai" id="jam_mulai_{{ $cls->id }}" class="form-control"
                                    value="{{ \Carbon\Carbon::parse($cls->jam_mulai ?? '07:00:00')->format('H:i') }}" required>
                                <small class="text-muted" style="font-size: 11px;">Sistem mulai menerima presensi.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="jam_masuk_{{ $cls->id }}">Batas Hadir (Jam Masuk) <span class="text-danger">*</span></label>
                                <input type="time" name="jam_masuk" id="jam_masuk_{{ $cls->id }}" class="form-control"
                                    value="{{ \Carbon\Carbon::parse($cls->jam_masuk ?? '07:15:00')->format('H:i') }}" required>
                                <small class="text-muted" style="font-size: 11px;">Scan antara jam mulai s/d jam ini berstatus Hadir.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="batas_terlambat_{{ $cls->id }}">Batas Terlambat <span class="text-danger">*</span></label>
                                <input type="time" name="batas_terlambat" id="batas_terlambat_{{ $cls->id }}" class="form-control"
                                    value="{{ \Carbon\Carbon::parse($cls->batas_terlambat ?? '07:30:00')->format('H:i') }}" required>
                                <small class="text-muted" style="font-size: 11px;">Scan s/d jam ini berstatus Terlambat. Lewat dari ini otomatis Alpa.</small>
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
    @endforeach
@endsection

