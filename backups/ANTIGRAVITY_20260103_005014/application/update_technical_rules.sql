-- Metrage V4 - Technical Rules Injection
-- Context: Adding Renovation/Neuf specific questions

-- 1. FENETRE - RENOVATION Questions
INSERT INTO metrage_etapes (categorie, ordre, code_etape, nom_etape, message_assistant, type_saisie, options_json, condition_json, est_obligatoire)
VALUES 
(
    'FENETRE', 
    10, 
    'etat_dormant', 
    'État du dormant existant', 
    'Quel est l\'<strong>état du dormant</strong> conservé ?', 
    'liste', 
    '["Bon état", "Moyen (à traiter)", "Mauvais (Dépose totale recommandée)"]', 
    '{"field": "technique.pose", "value": "RENOVATION", "operator": "eq"}',
    1
),
(
    'FENETRE', 
    11, 
    'type_dormant_existant', 
    'Matériau dormant existant', 
    'Quel est le <strong>matériau</strong> du cadre existant ?', 
    'liste', 
    '["Bois", "PVC", "Alu", "Fer"]', 
    '{"field": "technique.pose", "value": "RENOVATION", "operator": "eq"}',
    1
),
(
    'FENETRE', 
    12, 
    'cote_passage', 
    'Cote de passage (Clair de jour)', 
    'Mesurez la <strong>largeur de passage</strong> actuelle (fond de feuillure).', 
    'mm', 
    NULL, 
    '{"field": "technique.pose", "value": "RENOVATION", "operator": "eq"}',
    1
);

-- 2. FENETRE - TUNNEL Questions
INSERT INTO metrage_etapes (categorie, ordre, code_etape, nom_etape, message_assistant, type_saisie, options_json, condition_json, est_obligatoire)
VALUES 
(
    'FENETRE', 
    10, 
    'type_mur', 
    'Type de mur', 
    'Quel est le <strong>matériau du mur</strong> ?', 
    'liste', 
    '["Parpaing", "Brique", "Pierre", "Ossature Bois"]', 
    '{"field": "technique.pose", "value": "TUNNEL", "operator": "eq"}',
    1
),
(
    'FENETRE', 
    11, 
    'isolation_ext', 
    'Isolation Extérieure (ITE)', 
    'Y a-t-il une <strong>isolation extérieure</strong> prévue ?', 
    'binaire', 
    NULL, 
    '{"field": "technique.pose", "value": "TUNNEL", "operator": "eq"}',
    1
);

-- 3. VOLET - TRADITIONNEL Questions
INSERT INTO metrage_etapes (categorie, ordre, code_etape, nom_etape, message_assistant, type_saisie, options_json, condition_json, est_obligatoire)
VALUES 
(
    'VOLET', 
    10, 
    'type_coffre', 
    'Type de coffre', 
    'S\'agit-il d\'un coffre <strong>Titan</strong> ou <strong>Menuisé</strong> ?', 
    'liste', 
    '["Titan (Intégré maçonnerie)", "Menuisé (Bois intérieur)"]', 
    '{"field": "technique.pose", "value": "TRADITIONNEL", "operator": "eq"}',
    1
);
