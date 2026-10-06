<div class="modal fade" id="modalHapusSiswa{{ $siswa->id }}" data-bs-backdrop="static" tabindex="-1" aria-labelledby="judulHapusSiswa{{ $siswa->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="white-space: normal;">
            <div class="modal-header">
                <h5 class="modal-title" id="judulHapusSiswa{{ $siswa->id }}">Hapus Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-2">
                @php
                    $primaryFace = $siswa->faces->first();
                @endphp

                <!-- 1. Foto Siswa di Bagian Atas -->
                <div class="mb-3">
                    @if ($primaryFace)
                        <img src="{{ asset('storage/' . $primaryFace->file_path) }}" alt="{{ $siswa->name }}"
                            class="rounded-circle border" style="width: 85px; height: 85px; object-fit: cover;">
                    @else
                        <div class="rounded-circle border d-inline-flex align-items-center justify-content-center" style="width: 85px; height: 85px;">
                            <i class="icon-base bx bx-user fs-1 text-muted"></i>
                        </div>
                    @endif
                </div>

                <!-- 2. Detail Siswa di Bawah Foto -->
                <h5 class="fw-bold mb-3 text-dark">{{ $siswa->name }}</h5>

                <div class="text-start px-2 mb-3">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted p-1" style="width: 42%;">NISN</td>
                                <td class="p-1 fw-semibold">: {{ $siswa->nisn }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted p-1">Kelas</td>
                                <td class="p-1 fw-semibold">: {{ $siswa->classes ? $siswa->classes->name : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted p-1">Jenis Kelamin</td>
                                <td class="p-1 fw-semibold">: {{ $siswa->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted p-1">No. WhatsApp Siswa</td>
                                <td class="p-1 fw-semibold">: {{ $siswa->phone ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted p-1">Orang Tua / Wali</td>
                                <td class="p-1 fw-semibold">: {{ $siswa->parent_name ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted p-1">No. WhatsApp Ortu</td>
                                <td class="p-1 fw-semibold">: {{ $siswa->parent_phone ?: '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 3. Alert Konfirmasi di Bagian Bawah -->
                <div class="alert alert-danger d-flex align-items-center text-start gap-2 mb-0" role="alert">
                    <i class="icon-base bx bx-error-circle fs-4 text-danger flex-shrink-0"></i>
                    <div class="small">
                        Apakah Anda yakin ingin menghapus data siswa ini? Seluruh sampel foto wajah dan data presensi akan dihapus secara permanen.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <form action="{{ route('admin.student.destroy', $siswa->id) }}" method="POST" class="d-inline m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
