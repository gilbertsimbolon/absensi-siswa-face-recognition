<div class="modal fade" id="modalHapusSiswa{{ $siswa->id }}" data-bs-backdrop="static" tabindex="-1" aria-labelledby="judulHapusSiswa{{ $siswa->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="judulHapusSiswa{{ $siswa->id }}">Hapus Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <p class="mb-0">
                    Apakah Anda yakin ingin menghapus data siswa <strong>{{ $siswa->name }}</strong> (NISN: {{ $siswa->nisn }})?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <form action="{{ route('admin.student.destroy', $siswa->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
