<?php
// tools/seed_production_data_php.php
require_once __DIR__ . '/../db.php';

echo "<h1>🧪 SEEDING PRODUCTION - VERSION PHP</h1>";

try {
    $pdo->beginTransaction();
    
    // ========================================================================
    // SCENARIO A: M. DUPONT
    // ========================================================================
    echo "<h3>Scénario A: M. Dupont</h3>";
    
    $stmt = $pdo->prepare("INSERT INTO clients (nom_principal, prenom, type_client, adresse_principale, code_postal, ville, telephone_principal, email, source_lead, date_creation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute(['DUPONT', 'Jean-Pierre', 'particulier', '45 Avenue de la République', '75011', 'Paris', '0612345678', 'jp.dupont@gmail.com', 'Site web']);
    $client_dupont = $pdo->lastInsertId();
    echo "<p>✓ Client créé (ID: $client_dupont)</p>";
    
    $stmt = $pdo->prepare("INSERT INTO affaires (client_id, numero_prodevis, nom_affaire, type_projet, adresse_chantier, code_postal_chantier, ville_chantier, date_creation, statut, montant_estime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$client_dupont, 'PRO-2026-001', 'Rénovation Appartement Paris 11', 'renovation', '45 Avenue de la République', '75011', 'Paris', '2026-01-02', 'en_cours', 8500.00]);
    $affaire_dupont = $pdo->lastInsertId();
    echo "<p>✓ Affaire créée (ID: $affaire_dupont)</p>";
    
    $stmt = $pdo->prepare("INSERT INTO metrage_interventions (affaire_id, date_intervention, statut, technicien, adresse_intervention, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$affaire_dupont, '2026-01-05', 'termine', 'Marc LEFEBVRE', '45 Avenue de la République, 75011 Paris', 'Client présent, mesures prises dans salon, chambre, cuisine']);
    $intervention_dupont = $pdo->lastInsertId();
    echo "<p>✓ Intervention créée (ID: $intervention_dupont)</p>";
    
    // 5 fenêtres
    $fenetre_json = json_encode([
        'type' => 'fenetre',
        'materiau' => 'pvc',
        'ouverture' => 'oscillo_battant',
        'nb_vantaux' => 2,
        'cotes' => [
            'largeur' => 1200,
            'hauteur' => 1450,
            'tableau_largeur' => 1220,
            'tableau_hauteur' => 1470
        ],
        'vitrage' => [
            'type' => 'standard',
            'composition' => '4/16/4',
            'poids_m2' => 20
        ],
        'finition' => [
            'interieur' => 'blanc',
            'exterieur' => 'blanc'
        ],
        'pose' => [
            'type' => 'renovation',
            'support' => 'beton'
        ],
        'options' => []
    ]);
    
    $stmt = $pdo->prepare("INSERT INTO metrage_lignes (intervention_id, type_id, designation, data_json, ordre) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$intervention_dupont, 1, 'Fenêtre Salon - Gauche', $fenetre_json, 1]);
    $stmt->execute([$intervention_dupont, 1, 'Fenêtre Salon - Droite', $fenetre_json, 2]);
    $stmt->execute([$intervention_dupont, 1, 'Fenêtre Chambre', $fenetre_json, 3]);
    $stmt->execute([$intervention_dupont, 1, 'Fenêtre Cuisine', $fenetre_json, 4]);
    $stmt->execute([$intervention_dupont, 1, 'Fenêtre Bureau', $fenetre_json, 5]);
    echo "<p>✓ 5 lignes métrage créées</p>";
    
    // ========================================================================
    // SCENARIO B: DESIGN & CO
    // ========================================================================
    echo "<h3>Scénario B: Design & Co</h3>";
    
    $stmt = $pdo->prepare("INSERT INTO clients (nom_principal, type_client, adresse_principale, code_postal, ville, telephone_principal, email, source_lead, date_creation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute(['DESIGN & CO ARCHITECTES', 'professionnel', '12 Rue du Design', '69002', 'Lyon', '0478123456', 'contact@designco-archi.fr', 'Prescription architecte']);
    $client_design = $pdo->lastInsertId();
    echo "<p>✓ Client créé (ID: $client_design)</p>";
    
    $stmt = $pdo->prepare("INSERT INTO affaires (client_id, numero_prodevis, nom_affaire, type_projet, adresse_chantier, code_postal_chantier, ville_chantier, date_creation, statut, montant_estime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$client_design, 'PRO-2026-002', 'Villa Contemporaine Lyon', 'neuf', 'Chemin des Collines, 69450 Saint-Cyr-au-Mont-d\'Or', '69450', 'Saint-Cyr-au-Mont-d\'Or', '2026-01-02', 'en_cours', 48000.00]);
    $affaire_design = $pdo->lastInsertId();
    echo "<p>✓ Affaire créée (ID: $affaire_design)</p>";
    
    $stmt = $pdo->prepare("INSERT INTO metrage_interventions (affaire_id, date_intervention, statut, technicien, adresse_intervention, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$affaire_design, '2026-01-08', 'termine', 'Sophie MARTIN', 'Chemin des Collines, 69450 Saint-Cyr-au-Mont-d\'Or', 'Chantier neuf, plans architecte fournis']);
    $intervention_design = $pdo->lastInsertId();
    echo "<p>✓ Intervention créée (ID: $intervention_design)</p>";
    
    // 2 baies coulissantes
    $baie_json = json_encode([
        'type' => 'baie_coulissante',
        'materiau' => 'aluminium',
        'ouverture' => 'coulissant',
        'nb_vantaux' => 3,
        'cotes' => [
            'largeur' => 3500,
            'hauteur' => 2400,
            'tableau_largeur' => 3520,
            'tableau_hauteur' => 2420
        ],
        'vitrage' => [
            'type' => 'stadip_silence',
            'composition' => '44.2/16/44.2',
            'poids_m2' => 45,
            'performance' => [
                'acoustique' => 'Rw=42dB',
                'thermique' => 'Ug=1.0'
            ]
        ],
        'finition' => [
            'interieur' => 'RAL 7016',
            'exterieur' => 'RAL 7016',
            'code_ral' => '7016'
        ],
        'pose' => [
            'type' => 'tunnel',
            'support' => 'beton_arme',
            'seuil' => 'encastre_pmr'
        ],
        'options' => [
            'volet_roulant_motorise_somfy',
            'seuil_pmr',
            'vitrage_phonique',
            'vitrage_securite'
        ],
        'volet' => [
            'type' => 'roulant',
            'motorisation' => 'somfy_io',
            'coffre' => 'tunnel',
            'lames' => 'aluminium'
        ]
    ]);
    
    $stmt = $pdo->prepare("INSERT INTO metrage_lignes (intervention_id, type_id, designation, data_json, ordre) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$intervention_design, 2, 'Baie Coulissante Séjour - Vue Jardin', $baie_json, 1]);
    $stmt->execute([$intervention_design, 2, 'Baie Coulissante Cuisine - Vue Terrasse', $baie_json, 2]);
    echo "<p>✓ 2 lignes métrage créées</p>";
    
    // ========================================================================
    // SCENARIO C: MME MICHU
    // ========================================================================
    echo "<h3>Scénario C: Mme Michu</h3>";
    
    $stmt = $pdo->prepare("INSERT INTO clients (nom_principal, prenom, type_client, adresse_principale, code_postal, ville, telephone_principal, email, source_lead, date_creation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute(['MICHU', 'Germaine', 'particulier', '8 Rue de l\'Église', '31000', 'Toulouse', '0561234567', 'g.michu@orange.fr', 'Bouche à oreille']);
    $client_michu = $pdo->lastInsertId();
    echo "<p>✓ Client créé (ID: $client_michu)</p>";
    
    $stmt = $pdo->prepare("INSERT INTO affaires (client_id, numero_prodevis, nom_affaire, type_projet, adresse_chantier, code_postal_chantier, ville_chantier, date_creation, statut, montant_estime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$client_michu, 'PRO-2026-003', 'Rénovation Maison Ancienne Toulouse', 'renovation', '8 Rue de l\'Église', '31000', 'Toulouse', '2026-01-02', 'en_cours', 3800.00]);
    $affaire_michu = $pdo->lastInsertId();
    echo "<p>✓ Affaire créée (ID: $affaire_michu)</p>";
    
    $stmt = $pdo->prepare("INSERT INTO metrage_interventions (affaire_id, date_intervention, statut, technicien, adresse_intervention, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$affaire_michu, '2026-01-10', 'termine', 'Pierre DUBOIS', '8 Rue de l\'Église, 31000 Toulouse', 'Maison ancienne, formes spéciales']);
    $intervention_michu = $pdo->lastInsertId();
    echo "<p>✓ Intervention créée (ID: $intervention_michu)</p>";
    
    // Fenêtre trapèze
    $trapeze_json = json_encode([
        'type' => 'fenetre',
        'forme' => 'trapeze',
        'materiau' => 'pvc',
        'ouverture' => 'fixe',
        'nb_vantaux' => 1,
        'cotes' => [
            'base_largeur' => 1000,
            'sommet_largeur' => 600,
            'hauteur' => 800,
            'angle_gauche' => 75,
            'angle_droit' => 75
        ],
        'geometrie' => [
            'type' => 'trapeze_isocele',
            'points' => [
                ['x' => 0, 'y' => 0],
                ['x' => 1000, 'y' => 0],
                ['x' => 900, 'y' => 800],
                ['x' => 100, 'y' => 800]
            ]
        ],
        'vitrage' => [
            'type' => 'standard',
            'composition' => '4/16/4',
            'poids_m2' => 20
        ],
        'finition' => [
            'interieur' => 'blanc',
            'exterieur' => 'blanc'
        ],
        'pose' => [
            'type' => 'renovation',
            'support' => 'pierre'
        ],
        'options' => ['forme_speciale']
    ]);
    
    // Fenêtre cintrée
    $cintree_json = json_encode([
        'type' => 'fenetre',
        'forme' => 'cintree',
        'materiau' => 'pvc',
        'ouverture' => 'fixe',
        'nb_vantaux' => 1,
        'cotes' => [
            'largeur' => 1200,
            'hauteur_droite' => 1400,
            'fleche' => 200
        ],
        'geometrie' => [
            'type' => 'arc_plein_cintre',
            'rayon' => 600,
            'centre' => ['x' => 600, 'y' => 1400]
        ],
        'vitrage' => [
            'type' => 'standard',
            'composition' => '4/16/4',
            'poids_m2' => 20
        ],
        'finition' => [
            'interieur' => 'blanc',
            'exterieur' => 'blanc'
        ],
        'pose' => [
            'type' => 'renovation',
            'support' => 'pierre'
        ],
        'options' => ['forme_speciale', 'cintrage']
    ]);
    
    $stmt = $pdo->prepare("INSERT INTO metrage_lignes (intervention_id, type_id, designation, data_json, ordre) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$intervention_michu, 1, 'Fenêtre Grenier - Trapèze', $trapeze_json, 1]);
    $stmt->execute([$intervention_michu, 1, 'Fenêtre Escalier - Cintrée', $cintree_json, 2]);
    echo "<p>✓ 2 lignes métrage créées</p>";
    
    $pdo->commit();
    
    echo "<div class='alert alert-success mt-4'>";
    echo "<h2>✅ SEEDING TERMINÉ</h2>";
    echo "<p>3 clients, 3 affaires, 3 interventions, 9 lignes métrage</p>";
    echo "</div>";
    
    // Verification
    echo "<h2>📊 VÉRIFICATION</h2>";
    $stmt = $pdo->query("
        SELECT 
            c.nom_principal,
            a.nom_affaire,
            COUNT(ml.id) as nb_lignes
        FROM clients c
        JOIN affaires a ON c.id = a.client_id
        JOIN metrage_interventions mi ON a.id = mi.affaire_id
        JOIN metrage_lignes ml ON mi.id = ml.intervention_id
        WHERE a.numero_prodevis LIKE 'PRO-2026-%'
        GROUP BY c.id, a.id
    ");
    
    echo "<table class='table'>";
    echo "<tr><th>Client</th><th>Affaire</th><th>Lignes</th></tr>";
    while($row = $stmt->fetch()) {
        echo "<tr><td>{$row['nom_principal']}</td><td>{$row['nom_affaire']}</td><td>{$row['nb_lignes']}</td></tr>";
    }
    echo "</table>";
    
} catch(Exception $e) {
    $pdo->rollBack();
    echo "<div class='alert alert-danger'>";
    echo "<h3>❌ ERREUR</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
