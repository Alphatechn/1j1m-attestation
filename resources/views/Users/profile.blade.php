@extends('layouts.front')

@section('title', 'Profil')

@section('content')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Profil</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Profil</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3 col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile text-center">
                            <img id="profile-photo" class="profile-user-img img-fluid img-circle"
                                src="{{ asset(auth()->user()->photo ?? 'assets/images/default.jpg') }}"
                                alt="Photo de profil"
                                onerror="this.src='{{ asset('assets/images/default.jpg') }}'">
                            <h3 class="profile-username" id="profile-name">{{ auth()->user()->name }}</h3>
                            <p class="text-muted" id="profile-role">
                                {{ auth()->user()->roles->first()->name ?? 'Utilisateur' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-9 col-12">
                    <div class="card">
                        <div class="card-header p-2">
                            <ul class="nav nav-pills">
                                <li class="nav-item">
                                    <a class="nav-link active" href="#details" data-bs-toggle="tab">Détails</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#edit" data-bs-toggle="tab">Modifier</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#password" data-bs-toggle="tab">Mot de passe</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <!-- Détails -->
                                <div class="active tab-pane" id="details">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <b>Nom</b>
                                            <span id="detail-name">{{ auth()->user()->name }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <b>Email</b>
                                            <span id="detail-email">{{ auth()->user()->email ?? 'Non renseigné' }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <b>Rôle</b>
                                            <span id="detail-role">
                                                {{ auth()->user()->roles->first()->name ?? 'Utilisateur' }}
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <b>Membre depuis</b>
                                            <span>{{ auth()->user()->created_at->format('d/m/Y') }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Modifier infos -->
                                <div class="tab-pane" id="edit">
                                    <form id="profileEditForm" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <div class="mb-3">
                                            <label for="edit_name" class="form-label">Nom <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="edit_name" name="name"
                                                   value="{{ auth()->user()->name }}" required>
                                            <div class="invalid-feedback" id="name-error"></div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="edit_email" name="email"
                                                   value="{{ auth()->user()->email }}">
                                            <div class="invalid-feedback" id="email-error"></div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_photo" class="form-label">Photo de profil</label>

                                            <div class="mb-3">
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-outline-primary" id="capture-btn">
                                                        <i class="fas fa-camera"></i> Prendre une photo
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary" id="upload-btn">
                                                        <i class="fas fa-upload"></i> Télécharger
                                                    </button>
                                                </div>
                                            </div>

                                            <div id="camera-section" style="display: none;">
                                                <div class="mb-2">
                                                    <button type="button" class="btn btn-sm btn-info" id="switch-camera-btn">
                                                        <i class="fas fa-sync-alt"></i> Changer de caméra
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-success" id="validate-capture-btn">
                                                        <i class="fas fa-check"></i> Valider la capture
                                                    </button>
                                                </div>
                                                <video id="camera-stream" width="300" height="300" autoplay
                                                       style="display: none; border: 2px dashed #dee2e6; border-radius: 8px;"></video>
                                            </div>

                                            <input type="file" class="form-control d-none" id="edit_photo" name="photo" accept="image/*">

                                            <div id="preview-section" class="mt-3" style="display: none;">
                                                <div class="mb-2">
                                                    <button type="button" class="btn btn-sm btn-primary" id="validate-crop-btn">
                                                        <i class="fas fa-crop"></i> Valider le rognage
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-secondary" id="cancel-crop-btn">
                                                        <i class="fas fa-times"></i> Annuler
                                                    </button>
                                                </div>
                                                <div class="cropper-container">
                                                    <img id="preview-photo" src="" alt="Aperçu"
                                                         style="max-width: 100%; max-height: 300px;">
                                                </div>
                                                <canvas id="crop-canvas" style="display: none;"></canvas>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center">
                                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                                <i class="fas fa-save"></i> Enregistrer les modifications
                                            </button>
                                            <div id="editProfileMsg" class="ms-3"></div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Modifier mot de passe -->
                                <div class="tab-pane" id="password">
                                    <form id="passwordForm">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Mot de passe actuel <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="current_password"
                                                   name="current_password" required>
                                            <div class="invalid-feedback" id="current-password-error"></div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="password_new"
                                                   name="password" required minlength="6">
                                            <div class="form-text">Le mot de passe doit contenir au moins 6 caractères.</div>
                                            <div class="invalid-feedback" id="password-error"></div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="password_confirmation" class="form-label">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="password_confirmation"
                                                   name="password_confirmation" required>
                                            <div class="invalid-feedback" id="password-confirmation-error"></div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fas fa-key"></i> Changer le mot de passe
                                            </button>
                                            <div id="passwordMsg" class="ms-3"></div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .profile-user-img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border: 3px solid #dee2e6;
    }
    .cropper-container {
        max-width: 100%;
        margin: 0 auto;
    }
    .list-group-item {
        border: none;
        padding: 0.75rem 0;
    }
    .nav-pills .nav-link.active {
        background-color: #0d6efd;
    }
</style>
@endsection

@section('scripts')
<script>
    let cropper;
    let croppedBlob = null;
    let videoStream = null;
    let useFrontCamera = true;

    $(document).ready(function() {
        // Initialisation des valeurs du formulaire
        $('#edit_name').val('{{ auth()->user()->name }}');
        $('#edit_email').val('{{ auth()->user()->email }}');

        // Gestion de l'upload de fichier
        $('#upload-btn').on('click', function() {
            $('#edit_photo').click();
        });

        $('#edit_photo').on('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (!file.type.match('image.*')) {
                showAlert('Veuillez sélectionner une image valide.', 'error');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(event) {
                showPreview(event.target.result);
            };
            reader.readAsDataURL(file);
        });

        // Capture photo
        $('#capture-btn').on('click', function() {
            $('#camera-section').show();
            startCamera();
        });

        $('#switch-camera-btn').on('click', function() {
            useFrontCamera = !useFrontCamera;
            stopCamera();
            startCamera();
        });

        $('#validate-capture-btn').on('click', function() {
            capturePhoto();
        });

        $('#cancel-crop-btn').on('click', function() {
            resetPhotoSection();
        });

        $('#validate-crop-btn').on('click', function() {
            validateCrop();
        });

        // Soumission du formulaire de profil
        $('#profileEditForm').on('submit', function(e) {
            e.preventDefault();
            updateProfile();
        });

        // Soumission du formulaire de mot de passe
        $('#passwordForm').on('submit', function(e) {
            e.preventDefault();
            updatePassword();
        });

        function startCamera() {
            const constraints = {
                video: {
                    facingMode: useFrontCamera ? "user" : "environment",
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };

            navigator.mediaDevices.getUserMedia(constraints)
                .then(stream => {
                    const video = document.getElementById('camera-stream');
                    video.srcObject = stream;
                    videoStream = stream;
                    video.style.display = 'block';
                    $('#validate-capture-btn').show();
                })
                .catch(err => {
                    console.error('Erreur caméra:', err);
                    showAlert('Impossible d\'accéder à la caméra: ' + err.message, 'error');
                });
        }

        function stopCamera() {
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
                videoStream = null;
            }
            $('#camera-stream').hide();
            $('#validate-capture-btn').hide();
        }

        function capturePhoto() {
            const video = document.getElementById('camera-stream');
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            showPreview(canvas.toDataURL('image/jpeg'));
            stopCamera();
            $('#camera-section').hide();
        }

        function showPreview(dataUrl) {
            $('#preview-section').show();
            $('#preview-photo').attr('src', dataUrl);

            if (cropper) {
                cropper.destroy();
            }

            cropper = new Cropper(document.getElementById('preview-photo'), {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 0.8,
                movable: true,
                rotatable: true,
                scalable: true,
                zoomable: true
            });
        }

        function validateCrop() {
            if (cropper) {
                cropper.getCroppedCanvas().toBlob(function(blob) {
                    croppedBlob = blob;
                    showAlert('Photo prête à être enregistrée!', 'success');
                    $('#validate-crop-btn').hide();
                }, 'image/jpeg', 0.9);
            }
        }

        function resetPhotoSection() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            croppedBlob = null;
            $('#preview-section').hide();
            $('#camera-section').hide();
            $('#edit_photo').val('');
            stopCamera();
        }

        function updateProfile() {
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('name', $('#edit_name').val());
            formData.append('email', $('#edit_email').val());

            if (croppedBlob) {
                formData.append('photo', croppedBlob, 'profile.jpg');
            }

            $('#submit-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

            $.ajax({
                url: '{{ route("users.profile.update") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    showAlert(response.message, 'success');
                    $('#submit-btn').prop('disabled', false).html('<i class="fas fa-save"></i> Enregistrer les modifications');

                    // Mettre à jour l'affichage
                    if (response.data.photo) {
                        $('#profile-photo').attr('src', response.data.photo + '?t=' + new Date().getTime());
                    }
                    $('#profile-name').text(response.data.name);
                    $('#detail-name').text(response.data.name);
                    $('#detail-email').text(response.data.email);

                    resetPhotoSection();
                },
                error: function(xhr) {
                    $('#submit-btn').prop('disabled', false).html('<i class="fas fa-save"></i> Enregistrer les modifications');

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        showValidationErrors(errors);
                    } else {
                        showAlert(xhr.responseJSON?.message || 'Erreur lors de la mise à jour du profil', 'error');
                    }
                }
            });
        }

        function updatePassword() {
            const formData = {
                current_password: $('#current_password').val(),
                password: $('#password_new').val(),
                password_confirmation: $('#password_confirmation').val()
            };

            $('#passwordForm button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Modification...');

            $.ajax({
                url: '{{ route("users.password.update") }}',
                method: 'PUT',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    showAlert(response.message, 'success', '#passwordMsg');
                    $('#passwordForm')[0].reset();
                    $('#passwordForm button[type="submit"]').prop('disabled', false).html('<i class="fas fa-key"></i> Changer le mot de passe');
                    clearValidationErrors();
                },
                error: function(xhr) {
                    $('#passwordForm button[type="submit"]').prop('disabled', false).html('<i class="fas fa-key"></i> Changer le mot de passe');

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        console.log(errors);
                        showValidationErrors(errors, 'password');
                    } else {
                        showAlert(xhr.responseJSON?.message || 'Erreur lors de la modification du mot de passe', 'error', '#passwordMsg');
                    }
                }
            });
        }

        function showValidationErrors(errors, prefix = '') {
            // Clear previous errors
            clearValidationErrors();

            // Show new errors
            $.each(errors, function(field, messages) {
                const fieldId = prefix ? field : `edit_${field}`;
                const errorId = prefix ? `${field}-error` : `${field}-error`;
                $(`#${fieldId}`).addClass('is-invalid');
                $(`#${errorId}`).text(messages[0]).show();
            });
        }

        function clearValidationErrors() {
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').hide();
        }

        function showAlert(message, type = 'info', container = '#editProfileMsg') {
            const colors = {
                success: 'green',
                error: 'red',
                warning: 'orange',
                info: 'blue'
            };

            $(container).html(`<span style="color: ${colors[type]}">${message}</span>`);

            if (type === 'success') {
                setTimeout(() => $(container).empty(), 5000);
            }
        }
    });
</script>
@endsection
