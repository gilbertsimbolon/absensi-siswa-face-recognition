<div class="modal fade" id="modalTambahSiswa" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="judulTambahSiswa" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="judulTambahSiswa">Tambah Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('admin.student.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <!-- Garis Pemisah Data Siswa -->
                    <div class="d-flex align-items-center mb-3">
                        <span class="text-uppercase small fw-semibold text-muted me-2">Data Siswa</span>
                        <hr class="flex-grow-1 m-0">
                    </div>

                    <div class="row">
                        <!-- Input Nama Lengkap -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="tambah_nama">Nama Lengkap</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-user"></i></span>
                                <input type="text" class="form-control" id="tambah_nama" name="name"
                                    placeholder="Nama lengkap siswa" value="{{ old('name') }}" required />
                            </div>
                        </div>

                        <!-- Input NISN -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="tambah_nisn">Nomor Induk Siswa Nasional (NISN)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-id-card"></i></span>
                                <input type="text" class="form-control" id="tambah_nisn" name="nisn"
                                    placeholder="0051234567" value="{{ old('nisn') }}" required />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Input Kelas -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="tambah_kelas">Kelas</label>
                            <select class="form-select" id="tambah_kelas" name="class_id" required>
                                <option value="" disabled selected>Pilih Kelas</option>
                                @foreach ($classes as $kelas)
                                    <option value="{{ $kelas->id }}" {{ old('class_id') == $kelas->id ? 'selected' : '' }}>
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
                                    <input class="form-check-input" type="radio" name="gender" id="tambah_gender_l" value="L" {{ old('gender', 'L') == 'L' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="tambah_gender_l">Laki-laki</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="tambah_gender_p" value="P" {{ old('gender') == 'P' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="tambah_gender_p">Perempuan</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Input Email Siswa -->
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold" for="tambah_email">Email Siswa (Notifikasi Presensi)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-envelope"></i></span>
                                <input type="email" class="form-control" id="tambah_email" name="email"
                                    placeholder="siswa@email.com" value="{{ old('email') }}" />
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
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="tambah_nama_ortu">Nama Orang Tua / Wali</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-user"></i></span>
                                <input type="text" class="form-control" id="tambah_nama_ortu" name="parent_name"
                                    placeholder="Nama orang tua / wali" value="{{ old('parent_name') }}" />
                            </div>
                        </div>

                        <!-- Input Email Orang Tua -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="tambah_email_ortu">Email Orang Tua (Notifikasi Presensi)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-envelope"></i></span>
                                <input type="email" class="form-control" id="tambah_email_ortu" name="parent_email"
                                    placeholder="ortu@email.com" value="{{ old('parent_email') }}" />
                            </div>
                        </div>
                    </div>

                    <!-- Garis Pemisah Dataset Foto Wajah (AI Face Embedding) -->
                    <div class="d-flex align-items-center mb-3 mt-2">
                        <span class="text-uppercase small fw-semibold text-muted me-2">Foto Wajah Siswa (AI Face Embedding)</span>
                        <hr class="flex-grow-1 m-0">
                    </div>

                    <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-3">
                        <i class="icon-base bx bx-info-circle me-2 fs-5"></i>
                        <span>Sistem dilengkapi fitur <strong>AI Auto-Crop & Face Zoom</strong>. Anda cukup mengunggah minimal <strong>1 foto</strong> (baik foto selfie, setengah badan, atau full body). AI akan secara otomatis mendeteksi wajah, memotong (zoom) ke area wajah, dan menyimpan vektor embedding 128-dimensi ke database.</span>
                    </div>

                    <div id="container-foto-tambah">
                        <div class="row align-items-center mb-2 item-foto-tambah">
                            <div class="col-md-9">
                                <label class="form-label fw-semibold small mb-1">Foto Wajah #1 <span class="text-danger">*</span></label>
                                <input type="file" class="form-control form-control-sm" name="photos[]" accept="image/*" required />
                            </div>
                            <div class="col-md-3 pt-3">
                                <span class="badge bg-label-primary">Foto Utama</span>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="btn-tambah-foto-input">
                        <i class="icon-base bx bx-plus me-1"></i> Tambah Foto Lainnya (Opsional)
                    </button>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const container = document.getElementById('container-foto-tambah');
                    const btnAdd = document.getElementById('btn-tambah-foto-input');
                    if (btnAdd && container) {
                        btnAdd.addEventListener('click', function () {
                            const count = container.querySelectorAll('.item-foto-tambah').length + 1;
                            const div = document.createElement('div');
                            div.className = 'row align-items-center mb-2 item-foto-tambah';
                            div.innerHTML = `
                                <div class="col-md-9">
                                    <label class="form-label fw-semibold small mb-1">Foto Wajah #${count} (Opsional)</label>
                                    <input type="file" class="form-control form-control-sm" name="photos[]" accept="image/*" />
                                </div>
                                <div class="col-md-3 pt-3">
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-hapus-foto">
                                        <i class="icon-base bx bx-trash me-1"></i> Hapus
                                    </button>
                                </div>
                            `;
                            container.appendChild(div);
                            div.querySelector('.btn-hapus-foto').addEventListener('click', function () {
                                div.remove();
                            });
                        });
                    }
                });
                </script>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-success">Simpan Data Siswa</button>
                </div>
            </form>
        </div>
    </div>
</div>

