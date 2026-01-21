-- ============================================================================
-- ANTIGRAVITY V2 - PRODUCTION-GRADE TEST DATASET (SEEDING)
-- ============================================================================
-- Generated: 2026-01-02
-- Purpose: Stress-test Wizard V4, Canvas, and Margin Calculations
-- Scenarios: 3 realistic customer cases (Standard, Luxury, Edge Case)
-- ============================================================================

-- Optional: Cleanup existing test data (UNCOMMENT TO USE)
-- TRUNCATE TABLE metrage_lignes;
-- TRUNCATE TABLE metrage_interventions;
-- DELETE FROM affaires WHERE id > 100; -- Keep production data
-- DELETE FROM clients WHERE id > 100;

-- ============================================================================
-- SCENARIO A: "LE STANDARD" - M. DUPONT
-- ============================================================================
-- Client: Particulier, Rénovation appartement, Budget: ~8,000€
-- Products: 5 Fenêtres PVC Blanc, 2 vantaux, Pose rénovation

-- Client
INSERT INTO clients (
    nom_principal, prenom, type_client, adresse_principale, 
    code_postal, ville, telephone_principal, email, 
    source_lead, date_creation
) VALUES (
    'DUPONT', 'Jean-Pierre', 'particulier', 
    '45 Avenue de la République', '75011', 'Paris',
    '0612345678', 'jp.dupont@gmail.com',
    'Site web', NOW()
);

SET @client_dupont = LAST_INSERT_ID();

-- Affaire
INSERT INTO affaires (
    client_id, numero_prodevis, nom_affaire, type_projet,
    adresse_chantier, code_postal_chantier, ville_chantier,
    date_creation, statut, montant_estime
) VALUES (
    @client_dupont, 'PRO-2026-001', 'Rénovation Appartement Paris 11',
    'renovation', '45 Avenue de la République', '75011', 'Paris',
    '2026-01-02', 'en_cours', 8500.00
);

SET @affaire_dupont = LAST_INSERT_ID();

-- Metrage Intervention
INSERT INTO metrage_interventions (
    affaire_id, date_intervention, statut, technicien,
    adresse_intervention, notes
) VALUES (
    @affaire_dupont, '2026-01-05', 'termine', 'Marc LEFEBVRE',
    '45 Avenue de la République, 75011 Paris',
    'Client présent, mesures prises dans salon, chambre, cuisine'
);

SET @intervention_dupont = LAST_INSERT_ID();

-- Metrage Lignes (5 fenêtres)
INSERT INTO metrage_lignes (intervention_id, type_id, designation, data_json, ordre) VALUES
(@intervention_dupont, 1, 'Fenêtre Salon - Gauche', '{
  "type": "fenetre",
  "materiau": "pvc",
  "ouverture": "oscillo_battant",
  "nb_vantaux": 2,
  "cotes": {
    "largeur": 1200,
    "hauteur": 1450,
    "tableau_largeur": 1220,
    "tableau_hauteur": 1470
  },
  "vitrage": {
    "type": "standard",
    "composition": "4/16/4",
    "poids_m2": 20
  },
  "finition": {
    "interieur": "blanc",
    "exterieur": "blanc"
  },
  "pose": {
    "type": "renovation",
    "support": "beton"
  },
  "options": []
}', 1),

(@intervention_dupont, 1, 'Fenêtre Salon - Droite', '{
  "type": "fenetre",
  "materiau": "pvc",
  "ouverture": "oscillo_battant",
  "nb_vantaux": 2,
  "cotes": {
    "largeur": 1200,
    "hauteur": 1450,
    "tableau_largeur": 1220,
    "tableau_hauteur": 1470
  },
  "vitrage": {
    "type": "standard",
    "composition": "4/16/4",
    "poids_m2": 20
  },
  "finition": {
    "interieur": "blanc",
    "exterieur": "blanc"
  },
  "pose": {
    "type": "renovation",
    "support": "beton"
  },
  "options": []
}', 2),

(@intervention_dupont, 1, 'Fenêtre Chambre', '{
  "type": "fenetre",
  "materiau": "pvc",
  "ouverture": "oscillo_battant",
  "nb_vantaux": 2,
  "cotes": {
    "largeur": 1000,
    "hauteur": 1350,
    "tableau_largeur": 1020,
    "tableau_hauteur": 1370
  },
  "vitrage": {
    "type": "standard",
    "composition": "4/16/4",
    "poids_m2": 20
  },
  "finition": {
    "interieur": "blanc",
    "exterieur": "blanc"
  },
  "pose": {
    "type": "renovation",
    "support": "beton"
  },
  "options": []
}', 3),

