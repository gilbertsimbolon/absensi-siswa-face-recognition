@extends('layouts.admin.app')

@section('title', 'Data Guru | SMAN 1 Tondano')

@section('content')
    <div class="mt-0">
        <div class=" d-flex justify-content-end align-items-center mb-5">
            <button type="button" class="btn btn-success">Tambah Data</button>
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
                        <tr>
                            <!-- Nomor -->
                            <td class="text-center">
                                1
                            </td>

                            <!-- Nama -->
                            <td>
                                Gilbert Simbolon
                            </td>

                            <!-- Email -->
                            <td>
                                gilbertsimbolon@smansatdo.com
                            </td>

                            <!-- NIP -->
                            <td>
                                1271090107050001
                            </td>

                            <!-- Kelas -->
                            <td>
                                <span class="badge bg-label-primary me-1">XI-3</span>
                            </td>

                            <!-- No. Handphone -->
                            <td>
                                085399684844
                            </td>

                            <!-- Aksi -->
                            <td class="text-center">
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="icon-base bx bx-edit-alt me-1"></i> Edit</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="icon-base bx bx-trash me-1"></i> Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
