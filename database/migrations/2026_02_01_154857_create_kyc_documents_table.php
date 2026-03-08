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
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('fichier', 255)->nullable();
            $table->timestamp('date_validation')->nullable();
        });
        
        DB::statement('ALTER TABLE kyc_documents ADD COLUMN type_document type_document_enum');
        DB::statement('ALTER TABLE kyc_documents ADD COLUMN statut statut_kyc_enum DEFAULT \'en_attente\'');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
    }
};
