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
                        <!-- Input No. WhatsApp Siswa -->
                        <div class="col-12 mb-3">
                            <label class="form-label">No. Telp / WhatsApp Siswa (Untuk Notifikasi Absensi)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bxl-whatsapp"></i></span>
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
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Orang Tua / Wali</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-user"></i></span>
                                <input type="text" class="form-control" name="parent_name"
                                    value="{{ old('parent_name', $siswa->parent_name) }}" placeholder="Nama orang tua / wali" />
                            </div>
                        </div>

                        <!-- Input No Telp Orang Tua -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No. Telp / WhatsApp Orang Tua (Untuk Notifikasi Absensi)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-phone"></i></span>
                                <input type="text" class="form-control" name="parent_phone"
                                    value="{{ old('parent_phone', $siswa->parent_phone) }}" placeholder="0812 3456 7890" />
                            </div>
                        </div>
                    </div>

                    <!-- Garis Pemisah Dataset Foto Wajah (3 Sampel) -->
                    <div class="d-flex align-items-center mb-3 mt-2">
                        <span class="text-uppercase small fw-semibold text-muted me-2">Pembaruan Sampel Foto Wajah (Opsional)</span>
                        <hr class="flex-grow-1 m-0">
                    </div>

                    @php
                        $faceDepan = $siswa->faces->firstWhere('label', 'Tampak Depan') ?? $siswa->faces->get(0);
                        $faceKanan = $siswa->faces->firstWhere('label', 'Serong Kanan') ?? $siswa->faces->get(1);
                        $faceKiri = $siswa->faces->firstWhere('label', 'Serong Kiri') ?? $siswa->faces->get(2);
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
