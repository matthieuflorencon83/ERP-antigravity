<?php
/**
 * Script d'installation des index de performance
 * À exécuter une seule fois via : http://localhost/antigravity/install/run_indexes.php
 */

// Sécurité : empêcher l'exécution multiple
$lock_file = __DIR__ . '/indexes_installed.lock';
if (file_exists($lock_file)) {
    die('✅ Les index ont déjà été installés. Supprimez le fichier "indexes_installed.lock" pour réexécuter.');
}

require_once '../db.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Index Performance</title>
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
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">⚡ Installation des Index de Performance</h3>
            </div>
            <div class="card-body">
                <div id="log" class="mb-3" style="max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 14px;">
                    <?php
                    
                    $indexes = [
                        // TABLE COMMANDES
                        "ALTER TABLE commandes ADD INDEX idx_statut (statut)" => "Index statut sur commandes",
                        "ALTER TABLE commandes ADD INDEX idx_date_commande (date_commande)" => "Index date_commande sur commandes",
                        "ALTER TABLE commandes ADD INDEX idx_date_livraison (date_livraison_prevue)" => "Index date_livraison sur commandes",
                        "ALTER TABLE commandes ADD INDEX idx_affaire_id (affaire_id)" => "Index affaire_id sur commandes",
                        "ALTER TABLE commandes ADD INDEX idx_fournisseur_id (fournisseur_id)" => "Index fournisseur_id sur commandes",
                        "ALTER TABLE commandes ADD INDEX idx_statut_date (statut, date_commande)" => "Index composite statut+date sur commandes",
                        
                        // TABLE AFFAIRES
                        "ALTER TABLE affaires ADD INDEX idx_client_id (client_id)" => "Index client_id sur affaires",
                        "ALTER TABLE affaires ADD INDEX idx_statut (statut)" => "Index statut sur affaires",
                        "ALTER TABLE affaires ADD INDEX idx_date_creation (date_creation)" => "Index date_creation sur affaires",
                        "ALTER TABLE affaires ADD INDEX idx_client_statut (client_id, statut)" => "Index composite client+statut sur affaires",
                        
                        // TABLE CLIENTS
                        "ALTER TABLE clients ADD INDEX idx_nom (nom)" => "Index nom sur clients",
                        "ALTER TABLE clients ADD INDEX idx_email (email)" => "Index email sur clients",
                        
                        // TABLE FOURNISSEURS
                        "ALTER TABLE fournisseurs ADD INDEX idx_code_fou (code_fou)" => "Index code_fou sur fournisseurs",
                        "ALTER TABLE fournisseurs ADD INDEX idx_nom (nom)" => "Index nom sur fournisseurs",
                        
                        // TABLE PLANNING_EVENTS
                        "ALTER TABLE planning_events ADD INDEX idx_start_date (start_date)" => "Index start_date sur planning_events",
                        "ALTER TABLE planning_events ADD INDEX idx_end_date (end_date)" => "Index end_date sur planning_events",
                        "ALTER TABLE planning_events ADD INDEX idx_affaire_id (affaire_id)" => "Index affaire_id sur planning_events",
                        "ALTER TABLE planning_events ADD INDEX idx_date_range (start_date, end_date)" => "Index composite dates sur planning_events",
                    ];
                    
                    $success_count = 0;
                    $error_count = 0;
                    $skip_count = 0;
                    
                    echo "<p class='log-info'><strong>🚀 Début de l'installation...</strong></p>";
                    
                    foreach ($indexes as $sql => $description) {
                        try {
                            $pdo->exec($sql);
                            echo "<p class='log-success'>✅ $description</p>";
                            $success_count++;
                        } catch (PDOException $e) {
                            // Vérifier si l'erreur est "index déjà existant"
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
                        // Créer le fichier lock
                        file_put_contents($lock_file, date('Y-m-d H:i:s'));
                        echo "<p class='log-success'><strong>✅ Installation terminée avec succès !</strong></p>";
                        echo "<p class='log-info'>Un fichier de verrouillage a été créé pour empêcher une réexécution accidentelle.</p>";
                    } else {
                        echo "<p class='log-error'><strong>⚠️ Installation terminée avec des erreurs.</strong></p>";
                    }
                    
                    ?>
                </div>
                
                <div class="alert alert-info">
                    <h5>📝 Prochaines étapes :</h5>
                    <ol>
                        <li>Retournez sur votre application</li>
                        <li>Testez les performances (dashboard, listes)</li>
                        <li>Les requêtes devraient être 80% plus rapides ⚡</li>
                    </ol>
                </div>
                
                <a href="../dashboard.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Retour au Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
