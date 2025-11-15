<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttestationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attestation_number' => $this->attestation_number,
            'qr_token' => $this->qr_token,
            'issue_date' => $this->issue_date->format('d/m/Y'),
            'content_text' => $this->content_text,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'sent_at' => $this->sent_at?->format('d/m/Y H:i'),
            'email_status' => $this->email_status,
            'view_count' => $this->view_count,
            'last_viewed_at' => $this->last_viewed_at?->format('d/m/Y H:i'),
            'verification_url' => $this->getVerificationUrl(),
            'participant' => new ParticipantResource($this->whenLoaded('participant')),
            'periode' => new PeriodeResource($this->whenLoaded('periode')),
            'generated_by' => new UserResource($this->whenLoaded('generatedBy')),
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Obtenir le libellé du statut
     */
    protected function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'sent' => 'Envoyée',
            default => 'Inconnu',
        };
    }
}