(@intervention_dupont, 1, 'Fenêtre Cuisine', '{
  "type": "fenetre",
  "materiau": "pvc",
  "ouverture": "soufflet",
  "nb_vantaux": 1,
  "cotes": {
    "largeur": 800,
    "hauteur": 600,
    "tableau_largeur": 820,
    "tableau_hauteur": 620
  },
  "vitrage": {
    "type": "standard",
    "composition": "4/16/4",
    "poids_m2": 20
  },
  "finition": {
    "interieur": "blanc",
    "exterieur": "blanc"
  },
  "pose": {
    "type": "renovation",
    "support": "beton"
  },
  "options": []
}', 4),

(@intervention_dupont, 1, 'Fenêtre Bureau', '{
  "type": "fenetre",
  "materiau": "pvc",
  "ouverture": "oscillo_battant",
  "nb_vantaux": 2,
  "cotes": {
    "largeur": 1400,
    "hauteur": 1450,
    "tableau_largeur": 1420,
    "tableau_hauteur": 1470
  },
  "vitrage": {
    "type": "standard",
    "composition": "4/16/4",
    "poids_m2": 20
  },
  "finition": {
    "interieur": "blanc",
    "exterieur": "blanc"
  },
  "pose": {
    "type": "renovation",
    "support": "beton"
  },
  "options": []
}', 5);

-- ============================================================================
-- SCENARIO B: "LE LUXE TECHNIQUE" - DESIGN & CO
-- ============================================================================
-- Client: Cabinet d'architectes, Villa contemporaine neuf, Budget: ~45,000€
-- Products: 2 Baies coulissantes Alu 3 vantaux, RAL 7016, Volets motorisés

-- Client
INSERT INTO clients (
    nom_principal, type_client, adresse_principale,
    code_postal, ville, telephone_principal, email,
    source_lead, date_creation
) VALUES (
    'DESIGN & CO ARCHITECTES', 'professionnel',
    '12 Rue du Design', '69002', 'Lyon',
    '0478123456', 'contact@designco-archi.fr',
    'Prescription architecte', NOW()
);

SET @client_design = LAST_INSERT_ID();

-- Affaire
INSERT INTO affaires (
    client_id, numero_prodevis, nom_affaire, type_projet,
    adresse_chantier, code_postal_chantier, ville_chantier,
    date_creation, statut, montant_estime
) VALUES (
    @client_design, 'PRO-2026-002', 'Villa Contemporaine Lyon',
    'neuf', 'Chemin des Collines, 69450 Saint-Cyr-au-Mont-d\'Or',
    '69450', 'Saint-Cyr-au-Mont-d\'Or',
    '2026-01-02', 'en_cours', 48000.00
);

SET @affaire_design = LAST_INSERT_ID();

-- Metrage Intervention
INSERT INTO metrage_interventions (
    affaire_id, date_intervention, statut, technicien,
    adresse_intervention, notes
) VALUES (
    @affaire_design, '2026-01-08', 'termine', 'Sophie MARTIN',
    'Chemin des Collines, 69450 Saint-Cyr-au-Mont-d\'Or',
    'Chantier neuf, plans architecte fournis, mesures vérifiées sur place'
);

SET @intervention_design = LAST_INSERT_ID();

-- Metrage Lignes (2 baies coulissantes)
INSERT INTO metrage_lignes (intervention_id, type_id, designation, data_json, ordre) VALUES
(@intervention_design, 2, 'Baie Coulissante Séjour - Vue Jardin', '{
  "type": "baie_coulissante",
  "materiau": "aluminium",
  "ouverture": "coulissant",
  "nb_vantaux": 3,
  "cotes": {
    "largeur": 3500,
    "hauteur": 2400,
    "tableau_largeur": 3520,
    "tableau_hauteur": 2420
  },
  "vitrage": {
    "type": "stadip_silence",
    "composition": "44.2/16/44.2",
    "poids_m2": 45,
    "performance": {
      "acoustique": "Rw=42dB",
      "thermique": "Ug=1.0"
    }
  },
  "finition": {
    "interieur": "RAL 7016",
    "exterieur": "RAL 7016",
    "code_ral": "7016"
  },
  "pose": {
    "type": "tunnel",
    "support": "beton_arme",
    "seuil": "encastre_pmr"
  },
  "options": [
    "volet_roulant_motorise_somfy",
    "seuil_pmr",
    "vitrage_phonique",
    "vitrage_securite"
  ],
  "volet": {
    "type": "roulant",
    "motorisation": "somfy_io",
    "coffre": "tunnel",
    "lames": "aluminium"
  }
}', 1),

