<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Créer le Super Admin
        $superAdmin = User::firstOrCreate(
            ['login' => 'superadmin'],
            [
                'name' => 'Super Administrateur',
                'email' => 'superadmin@attestations.cm',
                'password' => Hash::make('SuperAdmin@2025'),
                'is_active' => true,
                'is_delete' => false,
                'photo' => 'assets/images/default.jpg',
            ]
        );

        if ($superAdmin->wasRecentlyCreated) {
            $superAdmin->assignRole('Super Admin');
            $this->command->info('✅ Super Admin créé: superadmin / SuperAdmin@2025');
        }

        // Créer l'Admin
        $admin = User::firstOrCreate(
            ['login' => 'admin'],
            [
                'name' => 'Administrateur',
                'email' => 'admin@attestations.cm',
                'password' => Hash::make('Admin@2025'),
                'is_active' => true,
                'is_delete' => false,
                'photo' => 'assets/images/default.jpg',
            ]
        );

        if ($admin->wasRecentlyCreated) {
            $admin->assignRole('Admin');
            $this->command->info('✅ Admin créé: admin / Admin@2025');
        }

        // Créer un Manager
        $manager = User::firstOrCreate(
            ['login' => 'manager'],
            [
                'name' => 'Manager Test',
                'email' => 'manager@attestations.cm',
                'password' => Hash::make('Manager@2025'),
                'is_active' => true,
                'is_delete' => false,
                'photo' => 'assets/images/default.jpg',
            ]
        );

        if ($manager->wasRecentlyCreated) {
            $manager->assignRole('Manager');
            $this->command->info('✅ Manager créé: manager / Manager@2025');
        }

        // Créer un Operator
        $operator = User::firstOrCreate(
            ['login' => 'operator'],
            [
                'name' => 'Opérateur Test',
                'email' => 'operator@attestations.cm',
                'password' => Hash::make('Operator@2025'),
                'is_active' => true,
                'is_delete' => false,
                'photo' => 'assets/images/default.jpg',
            ]
        );

        if ($operator->wasRecentlyCreated) {
            $operator->assignRole('Operator');
            $this->command->info('✅ Operator créé: operator / Operator@2025');
        }

        // Créer un Viewer
        $viewer = User::firstOrCreate(
            ['login' => 'viewer'],
            [
                'name' => 'Lecteur Test',
                'email' => 'viewer@attestations.cm',
                'password' => Hash::make('Viewer@2025'),
                'is_active' => true,
                'is_delete' => false,
                'photo' => 'assets/images/default.jpg',
            ]
        );

        if ($viewer->wasRecentlyCreated) {
            $viewer->assignRole('Viewer');
            $this->command->info('✅ Viewer créé: viewer / Viewer@2025');
        }

        $this->command->info('');
        $this->command->info('📋 RÉCAPITULATIF DES COMPTES');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('🔴 Super Admin: superadmin / SuperAdmin@2025');
        $this->command->info('🟠 Admin:       admin      / Admin@2025');
        $this->command->info('🟡 Manager:     manager    / Manager@2025');
        $this->command->info('🟢 Operator:    operator   / Operator@2025');
        $this->command->info('🔵 Viewer:      viewer     / Viewer@2025');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->warn('⚠️  Changez ces mots de passe en production!');
    }
}
