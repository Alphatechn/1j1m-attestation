<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
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
            'name' => $this->name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'whatsapp' => $this->whatsapp,
            'training_group' => $this->training_group,
            'validation_status' => $this->validation_status,
            'homework_screenshot_paths' => $this->homework_screenshot_paths,
            'is_active' => $this->is_active,
            'periode' => new PeriodeResource($this->whenLoaded('periode')),
            'has_attestation' => $this->hasAttestation(),
            'attestations_count' => $this->whenCounted('attestations'),
            'latest_attestation' => new AttestationResource($this->whenLoaded('attestations', function() {
                return $this->getLatestAttestation();
            })),
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),
        ];
    }
}
