<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Démarrage du seeding de la base de données...');
        $this->command->info('');

        // Ordre d'exécution des seeders
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            // PeriodeSeeder::class, // Décommenter si vous voulez des données de test
        ]);

        $this->command->info('');
        $this->command->info('✅ Base de données seedée avec succès!');
        $this->command->info('🌐 Accédez à l\'application et connectez-vous');
    }
}
