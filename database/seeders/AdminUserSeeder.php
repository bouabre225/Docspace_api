<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Évite les doublons
        if (User::where('email', 'wixev70924@devlug.com')->exists()) {
            $this->command->info('Admin déjà existant, skipped.');
            return;
        }

        User::create([
            'id'           => (string) Str::uuid(),
            'nom'          => 'Super Admin',
            'email'        => 'wixev70924@devlug.com',
            'mot_de_passe' => Hash::make('Admin@2026'),
            'role'         => 'admin',
            'statut'       => 'actif',
            'type_compte'  => 'professionnel',
            'verifie_kyc'  => true,
            'badge_verifie' => true,
        ]);

        $this->command->info('Admin créé : wixev70924@devlug.com / Admin@2026');
    }
}