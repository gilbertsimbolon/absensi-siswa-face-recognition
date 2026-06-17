@extends('layouts.admin.app')

@section('title', 'Data Guru | SMAN 1 Tondano')

@section('content')
    <div class="mt-0">
        <div class=" d-flex justify-content-end align-items-center mb-5">
            <!-- Modal Tambah Data -->
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahDataGuru">Tambah
                Data</button>

            <div class="modal fade" id="modalTambahDataGuru" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="staticBackdropLabel">Tambah Data Guru</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="{{ route('admin.teacher.store') }}">
                                @csrf
                                <div class="d-flex align-items-center mb-4">
                                    <span class="text-uppercase small fw-semibold text-muted me-3">
                                        Akun
                                    </span>
                                    <hr class="flex-grow-1 m-0">
                                </div>

                                <!-- Input Nama -->
                                <div class="row mb-6">
                                    <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Nama</label>
                                    <div class="col-sm-10">
                                        <div class="input-group input-group-merge">
                                            <span id="basic-icon-default-fullname2" class="input-group-text"><i
                                                    class="icon-base bx bx-user"></i></span>
                                            <input type="text" class="form-control" id="basic-icon-default-fullname"
                                                name="name" placeholder="Josep Gilbert Andriano Simbolon, S. Kom.,"
                                                aria-label="Josep Gilbert Andriano Simbolon, S. Kom.,"
                                                aria-describedby="basic-icon-default-fullname2" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Input Email -->
                                <div class="row mb-6">
                                    <label class="col-sm-2 col-form-label" for="basic-icon-default-email">Email</label>
                                    <div class="col-sm-10">
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text"><i class="icon-base bx bx-envelope"></i></span>
                                            <input type="text" id="basic-icon-default-email" class="form-control"
                                                name="email" placeholder="gilbertsimbolon@smansatdo.com"
                                                aria-label="gilbertsimbolon@smansatdo.com"
                                                aria-describedby="basic-icon-default-email2" />
                                            <span id="basic-icon-default-email2"
                                                class="input-group-text">@smansatdo.com</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Input Password -->
                                <div class="row mb-6">
                                    <label class="col-sm-2 col-form-label" for="basic-icon-default-key">Password</label>
                                    <div class="col-sm-10">
                                        <div class="input-group input-group-merge">
                                            <span id="basic-icon-default-key2" class="input-group-text"><i
                                                    class="icon-base bx bx-lock-alt"></i></span>
                                            <input type="text" id="basic-icon-default-key" name="password"
                                                class="form-control" placeholder="Masukkan password"
                                                aria-label="Masukkan password" aria-describedby="basic-icon-default-key2" />
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center mb-4">
                                    <span class="text-uppercase small fw-semibold text-muted me-3">
                                        Personal
                                    </span>
                                    <hr class="flex-grow-1 m-0">
                                </div>

                                <!-- Input NIP -->
                                <div class="row mb-6">
                                    <label class="col-sm-2 col-form-label" for="basic-icon-default-phone">NIP</label>
                                    <div class="col-sm-10">
                                        <div class="input-group input-group-merge">
                                            <span id="basic-icon-default-phone2" class="input-group-text"><i
                                                    class="icon-base bx bx-id-card"></i></span>
                                            <input type="text" id="basic-icon-default-phone"
                                                class="form-control phone-mask" name="nip" placeholder="1239824142xxxxx"
                                                aria-label="1239824142xxxxx" aria-describedby="basic-icon-default-phone2" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Input No. Handphone -->
                                <div class="row mb-1">
                                    <label class="col-sm-2 col-form-label" for="basic-icon-default-phone">No.
                                        Telp</label>
                                    <div class="col-sm-10">
                                        <div class="input-group input-group-merge">
                                            <span id="basic-icon-default-phone2" class="input-group-text"><i
                                                    class="icon-base bx bx-phone"></i></span>
                                            <input type="text" id="basic-icon-default-phone" name="phone"
                                                class="form-control phone-mask" placeholder="0812 3456 7890"
                                                aria-label="0812 3456 7890"
                                                aria-describedby="basic-icon-default-phone2" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Button -->
                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        Tutup
                                    </button>

                                    <button type="submit" class="btn btn-success">
                                        Simpan
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <h5 class="card-header">Data Guru</h5>
            <div class="table-responsive text-nowrap">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th class="text-center">No.</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Nomor Induk Pegawai</th>
                            <th>Kelas</th>
                            <th>No. Handphone</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse ($teachers as $teacher)
                            <tr>
                                <!-- Nomor -->
                                <td class="text-center">
                                    {{ $loop->iteration }}
                                </td>

                                <!-- Nama -->
                                <td>{{ $teacher->user->name }}</td>

                                <!-- Email -->
                                <td>{{ $teacher->user->email }}</td>

                                <!-- NIP -->
                                <td>{{ $teacher->nip }}</td>

                                <!-- Kelas -->
                                <td>
                                    @if ($teacher->classes)
                                        <span class="badge bg-label-primary">
                                            {{ $teacher->classes->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- No. Telp -->
                                <td>{{ $teacher->phone }}</td>

                                <!-- Aksi -->
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-hide-arrow" data-bs-toggle="dropdown">
                                            <i class="icon-base bx bx-dots-vertical-rounded"></i> 
                                        </button>
                                        <div class="dropdown-menu"> 
                                            <a class="dropdown-item" href="javascript:void(0);">
                                                <i class="icon-base bx bx-edit-alt me-1"></i>
                                                Edit
                                            </a> 
                                            <a class="dropdown-item" href="javascript:void(0);">
                                                <i class="icon-base bx bx-trash me-1"></i>
                                                Delete
                                            </a> 
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Belum ada data.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
