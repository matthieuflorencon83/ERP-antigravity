<?php
require_once __DIR__ . '/../db.php';

try {
    echo "🌱 Seeding Test Data...\n";
    
    // 1. Clients
    $stmt = $pdo->prepare("INSERT IGNORE INTO clients (code_client, nom_principal, prenom, ville) VALUES 
        ('C001', 'Dupont', 'Jean', 'Paris'),
        ('C002', 'Martin', 'Sophie', 'Lyon'),
        ('C003', 'Durand', 'Paul', 'Marseille')");
    $stmt->execute();
        
    // 2. Affaires
    $pdo->exec("INSERT INTO affaires (nom_affaire, client_id, statut, montant_ht, date_signature) VALUES 
        ('Rénovation Cuisine', 1, 'Signé', 15000.00, NOW()),
        ('Extension Garage', 2, 'En cours', 25000.00, NOW()),
        ('Changement Fenêtres', 3, 'Terminé', 8000.00, NOW())");

    // 3. Metrage Interventions
    $lastAffaire = $pdo->lastInsertId();
    $pdo->exec("INSERT INTO metrage_interventions (affaire_id, statut, date_prevue) VALUES 
        ($lastAffaire, 'PLANIFIE', NOW() + INTERVAL 2 DAY)");

    echo "✅ Test Data Injected.\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
