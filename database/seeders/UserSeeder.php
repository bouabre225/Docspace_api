<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ne pas truncate car les contraintes de clés étrangères
        DB::table('users')->delete();

        $users = [
            [
                'id' => '94bed60e-1c94-4538-a98e-733ecda91606',
                'google_id' => null,
                'avatar' => null,
                'nom' => 'Admin User',
                'email' => 'admin@docspace.com',
                'mot_de_passe' => Hash::make('password123'),
                'telephone' => '+33612345678',
                'pays' => 'France',
                'devise' => 'EUR',
                'adresse' => '123 Rue de la Paix, Paris',
                'role' => 'admin',
                'type_compte' => 'professionnel',
                'statut' => 'actif',
                'verifie_kyc' => true,
                'badge_verifie' => true,
                'note_moyenne' => 5.0,
                'created_at' => now(),
            ],
            [
                'id' => '94bed60e-2c94-4538-a98e-733ecda91607',
                'google_id' => null,
                'avatar' => null,
                'nom' => 'Vendeur Test',
                'email' => 'vendeur@docspace.com',
                'mot_de_passe' => Hash::make('password123'),
                'telephone' => '+33623456789',
                'pays' => 'France',
                'devise' => 'EUR',
                'adresse' => '456 Avenue des Champs, Lyon',
                'role' => 'vendeur',
                'type_compte' => 'professionnel',
                'statut' => 'actif',
                'verifie_kyc' => true,
                'badge_verifie' => true,
                'note_moyenne' => 4.5,
                'created_at' => now(),
            ],
            [
                'id' => '94bed60e-3c94-4538-a98e-733ecda91608',
                'google_id' => null,
                'avatar' => null,
                'nom' => 'Acheteur Test',
                'email' => 'acheteur@docspace.com',
                'mot_de_passe' => Hash::make('password123'),
                'telephone' => '+33634567890',
                'pays' => 'Belgique',
                'devise' => 'EUR',
                'adresse' => '789 Boulevard Central, Bruxelles',
                'role' => 'acheteur',
                'type_compte' => 'particulier',
                'statut' => 'actif',
                'verifie_kyc' => false,
                'badge_verifie' => false,
                'note_moyenne' => 0,
                'created_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert($user);
        }
    }
}
