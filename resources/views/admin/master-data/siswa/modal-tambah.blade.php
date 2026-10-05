<div class="modal fade" id="modalTambahSiswa" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="judulTambahSiswa" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="judulTambahSiswa">Tambah Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('admin.student.store') }}">
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
                        <!-- Input No. WhatsApp Siswa -->
                        <div class="col-12 mb-3">
                            <label class="form-label" for="tambah_phone">No. Telp / WhatsApp Siswa (Untuk Notifikasi Absensi)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bxl-whatsapp"></i></span>
                                <input type="text" class="form-control" id="tambah_phone" name="phone"
                                    placeholder="0812 3456 7890" value="{{ old('phone') }}" />
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

                        <!-- Input No Telp Orang Tua -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="tambah_no_ortu">No. Telp / WhatsApp Orang Tua (Untuk Notifikasi Absensi)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bxl-whatsapp"></i></span>
                                <input type="text" class="form-control" id="tambah_no_ortu" name="parent_phone"
                                    placeholder="0812 3456 7890" value="{{ old('parent_phone') }}" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-success">Simpan Data Siswa</button>
                </div>
            </form>
        </div>
    </div>
</div>
