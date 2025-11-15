<nav class="app-header navbar navbar-expand bg-body sticky-top ">
    <!--begin::Container-->
    <div class="container-fluid">
        <!--begin::Start Navbar Links-->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="bi bi-list"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <b class="text-danger" id="countdown"></b>
                </a>
            </li>

        </ul>
        <!--end::Start Navbar Links-->
        <!--begin::End Navbar Links-->
        <ul class="navbar-nav ms-auto">
            <!--begin::Fullscreen Toggle-->
            <li class="nav-item">
                <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                    <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                    <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
                </a>
            </li>

            <!--end::Fullscreen Toggle-->
            <!--begin::User Menu Dropdown-->
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <img id="user-photo" src="{{ asset(auth()->user()->photo ?? 'assets/images/default.jpg') }}"
                                alt="Photo de profil"
                                onerror="this.src='{{ asset('assets/images/default.jpg') }}'" class="user-image rounded-circle shadow" alt="User Image" />
                    <span id="user-name" class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                    <!--begin::User Image-->
                    <li class="user-header text-bg-primary">
                        <img id="dropdown-user-photo" src="{{ asset(auth()->user()->photo ?? 'assets/images/default.jpg') }}" class="rounded-circle shadow" alt="User Image" />
                        <p>
                        <h6 id="dropdown-user-name">{{ auth()->user()->name }}</h6>
                        <small id="dropdown-role">{{ auth()->user()->roles->first()->name ?? 'Utilisateur' }}</small>
                        </p>
                    </li>
                    <!--end::User Image-->

                    <!--begin::Menu Footer-->
                    <li class="user-footer">
                        <a href="{{ route('users.profile') }}" class="btn btn-default btn-flat">Profile</a>
                        <a href="#" id="logout" class="btn btn-default btn-flat float-end">Déconnexion</a>
                    </li>
                    <!--end::Menu Footer-->
                </ul>
            </li>
            <!--end::User Menu Dropdown-->
        </ul>
        <!--end::End Navbar Links-->
    </div>
    <!--end::Container-->
</nav>

@section('scriptheader')
    <script>

        function logoutUser() {
            $.ajax({
                method: "POST",
                url: "/logout",
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                complete: function() {
                    window.location.href = "/login";
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {

            // Déconnexion - Nettoyer le localStorage
            const logoutBtn = document.getElementById('logout');
            if (!logoutBtn) {
                console.warn("Bouton logout non trouvé.");
                return;
            }
            logoutBtn.addEventListener('click', function() {
                Swal.fire({
                    title: "Êtes-vous sûr de vouloir vous déconnecter ?",
                    text: "Cette action est irréversible !",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Oui, me déconnecter",
                    cancelButtonText: "Annuler"
                }).then((result) => {
                    if (result.isConfirmed) {
                        logoutUser();
                    }
                });
            });
        });

        // Durée du compte à rebours en secondes (10 minutes = 600 secondes)
        var countdownTime = 600;

        function updateCountdown() {
            var countdownElement = document.getElementById('countdown');
            var minutes = Math.floor(countdownTime / 60);
            var seconds = countdownTime % 60;
            countdownElement.innerHTML = '[ ' + minutes + " : " + seconds + " ] ";
            countdownTime--;

            if (countdownTime < 0) {
                clearInterval(countdownInterval);
                logoutUser();
            }
        }

        var countdownInterval = setInterval(updateCountdown, 1000);
    </script>
@endsection
