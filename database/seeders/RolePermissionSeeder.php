<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Réinitialiser le cache des rôles et permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions
        $permissions = [
            // Gestion des utilisateurs
            'manage users',
            'create users',
            'edit users',
            'delete users',
            'view users',

            // Gestion des périodes
            'manage periodes',
            'create periodes',
            'edit periodes',
            'delete periodes',
            'view periodes',

            // Gestion des participants
            'manage participants',
            'create participants',
            'edit participants',
            'delete participants',
            'view participants',
            'import participants',
            'export participants',

            // Gestion des attestations
            'manage attestations',
            'create attestations',
            'send attestations',
            'delete attestations',
            'view attestations',
            'download attestations',

            // Dashboard et statistiques
            'view dashboard',
            'view statistics',

            // Paramètres système
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Créer les rôles et assigner les permissions

        // 1. Super Admin - Accès total
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        // 2. Admin - Gestion complète sauf paramètres système
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([
            'manage users',
            'create users',
            'edit users',
            'view users',
            'manage periodes',
            'create periodes',
            'edit periodes',
            'delete periodes',
            'view periodes',
            'manage participants',
            'create participants',
            'edit participants',
            'delete participants',
            'view participants',
            'import participants',
            'export participants',
            'manage attestations',
            'create attestations',
            'send attestations',
            'delete attestations',
            'view attestations',
            'download attestations',
            'view dashboard',
            'view statistics',
        ]);

        // 3. Manager - Gestion des périodes, participants et attestations
        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $manager->givePermissionTo([
            'view users',
            'manage periodes',
            'create periodes',
            'edit periodes',
            'view periodes',
            'manage participants',
            'create participants',
            'edit participants',
            'view participants',
            'import participants',
            'export participants',
            'manage attestations',
            'create attestations',
            'send attestations',
            'view attestations',
            'download attestations',
            'view dashboard',
            'view statistics',
        ]);

        // 4. Operator - Gestion des participants et attestations uniquement
        $operator = Role::firstOrCreate(['name' => 'Operator', 'guard_name' => 'web']);
        $operator->givePermissionTo([
            'view periodes',
            'manage participants',
            'create participants',
            'edit participants',
            'view participants',
            'manage attestations',
            'create attestations',
            'send attestations',
            'view attestations',
            'download attestations',
            'view dashboard',
        ]);

        // 5. Viewer - Lecture seule
        $viewer = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);
        $viewer->givePermissionTo([
            'view users',
            'view periodes',
            'view participants',
            'view attestations',
            'view dashboard',
        ]);

        $this->command->info('✅ Rôles et permissions créés avec succès!');
        $this->command->info('📋 Rôles: Super Admin, Admin, Manager, Operator, Viewer');
        $this->command->info('🔐 Total permissions: ' . count($permissions));
    }
}
