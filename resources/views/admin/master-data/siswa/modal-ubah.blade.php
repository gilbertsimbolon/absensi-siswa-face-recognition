<div class="modal fade" id="modalUbahSiswa{{ $siswa->id }}" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="judulUbahSiswa{{ $siswa->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="judulUbahSiswa{{ $siswa->id }}">Edit Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('admin.student.update', $siswa->id) }}">
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
                                    value="{{ $siswa->name }}" required />
                            </div>
                        </div>

                        <!-- Input NISN -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor Induk Siswa Nasional (NISN)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-id-card"></i></span>
                                <input type="text" class="form-control" name="nisn"
                                    value="{{ $siswa->nisn }}" required />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Input Kelas -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kelas</label>
                            <select class="form-select" name="class_id" required>
                                @foreach ($classes as $kelas)
                                    <option value="{{ $kelas->id }}" {{ $siswa->class_id == $kelas->id ? 'selected' : '' }}>
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
                                    <input class="form-check-input" type="radio" name="gender" id="edit_gender_l_{{ $siswa->id }}" value="L" {{ $siswa->gender == 'L' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="edit_gender_l_{{ $siswa->id }}">Laki-laki</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="edit_gender_p_{{ $siswa->id }}" value="P" {{ $siswa->gender == 'P' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="edit_gender_p_{{ $siswa->id }}">Perempuan</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Input No. WhatsApp Siswa -->
                        <div class="col-12 mb-3">
                            <label class="form-label">No. Telp / WhatsApp Siswa (Untuk Notifikasi Absensi)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bxl-whatsapp"></i></span>
                                <input type="text" class="form-control" name="phone"
                                    value="{{ $siswa->phone }}" placeholder="0812 3456 7890" />
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
                            <label class="form-label">Nama Orang Tua / Wali</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-user"></i></span>
                                <input type="text" class="form-control" name="parent_name"
                                    value="{{ $siswa->parent_name }}" placeholder="Nama orang tua / wali" />
                            </div>
                        </div>

                        <!-- Input No Telp Orang Tua -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No. Telp / WhatsApp Orang Tua (Untuk Notifikasi Absensi)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bxl-whatsapp"></i></span>
                                <input type="text" class="form-control" name="parent_phone"
                                    value="{{ $siswa->parent_phone }}" placeholder="0812 3456 7890" />
                            </div>
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
