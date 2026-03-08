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
        // Create ENUMs if not exists
        DB::statement("DO $$ BEGIN
            CREATE TYPE user_role AS ENUM ('acheteur', 'vendeur', 'admin');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        DB::statement("DO $$ BEGIN
            CREATE TYPE type_compte_enum AS ENUM ('particulier', 'professionnel');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        DB::statement("DO $$ BEGIN
            CREATE TYPE statut_user_enum AS ENUM ('actif', 'suspendu', 'supprime');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        DB::statement("DO $$ BEGIN
            CREATE TYPE type_document_enum AS ENUM ('cni', 'passeport');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        DB::statement("DO $$ BEGIN
            CREATE TYPE statut_kyc_enum AS ENUM ('en_attente', 'valide', 'refuse');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        DB::statement("DO $$ BEGIN
            CREATE TYPE etat_annonce_enum AS ENUM ('neuf', 'tres_bon', 'bon', 'acceptable');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        DB::statement("DO $$ BEGIN
            CREATE TYPE statut_annonce_enum AS ENUM ('active', 'vendue', 'suspendue');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        DB::statement("DO $$ BEGIN
            CREATE TYPE statut_commande_enum AS ENUM ('en_attente', 'expediee', 'livree', 'cloturee');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        DB::statement("DO $$ BEGIN
            CREATE TYPE moyen_paiement_enum AS ENUM ('stripe', 'paypal');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        DB::statement("DO $$ BEGIN
            CREATE TYPE statut_paiement_enum AS ENUM ('bloque', 'libere', 'rembourse');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        DB::statement("DO $$ BEGIN
            CREATE TYPE motif_litige_enum AS ENUM ('non_conforme', 'defectueux', 'perdu');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        DB::statement("DO $$ BEGIN
            CREATE TYPE statut_litige_enum AS ENUM ('ouvert', 'en_cours', 'resolu');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        DB::statement("DO $$ BEGIN
            CREATE TYPE notification_type_enum AS ENUM ('message', 'commande', 'litige', 'systeme');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        DB::statement("DO $$ BEGIN
            CREATE TYPE notification_canal_enum AS ENUM ('push', 'email', 'sms');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('google_id', 150)->unique()->nullable();
            $table->string('avatar', 100)->nullable();
            $table->string('nom', 100);
            $table->string('email', 150)->unique();
            $table->string('mot_de_passe', 255);
            $table->string('telephone', 20)->nullable();
            $table->string('pays', 50)->nullable();
            $table->string('devise', 10)->nullable();
            $table->text('adresse')->nullable();
            $table->string('two_factor_secret', 100)->nullable();
            $table->boolean('verifie_kyc')->default(false);
            $table->boolean('badge_verifie')->default(false);
            $table->decimal('note_moyenne', 2, 1)->default(0);
            $table->timestamp('two_factor_enable_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement('ALTER TABLE users ADD COLUMN role user_role NOT NULL');
        DB::statement('ALTER TABLE users ADD COLUMN type_compte type_compte_enum NOT NULL');
        DB::statement('ALTER TABLE users ADD COLUMN statut statut_user_enum DEFAULT \'actif\'');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');

        DB::statement("DROP TYPE IF EXISTS user_role CASCADE");
        DB::statement("DROP TYPE IF EXISTS type_compte_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS statut_user_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS type_document_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS statut_kyc_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS etat_annonce_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS statut_annonce_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS statut_commande_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS moyen_paiement_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS statut_paiement_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS motif_litige_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS statut_litige_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS notification_type_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS notification_canal_enum CASCADE");
    }
};
