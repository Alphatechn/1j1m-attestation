<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $participantId = $this->route('participant');

        return [
            'periode_id' => 'required|exists:periodes,id',
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('participants')->ignore($participantId),
            ],
            'phone' => 'nullable|string|max:20',
            'organisation' => 'nullable|string|max:255',
            'fonction' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'periode_id.required' => 'La période est obligatoire.',
            'periode_id.exists' => 'La période sélectionnée n\'existe pas.',
            'name.required' => 'Le nom complet est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'matricule.unique' => 'Ce matricule est déjà utilisé.',
        ];
    }
}
