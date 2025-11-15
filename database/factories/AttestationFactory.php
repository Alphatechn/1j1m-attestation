<?php

namespace Database\Factories;

use App\Models\Attestation;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AttestationFactory extends Factory
{
    protected $model = Attestation::class;

    public function definition(): array
    {
        $participant = Participant::factory()->create();

        return [
            'participant_id' => $participant->id,
            'periode_id' => $participant->periode_id,
            'generated_by' => User::inRandomOrder()->first()?->id,
            'attestation_number' => 'ATT-' . date('Y') . '-' . strtoupper(Str::random(8)),
            'qr_token' => Str::random(32),
            'issue_date' => Carbon::now()->subDays($this->faker->numberBetween(0, 90)),
            'content_text' => $this->generateContentText($participant),
            'status' => $this->faker->randomElement(['pending', 'sent']),
            'sent_at' => $this->faker->optional(0.7)->dateTimeBetween('-60 days', 'now'),
            'email_status' => $this->faker->randomElement(['success', 'failed', 'pending', null]),
            'view_count' => $this->faker->numberBetween(0, 50),
            'last_viewed_at' => $this->faker->optional(0.5)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Générer le contenu texte de l'attestation
     */
    protected function generateContentText($participant): string
    {
        return "Je soussigné(e), certifie que " . $participant->full_name .
               " a participé à la formation/session organisée durant la période " .
               $participant->periode->full_libelle . ". Cette attestation est délivrée pour servir " .
               "et valoir ce que de droit.";
    }

    /**
     * Attestation envoyée
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => Carbon::now()->subDays($this->faker->numberBetween(1, 30)),
            'email_status' => 'success',
        ]);
    }

    /**
     * Attestation en attente
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'sent_at' => null,
            'email_status' => null,
        ]);
    }

    /**
     * Attestation pour un participant spécifique
     */
    public function forParticipant(Participant $participant): static
    {
        return $this->state(fn (array $attributes) => [
            'participant_id' => $participant->id,
            'periode_id' => $participant->periode_id,
            'content_text' => $this->generateContentText($participant),
        ]);
    }
}
