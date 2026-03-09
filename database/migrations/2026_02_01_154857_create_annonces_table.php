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
        Schema::create('annonces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendeur_id')->constrained('users');
            $table->string('titre', 200);
            $table->text('description')->nullable();
            $table->string('categorie', 100)->nullable();
            $table->decimal('prix_vendeur', 10, 2);
            $table->decimal('frais_protection', 10, 2)->storedAs('prix_vendeur * 0.08');
            $table->decimal('prix_total', 10, 2)->storedAs('prix_vendeur * 1.08');
            $table->integer('quantite')->default(1);
            $table->string('pays_expedition', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
        
        DB::statement('ALTER TABLE annonces ADD COLUMN etat etat_annonce_enum');
        DB::statement('ALTER TABLE annonces ADD COLUMN statut statut_annonce_enum DEFAULT \'active\'');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annonces');
    }
};
