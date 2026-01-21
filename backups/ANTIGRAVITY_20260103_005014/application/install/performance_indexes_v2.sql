-- ⚡ INDEX DE PERFORMANCE - ADAPTÉS À VOTRE SCHÉMA RÉEL
-- Généré automatiquement le 2025-12-30
-- À exécuter via: http://localhost/antigravity/install/run_indexes_v2.php

-- ============================================
-- TABLE: commandes_achats (pas "commandes")
-- ============================================
-- Déjà existants: idx_cmd_fournisseur, idx_cmd_affaire, idx_cmd_statut, idx_cmd_date

-- Index sur dates de livraison
ALTER TABLE commandes_achats ADD INDEX idx_date_livraison_reelle (date_livraison_reelle);
ALTER TABLE commandes_achats ADD INDEX idx_date_livraison_prevue (date_livraison_prevue);
ALTER TABLE commandes_achats ADD INDEX idx_date_arc_recu (date_arc_recu);
ALTER TABLE commandes_achats ADD INDEX idx_date_en_attente (date_en_attente);

-- Index sur statut IA
ALTER TABLE commandes_achats ADD INDEX idx_statut_ia (statut_ia);


-- ============================================
-- TABLE: affaires
-- ============================================
-- Déjà existants: plusieurs index dont idx_client_id, idx_statut, idx_date_creation

-- Index sur dates de pose
ALTER TABLE affaires ADD INDEX idx_date_pose_debut (date_pose_debut);
ALTER TABLE affaires ADD INDEX idx_date_pose_fin (date_pose_fin);
ALTER TABLE affaires ADD INDEX idx_date_signature (date_signature);

-- Index sur statut chantier
ALTER TABLE affaires ADD INDEX idx_statut_chantier (statut_chantier);


-- ============================================
-- TABLE: email_sent (pas "emails")
-- ============================================
-- Déjà existants: idx_client, idx_affaire, idx_sent_at

-- Pas besoin d'index supplémentaires, déjà bien optimisé


-- ============================================
-- TABLE: clients
-- ============================================
-- Structure réelle: nom_principal, email_principal (pas "nom" et "email")

-- Index sur nom pour recherche
ALTER TABLE clients ADD INDEX idx_nom_principal (nom_principal);

-- Index sur email pour recherche
ALTER TABLE clients ADD INDEX idx_email_principal (email_principal);


-- ============================================
-- TABLE: planning_events (si existe)
-- ============================================
-- Note: Cette table n'apparaît pas dans votre schéma
-- Les index seront ignorés si la table n'existe pas


-- ============================================
-- TABLE: tasks
-- ============================================
-- Déjà existants: idx_user_status

-- Index sur date d'échéance
ALTER TABLE tasks ADD INDEX idx_due_date (due_date);


-- ============================================
-- TABLE: sav_tickets
-- ============================================
-- Déjà existants: client_id, affaire_id, created_by

-- Index sur statut et urgence
ALTER TABLE sav_tickets ADD INDEX idx_statut_urgence (statut, urgence);


-- ============================================
-- TABLE: metrage_interventions
-- ============================================

-- Index sur dates
ALTER TABLE metrage_interventions ADD INDEX idx_date_prevue (date_prevue);
ALTER TABLE metrage_interventions ADD INDEX idx_date_realisee (date_realisee);

-- Index sur statut
ALTER TABLE metrage_interventions ADD INDEX idx_statut_metrage (statut);


-- ============================================
-- TABLE: stocks_mouvements
-- ============================================
-- Déjà existants: idx_article_date, idx_type, idx_mvt_article, idx_mvt_date

-- Index sur user_id
ALTER TABLE stocks_mouvements ADD INDEX idx_user_id (user_id);

-- Index sur affaire_id
ALTER TABLE stocks_mouvements ADD INDEX idx_affaire_id (affaire_id);


-- ============================================
-- TABLE: devis
-- ============================================

-- Index sur dates
ALTER TABLE devis ADD INDEX idx_date_validite (date_validite);

-- Index composite statut + date
ALTER TABLE devis ADD INDEX idx_statut_date (statut, date_creation);


-- ============================================
-- VÉRIFICATION
-- ============================================
-- Pour vérifier: SHOW INDEX FROM commandes_achats;
