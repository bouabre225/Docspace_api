<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('litiges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('commande_id')->constrained('commandes');
            $table->foreignUuid('acheteur_id')->constrained('users');
            $table->text('preuves')->nullable();
            $table->timestamp('date_signalement')->useCurrent();
        });
        
        DB::statement('ALTER TABLE litiges ADD COLUMN motif motif_litige_enum');
        DB::statement('ALTER TABLE litiges ADD COLUMN statut statut_litige_enum DEFAULT \'ouvert\'');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('litiges');
    }
};