(@intervention_design, 2, 'Baie Coulissante Cuisine - Vue Terrasse', '{
  "type": "baie_coulissante",
  "materiau": "aluminium",
  "ouverture": "coulissant",
  "nb_vantaux": 3,
  "cotes": {
    "largeur": 3000,
    "hauteur": 2400,
    "tableau_largeur": 3020,
    "tableau_hauteur": 2420
  },
  "vitrage": {
    "type": "stadip_silence",
    "composition": "44.2/16/44.2",
    "poids_m2": 45,
    "performance": {
      "acoustique": "Rw=42dB",
      "thermique": "Ug=1.0"
    }
  },
  "finition": {
    "interieur": "RAL 7016",
    "exterieur": "RAL 7016",
    "code_ral": "7016"
  },
  "pose": {
    "type": "tunnel",
    "support": "beton_arme",
    "seuil": "encastre_pmr"
  },
  "options": [
    "volet_roulant_motorise_somfy",
    "seuil_pmr",
    "vitrage_phonique",
    "vitrage_securite"
  ],
  "volet": {
    "type": "roulant",
    "motorisation": "somfy_io",
    "coffre": "tunnel",
    "lames": "aluminium"
  }
}', 2);

-- ============================================================================
-- SCENARIO C: "LE CAS LIMITE" - MME MICHU
-- ============================================================================
-- Client: Particulier, Maison ancienne, Budget: ~3,500€
-- Products: Fenêtre trapèze + Fenêtre cintrée (formes spéciales)

-- Client
INSERT INTO clients (
    nom_principal, prenom, type_client, adresse_principale,
    code_postal, ville, telephone_principal, email,
    source_lead, date_creation
) VALUES (
    'MICHU', 'Germaine', 'particulier',
    '8 Rue de l\'Église', '31000', 'Toulouse',
    '0561234567', 'g.michu@orange.fr',
    'Bouche à oreille', NOW()
);

SET @client_michu = LAST_INSERT_ID();

-- Affaire
INSERT INTO affaires (
    client_id, numero_prodevis, nom_affaire, type_projet,
    adresse_chantier, code_postal_chantier, ville_chantier,
    date_creation, statut, montant_estime
) VALUES (
    @client_michu, 'PRO-2026-003', 'Rénovation Maison Ancienne Toulouse',
    'renovation', '8 Rue de l\'Église', '31000', 'Toulouse',
    '2026-01-02', 'en_cours', 3800.00
);

SET @affaire_michu = LAST_INSERT_ID();

-- Metrage Intervention
INSERT INTO metrage_interventions (
    affaire_id, date_intervention, statut, technicien,
    adresse_intervention, notes
) VALUES (
    @affaire_michu, '2026-01-10', 'termine', 'Pierre DUBOIS',
    '8 Rue de l\'Église, 31000 Toulouse',
    'Maison ancienne, formes spéciales, murs en pierre, mesures complexes'
);

SET @intervention_michu = LAST_INSERT_ID();

-- Metrage Lignes (2 fenêtres formes spéciales)
INSERT INTO metrage_lignes (intervention_id, type_id, designation, data_json, ordre) VALUES
(@intervention_michu, 1, 'Fenêtre Grenier - Trapèze', '{
  "type": "fenetre",
  "forme": "trapeze",
  "materiau": "pvc",
  "ouverture": "fixe",
  "nb_vantaux": 1,
  "cotes": {
    "base_largeur": 1000,
    "sommet_largeur": 600,
    "hauteur": 800,
    "angle_gauche": 75,
    "angle_droit": 75
  },
  "geometrie": {
    "type": "trapeze_isocele",
    "points": [
      {"x": 0, "y": 0},
      {"x": 1000, "y": 0},
      {"x": 900, "y": 800},
      {"x": 100, "y": 800}
    ]
  },
  "vitrage": {
    "type": "standard",
    "composition": "4/16/4",
    "poids_m2": 20
  },
  "finition": {
    "interieur": "blanc",
    "exterieur": "blanc"
  },
  "pose": {
    "type": "renovation",
    "support": "pierre"
  },
  "options": ["forme_speciale"]
}', 1),

(@intervention_michu, 1, 'Fenêtre Escalier - Cintrée', '{
  "type": "fenetre",
  "forme": "cintree",
  "materiau": "pvc",
  "ouverture": "fixe",
  "nb_vantaux": 1,
  "cotes": {
    "largeur": 1200,
    "hauteur_droite": 1400,
    "fleche": 200
  },
  "geometrie": {
    "type": "arc_plein_cintre",
    "rayon": 600,
    "centre": {"x": 600, "y": 1400}
  },
  "vitrage": {
    "type": "standard",
    "composition": "4/16/4",
    "poids_m2": 20
  },
  "finition": {
    "interieur": "blanc",
    "exterieur": "blanc"
  },
  "pose": {
    "type": "renovation",
    "support": "pierre"
  },
  "options": ["forme_speciale", "cintrage"]
}', 2);

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================
-- Uncomment to verify data after insertion:

-- SELECT * FROM clients WHERE id >= @client_dupont;
-- SELECT * FROM affaires WHERE id >= @affaire_dupont;
-- SELECT * FROM metrage_interventions WHERE id >= @intervention_dupont;
-- SELECT * FROM metrage_lignes WHERE intervention_id >= @intervention_dupont;

-- ============================================================================
-- END OF SEEDING SCRIPT
-- ============================================================================
