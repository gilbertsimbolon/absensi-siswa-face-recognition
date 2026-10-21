<div class="modal fade" id="modalDetailFotoSiswa{{ $siswa->id }}" data-bs-backdrop="static" tabindex="-1" aria-labelledby="judulDetailFoto{{ $siswa->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="white-space: normal;">
            <div class="modal-header">
                <h5 class="modal-title" id="judulDetailFoto{{ $siswa->id }}">
                    Foto Wajah Siswa - {{ $siswa->name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                @if ($siswa->faces->count() > 0)
                    <div class="row">
                        @foreach ($siswa->faces as $face)
                            <div class="col-md-4 mb-3 text-center">
                                <a href="{{ asset('storage/' . $face->file_path) }}" target="_blank" class="d-block mb-2">
                                    <img src="{{ asset('storage/' . $face->file_path) }}" alt="Foto Siswa" class="img-fluid rounded border shadow-sm" style="max-height: 220px; object-fit: cover;">
                                </a>
                                <span class="d-block text-muted small mb-1">Sampel #{{ $loop->iteration }}</span>
                                @if (!empty($face->embedding))
                                    <span class="badge bg-label-success small"><i class="icon-base bx bx-check-circle me-1"></i> Vektor 128-D Tersimpan</span>
                                @else
                                    <span class="badge bg-label-danger small d-block mb-1">Vektor Belum Diekstrak</span>
                                    <form action="{{ route('admin.student.sync-face', $siswa->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-primary mt-1" title="Ekstrak fitur citra wajah AI">
                                            <i class="icon-base bx bx-scan me-1"></i> Ekstrak Vektor
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <p class="mb-0">Belum ada sampel foto wajah untuk siswa ini.</p>
                    </div>
                @endif
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div>
                    @if ($siswa->faces->whereNull('embedding')->count() > 0)
                        <form action="{{ route('admin.student.sync-face', $siswa->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="icon-base bx bx-scan me-1"></i> Ekstrak Semua Vektor Siswa Ini
                            </button>
                        </form>
                    @endif
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
