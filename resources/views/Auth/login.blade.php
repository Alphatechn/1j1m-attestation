<!doctype html>
<html lang="fr">
<!--begin::Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Connexion | Plateforme d'Attestations</title>
    <!--begin::Primary Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('layouts.inc.css')

    <style>
        :root {
            --primary-gold: #FFD700;
            --secondary-gold: #FFA500;
            --dark-gold: #B8860B;
            --black: #000000;
            --dark-gray: #1a1a1a;
            --light-gray: #f5f5f5;
            --white: #ffffff;
        }

        body {
            position: relative;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("{{ asset('assets/images/arr.png') }}");
            background-size: cover;
            background-position: center;
            filter: blur(2px);
            z-index: -5;
        }

        .login-box {
            width: 400px;
            max-width: 90%;
        }

        .bgs-transparent-h {
            background-color: rgb(255, 255, 255);
            border-radius: 10px;
            border-top: 5px solid #FFA500;
            border-bottom: 5px solid #FFA500;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid #dee2e6;
            padding: 1.5rem;
        }

        .login-card-body {
            padding: 2rem;
        }

        .login-box-msg {
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 500;
            color: #495057;
        }

        .input-group-text {
            background-color: #f8f9fa;
        }

        .form-floating > .form-control {
            height: calc(3.5rem + 2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            color: var(--black);
            border: 0px solid var(--black);
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 700;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-gold), var(--dark-gold));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
            color: var(--black);
            border-color: var(--black);
        }

        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }

        .alert {
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }

        #loginBtn {
            position: relative;
        }

        #loginBtn.loading {
            pointer-events: none;
            opacity: 0.7;
        }
    </style>
</head>

<body class="login-page bg-body-secondary">
    <div class="login-box">
        <div class="card bgs-transparent-h">
            <div class="card-header">
                <a href="{{ url('/') }}"
                    class="link-dark text-center link-offset-2 link-opacity-100 link-opacity-50-hover d-block">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Logo" style="max-height: 50px;">
                </a>
            </div>

            <div class="card-body login-card-body">
                <p class="login-box-msg">Connectez-vous pour commencer votre session</p>

                <!-- Messages de succès/erreur -->
                <div id="alertContainer"></div>

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form id="loginForm" method="POST" action="{{ route('login.post') }}">
                    @csrf

                    <div class="input-group mb-1 flex-nowrap">
                        <div class="form-floating flex-grow-1">
                            <input id="login"
                                   name="login"
                                   type="text"
                                   class="form-control @error('login') is-invalid @enderror"
                                   value="{{ old('login') }}"
                                   placeholder="Login"
                                   autofocus
                                   required />
                            <label for="login">Login</label>
                        </div>
                        <div class="input-group-text">
                            <span class="bi bi-person-fill"></span>
                        </div>
                    </div>
                    <span id="error_login" class="error-message"></span>

                    <div class="input-group mb-1 mt-3">
                        <div class="form-floating flex-grow-1">
                            <input id="password"
                                   name="password"
                                   type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Mot de passe"
                                   required />
                            <label for="password">Mot de passe</label>
                        </div>
                        <div class="input-group-text">
                            <span class="bi bi-lock-fill"></span>
                        </div>
                    </div>
                    <span id="error_password" class="error-message"></span>

                    <!-- Remember Me (optionnel) -->
                    <div class="form-check mt-3 mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">
                            Se souvenir de moi
                        </label>
                    </div>

                    <!--begin::Row-->
                    <div class="row mt-3">
                        <div class="col-12">
                            <button id="loginBtn" class="btn btn-primary w-100" type="submit">
                                <span id="btnText">Se Connecter</span>
                                <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </span>
                            </button>
                        </div>
                    </div>
                    <!--end::Row-->
                </form>
            </div>

            <div class="card-footer text-center bg-transparent">
                <h6 class="mb-0">© {{ date('Y') }} 1J1M – Tous droits réservés.</h6>
            </div>
        </div>
    </div>

    @include('layouts.inc.js')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            const alertContainer = document.getElementById('alertContainer');

            // Fonction pour afficher les alertes
            function showAlert(message, type = 'danger') {
                const alert = document.createElement('div');
                alert.className = `alert alert-${type} alert-dismissible fade show`;
                alert.role = 'alert';
                alert.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                alertContainer.innerHTML = '';
                alertContainer.appendChild(alert);

                // Auto-fermer après 5 secondes
                setTimeout(() => {
                    alert.classList.remove('show');
                    setTimeout(() => alert.remove(), 150);
                }, 5000);
            }

            // Fonction pour afficher les erreurs de champs
            function showFieldError(fieldId, message) {
                const errorSpan = document.getElementById('error_' + fieldId);
                const inputField = document.getElementById(fieldId);

                if (errorSpan) {
                    errorSpan.textContent = message;
                }
                if (inputField) {
                    inputField.classList.add('is-invalid');
                }
            }

            // Fonction pour effacer les erreurs
            function clearErrors() {
                document.getElementById('error_login').textContent = '';
                document.getElementById('error_password').textContent = '';
                document.getElementById('login').classList.remove('is-invalid');
                document.getElementById('password').classList.remove('is-invalid');
                alertContainer.innerHTML = '';
            }

            // Soumission du formulaire
            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Effacer les erreurs précédentes
                clearErrors();

                // Désactiver le bouton et afficher le spinner
                loginBtn.classList.add('loading');
                btnText.classList.add('d-none');
                btnSpinner.classList.remove('d-none');

                const formData = new FormData(loginForm);

                try {
                    const response = await fetch("{{ route('login.post') }}", {
                        method: "POST",
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: formData
                    });

                    const result = await response.json();

                    if (response.status === 422) {
                        // Erreurs de validation
                        if (result.errors) {
                            if (result.errors.login) {
                                showFieldError('login', result.errors.login[0]);
                            }
                            if (result.errors.password) {
                                showFieldError('password', result.errors.password[0]);
                            }
                        }
                        showAlert(result.message || 'Veuillez corriger les erreurs.', 'danger');
                    } else if (response.status === 429) {
                        // Trop de tentatives
                        showAlert(result.message, 'warning');
                    } else if (response.status === 403) {
                        // Compte désactivé
                        showAlert(result.message, 'danger');
                    } else if (response.ok) {
                        // Succès
                        showAlert(result.message || 'Connexion réussie ! Redirection...', 'success');

                        // Redirection après 1 seconde
                        setTimeout(() => {
                            window.location.href = result.redirect || "{{ url('/home') }}";
                        }, 1000);
                    } else {
                        // Autre erreur
                        showAlert(result.message || 'Une erreur est survenue.', 'danger');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    showAlert('Erreur de connexion au serveur.', 'danger');
                } finally {
                    // Réactiver le bouton
                    loginBtn.classList.remove('loading');
                    btnText.classList.remove('d-none');
                    btnSpinner.classList.add('d-none');
                }
            });

            // Effacer les erreurs lors de la saisie
            document.getElementById('login').addEventListener('input', function() {
                this.classList.remove('is-invalid');
                document.getElementById('error_login').textContent = '';
            });

            document.getElementById('password').addEventListener('input', function() {
                this.classList.remove('is-invalid');
                document.getElementById('error_password').textContent = '';
            });
        });
    </script>
</body>

</html>
