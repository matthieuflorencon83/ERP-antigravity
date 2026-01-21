<?php
// tools/seed_agenda_appointments.php
require_once __DIR__ . '/../db.php';

echo "<h2>📅 Ajout Rendez-vous Agenda</h2>";

try {
    $pdo->beginTransaction();
    
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'rendez_vous'");
    $tableExists = $stmt->fetch();
    
    if(!$tableExists) {
        echo "<h4>Création table rendez_vous</h4>";
        $pdo->exec("
            CREATE TABLE `rendez_vous` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `affaire_id` INT NOT NULL,
                `type` ENUM('metrage', 'pose', 'livraison') NOT NULL,
                `date_rdv` DATE NOT NULL,
                `heure_debut` TIME DEFAULT '09:00:00',
                `heure_fin` TIME DEFAULT '12:00:00',
                `statut` VARCHAR(50) DEFAULT 'planifie',
                `notes` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_affaire` (`affaire_id`),
                INDEX `idx_date` (`date_rdv`),
                INDEX `idx_type` (`type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<p>✓ Table créée</p>";
    }
    
    // Add appointments
    echo "<h4>Ajout rendez-vous</h4>";
    
    $appointments = [
        // Métrages (prochains jours)
        [1, 'metrage', '2026-01-03', '09:00:00', '11:00:00', 'planifie', 'Métrage fenêtres appartement Paris 8'],
        [2, 'metrage', '2026-01-06', '14:00:00', '16:00:00', 'planifie', 'Métrage véranda Lyon 3'],
        [3, 'metrage', '2026-01-08', '10:00:00', '12:00:00', 'planifie', 'Métrage immeuble 12 fenêtres'],
        [4, 'metrage', '2026-01-10', '09:30:00', '11:30:00', 'planifie', 'Métrage maison Toulouse'],
        
        // Poses (dans 2-3 semaines)
        [1, 'pose', '2026-01-20', '08:00:00', '17:00:00', 'planifie', 'Pose fenêtres Paris 8 - 2 jours'],
        [2, 'pose', '2026-01-22', '08:00:00', '18:00:00', 'planifie', 'Pose véranda Lyon - 3 jours'],
        [3, 'pose', '2026-01-25', '08:00:00', '17:00:00', 'planifie', 'Pose immeuble Lyon - 5 jours'],
        [5, 'pose', '2026-01-28', '08:00:00', '17:00:00', 'planifie', 'Pose résidence Nantes - 10 jours']
    ];
    
    foreach($appointments as $apt) {
        $stmt = $pdo->prepare("
            INSERT INTO rendez_vous (affaire_id, type, date_rdv, heure_debut, heure_fin, statut, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute($apt);
    }
    
    echo "<p>✓ " . count($appointments) . " rendez-vous ajoutés</p>";
    
    $pdo->commit();
    
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ Agenda Rempli</h4>";
    echo "<ul>";
    echo "<li>4 rendez-vous métrage (3-10 janvier)</li>";
    echo "<li>4 rendez-vous pose (20-28 janvier)</li>";
    echo "</ul>";
    echo "<p>Rafraîchissez le dashboard pour voir les rendez-vous.</p>";
    echo "</div>";
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo "<div class='alert alert-danger'>Erreur: " . $e->getMessage() . "</div>";
}
