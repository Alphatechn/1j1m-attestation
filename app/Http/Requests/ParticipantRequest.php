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
            'civility' => 'nullable|string|max:20',
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('participants')->ignore($participantId),
            ],
            'city' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:30',
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
        ];
    }
}
