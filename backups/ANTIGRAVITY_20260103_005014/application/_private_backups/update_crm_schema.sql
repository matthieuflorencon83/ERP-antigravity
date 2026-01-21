-- Ajout des colonnes manquantes à la table fournisseurs
ALTER TABLE fournisseurs
ADD COLUMN code_fou VARCHAR(50) NULL AFTER nom,
ADD COLUMN adresse_postale TEXT NULL AFTER email_commande,
ADD COLUMN code_postal VARCHAR(20) NULL AFTER adresse_postale,
ADD COLUMN ville VARCHAR(100) NULL AFTER code_postal,
ADD COLUMN pays VARCHAR(50) DEFAULT 'France' AFTER ville,
ADD COLUMN siret VARCHAR(50) NULL AFTER pays,
ADD COLUMN tva_intra VARCHAR(50) NULL AFTER siret,
ADD COLUMN site_web VARCHAR(255) NULL AFTER tva_intra,
ADD COLUMN notes TEXT NULL AFTER site_web,
ADD COLUMN condition_paiement VARCHAR(100) DEFAULT '30 jours fin de mois' AFTER notes;

-- Renommage éventuel pour cohérence (optionnel, on garde l'existant pour pas casser)
-- ALTER TABLE fournisseurs CHANGE email_commande email_general VARCHAR(100);

-- Création table des adresses multiples (Siège, Enlèvement, Livraison...)
CREATE TABLE IF NOT EXISTS fournisseur_adresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fournisseur_id INT NOT NULL,
    type_adresse VARCHAR(50) DEFAULT 'Livraison', -- Siège, Livraison, Enlèvement, Facturation
    adresse VARCHAR(255) NOT NULL,
    code_postal VARCHAR(20),
    ville VARCHAR(100),
    pays VARCHAR(50) DEFAULT 'France',
    contact_sur_place VARCHAR(100),
    telephone VARCHAR(50),
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mettre à jour les enregistrements existants (Init Siège vide)
UPDATE fournisseurs SET pays = 'France' WHERE pays IS NULL;
