<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

    <!-- Logo -->
    <div class="app-brand demo">
        <a href="{{ route('admin.dashboard.index') }}" class="app-brand-link d-flex align-items-center">
            <span class="app-brand-logo demo">
                <img src="{{ asset('img/logo.png') }}" alt="Logo" style="width: 40px">
            </span>

            <div class="ms-2 mt-1 d-flex flex-column">
                <span class="fw-bold text-uppercase" style="font-size:16px;">
                    SMAN 1 Tondano
                </span>

                <hr class="my-1" style="border-color:#bdbdbd;opacity:1;">

                <small class="text-muted text-center" style="font-size:11px;">
                    Smart Attendance System
                </small>
            </div>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="bx bx-chevron-left d-block d-xl-none align-middle"></i>
        </a>
    </div>

    <div class="menu-divider mt-0"></div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">

        <!-- Dashboard -->
        <li class="menu-item active">
            <a href="{{ route('admin.dashboard.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div>Dashboard</div>
            </a>
        </li>

        <!-- MASTER DATA -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Master Data</span>
        </li>

        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div>Master Data</div>
            </a>

            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Data Siswa</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Data Guru</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="{{ route('admin.classes.index') }}" class="menu-link">
                        <div>Data Kelas</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Mata Pelajaran</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Tahun Ajaran</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- PERANGKAT -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Perangkat</span>
        </li>

        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-camera"></i>
                <div>Perangkat</div>
            </a>

            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Hubungkan Device</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- ABSENSI -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Absensi</span>
        </li>

        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-calendar-check"></i>
                <div>Absensi</div>
            </a>

            <ul class="menu-sub">

                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Absensi Siswa</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Riwayat Absensi</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Rekap Kehadiran</div>
                    </a>
                </li>

            </ul>
        </li>

        <!-- LAPORAN -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Laporan</span>
        </li>

        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                <div>Laporan</div>
            </a>

            <ul class="menu-sub">

                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Laporan Harian</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Laporan Bulanan</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Laporan per Kelas</div>
                    </a>
                </li>

            </ul>
        </li>

        <!-- MANAJEMEN USER -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Manajemen</span>
        </li>

        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div>Manajemen User</div>
            </a>

            <ul class="menu-sub">

                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Data User</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Role & Hak Akses</div>
                    </a>
                </li>

            </ul>
        </li>

        <!-- PENGATURAN -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Pengaturan</span>
        </li>

        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-cog"></i>
                <div>Pengaturan</div>
            </a>

            <ul class="menu-sub">

                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Profil Sekolah</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Jam Sekolah</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div>Backup Database</div>
                    </a>
                </li>

            </ul>
        </li>

    </ul>

</aside>
