<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TYPE statut_paiement_enum ADD VALUE IF NOT EXISTS 'en_attente'");
        DB::statement("ALTER TYPE statut_paiement_enum ADD VALUE IF NOT EXISTS 'annule'");
        DB::statement("ALTER TYPE statut_paiement_enum ADD VALUE IF NOT EXISTS 'echoue'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
