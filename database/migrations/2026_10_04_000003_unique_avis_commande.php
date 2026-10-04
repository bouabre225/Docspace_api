<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->unique('commande_id', 'avis_commande_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->dropUnique('avis_commande_id_unique');
        });
    }
};
