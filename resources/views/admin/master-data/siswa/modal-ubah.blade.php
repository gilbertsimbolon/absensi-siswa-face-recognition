<div class="modal fade" id="modalUbahSiswa{{ $siswa->id }}" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="judulUbahSiswa{{ $siswa->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="white-space: normal;">
            <div class="modal-header">
                <h5 class="modal-title" id="judulUbahSiswa{{ $siswa->id }}">Edit Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('admin.student.update', $siswa->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body text-start">
                    <!-- Garis Pemisah Data Siswa -->
                    <div class="d-flex align-items-center mb-3">
                        <span class="text-uppercase small fw-semibold text-muted me-2">Data Siswa</span>
                        <hr class="flex-grow-1 m-0">
                    </div>

                    <div class="row">
                        <!-- Input Nama Lengkap -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-user"></i></span>
                                <input type="text" class="form-control" name="name"
                                    value="{{ old('name', $siswa->name) }}" required />
                            </div>
                        </div>

                        <!-- Input NISN -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor Induk Siswa Nasional (NISN)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-id-card"></i></span>
                                <input type="text" class="form-control" name="nisn"
                                    value="{{ old('nisn', $siswa->nisn) }}" required />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Input Kelas -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kelas</label>
                            <select class="form-select" name="class_id" required>
                                @foreach ($classes as $kelas)
                                    <option value="{{ $kelas->id }}" {{ old('class_id', $siswa->class_id) == $kelas->id ? 'selected' : '' }}>
                                        {{ $kelas->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Input Jenis Kelamin -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <div class="d-flex gap-4 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="edit_gender_l_{{ $siswa->id }}" value="L" {{ old('gender', $siswa->gender) == 'L' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="edit_gender_l_{{ $siswa->id }}">Laki-laki</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="edit_gender_p_{{ $siswa->id }}" value="P" {{ old('gender', $siswa->gender) == 'P' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="edit_gender_p_{{ $siswa->id }}">Perempuan</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Input Email Siswa -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Email Siswa (Notifikasi Presensi)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-envelope"></i></span>
                                <input type="email" class="form-control" name="email"
                                    value="{{ old('email', $siswa->email) }}" placeholder="siswa@email.com" />
                            </div>
                        </div>

                        <!-- Input No. Telp Siswa -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No. Telp Siswa (Opsional)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-phone"></i></span>
                                <input type="text" class="form-control" name="phone"
                                    value="{{ old('phone', $siswa->phone) }}" placeholder="0812 3456 7890" />
                            </div>
                        </div>
                    </div>

                    <!-- Garis Pemisah Data Orang Tua -->
                    <div class="d-flex align-items-center mb-3 mt-2">
                        <span class="text-uppercase small fw-semibold text-muted me-2">Data Orang Tua</span>
                        <hr class="flex-grow-1 m-0">
                    </div>

                    <div class="row">
                        <!-- Input Nama Orang Tua -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nama Orang Tua / Wali</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-user"></i></span>
                                <input type="text" class="form-control" name="parent_name"
                                    value="{{ old('parent_name', $siswa->parent_name) }}" placeholder="Nama orang tua / wali" />
                            </div>
                        </div>

                        <!-- Input Email Orang Tua -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Email Orang Tua (Notifikasi Presensi)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-envelope"></i></span>
                                <input type="email" class="form-control" name="parent_email"
                                    value="{{ old('parent_email', $siswa->parent_email) }}" placeholder="ortu@email.com" />
                            </div>
                        </div>

                        <!-- Input No Telp Orang Tua -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">No. Telp Orang Tua (Opsional)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-phone"></i></span>
                                <input type="text" class="form-control" name="parent_phone"
                                    value="{{ old('parent_phone', $siswa->parent_phone) }}" placeholder="0812 3456 7890" />
                            </div>
                        </div>
                    </div>

                    <!-- Garis Pemisah Dataset Foto Wajah (AI Face Embedding) -->
                    <div class="d-flex align-items-center mb-3 mt-2">
                        <span class="text-uppercase small fw-semibold text-muted me-2">Foto Wajah Siswa Terdaftar (AI Face Embedding)</span>
                        <hr class="flex-grow-1 m-0">
                    </div>

                    @if ($siswa->faces->isNotEmpty())
                        <div class="row mb-3">
                            @foreach ($siswa->faces as $face)
                                <div class="col-4 col-md-3 mb-2 text-center">
                                    <img src="{{ asset('storage/' . $face->file_path) }}" alt="Foto Siswa"
                                        class="rounded border shadow-sm mb-1" style="width: 80px; height: 80px; object-fit: cover;">
                                    <span class="d-block small text-muted">Foto #{{ $loop->iteration }}</span>
                                    @if (!empty($face->embedding))
                                        <span class="badge bg-label-success" style="font-size: 0.65rem;">128-D Vektor</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-warning py-2 px-3 small mb-3">
                            <i class="icon-base bx bx-error me-1"></i> Belum ada foto wajah terdaftar untuk siswa ini.
                        </div>
                    @endif

                    <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-3">
                        <i class="icon-base bx bx-info-circle me-2 fs-5"></i>
                        <span><strong>AI Auto-Crop & Face Zoom:</strong> Unggah foto baru di bawah jika ingin memperbarui / menambahkan sampel foto wajah (otomatis dipotong & di-zoom ke wajah).</span>
                    </div>

                    <div id="container-foto-ubah-{{ $siswa->id }}">
                        <div class="row align-items-center mb-2 item-foto-ubah">
                            <div class="col-md-9">
                                <label class="form-label fw-semibold small mb-1">Unggah Foto Wajah Baru (Opsional)</label>
                                <input type="file" class="form-control form-control-sm" name="photos[]" accept="image/*" />
                            </div>
                            <div class="col-md-3 pt-3">
                                <span class="badge bg-label-secondary">Sampel Baru</span>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="btn-tambah-foto-ubah-{{ $siswa->id }}">
                        <i class="icon-base bx bx-plus me-1"></i> Tambah Foto Lainnya
                    </button>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const containerUbah = document.getElementById('container-foto-ubah-{{ $siswa->id }}');
                    const btnAddUbah = document.getElementById('btn-tambah-foto-ubah-{{ $siswa->id }}');
                    if (btnAddUbah && containerUbah) {
                        btnAddUbah.addEventListener('click', function () {
                            const count = containerUbah.querySelectorAll('.item-foto-ubah').length + 1;
                            const div = document.createElement('div');
                            div.className = 'row align-items-center mb-2 item-foto-ubah';
                            div.innerHTML = `
                                <div class="col-md-9">
                                    <label class="form-label fw-semibold small mb-1">Unggah Foto Tambahan #${count}</label>
                                    <input type="file" class="form-control form-control-sm" name="photos[]" accept="image/*" />
                                </div>
                                <div class="col-md-3 pt-3">
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-hapus-foto-ubah">
                                        <i class="icon-base bx bx-trash me-1"></i> Hapus
                                    </button>
                                </div>
                            `;
                            containerUbah.appendChild(div);
                            div.querySelector('.btn-hapus-foto-ubah').addEventListener('click', function () {
                                div.remove();
                            });
                        });
                    }
                });
                </script>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
