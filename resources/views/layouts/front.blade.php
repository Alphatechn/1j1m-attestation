<!doctype html>
<html lang="fr">
<!--begin::Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>@yield('title')| CertiBot-1J1M </title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!--begin::Primary Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Bootstrap CSS (doit venir en premier car c'est le framework de base) -->
    @include('layouts.inc.css')
    @yield('styles')
</head>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg sidebar-mini bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
        <!--begin::Header-->
        @include('layouts.inc.navbar')
        <!--end::Header-->
        <!--begin::Sidebar-->
        @include('layouts.inc.sidebar')
        <!--end::Sidebar-->
        <!--begin::App Main-->
        <main class="app-main">
            <!--begin::App Content Header-->
            @yield('content')
            <!--end::App Content-->
        </main>
        <!--end::App Main-->
        <!--begin::Footer-->
        @include('layouts.inc.footer')
        <a href="#" id="back-to-top" class="btn btn-primary btn-lg rounded-circle"
            style="position: fixed; bottom: 40px; right: 40px; display: none; z-index: 9999;">
            <i class="bi bi-arrow-up"></i>
        </a>
        <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    @include('layouts.inc.js')
    <!--begin::OverlayScrollbars Configure-->
    <script>
        const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
        const Default = {
            scrollbarTheme: 'os-theme-light',
            scrollbarAutoHide: 'leave',
            scrollbarClickScroll: true,
        };
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
            if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: Default.scrollbarTheme,
                        autoHide: Default.scrollbarAutoHide,
                        clickScroll: Default.scrollbarClickScroll,
                    },
                });
            }
        });
    </script>
    <script>
        // Afficher/Masquer le bouton selon le scroll
        $(window).on('scroll', function() {
            if ($(this).scrollTop() > 200) {
                $('#back-to-top').fadeIn();
            } else {
                $('#back-to-top').fadeOut();
            }
        });

        // Scroll vers le haut au clic
        $('#back-to-top').on('click', function(e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: 0
            }, 500);
        });
    </script>
    @yield('scripts')
    @yield('scriptheader')
    @yield('scriptsidebar')

    <!--end::OverlayScrollbars Configure-->
    <!--end::Script-->
</body>
<!--end::Body-->

</html>
