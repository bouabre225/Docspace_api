<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('visitor_id', 64)->index();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('annonce_id')->nullable()->constrained('annonces')->onDelete('cascade');
            $table->string('page', 255)->nullable();
            $table->string('referer', 500)->nullable();
            $table->timestamps();
            $table->index(['annonce_id', 'created_at'], 'visites_annonce_date_index');
            $table->index(['visitor_id', 'created_at'], 'visites_visitor_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visites');
    }
};
