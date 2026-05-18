@extends('layouts.public')

@section('title', 'Demande d\'attestation')

@section('page-subtitle', 'Formulaire de validation des participants 1J1M')

@section('content')
<div class="public-container">
    <div class="request-hero">
        <div>
            <span class="eyebrow">Validation participant</span>
            <h1>Demande d'attestation 1J1M</h1>
            <p>Renseignez vos informations de formation. L'équipe vérifiera votre dossier avant la génération de votre attestation.</p>
        </div>
        <div class="hero-mark">
            <i class="bi bi-patch-check"></i>
        </div>
    </div>

    <div class="container-fluid p-4 p-lg-5">
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        @if(session('existing_attestation'))
            <div class="alert alert-warning border-0 shadow-sm">
                <h5 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Attestation déjà disponible</h5>
                <p class="mb-3">{{ session('existing_attestation.message') }} Numéro: <strong>{{ session('existing_attestation.number') }}</strong></p>
                <a href="{{ session('existing_attestation.preview_url') }}" target="_blank" class="btn btn-dark me-2">
                    <i class="bi bi-eye me-1"></i>Visualiser
                </a>
                <a href="{{ session('existing_attestation.download_url') }}" class="btn btn-primary-custom">
                    <i class="bi bi-download me-1"></i>Télécharger
                </a>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                <strong>Merci de corriger les champs signalés.</strong>
            </div>
        @endif

        <form method="POST" action="{{ route('public.participants.request.store') }}" enctype="multipart/form-data" class="request-form">
            @csrf

            <div class="section-title">
                <span>1</span>
                <h2>Identité et contact</h2>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Civilité <span class="text-danger">*</span></label>
                    <select name="civility" class="form-select @error('civility') is-invalid @enderror" required>
                        <option value="">Choisir</option>
                        @foreach(['M.' => 'M.', 'Mme' => 'Mme', 'Mlle' => 'Mlle'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('civility') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('civility')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-9">
                    <label class="form-label">Noms et prénoms complets <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="Ex: Lionel TEJEM" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Adresse email <span class="text-danger">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="lioneltejem@gmail.com" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ville <span class="text-danger">*</span></label>
                    <input type="text" name="city" value="{{ old('city') }}" class="form-control @error('city') is-invalid @enderror" placeholder="Yaoundé" required>
                    @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Numéro WhatsApp <span class="text-danger">*</span></label>
                    <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" class="form-control @error('whatsapp') is-invalid @enderror" placeholder="+237 6 XX XX XX XX" required>
                    @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="section-title mt-5">
                <span>2</span>
                <h2>Formation suivie</h2>
            </div>

            <div class="row g-3">
                @error('periode')
                    <div class="col-12">
                        <div class="alert alert-warning border-0">{{ $message }}</div>
                    </div>
                @enderror
                <div class="col-12">
                    <label class="form-label">Groupe de formation <span class="text-danger">*</span></label>
                    <input type="text" name="training_group" value="{{ old('training_group') }}" class="form-control @error('training_group') is-invalid @enderror" placeholder="Ex: G10A" required>
                    @error('training_group')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Noms et WhatsApp de 3 camarades de promo <span class="text-danger">*</span></label>
                    <textarea name="classmates_contacts" rows="3" class="form-control @error('classmates_contacts') is-invalid @enderror" required>{{ old('classmates_contacts') }}</textarea>
                    @error('classmates_contacts')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="section-title mt-5">
                <span>3</span>
                <h2>Compétences et preuves</h2>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Comment les cours sont dispensés à 1J1M ? <span class="text-danger">*</span></label>
                    <textarea name="course_delivery_explanation" rows="4" class="form-control @error('course_delivery_explanation') is-invalid @enderror" required>{{ old('course_delivery_explanation') }}</textarea>
                    @error('course_delivery_explanation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Compétences techniques acquises ou renforcées <span class="text-danger">*</span></label>
                    <textarea name="technical_skills" rows="4" class="form-control @error('technical_skills') is-invalid @enderror" required>{{ old('technical_skills') }}</textarea>
                    @error('technical_skills')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Art oratoire: structure et techniques d'attention <span class="text-danger">*</span></label>
                    <textarea name="public_speaking_description" rows="4" class="form-control @error('public_speaking_description') is-invalid @enderror" required>{{ old('public_speaking_description') }}</textarea>
                    @error('public_speaking_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Étapes pour mettre en place une activité e-commerce <span class="text-danger">*</span></label>
                    <textarea name="ecommerce_steps" rows="4" class="form-control @error('ecommerce_steps') is-invalid @enderror" required>{{ old('ecommerce_steps') }}</textarea>
                    @error('ecommerce_steps')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Comment comptez-vous mettre ces compétences à profit ? <span class="text-danger">*</span></label>
                    <textarea name="knowledge_use_plan" rows="4" class="form-control @error('knowledge_use_plan') is-invalid @enderror" required>{{ old('knowledge_use_plan') }}</textarea>
                    @error('knowledge_use_plan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Nom, prénom et matière de vos 5 coachs <span class="text-danger">*</span></label>
                    <textarea name="coaches" rows="4" class="form-control @error('coaches') is-invalid @enderror" required>{{ old('coaches') }}</textarea>
                    @error('coaches')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Capture d'écran d'un devoir rendu <span class="text-danger">*</span></label>
                    <input type="file" name="homework_screenshots[]" class="form-control @error('homework_screenshots') is-invalid @enderror @error('homework_screenshots.*') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp" multiple required>
                    <div class="form-text">Affiche publicitaire, art oratoire ou autre devoir. Plusieurs images possibles: JPG, PNG ou WEBP, 4 Mo max par image.</div>
                    <div id="selected_screenshots_preview" class="selected-screenshots-preview mt-3"></div>
                    @error('homework_screenshots')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('homework_screenshots.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('public.home') }}" class="btn btn-outline-dark">
                    <i class="bi bi-arrow-left me-1"></i>Retour
                </a>
                <button type="submit" class="btn btn-primary-custom">
                    <i class="bi bi-send-check me-1"></i>Envoyer ma demande
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('styles')
<style>
    .request-hero {
        align-items: center;
        background: linear-gradient(135deg, #070707 0%, #24201a 55%, #d99a00 100%);
        color: #fff;
        display: flex;
        justify-content: space-between;
        gap: 2rem;
        padding: 2.5rem;
    }

    .request-hero h1 {
        font-size: clamp(2rem, 4vw, 3.6rem);
        font-weight: 800;
        letter-spacing: 0;
        margin: .35rem 0 .75rem;
        overflow-wrap: anywhere;
    }

    .request-hero p {
        color: rgba(255,255,255,.84);
        font-size: 1.05rem;
        margin: 0;
        max-width: 720px;
    }

    .eyebrow {
        color: var(--primary-gold);
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0;
    }

    .hero-mark {
        align-items: center;
        border: 2px solid rgba(255,215,0,.6);
        border-radius: 999px;
        display: flex;
        flex: 0 0 116px;
        height: 116px;
        justify-content: center;
        width: 116px;
    }

    .hero-mark i {
        color: var(--primary-gold);
        font-size: 4rem;
    }

    .request-form {
        background: #fff;
        min-width: 0;
    }

    .section-title {
        align-items: center;
        display: flex;
        gap: .8rem;
        margin-bottom: 1rem;
    }

    .section-title span {
        align-items: center;
        background: var(--black);
        border: 2px solid var(--primary-gold);
        border-radius: 50%;
        color: var(--primary-gold);
        display: inline-flex;
        font-weight: 800;
        height: 38px;
        justify-content: center;
        width: 38px;
    }

    .section-title h2 {
        font-size: 1.25rem;
        font-weight: 800;
        margin: 0;
        overflow-wrap: anywhere;
    }

    .request-form textarea {
        min-height: 120px;
        resize: vertical;
    }

    .request-form .form-control,
    .request-form .form-select {
        min-width: 0;
        width: 100%;
    }

    .selected-screenshots-preview {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    }

    .selected-screenshot {
        border: 1px solid #e4e4e4;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
    }

    .selected-screenshot img {
        aspect-ratio: 4 / 3;
        display: block;
        object-fit: cover;
        width: 100%;
    }

    .selected-screenshot span {
        display: block;
        font-size: .78rem;
        padding: 8px;
        text-align: center;
        overflow-wrap: anywhere;
    }

    .form-actions {
        align-items: center;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 1.5rem;
    }

    @media (max-width: 768px) {
        .request-hero {
            align-items: flex-start;
            flex-direction: column;
            padding: 1.5rem;
        }

        .request-hero h1 {
            font-size: 2rem;
            line-height: 1.08;
        }

        .request-hero p {
            font-size: .98rem;
        }

        .hero-mark {
            height: 82px;
            width: 82px;
        }

        .hero-mark i {
            font-size: 2.75rem;
        }

        .form-actions {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .form-actions .btn {
            width: 100%;
        }
    }

    @media (max-width: 420px) {
        .request-hero {
            padding: 1.15rem;
        }

        .section-title {
            align-items: flex-start;
        }

        .section-title span {
            flex: 0 0 34px;
            height: 34px;
            width: 34px;
        }

        .section-title h2 {
            font-size: 1.08rem;
            line-height: 1.25;
        }

        .selected-screenshots-preview {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.querySelector('input[name="homework_screenshots[]"]');
    const preview = document.getElementById('selected_screenshots_preview');

    if (!input || !preview) {
        return;
    }

    input.addEventListener('change', function () {
        preview.innerHTML = '';

        Array.from(input.files || []).forEach((file, index) => {
            if (!file.type.startsWith('image/')) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                const item = document.createElement('div');
                item.className = 'selected-screenshot';
                item.innerHTML = `
                    <img src="${event.target.result}" alt="Capture sélectionnée ${index + 1}">
                    <span>Capture ${index + 1}</span>
                `;
                preview.appendChild(item);
            };
            reader.readAsDataURL(file);
        });
    });
});
</script>
@endsection
