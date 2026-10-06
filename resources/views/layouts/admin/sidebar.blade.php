<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme d-flex flex-column" style="height: 100vh; max-height: 100vh; overflow: hidden;">

    <!-- Logo (Fixed Header) -->
    <div class="sidebar-brand-custom d-flex flex-column align-items-center justify-content-center py-4 px-3 flex-shrink-0" 
        style="height: auto !important; min-height: 150px !important; position: sticky; top: 0; z-index: 10; background-color: #fff; width: 100%;">
        <div class="d-flex align-items-center justify-content-end w-100 position-relative">
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large position-absolute end-0 top-0 d-xl-none" style="top: -10px !important;">
                <i class="bx bx-chevron-left align-middle"></i>
            </a>
        </div>

        <a href="{{ route('admin.dashboard.index') }}" class="d-flex flex-column align-items-center text-center text-decoration-none w-100">
            <div class="mb-2 text-center">
                <img src="{{ asset('img/logo.png') }}" alt="Logo SMAN 2 Tondano" style="width: 55px; height: auto; display: inline-block;">
            </div>

            <span class="fw-bold text-uppercase fs-6 text-dark" style="letter-spacing: 0.5px; line-height: 1.2;">
                SMAN 2 Tondano
            </span>

            <hr class="w-75 my-2" style="border-color: #e0e0e0; opacity: 1;">

            <p class="text-muted mb-0" style="font-size: 11px; line-height: 1.4;">
                Sistem Kehadiran Pintar
            </p>
        </a>
    </div>

    <div class="menu-divider mt-0 flex-shrink-0"></div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1 flex-grow-1 overflow-auto" style="overflow-y: auto !important; height: calc(100vh - 150px);">

        <!-- Dashboard -->
        <li class="menu-item {{ request()->routeIs('admin.dashboard.*') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div>Dashboard</div>
            </a>
        </li>

        <!-- MASTER DATA -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Master Data</span>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.student.*') ? 'active' : '' }}">
            <a href="{{ route('admin.student.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div>Data Siswa</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.teacher.*') ? 'active' : '' }}">
            <a href="{{ route('admin.teacher.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-id-card"></i>
                <div>Data Guru</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.classes.*') && !request()->routeIs('admin.classes.promotion.*') ? 'active' : '' }}">
            <a href="{{ route('admin.classes.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-door-open"></i>
                <div>Data Kelas</div>
            </a>
        </li>

        <!-- AKADEMIK -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Akademik</span>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.promotion.*') || request()->routeIs('admin.classes.promotion.*') ? 'active' : '' }}">
            <a href="{{ route('admin.promotion.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-trending-up"></i>
                <div>Kenaikan Kelas</div>
            </a>
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
