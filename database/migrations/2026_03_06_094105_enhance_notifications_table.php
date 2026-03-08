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
        Schema::table('notifications', function (Blueprint $table) {
            // Référence polymorphique vers l'entité concernée
            $table->string('reference_type', 50)->nullable()->after('type');
            // ex: 'commande', 'litige', 'message', 'annonce', 'kyc'
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');

            // Données supplémentaires flexibles (deep link, montant, etc.)
            $table->jsonb('metadata')->nullable()->after('contenu');

            // Date d'envoi effectif (peut différer de created_at si retry)
            $table->timestamp('sent_at')->nullable()->after('lu');

            // Index pour performances
            $table->index(['user_id', 'lu']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['reference_type', 'reference_id', 'metadata', 'sent_at']);
            $table->dropIndex(['user_id', 'lu']);
            $table->dropIndex(['reference_type', 'reference_id']);
            $table->dropIndex(['created_at']);
        });
    }
};
