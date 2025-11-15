<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PeriodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'libelle' => 'required|string|max:255',
            'mois_debut' => 'required|integer|between:1,12',
            'annee_debut' => 'required|integer|min:2020|max:2100',
            'mois_fin' => 'required|integer|between:1,12',
            'annee_fin' => 'required|integer|min:2020|max:2100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé est obligatoire.',
            'mois_debut.required' => 'Le mois de début est obligatoire.',
            'mois_debut.between' => 'Le mois de début doit être entre 1 et 12.',
            'annee_debut.required' => 'L\'année de début est obligatoire.',
            'annee_debut.min' => 'L\'année de début doit être au minimum 2020.',
            'mois_fin.required' => 'Le mois de fin est obligatoire.',
            'mois_fin.between' => 'Le mois de fin doit être entre 1 et 12.',
            'annee_fin.required' => 'L\'année de fin est obligatoire.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $dateDebut = "{$this->annee_debut}-{$this->mois_debut}-01";
            $dateFin = "{$this->annee_fin}-{$this->mois_fin}-01";

            if (strtotime($dateDebut) > strtotime($dateFin)) {
                $validator->errors()->add('date_fin', 'La date de fin doit être postérieure à la date de début.');
            }
        });
    }
}
