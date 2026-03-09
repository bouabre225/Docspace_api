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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users');
            $table->text('contenu')->nullable();
            $table->boolean('lu')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
        
        DB::statement('ALTER TABLE notifications ADD COLUMN type notification_type_enum');
        DB::statement('ALTER TABLE notifications ADD COLUMN canal notification_canal_enum');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
