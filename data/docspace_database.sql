-- =====================================================
-- Base de données DocSpace (PostgreSQL)
-- Marketplace d’équipements médicaux d’occasion
-- =====================================================

-- Création de la base (à exécuter séparément si besoin)
-- CREATE DATABASE docspace;

-- =====================================================
-- ENUMS
-- =====================================================

CREATE TYPE user_role AS ENUM ('acheteur', 'vendeur', 'admin');
CREATE TYPE type_compte_enum AS ENUM ('particulier', 'professionnel');
CREATE TYPE statut_user_enum AS ENUM ('actif', 'suspendu', 'supprime');

CREATE TYPE type_document_enum AS ENUM ('cni', 'passeport');
CREATE TYPE statut_kyc_enum AS ENUM ('en_attente', 'valide', 'refuse');

CREATE TYPE etat_annonce_enum AS ENUM ('neuf', 'tres_bon', 'bon', 'acceptable');
CREATE TYPE statut_annonce_enum AS ENUM ('active', 'vendue', 'suspendue');

CREATE TYPE statut_commande_enum AS ENUM ('en_attente', 'expediee', 'livree', 'cloturee', 'annulee');

CREATE TYPE moyen_paiement_enum AS ENUM ('stripe', 'paypal');
CREATE TYPE statut_paiement_enum AS ENUM ('bloque', 'libere', 'rembourse');

CREATE TYPE motif_litige_enum AS ENUM ('non_conforme', 'defectueux', 'perdu');
CREATE TYPE statut_litige_enum AS ENUM ('ouvert', 'en_cours', 'resolu');

CREATE TYPE notification_type_enum AS ENUM ('message', 'commande', 'litige', 'systeme');
CREATE TYPE notification_canal_enum AS ENUM ('push', 'email', 'sms');

-- =====================================================
-- TABLE USERS
-- =====================================================

CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    google_id VARCHAR(150) UNIQUE,
    avatar VARCHAR(100),
    role user_role NOT NULL DEFAULT 'acheteur',
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    pays VARCHAR(50),
    devise VARCHAR(10),
    adresse TEXT,
    two_factor_secret VARCHAR(100),
    verifie_kyc BOOLEAN DEFAULT FALSE,
    badge_verifie BOOLEAN DEFAULT FALSE,
    note_moyenne DECIMAL(2,1) DEFAULT 0,
    statut statut_user_enum DEFAULT 'actif',
    two_factor_enable_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- KYC DOCUMENTS
-- =====================================================

CREATE TABLE kyc_documents (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    type_document type_document_enum,
    fichier VARCHAR(255),
    statut statut_kyc_enum DEFAULT 'en_attente',
    date_validation TIMESTAMP,
    CONSTRAINT fk_kyc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE kyc_audits (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    admin_id BIGINT NOT NULL,
    document_id BIGINT,
    ancien_statut statut_kyc_enum,
    nouveau_statut statut_kyc_enum,
    commentaire TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kyc_audit_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_kyc_audit_admin FOREIGN KEY (admin_id) REFERENCES users(id),
    CONSTRAINT fk_kyc_audit_document FOREIGN KEY (document_id) REFERENCES kyc_documents(id)
);

-- =====================================================
-- ANNONCES
-- =====================================================

CREATE TABLE annonces (
    id BIGSERIAL PRIMARY KEY,
    vendeur_id BIGINT NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    categorie VARCHAR(100),
    etat etat_annonce_enum,
    prix_vendeur DECIMAL(10,2) NOT NULL,
    frais_protection DECIMAL(10,2) GENERATED ALWAYS AS (prix_vendeur * 0.08) STORED,
    prix_total DECIMAL(10,2) GENERATED ALWAYS AS (prix_vendeur * 1.08) STORED,
    quantite INT DEFAULT 1,
    pays_expedition VARCHAR(50),
    statut statut_annonce_enum DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_annonce_vendeur FOREIGN KEY (vendeur_id) REFERENCES users(id)
);

-- =====================================================
-- IMAGES DES ANNONCES
-- =====================================================

CREATE TABLE annonce_images (
    id BIGSERIAL PRIMARY KEY,
    annonce_id BIGINT NOT NULL,
    image_url VARCHAR(255),
    ordre INT,
    CONSTRAINT fk_image_annonce FOREIGN KEY (annonce_id) REFERENCES annonces(id) ON DELETE CASCADE
);

-- =====================================================
-- COMMANDES
-- =====================================================

CREATE TABLE commandes (
    id BIGSERIAL PRIMARY KEY,
    acheteur_id BIGINT NOT NULL,
    vendeur_id BIGINT NOT NULL,
    annonce_id BIGINT NOT NULL,
    quantite INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    statut statut_commande_enum DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_commande_acheteur FOREIGN KEY (acheteur_id) REFERENCES users(id),
    CONSTRAINT fk_commande_vendeur FOREIGN KEY (vendeur_id) REFERENCES users(id),
    CONSTRAINT fk_commande_annonce FOREIGN KEY (annonce_id) REFERENCES annonces(id)
);

-- =====================================================
-- PAIEMENTS
-- =====================================================

CREATE TABLE paiements (
    id BIGSERIAL PRIMARY KEY,
    commande_id BIGINT NOT NULL,
    moyen moyen_paiement_enum,
    montant DECIMAL(10,2),
    statut statut_paiement_enum,
    date_paiement TIMESTAMP,
    CONSTRAINT fk_paiement_commande FOREIGN KEY (commande_id) REFERENCES commandes(id)
);

-- =====================================================
-- LITIGES
-- =====================================================

CREATE TABLE litiges (
    id BIGSERIAL PRIMARY KEY,
    commande_id BIGINT NOT NULL,
    acheteur_id BIGINT NOT NULL,
    motif motif_litige_enum,
    preuves TEXT,
    statut statut_litige_enum DEFAULT 'ouvert',
    date_signalement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_litige_commande FOREIGN KEY (commande_id) REFERENCES commandes(id),
    CONSTRAINT fk_litige_acheteur FOREIGN KEY (acheteur_id) REFERENCES users(id)
);

-- =====================================================
-- AVIS
-- =====================================================

CREATE TABLE avis (
    id BIGSERIAL PRIMARY KEY,
    commande_id BIGINT NOT NULL,
    vendeur_id BIGINT NOT NULL,
    note_vendeur INT CHECK (note_vendeur BETWEEN 1 AND 5),
    note_conformite INT CHECK (note_conformite BETWEEN 1 AND 5),
    commentaire TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_avis_commande FOREIGN KEY (commande_id) REFERENCES commandes(id),
    CONSTRAINT fk_avis_vendeur FOREIGN KEY (vendeur_id) REFERENCES users(id)
);

-- =====================================================
-- MESSAGES
-- =====================================================

CREATE TABLE messages (
    id BIGSERIAL PRIMARY KEY,
    expediteur_id BIGINT NOT NULL,
    recepteur_id BIGINT NOT NULL,
    annonce_id BIGINT,
    contenu TEXT,
    lu BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_message_expediteur FOREIGN KEY (expediteur_id) REFERENCES users(id),
    CONSTRAINT fk_message_recepteur FOREIGN KEY (recepteur_id) REFERENCES users(id),
    CONSTRAINT fk_message_annonce FOREIGN KEY (annonce_id) REFERENCES annonces(id)
);

-- =====================================================
-- NOTIFICATIONS
-- =====================================================

CREATE TABLE notifications (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    type notification_type_enum,
    canal notification_canal_enum,
    contenu TEXT,
    lu BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id)
);