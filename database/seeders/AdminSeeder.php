<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un admin
        $admin = Admin::create([
            'nom' => 'Admin',
            'poste' => 'Super Admin',
            'date_creation' => now(),
        ]);

        // Créer l'utilisateur associé
        User::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'code' => 'ADMIN001',
            'userable_id' => $admin->id,
            'userable_type' => Admin::class,
        ]);

        // Créer un deuxième admin pour les tests
        $admin2 = Admin::create([
            'nom' => 'Manager',
            'poste' => 'Bank Manager',
            'date_creation' => now(),
        ]);

        User::create([
            'email' => 'manager@example.com',
            'password' => Hash::make('password123'),
            'code' => 'ADMIN002',
            'userable_id' => $admin2->id,
            'userable_type' => Admin::class,
        ]);
    }
}
