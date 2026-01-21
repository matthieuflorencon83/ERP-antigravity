-- ============================================
-- ANTIGRAVITY V2 - OPTIMISATION SCHEMA
-- Script d'ajout d'index de performance
-- À exécuter une seule fois après déploiement
-- ============================================

-- Désactiver les vérifications FK temporairement
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- INDEX DE RECHERCHE (Colonnes fréquemment filtrées)
-- ============================================

-- Recherche par référence article
ALTER TABLE articles_catalogue 
    ADD INDEX IF NOT EXISTS idx_ref_fournisseur (ref_fournisseur);

-- Recherche par référence commande
ALTER TABLE commandes_achats 
    ADD INDEX IF NOT EXISTS idx_ref_interne (ref_interne);

-- Recherche par numéro ProDevis
ALTER TABLE affaires 
    ADD INDEX IF NOT EXISTS idx_numero_prodevis (numero_prodevis);

-- Recherche par nom fournisseur
ALTER TABLE fournisseurs 
    ADD INDEX IF NOT EXISTS idx_nom (nom);

-- Recherche par nom client
ALTER TABLE clients 
    ADD INDEX IF NOT EXISTS idx_nom_principal (nom_principal);

-- ============================================
-- INDEX DE FILTRAGE (Colonnes ENUM/Statut)
-- ============================================

-- Filtrage par statut commande
ALTER TABLE commandes_achats 
    ADD INDEX IF NOT EXISTS idx_statut (statut);

-- Filtrage par statut affaire
ALTER TABLE affaires 
    ADD INDEX IF NOT EXISTS idx_statut (statut);

-- Filtrage par statut IA
ALTER TABLE commandes_achats 
    ADD INDEX IF NOT EXISTS idx_statut_ia (statut_ia);

-- Filtrage par statut besoin
ALTER TABLE besoins_chantier 
    ADD INDEX IF NOT EXISTS idx_statut (statut);

-- ============================================
-- INDEX COMPOSITES (Requêtes fréquentes)
-- ============================================

-- Commandes par fournisseur et statut (Dashboard)
ALTER TABLE commandes_achats 
    ADD INDEX IF NOT EXISTS idx_fournisseur_statut (fournisseur_id, statut);

-- Besoins par affaire et statut (Cockpit Affaire)
ALTER TABLE besoins_chantier 
    ADD INDEX IF NOT EXISTS idx_affaire_statut (affaire_id, statut);

-- Lignes par commande (Détail Commande)
ALTER TABLE lignes_achat 
    ADD INDEX IF NOT EXISTS idx_commande_id (commande_id);

-- ============================================
-- COLONNES MANQUANTES (Ajouts de Migration)
-- ============================================

-- Champs Passerelle Vente (Phase 11)
ALTER TABLE affaires 
    ADD COLUMN IF NOT EXISTS montant_ht DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS date_signature DATE DEFAULT NULL;

-- Champ Désignation Libre pour lignes sans article (Phase 12)
ALTER TABLE lignes_achat 
    ADD COLUMN IF NOT EXISTS designation VARCHAR(255) AFTER article_id;

-- Thème interface utilisateur
ALTER TABLE utilisateurs 
    ADD COLUMN IF NOT EXISTS theme_interface VARCHAR(10) DEFAULT 'dark';

-- Réactiver les vérifications FK
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- VÉRIFICATION
-- ============================================
-- SELECT TABLE_NAME, INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
-- WHERE TABLE_SCHEMA = 'antigravity' ORDER BY TABLE_NAME;
