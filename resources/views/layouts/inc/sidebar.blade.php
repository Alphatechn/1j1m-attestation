<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!--begin::Sidebar Brand-->
    <div class="sidebar-brand">
        <!--begin::Brand Link-->
        <a href="#" class="brand-link">
            <!--begin::Brand Image-->
            <img src="{{ asset('assets/images/logoside.png') }}" alt="CertiBot Logo" class="brand-image shadow" />
            <!--end::Brand Image-->
            <!--begin::Brand Text-->
            <span class="brand-text fw-white">CertiBot</span>
            <!--end::Brand Text-->
        </a>
        <!--end::Brand Link-->
    </div>
    <!--end::Sidebar Brand-->
    <!--begin::Sidebar Wrapper-->
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <!--begin::Sidebar Menu-->
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('dashboard.manage') }}" class="nav-link {{ request()->routeIs('dashboard.manage') ? 'active' : '' }}">
                        <!-- Dashboard -->
                        <i class="nav-icon bi bi-speedometer2"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('periodes.index') }}" class="nav-link {{ request()->routeIs('periodes.index') ? 'active' : '' }}">
                        <!-- Périodes -->
                        <i class="nav-icon bi bi-calendar"></i>
                        <p>Périodes</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}">
                        <!-- Utilisateurs -->
                        <i class="nav-icon bi bi-person"></i>
                        <p>Utilisateurs</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('participants.index') }}" class="nav-link {{ request()->routeIs('participants.index') ? 'active' : '' }}">
                        <!-- Participants -->
                        <i class="nav-icon bi bi-people"></i>
                        <p>Participants</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('attestations.index') }}" class="nav-link {{ request()->routeIs('attestations.index') ? 'active' : '' }}">
                        <!-- Attestations -->
                        <i class="nav-icon bi bi-file-earmark-text"></i>
                        <p>Attestations</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('attestations.bulk.index') ? 'active' : '' }}" href="{{ route('attestations.bulk.index') }}">
                        <i class="nav-icon bi bi-send"></i>
                        <p>Envoi Massif</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('users.profile') }}" class="nav-link {{ request()->routeIs('users.profile') ? 'active' : '' }}">
                        <!-- Profile -->
                        <i class="nav-icon bi bi-person"></i>
                        <p>Profile</p>
                    </a>
                </li>
            </ul>

            <!--end::Sidebar Menu-->
        </nav>
    </div>
    <!--end::Sidebar Wrapper-->
</aside>

@section('scriptsidebar')
    <style>
        .nav-link.active {
            background-color: #0d6efd !important;
            color: #fff !important;
        }
    </style>
@endsection
