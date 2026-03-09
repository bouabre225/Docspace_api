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
        Schema::create('commandes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('acheteur_id')->constrained('users');
            $table->foreignUuid('vendeur_id')->constrained('users');
            $table->foreignUuid('annonce_id')->constrained('annonces');
            $table->integer('quantite');
            $table->decimal('montant', 10, 2);
            $table->timestamp('created_at')->useCurrent();
        });
        
        DB::statement('ALTER TABLE commandes ADD COLUMN statut statut_commande_enum DEFAULT \'en_attente\'');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
