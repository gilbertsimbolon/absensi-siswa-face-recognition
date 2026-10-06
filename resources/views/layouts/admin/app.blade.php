<!DOCTYPE html>
<html lang="en" class="layout-menu-fixed layout-compact">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/x-icon">
    <title>@yield('title')</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- AOS CSS -->
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">

    <link rel="stylesheet" href="css/app.css">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/fonts/iconify-icons.css') }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat/assets/css/demo.css') }}" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />

    <!-- endbuild -->
    <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/libs/apex-charts/apex-charts.css') }}" />

    <style>
        html, body {
            height: 100vh;
            overflow: hidden;
        }
        .layout-wrapper, .layout-container {
            height: 100vh;
            max-height: 100vh;
            overflow: hidden;
        }
        .layout-page {
            height: 100vh;
            max-height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .content-wrapper {
            flex: 1 1 auto;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 0;
        }
        .content-wrapper > .container-xxl {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-height: 0;
            padding-top: 1rem !important;
            padding-bottom: 0.5rem !important;
        }
        .content-footer {
            flex-shrink: 0;
        }
        /* Custom scrollbar untuk tabel */
        .table-responsive::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 4px;
        }
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #aaa;
        }
        /* Sticky header tabel */
        .table-sticky-header thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background-color: #f5f5f9 !important;
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.08);
        }
    </style>

    <!-- Helpers -->
    <script src="{{ asset('sneat/assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('sneat/assets/js/config.js') }}"></script>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            @if (!isset($hideLayout) || !$hideLayout)
                @include('layouts.admin.sidebar')
            @endif

            <div class="layout-page">

                @if (!isset($hideLayout) || !$hideLayout)
                    @include('layouts.admin.navbar')
                @endif

                <div class="content-wrapper">

                    <div class="container-xxl flex-grow-1 container-p-y">
                        @yield('content')
                    </div>
                    @if (!isset($hideLayout) || !$hideLayout)
                        @include('layouts.admin.footer')
                    @endif
                </div>

            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="{{ asset('sneat/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('sneat/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('sneat/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('sneat/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('sneat/assets/vendor/js/menu.js') }}"></script>

    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

    <script>
        AOS.init();
    </script>

    <script src="{{ asset('sneat/assets/vendor/js/menu.js') }}"></script>

    <!-- Vendors JS -->
    <script src="{{ asset('sneat/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>

    <!-- Main JS -->
    <script src="{{ asset('sneat/assets/js/main.js') }}"></script>

    <!-- Page JS -->
    <script src="{{ asset('sneat/assets/js/dashboards-analytics.js') }}"></script>
</body>

</html>
