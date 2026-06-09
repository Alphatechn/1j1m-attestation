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

        @if(session('existing_submission'))
            @php
                $sub = session('existing_submission');
                $statusConfig = [
                    'pending'   => ['label' => 'En attente de vérification', 'class' => 'bg-warning text-dark'],
                    'validated' => ['label' => 'Dossier validé',             'class' => 'bg-success text-white'],
                    'rejected'  => ['label' => 'Demande rejetée',            'class' => 'bg-danger text-white'],
                ];
                $st = $statusConfig[$sub['status']] ?? ['label' => 'Statut inconnu', 'class' => 'bg-secondary text-white'];
            @endphp
            <div class="alert alert-warning border-0 shadow-sm">
                <h5 class="fw-bold mb-2">
                    <i class="bi bi-exclamation-triangle me-2"></i>Dossier déjà enregistré
                </h5>
                <p class="mb-2">{{ $sub['message'] }}</p>
                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                    <span class="badge {{ $st['class'] }} px-3 py-2 fs-6">{{ $sub['status_label'] }}</span>
                    @if(!empty($sub['submitted_at']))
                        <span class="text-muted small">Soumis le {{ $sub['submitted_at'] }}</span>
                    @endif
                </div>
                @if($sub['status'] === 'pending')
                    <p class="mb-0 text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        Votre dossier est en cours d'examen par l'équipe. Vous serez contacté(e) une fois la vérification terminée.
                    </p>
                @elseif($sub['status'] === 'rejected')
                    <p class="mb-0 text-muted small">
                        <i class="bi bi-x-circle me-1"></i>
                        Votre demande n'a pas été retenue. Contactez l'équipe 1J1M pour plus d'informations.
                    </p>
                @elseif($sub['status'] === 'validated' && $sub['has_attestation'])
                    <p class="mb-2">Numéro d'attestation : <strong>{{ $sub['number'] }}</strong></p>
                    <a href="{{ $sub['preview_url'] }}" target="_blank" class="btn btn-dark me-2">
                        <i class="bi bi-eye me-1"></i>Visualiser
                    </a>
                    <a href="{{ $sub['download_url'] }}" class="btn btn-primary-custom">
                        <i class="bi bi-download me-1"></i>Télécharger
                    </a>
                @elseif($sub['status'] === 'validated')
                    <p class="mb-0 text-muted small">
                        <i class="bi bi-check-circle me-1"></i>
                        Votre dossier a été validé. L'attestation sera générée et envoyée très prochainement.
                    </p>
                @endif
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
                    <div class="phone-input-group @error('whatsapp_country_code') is-invalid @enderror @error('whatsapp_number') is-invalid @enderror">
                        <select name="whatsapp_country_code" id="whatsapp_country_code"
                                class="form-select phone-country @error('whatsapp_country_code') is-invalid @enderror"
                                required>
                            <option value="">Indicatif</option>
                            @foreach($countries as $code => $country)
                                <option value="{{ $code }}"
                                    data-min="{{ $country['min'] }}"
                                    data-max="{{ $country['max'] }}"
                                    @selected(old('whatsapp_country_code', '237') === $code)>
                                    {{ $country['name'] }} (+{{ $code }})
                                </option>
                            @endforeach
                        </select>
                        <input type="text" name="whatsapp_number" id="whatsapp_number"
                               value="{{ old('whatsapp_number') }}"
                               class="form-control phone-number @error('whatsapp_number') is-invalid @enderror"
                               placeholder="691234567"
                               inputmode="numeric"
                               autocomplete="tel-national"
                               required>
                    </div>
                    @error('whatsapp_country_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('whatsapp_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="form-text" id="whatsapp_hint">Sélectionnez votre pays puis entrez le numéro sans l'indicatif</div>
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
                    <label class="form-label">Décris de manière détaillée comment étaient dispensés les cours à 1J1M <span class="text-danger">*</span></label>
                    <textarea name="course_delivery_explanation" rows="4" class="form-control @error('course_delivery_explanation') is-invalid @enderror" required>{{ old('course_delivery_explanation') }}</textarea>
                    @error('course_delivery_explanation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Concrètement, qu'est-ce que tu sais faire de nouveau aujourd'hui grâce à ta formation ? <span class="text-danger">*</span></label>
                    <textarea name="technical_skills" rows="4" class="form-control @error('technical_skills') is-invalid @enderror" required>{{ old('technical_skills') }}</textarea>
                    @error('technical_skills')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">S'agissant de l'art oratoire, Comment captiver ton Public lors de ta prise de parole ? <span class="text-danger">*</span></label>
                    <textarea name="public_speaking_description" rows="4" class="form-control @error('public_speaking_description') is-invalid @enderror" required>{{ old('public_speaking_description') }}</textarea>
                    @error('public_speaking_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Quelles sont les étapes clés pour lancer une activité de e-commerce ? <span class="text-danger">*</span></label>
                    <textarea name="ecommerce_steps" rows="4" class="form-control @error('ecommerce_steps') is-invalid @enderror" required>{{ old('ecommerce_steps') }}</textarea>
                    @error('ecommerce_steps')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Comment procéder pour faire désormais connaître ta marque grâce à internet ? <span class="text-danger">*</span></label>
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

    .phone-input-group {
        display: flex;
    }

    .phone-input-group .phone-country {
        border-radius: 6px 0 0 6px;
        border-right: 0;
        flex: 0 0 auto;
        max-width: 185px;
        min-width: 130px;
    }

    .phone-input-group .phone-number {
        border-radius: 0 6px 6px 0;
        flex: 1 1 0;
        min-width: 0;
    }

    .phone-input-group .phone-country.is-invalid,
    .phone-input-group .phone-number.is-invalid {
        border-color: #dc3545;
    }

    #whatsapp_hint { transition: color .2s; }
    #whatsapp_hint.hint-ok    { color: #198754; font-weight: 600; }
    #whatsapp_hint.hint-error { color: #dc3545; font-weight: 600; }

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
    // ── Aperçu captures d'écran ──────────────────────────────────────────
    const screenshotInput = document.querySelector('input[name="homework_screenshots[]"]');
    const preview = document.getElementById('selected_screenshots_preview');

    if (screenshotInput && preview) {
        screenshotInput.addEventListener('change', function () {
            preview.innerHTML = '';
            Array.from(screenshotInput.files || []).forEach((file, index) => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = function (e) {
                    const item = document.createElement('div');
                    item.className = 'selected-screenshot';
                    item.innerHTML = `
                        <img src="${e.target.result}" alt="Capture ${index + 1}">
                        <span>Capture ${index + 1}</span>
                    `;
                    preview.appendChild(item);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // ── Validation WhatsApp : indicatif + longueur du numéro ─────────────
    const countrySelect = document.getElementById('whatsapp_country_code');
    const numberInput   = document.getElementById('whatsapp_number');
    const hint          = document.getElementById('whatsapp_hint');

    if (!countrySelect || !numberInput || !hint) return;

    function getSelectedCountry() {
        const opt = countrySelect.options[countrySelect.selectedIndex];
        if (!opt || !opt.value) return null;
        return { min: parseInt(opt.dataset.min), max: parseInt(opt.dataset.max), name: opt.text };
    }

    function updateHint() {
        const country = getSelectedCountry();
        if (!country) {
            hint.textContent = 'Sélectionnez votre pays puis entrez le numéro sans l\'indicatif';
            hint.className = 'form-text';
            numberInput.removeAttribute('maxlength');
            return;
        }

        const len = numberInput.value.length;
        numberInput.setAttribute('maxlength', country.max);

        if (len === 0) {
            const digits = country.min === country.max
                ? `${country.min} chiffres`
                : `${country.min} à ${country.max} chiffres`;
            hint.textContent = `${country.name} : entrez ${digits} (sans l'indicatif)`;
            hint.className = 'form-text';
            return;
        }

        if (len < country.min) {
            hint.textContent = `Encore ${country.min - len} chiffre(s) à saisir`;
            hint.className = 'form-text hint-error';
        } else if (len > country.max) {
            hint.textContent = `Numéro trop long — maximum ${country.max} chiffres`;
            hint.className = 'form-text hint-error';
        } else {
            hint.textContent = `Numéro valide (${len} chiffres)`;
            hint.className = 'form-text hint-ok';
        }
    }

    // Autoriser uniquement les chiffres
    numberInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
        updateHint();
    });

    countrySelect.addEventListener('change', function () {
        const country = getSelectedCountry();
        if (country) {
            numberInput.setAttribute('placeholder', '6' + '0'.repeat(country.min - 1));
        }
        updateHint();
    });

    // Validation avant soumission
    numberInput.closest('form').addEventListener('submit', function (e) {
        const country = getSelectedCountry();
        if (!country) return;
        const len = numberInput.value.length;
        if (len < country.min || len > country.max) {
            e.preventDefault();
            updateHint();
            numberInput.focus();
        }
    });

    updateHint(); // init au chargement
});
</script>
@endsection
