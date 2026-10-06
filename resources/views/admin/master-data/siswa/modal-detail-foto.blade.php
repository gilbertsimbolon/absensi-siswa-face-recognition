<div class="modal fade" id="modalDetailFotoSiswa{{ $siswa->id }}" data-bs-backdrop="static" tabindex="-1" aria-labelledby="judulDetailFoto{{ $siswa->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
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
                                    <img src="{{ asset('storage/' . $face->file_path) }}" alt="{{ $face->label }}" class="img-fluid rounded" style="max-height: 220px; object-fit: cover;">
                                </a>
                                <span class="d-block text-muted small">{{ $face->label ?: 'Sampel #' . $loop->iteration }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <p class="mb-0">Belum ada sampel foto wajah untuk siswa ini.</p>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
