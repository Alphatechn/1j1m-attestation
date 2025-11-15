<?php

namespace Database\Factories;

use App\Models\Periode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class PeriodeFactory extends Factory
{
    protected $model = Periode::class;

    public function definition(): array
    {
        $moisDebut = $this->faker->numberBetween(1, 12);
        $anneeDebut = $this->faker->numberBetween(2023, 2025);

        // Calculer une date de fin après la date de début
        $moisFin = $moisDebut + $this->faker->numberBetween(1, 6);
        $anneeFin = $anneeDebut;

        if ($moisFin > 12) {
            $moisFin -= 12;
            $anneeFin++;
        }

        $dateDebut = Carbon::createFromDate($anneeDebut, $moisDebut, 1);
        $dateFin = Carbon::createFromDate($anneeFin, $moisFin, 1)->endOfMonth();

        $moisNoms = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        $libelle = $moisNoms[$moisDebut] . ' ' . $anneeDebut . ' à ' .
                   $moisNoms[$moisFin] . ' ' . $anneeFin;

        return [
            'libelle' => $libelle,
            'mois_debut' => str_pad($moisDebut, 2, '0', STR_PAD_LEFT),
            'annee_debut' => (string)$anneeDebut,
            'mois_fin' => str_pad($moisFin, 2, '0', STR_PAD_LEFT),
            'annee_fin' => (string)$anneeFin,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'description' => $this->faker->optional()->sentence(10),
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
