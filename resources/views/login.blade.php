@extends('layouts.app')

@section('title', 'Sistem Absensi | SMAN 1 Tondano')

@section('content')
    <div class="container min-vh-100 d-flex justify-content-center align-items-center">
        <div class="card shadow-lg border-0 rounded-5 overflow-hidden" style="width: 80rem; height: 40rem;">
            <div class="row g-0 h-100">
                <!-- Bagian Logo -->
                <div class="col-lg-6 d-flex flex-column justify-content-center align-items-center bg-info">

                    <img src="{{ asset('img/logo.png') }}" alt="Logo Sekolah" class="w-25" data-aos="flip-left"
                        data-aos-easing="ease-out-cubic" data-aos-duration="2000">

                    <h1 class="text-white fw-bold" data-aos="fade-right" data-aos-duration="2000">SMA Negeri 1 Tondano</h1>
                    <p class="text-white-50 text-center" data-aos="fade-right" data-aos-duration="3000">Smart Attedance System using Face Recognition Technology.</p>

                    <div data-aos="flip-left" data-aos-duration="2500">
                        <small class="text-white-50">
                            &copy; {{ date('Y') }} <a href="https://www.instagram.com/gilbertsmbln" target="_blank"
                                class="text-white-50 text-decoration-none">
                                bolonsite |
                            </a>SMA Negeri 1 Tondano.
                        </small>
                    </div>
                </div>

                <!-- Form Layout -->
                <div class="col-lg-6 d-flex flex-column justify-content-center align-items-center" data-aos="fade-down-right" data-aos-duration="2000">
                    <h4 class="fw-bold">Smart Attedance System</h4>
                    <p>Silahkan login terlebih dahulu.</p>

                    <form action="{{ route('login.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" aria-describedby="emailHelp" placeholder="xxxxxxxxxxx@smansatdo.com">
                            <div id="emailHelp" class="form-text">Gunakan akun yang sudah diberikan oleh admin.</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>

                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password">

                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success">Masuk</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- script fungsi show password -->
    <script>
        const password = document.getElementById('password');
        const toggle = document.getElementById('togglePassword');
        const icon = toggle.querySelector('i');

        toggle.addEventListener('click', function() {

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }

        });
    </script>
@endsection
