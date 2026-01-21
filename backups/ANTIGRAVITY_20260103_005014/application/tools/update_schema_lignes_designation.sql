-- update_schema_lignes_designation.sql
-- Ajout de la colonne designation pour les lignes d'achat livres (sans article_id)

ALTER TABLE lignes_achat ADD COLUMN designation VARCHAR(255) AFTER article_id;
