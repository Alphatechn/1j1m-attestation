<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Periode;
use App\Http\Requests\ParticipantRequest;
use App\Exports\ParticipantsExport;
use App\Imports\ParticipantsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ParticipantController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['publicForm', 'publicSubmit']);
        $this->middleware('permission:manage participants')->except([
        'index',                    // public function index()
        'show',                     // public function show()
        'export',                   // public function export()
        'downloadTemplate',         // public function downloadTemplate()
        'listByPeriode',            // public function listByPeriode()
        'withoutAttestation',       // public function withoutAttestation()
        'publicForm',
        'publicSubmit'
    ]);
    }

    public function publicForm()
    {
        $countries = self::getCountryPhoneLengths();
        return view('Participants.public-form', compact('countries'));
    }

    public function publicSubmit(Request $request)
    {
        $periode = Periode::active()
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->first();

        if (!$periode) {
            return back()->withInput()->withErrors([
                'periode' => 'Aucune période active n\'est disponible pour le moment. Veuillez réessayer plus tard.',
            ]);
        }

        $countryPhoneLengths = self::getCountryPhoneLengths();

        $validated = $request->validate([
            'civility'                    => 'required|string|max:20',
            'name'                        => 'required|string|max:255',
            'email'                       => 'required|email|max:255',
            'city'                        => 'required|string|max:255',
            'whatsapp_country_code'       => ['required', 'string', Rule::in(array_keys($countryPhoneLengths))],
            'whatsapp_number'             => ['required', 'string', 'regex:/^[0-9]+$/'],
            'training_group'              => 'required|string|max:255',
            'classmates_contacts'         => 'required|string|min:10',
            'course_delivery_explanation' => 'required|string|min:20',
            'technical_skills'            => 'required|string|min:20',
            'public_speaking_description' => 'required|string|min:20',
            'ecommerce_steps'             => 'required|string|min:20',
            'knowledge_use_plan'          => 'required|string|min:20',
            'coaches'                     => 'required|string|min:10',
            'homework_screenshots'        => 'required|array|min:1|max:6',
            'homework_screenshots.*'      => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
        ], [
            'civility.required'                    => 'La civilité est obligatoire.',
            'name.required'                        => 'Les noms et prénoms complets sont obligatoires.',
            'email.required'                       => 'L\'adresse email est obligatoire.',
            'email.email'                          => 'Veuillez renseigner une adresse email valide.',
            'city.required'                        => 'La ville est obligatoire.',
            'whatsapp_country_code.required'       => 'Veuillez sélectionner l\'indicatif de votre pays.',
            'whatsapp_country_code.in'             => 'L\'indicatif du pays sélectionné n\'est pas reconnu.',
            'whatsapp_number.required'             => 'Le numéro WhatsApp est obligatoire.',
            'whatsapp_number.regex'                => 'Le numéro WhatsApp ne doit contenir que des chiffres.',
            'training_group.required'              => 'Le groupe de formation est obligatoire.',
            'classmates_contacts.required'         => 'Veuillez renseigner les noms et WhatsApp de 3 camarades.',
            'classmates_contacts.min'              => 'Les informations sur vos camarades sont trop courtes.',
            'course_delivery_explanation.required' => 'Veuillez expliquer comment les cours sont dispensés à 1J1M.',
            'course_delivery_explanation.min'      => 'Votre explication sur le déroulement des cours est trop courte.',
            'technical_skills.required'            => 'Veuillez préciser les compétences techniques acquises.',
            'technical_skills.min'                 => 'Votre description des compétences techniques est trop courte.',
            'public_speaking_description.required' => 'Veuillez décrire votre méthode actuelle en art oratoire.',
            'public_speaking_description.min'      => 'Votre description en art oratoire est trop courte.',
            'ecommerce_steps.required'             => 'Veuillez décrire les étapes de mise en place d\'une activité e-commerce.',
            'ecommerce_steps.min'                  => 'Votre description des étapes e-commerce est trop courte.',
            'knowledge_use_plan.required'          => 'Veuillez expliquer comment vous comptez mettre vos compétences à profit.',
            'knowledge_use_plan.min'               => 'Votre plan d\'utilisation des compétences est trop court.',
            'coaches.required'                     => 'Veuillez renseigner vos 5 coachs et leurs matières.',
            'coaches.min'                          => 'Les informations sur vos coachs sont trop courtes.',
            'homework_screenshots.required'        => 'Veuillez envoyer au moins une capture d\'écran d\'un devoir rendu.',
            'homework_screenshots.array'           => 'Les captures doivent être envoyées sous forme de fichiers images.',
            'homework_screenshots.min'             => 'Veuillez envoyer au moins une capture d\'écran.',
            'homework_screenshots.max'             => 'Vous pouvez envoyer au maximum 6 captures d\'écran.',
            'homework_screenshots.*.image'         => 'Chaque capture doit être une image.',
            'homework_screenshots.*.mimes'         => 'Les captures doivent être au format JPG, PNG ou WEBP.',
            'homework_screenshots.*.max'           => 'Chaque capture ne doit pas dépasser 8 Mo.',
        ]);

        // Valider la longueur du numéro local selon le pays
        $countryConfig = $countryPhoneLengths[$validated['whatsapp_country_code']];
        $numberLen = strlen($validated['whatsapp_number']);
        if ($numberLen < $countryConfig['min'] || $numberLen > $countryConfig['max']) {
            $expected = $countryConfig['min'] === $countryConfig['max']
                ? "exactement {$countryConfig['min']} chiffres"
                : "entre {$countryConfig['min']} et {$countryConfig['max']} chiffres";
            return back()->withInput()->withErrors([
                'whatsapp_number' => "Le numéro pour {$countryConfig['name']} doit contenir {$expected}.",
            ]);
        }

        // Numéro WhatsApp complet avec indicatif (+237691234567) et version chiffres seuls pour la comparaison DB
        $fullWhatsapp      = '+' . $countryConfig['dial'] . $validated['whatsapp_number'];
        $whatsappDigitsOnly = $countryConfig['dial'] . $validated['whatsapp_number'];

        // Normaliser le nom pour comparaison insensible à la casse
        $normalizedName = mb_strtolower(trim(preg_replace('/\s+/', ' ', $validated['name'])));

        // Construit le payload renvoyé au formulaire en cas de doublon, avec le statut de la demande existante
        $buildDuplicatePayload = function (Participant $participant, string $field, string $message): array {
            $attestation = $participant->attestations->first();
            $statusLabels = [
                'pending'   => 'En attente de vérification',
                'validated' => 'Dossier validé',
                'rejected'  => 'Demande rejetée',
            ];
            return [
                'field'           => $field,
                'message'         => $message,
                'status'          => $participant->validation_status ?? 'pending',
                'status_label'    => $statusLabels[$participant->validation_status ?? 'pending'] ?? 'Statut inconnu',
                'submitted_at'    => $participant->submitted_at?->format('d/m/Y à H\hi'),
                'has_attestation' => $attestation !== null,
                'number'          => $attestation?->attestation_number,
                'preview_url'     => $attestation ? route('public.attestations.public.preview', $attestation->id) : null,
                'download_url'    => $attestation ? route('public.attestations.public.download', $attestation->id) : null,
            ];
        };

        // Vérification 1 : email déjà existant
        $existingByEmail = Participant::with(['attestations' => fn ($q) => $q->latest()])
            ->where('email', $validated['email'])
            ->first();
        if ($existingByEmail) {
            return back()->withInput()->with('existing_submission',
                $buildDuplicatePayload($existingByEmail, 'email', 'Cette adresse email est déjà enregistrée dans le système.')
            );
        }

        // Vérification 2 : numéro WhatsApp déjà enregistré (insensible au formatage, tous pays)
        $existingByWhatsapp = Participant::with(['attestations' => fn ($q) => $q->latest()])
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(whatsapp, ' ', ''), '-', ''), '.', ''), '(', ''), ')', ''), '+', '') = ?",
                [$whatsappDigitsOnly]
            )
            ->first();
        if ($existingByWhatsapp) {
            return back()->withInput()->with('existing_submission',
                $buildDuplicatePayload($existingByWhatsapp, 'whatsapp_number', 'Ce numéro WhatsApp est déjà enregistré dans le système.')
            );
        }

        // Vérification 3 : nom identique dans la même période (insensible à la casse)
        $existingByName = Participant::with(['attestations' => fn ($q) => $q->latest()])
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
            ->where('periode_id', $periode->id)
            ->first();
        if ($existingByName) {
            return back()->withInput()->with('existing_submission',
                $buildDuplicatePayload($existingByName, 'name', 'Ce nom est déjà enregistré pour la période en cours.')
            );
        }

        $paths = [];
        foreach ($request->file('homework_screenshots', []) as $file) {
            $paths[] = $file->store('homework_screenshots', 'public');
        }

        Participant::create([
            'periode_id'                  => $periode->id,
            'civility'                    => $validated['civility'],
            'name'                        => $validated['name'],
            'email'                       => $validated['email'],
            'city'                        => $validated['city'],
            'phone'                       => $fullWhatsapp,
            'whatsapp'                    => $fullWhatsapp,
            'training_group'              => $validated['training_group'],
            'classmates_contacts'         => $validated['classmates_contacts'],
            'course_delivery_explanation' => $validated['course_delivery_explanation'],
            'technical_skills'            => $validated['technical_skills'],
            'public_speaking_description' => $validated['public_speaking_description'],
            'ecommerce_steps'             => $validated['ecommerce_steps'],
            'knowledge_use_plan'          => $validated['knowledge_use_plan'],
            'coaches'                     => $validated['coaches'],
            'homework_screenshot_paths'   => $paths,
            'is_active'                   => false,
            'validation_status'           => 'pending',
            'submitted_at'                => now(),
        ]);

        return back()->with('success', 'Votre demande a bien été envoyée. Elle sera vérifiée avant la génération de votre attestation.');
    }

    /**
     * Liste des pays du monde pour l'indicatif WhatsApp, indexée par code ISO
     * (et non par indicatif) car plusieurs pays partagent le même indicatif
     * (+1 pour les USA/Canada/Caraïbes, +44 pour le Royaume-Uni, +7 pour la
     * Russie/Kazakhstan, etc.). Le champ 'dial' contient l'indicatif réel à
     * utiliser pour composer le numéro complet.
     */
    private static function getCountryPhoneLengths(): array
    {
        $countries = [
            'DZ' => ['name' => 'Algérie',                          'dial' => '213', 'min' => 9,  'max' => 9],
            'AO' => ['name' => 'Angola',                           'dial' => '244', 'min' => 9,  'max' => 9],
            'BJ' => ['name' => 'Bénin',                            'dial' => '229', 'min' => 8,  'max' => 10],
            'BW' => ['name' => 'Botswana',                         'dial' => '267', 'min' => 7,  'max' => 8],
            'BF' => ['name' => 'Burkina Faso',                     'dial' => '226', 'min' => 8,  'max' => 8],
            'BI' => ['name' => 'Burundi',                          'dial' => '257', 'min' => 8,  'max' => 8],
            'CM' => ['name' => 'Cameroun',                         'dial' => '237', 'min' => 9,  'max' => 9],
            'CV' => ['name' => 'Cap-Vert',                         'dial' => '238', 'min' => 7,  'max' => 7],
            'CF' => ['name' => 'Centrafrique',                     'dial' => '236', 'min' => 8,  'max' => 8],
            'KM' => ['name' => 'Comores',                          'dial' => '269', 'min' => 7,  'max' => 7],
            'CG' => ['name' => 'Congo-Brazzaville',                'dial' => '242', 'min' => 9,  'max' => 9],
            'CD' => ['name' => 'RD Congo',                         'dial' => '243', 'min' => 9,  'max' => 9],
            'CI' => ['name' => "Côte d'Ivoire",                    'dial' => '225', 'min' => 10, 'max' => 10],
            'DJ' => ['name' => 'Djibouti',                         'dial' => '253', 'min' => 8,  'max' => 8],
            'EG' => ['name' => 'Égypte',                           'dial' => '20',  'min' => 10, 'max' => 10],
            'ER' => ['name' => 'Érythrée',                         'dial' => '291', 'min' => 7,  'max' => 7],
            'SZ' => ['name' => 'Eswatini',                         'dial' => '268', 'min' => 8,  'max' => 8],
            'ET' => ['name' => 'Éthiopie',                         'dial' => '251', 'min' => 9,  'max' => 9],
            'GA' => ['name' => 'Gabon',                            'dial' => '241', 'min' => 7,  'max' => 8],
            'GM' => ['name' => 'Gambie',                           'dial' => '220', 'min' => 7,  'max' => 7],
            'GH' => ['name' => 'Ghana',                            'dial' => '233', 'min' => 9,  'max' => 9],
            'GN' => ['name' => 'Guinée',                           'dial' => '224', 'min' => 9,  'max' => 9],
            'GQ' => ['name' => 'Guinée Équatoriale',               'dial' => '240', 'min' => 9,  'max' => 9],
            'GW' => ['name' => 'Guinée-Bissau',                    'dial' => '245', 'min' => 7,  'max' => 7],
            'KE' => ['name' => 'Kenya',                            'dial' => '254', 'min' => 9,  'max' => 9],
            'LS' => ['name' => 'Lesotho',                          'dial' => '266', 'min' => 8,  'max' => 8],
            'LR' => ['name' => 'Liberia',                          'dial' => '231', 'min' => 7,  'max' => 9],
            'LY' => ['name' => 'Libye',                            'dial' => '218', 'min' => 9,  'max' => 9],
            'MG' => ['name' => 'Madagascar',                       'dial' => '261', 'min' => 9,  'max' => 9],
            'MW' => ['name' => 'Malawi',                           'dial' => '265', 'min' => 9,  'max' => 9],
            'ML' => ['name' => 'Mali',                             'dial' => '223', 'min' => 8,  'max' => 8],
            'MA' => ['name' => 'Maroc',                            'dial' => '212', 'min' => 9,  'max' => 9],
            'MU' => ['name' => 'Maurice',                          'dial' => '230', 'min' => 7,  'max' => 8],
            'MR' => ['name' => 'Mauritanie',                       'dial' => '222', 'min' => 8,  'max' => 8],
            'MZ' => ['name' => 'Mozambique',                       'dial' => '258', 'min' => 9,  'max' => 9],
            'NA' => ['name' => 'Namibie',                          'dial' => '264', 'min' => 7,  'max' => 9],
            'NE' => ['name' => 'Niger',                            'dial' => '227', 'min' => 8,  'max' => 8],
            'NG' => ['name' => 'Nigéria',                          'dial' => '234', 'min' => 10, 'max' => 10],
            'UG' => ['name' => 'Ouganda',                          'dial' => '256', 'min' => 9,  'max' => 9],
            'RW' => ['name' => 'Rwanda',                           'dial' => '250', 'min' => 9,  'max' => 9],
            'ST' => ['name' => 'Sao Tomé-et-Principe',             'dial' => '239', 'min' => 7,  'max' => 7],
            'SN' => ['name' => 'Sénégal',                          'dial' => '221', 'min' => 9,  'max' => 9],
            'SC' => ['name' => 'Seychelles',                       'dial' => '248', 'min' => 7,  'max' => 7],
            'SL' => ['name' => 'Sierra Leone',                     'dial' => '232', 'min' => 8,  'max' => 8],
            'SO' => ['name' => 'Somalie',                          'dial' => '252', 'min' => 7,  'max' => 9],
            'SD' => ['name' => 'Soudan',                           'dial' => '249', 'min' => 9,  'max' => 9],
            'SS' => ['name' => 'Soudan du Sud',                    'dial' => '211', 'min' => 9,  'max' => 9],
            'TZ' => ['name' => 'Tanzanie',                         'dial' => '255', 'min' => 9,  'max' => 9],
            'TD' => ['name' => 'Tchad',                            'dial' => '235', 'min' => 8,  'max' => 8],
            'TG' => ['name' => 'Togo',                             'dial' => '228', 'min' => 8,  'max' => 8],
            'TN' => ['name' => 'Tunisie',                          'dial' => '216', 'min' => 8,  'max' => 8],
            'ZA' => ['name' => 'Afrique du Sud',                   'dial' => '27',  'min' => 9,  'max' => 9],
            'ZM' => ['name' => 'Zambie',                           'dial' => '260', 'min' => 9,  'max' => 9],
            'ZW' => ['name' => 'Zimbabwe',                         'dial' => '263', 'min' => 9,  'max' => 9],

            'AL' => ['name' => 'Albanie',                          'dial' => '355', 'min' => 9,  'max' => 9],
            'DE' => ['name' => 'Allemagne',                        'dial' => '49',  'min' => 10, 'max' => 11],
            'AD' => ['name' => 'Andorre',                          'dial' => '376', 'min' => 6,  'max' => 6],
            'AT' => ['name' => 'Autriche',                         'dial' => '43',  'min' => 10, 'max' => 11],
            'BY' => ['name' => 'Biélorussie',                      'dial' => '375', 'min' => 9,  'max' => 9],
            'BE' => ['name' => 'Belgique',                         'dial' => '32',  'min' => 9,  'max' => 9],
            'BA' => ['name' => 'Bosnie-Herzégovine',                'dial' => '387', 'min' => 8,  'max' => 8],
            'BG' => ['name' => 'Bulgarie',                         'dial' => '359', 'min' => 9,  'max' => 9],
            'CY' => ['name' => 'Chypre',                           'dial' => '357', 'min' => 8,  'max' => 8],
            'HR' => ['name' => 'Croatie',                          'dial' => '385', 'min' => 9,  'max' => 9],
            'DK' => ['name' => 'Danemark',                         'dial' => '45',  'min' => 8,  'max' => 8],
            'ES' => ['name' => 'Espagne',                          'dial' => '34',  'min' => 9,  'max' => 9],
            'EE' => ['name' => 'Estonie',                          'dial' => '372', 'min' => 7,  'max' => 8],
            'FI' => ['name' => 'Finlande',                         'dial' => '358', 'min' => 9,  'max' => 10],
            'FR' => ['name' => 'France',                           'dial' => '33',  'min' => 9,  'max' => 9],
            'GR' => ['name' => 'Grèce',                            'dial' => '30',  'min' => 10, 'max' => 10],
            'HU' => ['name' => 'Hongrie',                          'dial' => '36',  'min' => 9,  'max' => 9],
            'IE' => ['name' => 'Irlande',                          'dial' => '353', 'min' => 9,  'max' => 9],
            'IS' => ['name' => 'Islande',                          'dial' => '354', 'min' => 7,  'max' => 7],
            'IT' => ['name' => 'Italie',                           'dial' => '39',  'min' => 9,  'max' => 10],
            'XK' => ['name' => 'Kosovo',                           'dial' => '383', 'min' => 8,  'max' => 9],
            'LV' => ['name' => 'Lettonie',                         'dial' => '371', 'min' => 8,  'max' => 8],
            'LI' => ['name' => 'Liechtenstein',                    'dial' => '423', 'min' => 7,  'max' => 9],
            'LT' => ['name' => 'Lituanie',                         'dial' => '370', 'min' => 8,  'max' => 8],
            'LU' => ['name' => 'Luxembourg',                       'dial' => '352', 'min' => 9,  'max' => 9],
            'MK' => ['name' => 'Macédoine du Nord',                'dial' => '389', 'min' => 8,  'max' => 8],
            'MT' => ['name' => 'Malte',                            'dial' => '356', 'min' => 8,  'max' => 8],
            'MD' => ['name' => 'Moldavie',                         'dial' => '373', 'min' => 8,  'max' => 8],
            'MC' => ['name' => 'Monaco',                           'dial' => '377', 'min' => 8,  'max' => 9],
            'ME' => ['name' => 'Monténégro',                       'dial' => '382', 'min' => 8,  'max' => 8],
            'NO' => ['name' => 'Norvège',                          'dial' => '47',  'min' => 8,  'max' => 8],
            'NL' => ['name' => 'Pays-Bas',                         'dial' => '31',  'min' => 9,  'max' => 9],
            'PL' => ['name' => 'Pologne',                          'dial' => '48',  'min' => 9,  'max' => 9],
            'PT' => ['name' => 'Portugal',                         'dial' => '351', 'min' => 9,  'max' => 9],
            'CZ' => ['name' => 'République Tchèque',               'dial' => '420', 'min' => 9,  'max' => 9],
            'RO' => ['name' => 'Roumanie',                         'dial' => '40',  'min' => 9,  'max' => 9],
            'GB' => ['name' => 'Royaume-Uni',                      'dial' => '44',  'min' => 10, 'max' => 10],
            'RU' => ['name' => 'Russie',                           'dial' => '7',   'min' => 10, 'max' => 10],
            'SM' => ['name' => 'Saint-Marin',                      'dial' => '378', 'min' => 6,  'max' => 9],
            'RS' => ['name' => 'Serbie',                           'dial' => '381', 'min' => 8,  'max' => 9],
            'SK' => ['name' => 'Slovaquie',                        'dial' => '421', 'min' => 9,  'max' => 9],
            'SI' => ['name' => 'Slovénie',                         'dial' => '386', 'min' => 8,  'max' => 8],
            'SE' => ['name' => 'Suède',                            'dial' => '46',  'min' => 9,  'max' => 9],
            'CH' => ['name' => 'Suisse',                           'dial' => '41',  'min' => 9,  'max' => 9],
            'UA' => ['name' => 'Ukraine',                          'dial' => '380', 'min' => 9,  'max' => 9],
            'VA' => ['name' => 'Vatican',                          'dial' => '379', 'min' => 6,  'max' => 10],

            'US' => ['name' => 'États-Unis',                       'dial' => '1',   'min' => 10, 'max' => 10],
            'CA' => ['name' => 'Canada',                           'dial' => '1',   'min' => 10, 'max' => 10],
            'AG' => ['name' => 'Antigua-et-Barbuda',               'dial' => '1',   'min' => 10, 'max' => 10],
            'AR' => ['name' => 'Argentine',                        'dial' => '54',  'min' => 10, 'max' => 11],
            'BS' => ['name' => 'Bahamas',                          'dial' => '1',   'min' => 10, 'max' => 10],
            'BB' => ['name' => 'Barbade',                          'dial' => '1',   'min' => 10, 'max' => 10],
            'BZ' => ['name' => 'Belize',                           'dial' => '501', 'min' => 7,  'max' => 7],
            'BO' => ['name' => 'Bolivie',                          'dial' => '591', 'min' => 8,  'max' => 8],
            'BR' => ['name' => 'Brésil',                           'dial' => '55',  'min' => 10, 'max' => 11],
            'CL' => ['name' => 'Chili',                            'dial' => '56',  'min' => 9,  'max' => 9],
            'CO' => ['name' => 'Colombie',                         'dial' => '57',  'min' => 10, 'max' => 10],
            'CR' => ['name' => 'Costa Rica',                       'dial' => '506', 'min' => 8,  'max' => 8],
            'CU' => ['name' => 'Cuba',                             'dial' => '53',  'min' => 8,  'max' => 8],
            'DM' => ['name' => 'Dominique',                        'dial' => '1',   'min' => 10, 'max' => 10],
            'DO' => ['name' => 'République Dominicaine',           'dial' => '1',   'min' => 10, 'max' => 10],
            'EC' => ['name' => 'Équateur',                         'dial' => '593', 'min' => 9,  'max' => 9],
            'SV' => ['name' => 'Salvador',                         'dial' => '503', 'min' => 8,  'max' => 8],
            'GD' => ['name' => 'Grenade',                          'dial' => '1',   'min' => 10, 'max' => 10],
            'GT' => ['name' => 'Guatemala',                        'dial' => '502', 'min' => 8,  'max' => 8],
            'GY' => ['name' => 'Guyana',                           'dial' => '592', 'min' => 7,  'max' => 7],
            'HT' => ['name' => 'Haïti',                            'dial' => '509', 'min' => 8,  'max' => 8],
            'HN' => ['name' => 'Honduras',                         'dial' => '504', 'min' => 8,  'max' => 8],
            'JM' => ['name' => 'Jamaïque',                         'dial' => '1',   'min' => 10, 'max' => 10],
            'MX' => ['name' => 'Mexique',                          'dial' => '52',  'min' => 10, 'max' => 10],
            'NI' => ['name' => 'Nicaragua',                        'dial' => '505', 'min' => 8,  'max' => 8],
            'PA' => ['name' => 'Panama',                           'dial' => '507', 'min' => 7,  'max' => 8],
            'PY' => ['name' => 'Paraguay',                         'dial' => '595', 'min' => 9,  'max' => 9],
            'PE' => ['name' => 'Pérou',                            'dial' => '51',  'min' => 9,  'max' => 9],
            'KN' => ['name' => 'Saint-Christophe-et-Niévès',       'dial' => '1',   'min' => 10, 'max' => 10],
            'LC' => ['name' => 'Sainte-Lucie',                     'dial' => '1',   'min' => 10, 'max' => 10],
            'VC' => ['name' => 'Saint-Vincent-et-les-Grenadines',  'dial' => '1',   'min' => 10, 'max' => 10],
            'SR' => ['name' => 'Suriname',                         'dial' => '597', 'min' => 7,  'max' => 7],
            'TT' => ['name' => 'Trinité-et-Tobago',                'dial' => '1',   'min' => 10, 'max' => 10],
            'UY' => ['name' => 'Uruguay',                          'dial' => '598', 'min' => 8,  'max' => 9],
            'VE' => ['name' => 'Venezuela',                        'dial' => '58',  'min' => 10, 'max' => 10],

            'AF' => ['name' => 'Afghanistan',                      'dial' => '93',  'min' => 9,  'max' => 9],
            'SA' => ['name' => 'Arabie Saoudite',                  'dial' => '966', 'min' => 9,  'max' => 9],
            'AM' => ['name' => 'Arménie',                          'dial' => '374', 'min' => 8,  'max' => 8],
            'AZ' => ['name' => 'Azerbaïdjan',                      'dial' => '994', 'min' => 9,  'max' => 9],
            'BH' => ['name' => 'Bahreïn',                          'dial' => '973', 'min' => 8,  'max' => 8],
            'BD' => ['name' => 'Bangladesh',                       'dial' => '880', 'min' => 10, 'max' => 10],
            'BT' => ['name' => 'Bhoutan',                          'dial' => '975', 'min' => 7,  'max' => 8],
            'MM' => ['name' => 'Birmanie (Myanmar)',               'dial' => '95',  'min' => 8,  'max' => 10],
            'BN' => ['name' => 'Brunei',                           'dial' => '673', 'min' => 7,  'max' => 7],
            'KH' => ['name' => 'Cambodge',                         'dial' => '855', 'min' => 8,  'max' => 9],
            'CN' => ['name' => 'Chine',                            'dial' => '86',  'min' => 11, 'max' => 11],
            'KP' => ['name' => 'Corée du Nord',                    'dial' => '850', 'min' => 8,  'max' => 10],
            'KR' => ['name' => 'Corée du Sud',                     'dial' => '82',  'min' => 9,  'max' => 10],
            'AE' => ['name' => 'Émirats Arabes Unis',              'dial' => '971', 'min' => 9,  'max' => 9],
            'GE' => ['name' => 'Géorgie',                          'dial' => '995', 'min' => 9,  'max' => 9],
            'HK' => ['name' => 'Hong Kong',                        'dial' => '852', 'min' => 8,  'max' => 8],
            'IN' => ['name' => 'Inde',                             'dial' => '91',  'min' => 10, 'max' => 10],
            'ID' => ['name' => 'Indonésie',                        'dial' => '62',  'min' => 9,  'max' => 11],
            'IQ' => ['name' => 'Irak',                             'dial' => '964', 'min' => 10, 'max' => 10],
            'IR' => ['name' => 'Iran',                             'dial' => '98',  'min' => 10, 'max' => 10],
            'IL' => ['name' => 'Israël',                           'dial' => '972', 'min' => 9,  'max' => 9],
            'JP' => ['name' => 'Japon',                            'dial' => '81',  'min' => 10, 'max' => 10],
            'JO' => ['name' => 'Jordanie',                         'dial' => '962', 'min' => 9,  'max' => 9],
            'KZ' => ['name' => 'Kazakhstan',                       'dial' => '7',   'min' => 10, 'max' => 10],
            'KG' => ['name' => 'Kirghizistan',                     'dial' => '996', 'min' => 9,  'max' => 9],
            'KW' => ['name' => 'Koweït',                           'dial' => '965', 'min' => 8,  'max' => 8],
            'LA' => ['name' => 'Laos',                             'dial' => '856', 'min' => 8,  'max' => 10],
            'LB' => ['name' => 'Liban',                            'dial' => '961', 'min' => 7,  'max' => 8],
            'MO' => ['name' => 'Macao',                            'dial' => '853', 'min' => 8,  'max' => 8],
            'MY' => ['name' => 'Malaisie',                         'dial' => '60',  'min' => 9,  'max' => 10],
            'MV' => ['name' => 'Maldives',                         'dial' => '960', 'min' => 7,  'max' => 7],
            'MN' => ['name' => 'Mongolie',                         'dial' => '976', 'min' => 8,  'max' => 8],
            'NP' => ['name' => 'Népal',                            'dial' => '977', 'min' => 10, 'max' => 10],
            'OM' => ['name' => 'Oman',                             'dial' => '968', 'min' => 8,  'max' => 8],
            'UZ' => ['name' => 'Ouzbékistan',                      'dial' => '998', 'min' => 9,  'max' => 9],
            'PK' => ['name' => 'Pakistan',                         'dial' => '92',  'min' => 10, 'max' => 10],
            'PS' => ['name' => 'Palestine',                        'dial' => '970', 'min' => 9,  'max' => 9],
            'PH' => ['name' => 'Philippines',                      'dial' => '63',  'min' => 10, 'max' => 10],
            'QA' => ['name' => 'Qatar',                            'dial' => '974', 'min' => 8,  'max' => 8],
            'SG' => ['name' => 'Singapour',                        'dial' => '65',  'min' => 8,  'max' => 8],
            'LK' => ['name' => 'Sri Lanka',                        'dial' => '94',  'min' => 9,  'max' => 9],
            'SY' => ['name' => 'Syrie',                            'dial' => '963', 'min' => 9,  'max' => 9],
            'TW' => ['name' => 'Taïwan',                           'dial' => '886', 'min' => 9,  'max' => 9],
            'TJ' => ['name' => 'Tadjikistan',                      'dial' => '992', 'min' => 9,  'max' => 9],
            'TH' => ['name' => 'Thaïlande',                        'dial' => '66',  'min' => 9,  'max' => 9],
            'TL' => ['name' => 'Timor Oriental',                   'dial' => '670', 'min' => 7,  'max' => 8],
            'TM' => ['name' => 'Turkménistan',                     'dial' => '993', 'min' => 8,  'max' => 8],
            'TR' => ['name' => 'Turquie',                          'dial' => '90',  'min' => 10, 'max' => 10],
            'VN' => ['name' => 'Vietnam',                          'dial' => '84',  'min' => 9,  'max' => 10],
            'YE' => ['name' => 'Yémen',                            'dial' => '967', 'min' => 9,  'max' => 9],

            'AU' => ['name' => 'Australie',                        'dial' => '61',  'min' => 9,  'max' => 9],
            'FJ' => ['name' => 'Fidji',                            'dial' => '679', 'min' => 7,  'max' => 7],
            'KI' => ['name' => 'Kiribati',                         'dial' => '686', 'min' => 5,  'max' => 8],
            'MH' => ['name' => 'Îles Marshall',                    'dial' => '692', 'min' => 7,  'max' => 7],
            'FM' => ['name' => 'Micronésie',                       'dial' => '691', 'min' => 7,  'max' => 7],
            'NR' => ['name' => 'Nauru',                            'dial' => '674', 'min' => 7,  'max' => 7],
            'NZ' => ['name' => 'Nouvelle-Zélande',                 'dial' => '64',  'min' => 8,  'max' => 10],
            'PW' => ['name' => 'Palaos',                           'dial' => '680', 'min' => 7,  'max' => 7],
            'PG' => ['name' => 'Papouasie-Nouvelle-Guinée',        'dial' => '675', 'min' => 7,  'max' => 8],
            'SB' => ['name' => 'Salomon',                          'dial' => '677', 'min' => 5,  'max' => 7],
            'WS' => ['name' => 'Samoa',                            'dial' => '685', 'min' => 5,  'max' => 7],
            'TO' => ['name' => 'Tonga',                            'dial' => '676', 'min' => 5,  'max' => 7],
            'TV' => ['name' => 'Tuvalu',                           'dial' => '688', 'min' => 5,  'max' => 6],
            'VU' => ['name' => 'Vanuatu',                          'dial' => '678', 'min' => 5,  'max' => 7],
        ];

        if (class_exists(\Collator::class)) {
            $collator = new \Collator('fr_FR');
            uasort($countries, fn ($a, $b) => $collator->compare($a['name'], $b['name']));
        } else {
            uasort($countries, fn ($a, $b) => strcmp($a['name'], $b['name']));
        }

        return $countries;
    }

    /**
     * Liste des participants
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Participant::with(['periode', 'attestations', 'validatedBy']);

            // Filtres
            if ($request->filled('periode_id')) {
                $query->where('periode_id', $request->periode_id);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->filled('has_attestation')) {
                if ($request->has_attestation == '1') {
                    $query->has('attestations');
                } else {
                    $query->doesntHave('attestations');
                }
            }

            if ($request->filled('validation_status')) {
                $query->where('validation_status', $request->validation_status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('city', 'LIKE', "%{$search}%")
                      ->orWhere('training_group', 'LIKE', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $participants = $query->get();

            return response()->json([
                'success' => true,
                'data' => $participants
            ]);
        }

        $periodes = Periode::active()->get();
        return view('Participants.index', compact('periodes'));
    }

    /**
     * Afficher un participant
     */
    public function show($id, Request $request)
    {
        try {
            $participant = Participant::with(['periode', 'attestations', 'validatedBy'])->findOrFail($id);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $participant
                ]);
            }

            return view('participants.show', compact('participant'));

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Participant non trouvé.'
                ], 404);
            }
            abort(404);
        }
    }

    /**
     * Créer un participant
     */
    public function store(ParticipantRequest $request)
    {
        try {
            DB::beginTransaction();

            $participant = Participant::create($request->validated());

            if ($participant->validation_status === 'validated' && !$participant->validated_at) {
                $participant->update([
                    'validated_at' => now(),
                    'validated_by' => auth()->id(),
                    'is_active' => true,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Participant créé avec succès.',
                'data' => $participant->load('periode')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un participant
     */

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $participant = Participant::findOrFail($id);

            // Validation manuelle pour contourner le problème de Rule::unique
            $validator = Validator::make($request->all(), [
                'periode_id' => 'required|exists:periodes,id',
                'name' => 'required|string|max:255',
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    Rule::unique('participants')->ignore($participant->id),
                ],
                'phone' => 'nullable|string|max:20',
                'whatsapp' => 'nullable|string|max:30',
                'city' => 'nullable|string|max:255',
                'civility' => 'nullable|string|max:20',
                'training_group' => 'nullable|string|max:255',
                'classmates_contacts' => 'nullable|string',
                'course_delivery_explanation' => 'nullable|string',
                'technical_skills' => 'nullable|string',
                'public_speaking_description' => 'nullable|string',
                'ecommerce_steps' => 'nullable|string',
                'knowledge_use_plan' => 'nullable|string',
                'coaches' => 'nullable|string',
                'validation_status' => 'nullable|in:pending,validated,rejected',
                'is_active' => 'boolean',
            ], [
                'email.unique' => 'Cet email est déjà utilisé.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            if (($data['validation_status'] ?? $participant->validation_status) === 'validated') {
                $data['validated_at'] = $participant->validated_at ?: now();
                $data['validated_by'] = $participant->validated_by ?: auth()->id();
                $data['is_active'] = $data['is_active'] ?? true;
            }

            $participant->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Participant mis à jour avec succès.',
                'data' => $participant->fresh(['periode'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un participant
     */
    public function destroy($id)
    {
        try {
            $participant = Participant::findOrFail($id);

            // Vérifier s'il y a des attestations
            if ($participant->attestations()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce participant car il possède des attestations.'
                ], 422);
            }

            $participant->delete();

            return response()->json([
                'success' => true,
                'message' => 'Participant supprimé avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver un participant
     */
    public function toggleStatus($id)
    {
        try {
            $participant = Participant::findOrFail($id);
            $participant->is_active = !$participant->is_active;
            $participant->save();

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès.',
                'data' => $participant
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut.'
            ], 500);
        }
    }

    public function validateParticipant($id)
    {
        $participant = Participant::findOrFail($id);

        $participant->update([
            'validation_status' => 'validated',
            'is_active' => true,
            'validated_at' => now(),
            'validated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Participant validé avec succès.',
            'data' => $participant->fresh(['periode', 'attestations', 'validatedBy'])
        ]);
    }

    public function bulkValidate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:participants,id',
        ], [
            'participant_ids.required' => 'Veuillez sélectionner au moins un participant.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $count = Participant::whereIn('id', $request->participant_ids)
            ->where('validation_status', 'pending')
            ->update([
                'validation_status' => 'validated',
                'is_active' => true,
                'validated_at' => now(),
                'validated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$count} participant(s) validé(s) avec succès.",
            'count' => $count,
        ]);
    }

    /**
     * Import en masse (CSV/Excel)
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB max
            'periode_id' => 'required|exists:periodes,id'
        ]);

        try {
            DB::beginTransaction();

            $import = new ParticipantsImport($request->periode_id);

            // Importer le fichier
            Excel::import($import, $request->file('file'));

            DB::commit();

            $response = [
                'success' => true,
                'message' => 'Import effectué avec succès.',
                'data' => [
                    'imported' => $import->getImportedCount(),
                    'failed' => $import->getFailedCount(),
                    'total' => $import->getImportedCount() + $import->getFailedCount()
                ]
            ];

            // Ajouter les erreurs détaillées si il y en a
            if ($import->getFailedCount() > 0) {
                $response['errors'] = $import->getErrors();
                $response['message'] = "Import partiellement réussi. {$import->getImportedCount()} participants importés, {$import->getFailedCount()} échecs.";
            }

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();

            // Gestion spécifique des erreurs d'import
            if (str_contains($e->getMessage(), 'You are not allowed to')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur: Le fichier est corrompu ou le format n\'est pas supporté.'
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export des participants
     */
    public function export(Request $request)
    {
        try {
            $periodeId = $request->get('periode_id');
            $periode = null;

            if ($periodeId) {
                $periode = Periode::find($periodeId);
            }

            $filename = 'participants_' . ($periode ? $periode->libelle : 'all') . '_' . date('Y-m-d_His') . '.xlsx';

            return Excel::download(new ParticipantsExport($periodeId), $filename);

        } catch (\Exception $e) {
            // Fallback si l'export échoue
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste pour select/dropdown (par période) avec recherche
     */
    public function listByPeriode($periodeId, Request $request)
    {
        try {
        $query = Participant::select('id', 'name', 'email', 'training_group')
            ->where('periode_id', $periodeId)
            ->active()
            ->validated();

            // Recherche par terme
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('training_group', 'LIKE', "%{$search}%");
                });
            }

            $participants = $query->orderBy('name')
                ->get()
                ->map(function($participant) {
                    return [
                        'id' => $participant->id,
                        'name' => $participant->full_name,
                        'email' => $participant->email,
                        'training_group' => $participant->training_group,
                        'has_attestation' => $participant->hasAttestation()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $participants
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des participants.'
            ], 500);
        }
    }

    /**
     * Participants sans attestation
     */
    public function withoutAttestation(Request $request)
    {
        $query = Participant::with('periode')
            ->doesntHave('attestations')
            ->active()
            ->validated();

        if ($request->filled('periode_id')) {
            $query->where('periode_id', $request->periode_id);
        }

        $participants = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $participants
        ]);
    }

/**
 * Télécharger le template d'import
 */
public function downloadTemplate()
{
    try {
        Log::info('Download template accessed'); // Test logging

        $templateData = [
            ['nom', 'email', 'telephone', 'ville', 'whatsapp', 'groupe_de_formation'],
            ['John Doe', 'john.doe@example.com', '+237690000000', 'Yaoundé', '+237690000000', 'Groupe A'],
            ['Jane Smith', 'jane.smith@example.com', '+237691111111', 'Douala', '+237691111111', 'Groupe B'],
        ];

        $filename = 'template_import_participants_' . date('Y-m-d') . '.xlsx';

        // Créer un export simple
        $export = new class($templateData) implements \Maatwebsite\Excel\Concerns\FromArray {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return $this->data;
            }
        };

        return Excel::download($export, $filename);

    } catch (\Exception $e) {
        Log::error('Template download error: ' . $e->getMessage());

        // Fallback CSV
        $filename = 'template_import_participants_' . date('Y-m-d') . '.csv';
        $csv = fopen('php://output', 'w');

        foreach ($templateData as $row) {
            fputcsv($csv, $row, ';');
        }

        fclose($csv);

        return response()->streamDownload(function() use ($templateData) {
            $output = fopen('php://output', 'w');
            foreach ($templateData as $row) {
                fputcsv($output, $row, ';');
            }
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
}
