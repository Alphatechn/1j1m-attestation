<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeriodeResource extends JsonResource
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
            'libelle' => $this->libelle,
            'full_libelle' => $this->full_libelle,
            'mois_debut' => $this->mois_debut,
            'annee_debut' => $this->annee_debut,
            'mois_fin' => $this->mois_fin,
            'annee_fin' => $this->annee_fin,
            'date_debut' => $this->date_debut?->format('d/m/Y'),
            'date_fin' => $this->date_fin?->format('d/m/Y'),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'participants_count' => $this->whenCounted('participants'),
            'attestations_count' => $this->whenCounted('attestations'),
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),
        ];
    }
}
