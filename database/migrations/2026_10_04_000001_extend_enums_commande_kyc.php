<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TYPE statut_commande_enum ADD VALUE IF NOT EXISTS 'payee'");
        DB::statement("ALTER TYPE statut_commande_enum ADD VALUE IF NOT EXISTS 'annulee'");
        DB::statement("ALTER TYPE statut_commande_enum ADD VALUE IF NOT EXISTS 'litige'");
        DB::statement("ALTER TYPE type_document_enum ADD VALUE IF NOT EXISTS 'permis'");
    }

    public function down(): void
    {
        // Les valeurs d'un ENUM PG ne peuvent pas être retirées
    }
};
