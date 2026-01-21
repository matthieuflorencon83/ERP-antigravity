<?php
/**
 * Script d'installation des index de performance V2
 * Adapté au schéma réel de la base de données
 */

// Sécurité
$lock_file = __DIR__ . '/indexes_v2_installed.lock';
if (file_exists($lock_file)) {
    die('✅ Les index V2 ont déjà été installés. Supprimez "indexes_v2_installed.lock" pour réexécuter.');
}

require_once '../db.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Installation Index V2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 40px 0; }
        .log-success { color: #28a745; }
        .log-error { color: #dc3545; }
        .log-info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h3 class="mb-0">⚡ Installation Index Performance V2 (Corrigée)</h3>
            </div>
            <div class="card-body">
                <div id="log" class="mb-3" style="max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 14px;">
                    <?php
                    
                    $indexes = [
                        // TABLE commandes_achats (pas "commandes")
                        "ALTER TABLE commandes_achats ADD INDEX idx_date_livraison_reelle (date_livraison_reelle)" => "Index date_livraison_reelle sur commandes_achats",
                        "ALTER TABLE commandes_achats ADD INDEX idx_date_livraison_prevue (date_livraison_prevue)" => "Index date_livraison_prevue sur commandes_achats",
                        "ALTER TABLE commandes_achats ADD INDEX idx_date_arc_recu (date_arc_recu)" => "Index date_arc_recu sur commandes_achats",
                        "ALTER TABLE commandes_achats ADD INDEX idx_date_en_attente (date_en_attente)" => "Index date_en_attente sur commandes_achats",
                        "ALTER TABLE commandes_achats ADD INDEX idx_statut_ia (statut_ia)" => "Index statut_ia sur commandes_achats",
                        
                        // TABLE affaires
                        "ALTER TABLE affaires ADD INDEX idx_date_pose_debut (date_pose_debut)" => "Index date_pose_debut sur affaires",
                        "ALTER TABLE affaires ADD INDEX idx_date_pose_fin (date_pose_fin)" => "Index date_pose_fin sur affaires",
                        "ALTER TABLE affaires ADD INDEX idx_date_signature (date_signature)" => "Index date_signature sur affaires",
                        "ALTER TABLE affaires ADD INDEX idx_statut_chantier (statut_chantier)" => "Index statut_chantier sur affaires",
                        
                        // TABLE clients (colonnes réelles)
                        "ALTER TABLE clients ADD INDEX idx_nom_principal (nom_principal)" => "Index nom_principal sur clients",
                        "ALTER TABLE clients ADD INDEX idx_email_principal (email_principal)" => "Index email_principal sur clients",
                        
                        // TABLE tasks
                        "ALTER TABLE tasks ADD INDEX idx_due_date (due_date)" => "Index due_date sur tasks",
                        
                        // TABLE sav_tickets
                        "ALTER TABLE sav_tickets ADD INDEX idx_statut_urgence (statut, urgence)" => "Index composite statut+urgence sur sav_tickets",
                        
                        // TABLE metrage_interventions
                        "ALTER TABLE metrage_interventions ADD INDEX idx_date_prevue (date_prevue)" => "Index date_prevue sur metrage_interventions",
                        "ALTER TABLE metrage_interventions ADD INDEX idx_date_realisee (date_realisee)" => "Index date_realisee sur metrage_interventions",
                        "ALTER TABLE metrage_interventions ADD INDEX idx_statut_metrage (statut)" => "Index statut sur metrage_interventions",
                        
                        // TABLE stocks_mouvements
                        "ALTER TABLE stocks_mouvements ADD INDEX idx_user_id (user_id)" => "Index user_id sur stocks_mouvements",
                        "ALTER TABLE stocks_mouvements ADD INDEX idx_affaire_id (affaire_id)" => "Index affaire_id sur stocks_mouvements",
                        
                        // TABLE devis
                        "ALTER TABLE devis ADD INDEX idx_date_validite (date_validite)" => "Index date_validite sur devis",
                        "ALTER TABLE devis ADD INDEX idx_statut_date (statut, date_creation)" => "Index composite statut+date sur devis",
                    ];
                    
                    $success_count = 0;
                    $error_count = 0;
                    $skip_count = 0;
                    
                    echo "<p class='log-info'><strong>🚀 Début de l'installation (version corrigée)...</strong></p>";
                    
                    foreach ($indexes as $sql => $description) {
                        try {
                            $pdo->exec($sql);
                            echo "<p class='log-success'>✅ $description</p>";
                            $success_count++;
                        } catch (PDOException $e) {
                            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                                echo "<p class='log-info'>⏭️ $description (déjà existant)</p>";
                                $skip_count++;
                            } else {
                                echo "<p class='log-error'>❌ $description : " . htmlspecialchars($e->getMessage()) . "</p>";
                                $error_count++;
                            }
                        }
                    }
                    
                    echo "<hr>";
                    echo "<p class='log-info'><strong>📊 Résumé :</strong></p>";
                    echo "<p class='log-success'>✅ Index créés : $success_count</p>";
                    echo "<p class='log-info'>⏭️ Index déjà existants : $skip_count</p>";
                    echo "<p class='log-error'>❌ Erreurs : $error_count</p>";
                    
                    if ($error_count === 0) {
                        file_put_contents($lock_file, date('Y-m-d H:i:s'));
                        echo "<p class='log-success'><strong>✅ Installation terminée avec succès !</strong></p>";
                        echo "<p class='log-info'>Les performances de votre application sont maintenant optimisées.</p>";
                    } else {
                        echo "<p class='log-error'><strong>⚠️ Installation terminée avec $error_count erreur(s).</strong></p>";
                    }
                    
                    ?>
                </div>
                
                <div class="alert alert-success">
                    <h5>🎯 Optimisations appliquées :</h5>
                    <ul>
                        <li><strong>commandes_achats</strong> : Index sur dates de livraison et statut IA</li>
                        <li><strong>affaires</strong> : Index sur dates de pose et statut chantier</li>
                        <li><strong>clients</strong> : Index sur nom et email (colonnes réelles)</li>
                        <li><strong>tasks</strong> : Index sur date d'échéance</li>
                        <li><strong>SAV</strong> : Index sur statut et urgence</li>
                        <li><strong>Métrage</strong> : Index sur dates et statut</li>
                    </ul>
                </div>
                
                <a href="../dashboard.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Retour au Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
