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
        Schema::create('avis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('commande_id')->constrained('commandes');
            $table->foreignUuid('vendeur_id')->constrained('users');
            $table->integer('note_vendeur')->unsigned()->default(5);
            $table->integer('note_conformite')->unsigned()->default(5);
            $table->text('commentaire')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};
