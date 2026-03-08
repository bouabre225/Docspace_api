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
        Schema::create('annonce_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('annonce_id')->constrained('annonces')->onDelete('cascade');
            $table->string('image_url', 255)->nullable();
            $table->integer('ordre')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annonce_images');
    }
};
