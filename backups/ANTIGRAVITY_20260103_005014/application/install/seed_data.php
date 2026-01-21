<?php
// install/seed_data.php
// Script de génération de données de test (Seed) pour Antigravity V3
// Usage: php install/seed_data.php

// 1. CONFIGURATION & CONNEXION
// Ajustement du chemin vers db.php (supposé à la racine "c:\laragon\www\antigravity\db.php")
$db_path = __DIR__ . '/../db.php';
if (!file_exists($db_path)) {
    die("❌ Erreur : Impossible de trouver $db_path\n");
}
require_once $db_path;
$pdo->exec("SET NAMES utf8mb4");

echo "⚠️ ATTENTION : Ce script va VIDER les tables et générer de fausses données.\n";
echo "Voulez-vous continuer ? (Attente 5s...)\n";
sleep(5);

try {
    // 2. VIDAGE PROPRE (TRUNCATE)
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
    
    $tables = [
        'commandes_achats', 
        'lignes_achat', 
        'tasks', 
        'task_items', 
        'affaires', 
        'clients', 
        'articles_catalogue', 
        'fournisseurs',
        'utilisateurs' // Ajout pour clean complet
    ];
    
    foreach ($tables as $tb) {
        $pdo->exec("TRUNCATE TABLE $tb;");
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
    echo "✅ Tables vidées avec succès.\n\n";

    // 0. SYSTEM DATA (USERS)
    $pwd_hash = password_hash('admin', PASSWORD_DEFAULT);
    $u_stmt = $pdo->prepare("INSERT INTO utilisateurs (identifiant, mot_de_passe_hash, nom_complet, role) VALUES (?, ?, ?, ?)");
    $u_stmt->execute(['admin', $pwd_hash, 'Administrateur Système', 'ADMIN']);
    $admin_id = $pdo->lastInsertId();
    echo "✅ Admin User créé (ID: $admin_id).\n";

    // 3. GENERATION NIVEAU 1
    
    // --- FOURNISSEURS ---
    $fournisseurs_data = [
        'AluTech Industries', 'Quincaillerie Pro', 'Verrerie du Sud', 'Profilés Express', 
        'Boulonnerie Martin', 'Store & Co', 'Motorisation Somfy', 'Joints Etanchéité', 
        'Bois Exotique Import', 'Peinture & Finition'
    ];
    
    $f_stmt = $pdo->prepare("INSERT INTO fournisseurs (nom, email_commande, code_fou, ville, code_postal) VALUES (?, ?, ?, ?, ?)");
    foreach ($fournisseurs_data as $index => $nom) {
        $email = strtolower(str_replace(' ', '', $nom)) . "@test.com";
        $code = "FOU" . str_pad($index+1, 3, '0', STR_PAD_LEFT);
        $f_stmt->execute([$nom, $email, $code, 'Marseille', '13000']);
    }
    echo "✅ 10 Fournisseurs créés.\n";
    
    // Récup IDs
    $fournisseur_ids = $pdo->query("SELECT id FROM fournisseurs")->fetchAll(PDO::FETCH_COLUMN);

    // --- ARTICLES ---
    $articles_types = ['Profilé Aluminium', 'Joint EPDM', 'Vis Inox', 'Moteur VR', 'Vitrage 4/16/4', 'Poignée Simple', 'Serrure 3 points'];
    $a_stmt = $pdo->prepare("INSERT INTO articles_catalogue (fournisseur_id, ref_fournisseur, designation_commerciale, prix_achat_actuel) VALUES (?, ?, ?, ?)");
    
    for ($i = 0; $i < 10; $i++) {
        $fid = $fournisseur_ids[array_rand($fournisseur_ids)];
        $type = $articles_types[array_rand($articles_types)];
        $ref = "REF-" . strtoupper(substr($type, 0, 3)) . "-" . rand(100, 999);
        $prix = rand(10, 500);
        $a_stmt->execute([$fid, $ref, "$type Standard", $prix]);
    }
    echo "✅ 10 Articles créés.\n";

    // --- CLIENTS ---
    $c_stmt = $pdo->prepare("INSERT INTO clients (nom_principal, email_principal, telephone_fixe, adresse_postale, ville, code_postal) VALUES (?, ?, ?, ?, ?, ?)");
    for ($i = 1; $i <= 10; $i++) {
        $nom = "Client Test " . $i;
        $email = "client$i@exemple.com";
        $tel = "04" . rand(10000000, 99999999);
        $adresse = "$i Rue de la République";
        $c_stmt->execute([$nom, $email, $tel, $adresse, 'Marseille', '13000']);
    }
    echo "✅ 10 Clients créés.\n";
    
    $client_ids = $pdo->query("SELECT id FROM clients")->fetchAll(PDO::FETCH_COLUMN);

    // 4. GENERATION NIVEAU 2
    
    // --- AFFAIRES ---
    $types_affaires = ['Véranda', 'Pergola', 'Fenêtres', 'Portail', 'Garde-corps'];
    $aff_stmt = $pdo->prepare("INSERT INTO affaires (client_id, nom_affaire, numero_prodevis, statut_chantier, date_pose_debut, designation) VALUES (?, ?, ?, ?, ?, ?)");
    
    for ($i = 0; $i < 10; $i++) {
        $cid = $client_ids[array_rand($client_ids)];
        $type = $types_affaires[array_rand($types_affaires)];
        $nom_affaire = "$type - Villa " . rand(1, 100);
        $designation = "Travaux de $type pour Villa";
        $prodevis = "DV-" . date('Y') . "-" . str_pad($i, 4, '0', STR_PAD_LEFT);
        
        // Random date future or past
        $days = rand(-30, 60);
        $date_pose = date('Y-m-d', strtotime("$days days"));
        $statut = ($days < 0) ? 'Terminé' : 'En Cours';
        
        $aff_stmt->execute([$cid, $nom_affaire, $prodevis, $statut, $date_pose, $designation]);
    }
    echo "✅ 10 Affaires créées.\n";
    
    $affaire_ids = $pdo->query("SELECT id FROM affaires")->fetchAll(PDO::FETCH_COLUMN);

    // 5. GENERATION NIVEAU 3
    
    // --- COMMANDES & TÂCHES ---
    $cmd_stmt = $pdo->prepare("INSERT INTO commandes_achats (fournisseur_id, affaire_id, ref_interne, designation, date_commande, date_en_attente, date_arc_recu, date_livraison_prevue, date_livraison_reelle, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Pattern: 2 En attente, 2 Commandé, 2 ARC, 2 Livraison Prévue, 2 Livré
    $y = date('Y');
    $yn = $y + 1;
    $scenarios = [
        ['En attente', NULL, "$yn-01-01", NULL, NULL, NULL, "$yn-02-01"],
        ['En attente', NULL, "$yn-01-02", NULL, NULL, NULL, "$yn-02-05"],
        ['Commandée', "$yn-01-10", NULL, NULL, NULL, NULL, "$yn-02-10"],
        ['Commandée', "$yn-01-12", NULL, NULL, NULL, NULL, "$yn-02-12"],
        ['ARC Reçu', "$yn-01-05", NULL, "$yn-01-08", NULL, NULL, "$yn-02-01"],
        ['ARC Reçu', "$yn-01-06", NULL, "$yn-01-09", NULL, NULL, "$yn-02-02"],
        ['Livraison Prévue', "$y-12-01", NULL, "$y-12-05", "$yn-01-15", NULL, "$yn-01-15"],
        ['Livraison Prévue', "$y-12-10", NULL, "$y-12-15", "$yn-01-20", NULL, "$yn-01-20"],
        ['Livrée', "$y-11-01", NULL, "$y-11-05", "$y-11-20", "$y-11-21", "$y-11-21"],
        ['Livrée', "$y-11-05", NULL, "$y-11-10", "$y-11-25", "$y-11-26", "$y-11-26"],
    ];

    $count = 0;
    foreach ($scenarios as $s) {
        $statut_txt = $s[0]; 
        $date_cmd = $s[1];
        $date_attente = $s[2]; 
        $date_arc = $s[3];
        $date_previe = $s[4]; // (Anciennement prevue, maintenant inutilisé dans insert direct mais gardé pour structure)
        $date_reelle = $s[5];
        $date_cible = $s[6];

        $final_delivery_date = $s[6]; 

        $fid = $fournisseur_ids[array_rand($fournisseur_ids)];
        $aid = $affaire_ids[array_rand($affaire_ids)];
        $ref_interne = "CMD-$yn-" . str_pad($count+1, 3, '0', STR_PAD_LEFT);
        $designation = "Commande Test - " . $statut_txt;
        
        // Mapping statut DB (Status basés sur functions.php badge_statut)
        // FORCE 'Brouillon' pour éviter les soucis d'encodage CLI Windows
        $statut_db = 'Brouillon';
        // if ($date_cmd) $statut_db = 'Commandee'; 
        // if ($date_arc) $statut_db = 'ARC Recu';
        // if ($date_reelle) $statut_db = 'Livree';

        $cmd_stmt->execute([$fid, $aid, $ref_interne, $designation, $date_cmd, $date_attente, $date_arc, $final_delivery_date, $date_reelle, $statut_db]);
        $count++;
    }
    echo "✅ 10 Commandes créées (Scénarios variés).\n";

    // --- TASKS ---
    // --- TASKS ---
    $task_stmt = $pdo->prepare("INSERT INTO tasks (commande_id, user_id, title, status, importance, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $item_stmt = $pdo->prepare("INSERT INTO task_items (task_id, content, is_completed) VALUES (?, ?, ?)");
    
    $task_statuses = ['todo', 'done']; 
    $importance = ['1', '2']; 
    
    for ($i = 1; $i <= 10; $i++) {
        $cmd_id = $i; 
        $t_stat = $task_statuses[array_rand($task_statuses)];
        $t_imp = $importance[array_rand($importance)];
        
        // Insertion avec user_id (Admin)
        $task_stmt->execute([$cmd_id, $admin_id, "Vérifier cotes pour commande #$i", $t_stat, $t_imp]);
        $tid = $pdo->lastInsertId();
        
        // Sous-tâches
        $item_stmt->execute([$tid, "Appeler le client", rand(0,1)]);
        $item_stmt->execute([$tid, "Valider métré", rand(0,1)]);
    }
    echo "✅ 10 Tâches créées.\n";

    echo "\n🎉 SEEDING TERMINE ! Base de données prête.\n";

} catch (Exception $e) {
    echo "❌ ERREUR CRITIQUE : " . $e->getMessage() . "\n";
    print_r($e->getTrace());
}
?>
