-- ============================================
-- ANTIGRAVITY SYNAPSE - SÉCURISATION SQL
-- ============================================
-- Création d'un utilisateur MySQL restreint pour le serveur MCP
-- DROITS : SELECT uniquement (lecture seule)
-- ============================================

-- 1. Créer l'utilisateur
CREATE USER IF NOT EXISTS 'antigravity_mcp_reader'@'localhost' 
IDENTIFIED BY 'MCP_Reader_2026!Secure';

-- 2. Accorder UNIQUEMENT les droits SELECT sur la base antigravity
GRANT SELECT ON antigravity.* TO 'antigravity_mcp_reader'@'localhost';

-- 3. Appliquer les changements
FLUSH PRIVILEGES;

-- 4. Vérification (à exécuter manuellement)
-- SHOW GRANTS FOR 'antigravity_mcp_reader'@'localhost';
-- Résultat attendu : GRANT SELECT ON `antigravity`.* TO `antigravity_mcp_reader`@`localhost`

-- ============================================
-- TESTS DE SÉCURITÉ (à exécuter après)
-- ============================================

-- Test 1 : SELECT doit fonctionner
-- SELECT COUNT(*) FROM affaires;  -- ✅ Doit réussir

-- Test 2 : INSERT doit échouer
-- INSERT INTO affaires (nom_affaire) VALUES ('test');  -- ❌ Doit échouer (Access Denied)

-- Test 3 : DROP doit échouer
-- DROP TABLE clients;  -- ❌ Doit échouer (Access Denied)
