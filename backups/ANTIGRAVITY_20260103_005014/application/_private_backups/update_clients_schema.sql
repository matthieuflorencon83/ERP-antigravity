-- ============================================
-- Script de Mise à Jour : Module CRM Clients
-- Date: 2025-12-25
-- ============================================

-- 1. MODIFICATION DE LA TABLE CLIENTS EXISTANTE
-- Ajout des colonnes manquantes

ALTER TABLE clients 
ADD COLUMN IF NOT EXISTS civilite ENUM('M.', 'Mme', 'Société', 'Autre') DEFAULT 'M.' AFTER id,
ADD COLUMN IF NOT EXISTS prenom VARCHAR(100) DEFAULT NULL AFTER nom_principal,
ADD COLUMN IF NOT EXISTS code_client VARCHAR(50) UNIQUE COMMENT 'Code unique client' AFTER prenom,
ADD COLUMN IF NOT EXISTS email_principal VARCHAR(255) AFTER code_client,
ADD COLUMN IF NOT EXISTS telephone_fixe VARCHAR(20) AFTER email_principal,
ADD COLUMN IF NOT EXISTS telephone_mobile VARCHAR(20) AFTER telephone_fixe,
ADD COLUMN IF NOT EXISTS adresse_postale TEXT AFTER telephone_mobile,
ADD COLUMN IF NOT EXISTS code_postal VARCHAR(5) AFTER adresse_postale,
ADD COLUMN IF NOT EXISTS ville VARCHAR(100) AFTER code_postal,
ADD COLUMN IF NOT EXISTS pays VARCHAR(100) DEFAULT 'France' AFTER ville,
ADD COLUMN IF NOT EXISTS siret VARCHAR(14) AFTER pays,
ADD COLUMN IF NOT EXISTS tva_intra VARCHAR(20) AFTER siret,
ADD COLUMN IF NOT EXISTS notes TEXT COMMENT 'Code porte, étage, instructions spéciales' AFTER tva_intra,
ADD COLUMN IF NOT EXISTS commentaire_livraison TEXT AFTER notes,
ADD COLUMN IF NOT EXISTS date_creation DATETIME DEFAULT CURRENT_TIMESTAMP AFTER commentaire_livraison,
ADD COLUMN IF NOT EXISTS date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER date_creation,
ADD COLUMN IF NOT EXISTS actif BOOLEAN DEFAULT TRUE AFTER date_modification;

-- Ajout des index
ALTER TABLE clients
ADD INDEX IF NOT EXISTS idx_code (code_client),
ADD INDEX IF NOT EXISTS idx_ville (ville);

-- 2. CRÉATION TABLE CLIENT_CONTACTS
-- Contacts secondaires (Conjoint, Assistant, etc.)

CREATE TABLE IF NOT EXISTS client_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    
    -- Identité du contact
    civilite ENUM('M.', 'Mme', 'Autre') DEFAULT 'M.',
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100),
    role VARCHAR(100) COMMENT 'Conjoint, Assistant, Comptable, etc.',
    
    -- Coordonnées
    email VARCHAR(255),
    telephone_fixe VARCHAR(20),
    telephone_mobile VARCHAR(20),
    
    -- Métadonnées
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Contacts secondaires des clients';

-- 3. CRÉATION TABLE CLIENT_ADRESSES
-- Adresses multiples (Domicile, Travail, Chantier, etc.)

CREATE TABLE IF NOT EXISTS client_adresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    
    -- Type d'adresse
    type_adresse ENUM('Domicile', 'Travail', 'Chantier', 'Facturation', 'Livraison', 'Autre') DEFAULT 'Domicile',
    
    -- Adresse complète
    adresse TEXT NOT NULL,
    code_postal VARCHAR(5),
    ville VARCHAR(100),
    pays VARCHAR(100) DEFAULT 'France',
    
    -- Contact sur place
    contact_sur_place VARCHAR(100),
    telephone VARCHAR(20),
    
    -- Instructions
    instructions TEXT COMMENT 'Code porte, étage, digicode, etc.',
    
    -- Métadonnées
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_type (type_adresse)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Adresses multiples des clients';

-- 4. CRÉATION TABLE CLIENT_TELEPHONES
-- Téléphones multiples (Bureau, Domicile, Portable, etc.)

CREATE TABLE IF NOT EXISTS client_telephones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    
    -- Type et numéro
    type_telephone ENUM('Bureau', 'Domicile', 'Portable', 'Fax', 'Autre') DEFAULT 'Portable',
    numero VARCHAR(20) NOT NULL,
    
    -- Informations complémentaires
    libelle VARCHAR(100) COMMENT 'Ex: Portable Pro, Tel Chantier',
    principal BOOLEAN DEFAULT FALSE,
    
    -- Métadonnées
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Téléphones multiples des clients';

-- 5. CRÉATION TABLE CLIENT_EMAILS
-- Emails multiples (Principal, Secondaire, Professionnel, etc.)

CREATE TABLE IF NOT EXISTS client_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    
    -- Email et type
    email VARCHAR(255) NOT NULL,
    type_email ENUM('Principal', 'Secondaire', 'Professionnel', 'Facturation', 'Autre') DEFAULT 'Principal',
    
    -- Informations complémentaires
    libelle VARCHAR(100),
    principal BOOLEAN DEFAULT FALSE,
    
    -- Métadonnées
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Emails multiples des clients';

-- ============================================
-- FIN DU SCRIPT
-- ============================================

-- Vérification
SELECT 'Script exécuté avec succès !' AS status;
