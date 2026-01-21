-- Ajout des colonnes d'adresse et administratifs à la table fournisseurs
ALTER TABLE fournisseurs ADD COLUMN IF NOT EXISTS adresse_postale VARCHAR(255) NULL;
ALTER TABLE fournisseurs ADD COLUMN IF NOT EXISTS code_postal VARCHAR(10) NULL;
ALTER TABLE fournisseurs ADD COLUMN IF NOT EXISTS ville VARCHAR(100) NULL;
ALTER TABLE fournisseurs ADD COLUMN IF NOT EXISTS pays VARCHAR(50) DEFAULT 'France';
ALTER TABLE fournisseurs ADD COLUMN IF NOT EXISTS siret VARCHAR(50) NULL;
ALTER TABLE fournisseurs ADD COLUMN IF NOT EXISTS tva_intra VARCHAR(50) NULL;
ALTER TABLE fournisseurs ADD COLUMN IF NOT EXISTS condition_paiement VARCHAR(100) NULL;
ALTER TABLE fournisseurs ADD COLUMN IF NOT EXISTS site_web VARCHAR(255) NULL;
ALTER TABLE fournisseurs ADD COLUMN IF NOT EXISTS notes TEXT NULL;

-- Création de la table des contacts fournisseurs
CREATE TABLE IF NOT EXISTS fournisseur_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fournisseur_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    role VARCHAR(100) NULL COMMENT 'Ex: Commercial, Comptabilité',
    email VARCHAR(150) NULL,
    telephone VARCHAR(50) NULL,
    mobile VARCHAR(50) NULL,
    est_principal TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
