<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\Periode;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    public function definition(): array
    {
        return [
            'periode_id' => Periode::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'city' => $this->faker->optional()->city(),
            'whatsapp' => $this->faker->optional()->phoneNumber(),
            'training_group' => $this->faker->optional()->word(),
            'is_active' => $this->faker->boolean(90),
            'validation_status' => 'validated',
        ];
    }

    /**
     * Participant avec une période spécifique
     */
    public function forPeriode(Periode $periode): static
    {
        return $this->state(fn (array $attributes) => [
            'periode_id' => $periode->id,
        ]);
    }

    /**
     * Participant actif
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Participant inactif
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
