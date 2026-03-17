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
        DB::statement("ALTER TYPE moyen_paiement_enum ADD VALUE IF NOT EXISTS 'fedapay'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ne peut pas retirer une valeur d'un enum existant
        // Cette migration est irreversible
    }
};
