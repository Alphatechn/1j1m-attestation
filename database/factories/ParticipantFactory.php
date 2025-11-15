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
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'matricule' => $this->faker->optional()->unique()->numerify('MAT-####-####'),
            'organisation' => $this->faker->optional()->company(),
            'fonction' => $this->faker->optional()->jobTitle(),
            'is_active' => $this->faker->boolean(90),
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
