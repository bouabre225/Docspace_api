<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['expediteur_id', 'recepteur_id'], 'messages_expediteur_recepteur_index');
        });
        Schema::table('commandes', function (Blueprint $table) {
            $table->index(['acheteur_id', 'vendeur_id', 'statut'], 'commandes_acheteur_vendeur_statut_index');
        });
        Schema::table('annonces', function (Blueprint $table) {
            $table->index(['statut', 'categorie'], 'annonces_statut_categorie_index');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_expediteur_recepteur_index');
        });
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropIndex('commandes_acheteur_vendeur_statut_index');
        });
        Schema::table('annonces', function (Blueprint $table) {
            $table->dropIndex('annonces_statut_categorie_index');
        });
    }
};
