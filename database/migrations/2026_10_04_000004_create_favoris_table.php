<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favoris', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('annonce_id')->constrained('annonces')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'annonce_id'], 'favoris_user_annonce_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favoris');
    }
};
