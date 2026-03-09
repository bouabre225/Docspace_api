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
        Schema::create('kyc_audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('admin_id')->constrained('users');
            $table->foreignUuid('document_id')->nullable()->constrained('kyc_documents');
            $table->text('commentaire')->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
        
        DB::statement('ALTER TABLE kyc_audits ADD COLUMN ancien_statut statut_kyc_enum');
        DB::statement('ALTER TABLE kyc_audits ADD COLUMN nouveau_statut statut_kyc_enum');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_audits');
    }
};
