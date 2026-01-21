-- ⚡ OPTIMISATION SQL - INDEX POUR PERFORMANCES
-- À exécuter dans phpMyAdmin ou MySQL Workbench
-- Date: 2025-12-30

-- ============================================
-- INDEX SUR TABLE COMMANDES
-- ============================================

-- Index sur statut (filtré très fréquemment)
ALTER TABLE commandes ADD INDEX IF NOT EXISTS idx_statut (statut);

-- Index sur date_commande (tri et filtres)
ALTER TABLE commandes ADD INDEX IF NOT EXISTS idx_date_commande (date_commande);

-- Index sur date_livraison_prevue (dashboard livraisons)
ALTER TABLE commandes ADD INDEX IF NOT EXISTS idx_date_livraison (date_livraison_prevue);

-- Index sur affaire_id (jointures fréquentes)
ALTER TABLE commandes ADD INDEX IF NOT EXISTS idx_affaire_id (affaire_id);

-- Index sur fournisseur_id (jointures fréquentes)
ALTER TABLE commandes ADD INDEX IF NOT EXISTS idx_fournisseur_id (fournisseur_id);

-- Index composite pour dashboard (statut + date)
ALTER TABLE commandes ADD INDEX IF NOT EXISTS idx_statut_date (statut, date_commande);


-- ============================================
-- INDEX SUR TABLE AFFAIRES
-- ============================================

-- Index sur client_id (jointures très fréquentes)
ALTER TABLE affaires ADD INDEX IF NOT EXISTS idx_client_id (client_id);

-- Index sur statut (filtres)
ALTER TABLE affaires ADD INDEX IF NOT EXISTS idx_statut (statut);

-- Index sur date_creation (tri)
ALTER TABLE affaires ADD INDEX IF NOT EXISTS idx_date_creation (date_creation);

-- Index composite pour listes filtrées
ALTER TABLE affaires ADD INDEX IF NOT EXISTS idx_client_statut (client_id, statut);


-- ============================================
-- INDEX SUR TABLE EMAILS (si existe)
-- ============================================

-- Index sur client_id
ALTER TABLE emails ADD INDEX IF NOT EXISTS idx_client_id (client_id);

-- Index sur affaire_id
ALTER TABLE emails ADD INDEX IF NOT EXISTS idx_affaire_id (affaire_id);

-- Index sur date_envoi (tri)
ALTER TABLE emails ADD INDEX IF NOT EXISTS idx_date_envoi (date_envoi);

-- Index sur seen (filtres lu/non lu)
ALTER TABLE emails ADD INDEX IF NOT EXISTS idx_seen (seen);


-- ============================================
-- INDEX SUR TABLE CLIENTS
-- ============================================

-- Index sur nom (recherche)
ALTER TABLE clients ADD INDEX IF NOT EXISTS idx_nom (nom);

-- Index sur email (recherche)
ALTER TABLE clients ADD INDEX IF NOT EXISTS idx_email (email);


-- ============================================
-- INDEX SUR TABLE FOURNISSEURS
-- ============================================

-- Index sur code_fou (recherche)
ALTER TABLE fournisseurs ADD INDEX IF NOT EXISTS idx_code_fou (code_fou);

-- Index sur nom (recherche)
ALTER TABLE fournisseurs ADD INDEX IF NOT EXISTS idx_nom (nom);


-- ============================================
-- INDEX SUR TABLE PLANNING_EVENTS
-- ============================================

-- Index sur start_date (requêtes de plage)
ALTER TABLE planning_events ADD INDEX IF NOT EXISTS idx_start_date (start_date);

-- Index sur end_date (requêtes de plage)
ALTER TABLE planning_events ADD INDEX IF NOT EXISTS idx_end_date (end_date);

-- Index sur affaire_id
ALTER TABLE planning_events ADD INDEX IF NOT EXISTS idx_affaire_id (affaire_id);

-- Index composite pour requêtes de plage
ALTER TABLE planning_events ADD INDEX IF NOT EXISTS idx_date_range (start_date, end_date);


-- ============================================
-- VÉRIFICATION DES INDEX
-- ============================================

-- Pour vérifier les index créés :
-- SHOW INDEX FROM commandes;
-- SHOW INDEX FROM affaires;
-- SHOW INDEX FROM emails;

-- ============================================
-- NOTES
-- ============================================

-- ✅ Ces index amélioreront significativement les performances
-- ⚠️ Les INSERT/UPDATE seront légèrement plus lents (négligeable)
-- 📊 Gain estimé : 50-80% sur les requêtes SELECT avec WHERE/JOIN
-- 🔍 Utiliser EXPLAIN pour analyser les requêtes avant/après
