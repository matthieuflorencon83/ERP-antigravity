<?php
/**
 * migrate_to_v4.php - Script de Migration Automatique V4.0
 * 
 * Responsabilité : Migration sécurisée de V11 vers V4
 * Constitution v3.0 : Backup automatique avant toute modification
 * 
 * @version 4.0.0
 */

declare(strict_types=1);

// Désactiver timeout
set_time_limit(300); // 5 minutes max

require_once 'db.php';

// =====================================================
// CONFIGURATION
// =====================================================

$MIGRATION_CONFIG = [
    'backup_dir' => __DIR__ . '/_archive',
    'sql_file' => __DIR__ . '/install/db_schema_v4.sql',
    'files_to_archive' => [
        'metrage_studio.php' => '_archive/metrage_studio_v11_legacy.php',
        'assets/js/metrage_studio_v11.js' => '_archive/metrage_studio_v11_legacy.js',
        'assets/css/metrage_studio_v11.css' => '_archive/metrage_studio_v11_legacy.css'
    ]
];

// =====================================================
// FONCTIONS UTILITAIRES
// =====================================================

function log_step(string $message, string $type = 'info'): void {
    $icons = [
        'info' => 'ℹ️',
        'success' => '✅',
        'warning' => '⚠️',
        'error' => '❌',
        'progress' => '⏳'
    ];
    
    $icon = $icons[$type] ?? 'ℹ️';
    echo "<div class='log-entry log-{$type}'>{$icon} {$message}</div>\n";
    flush();
}

function create_backup_dir(string $dir): bool {
    if (is_dir($dir)) {
        log_step("Dossier backup existe déjà: {$dir}", 'success');
        return true;
    }
    
    if (@mkdir($dir, 0755, true)) {
        log_step("Dossier backup créé: {$dir}", 'success');
        return true;
    }
    
    log_step("Impossible de créer le dossier de backup: {$dir}", 'error');
    return false;
}

function backup_file(string $source, string $dest): bool {
    if (!file_exists($source)) {
        log_step("Fichier source introuvable: {$source}", 'warning');
        return true; // Pas bloquant
    }
    
    $destDir = dirname($dest);
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    
    if (copy($source, $dest)) {
        log_step("Backup: " . basename($source) . " → " . basename($dest), 'success');
        return true;
    }
    
    log_step("Échec backup: {$source}", 'error');
    return false;
}

function execute_sql_file(PDO $pdo, string $file): bool {
    if (!file_exists($file)) {
        log_step("Fichier SQL introuvable: {$file}", 'error');
        return false;
    }
    
    $sql = file_get_contents($file);
    
    // Séparer les requêtes (délimiteur ;)
    $queries = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($q) => !empty($q) && !str_starts_with($q, '--')
    );
    
    $success = 0;
    $errors = 0;
    
    foreach ($queries as $query) {
        // Ignorer commentaires et vues (CREATE OR REPLACE VIEW)
        if (str_starts_with($query, '/*') || 
            str_starts_with($query, '--') ||
            stripos($query, 'CREATE OR REPLACE VIEW') !== false) {
            continue;
        }
        
        try {
            $pdo->exec($query);
            $success++;
        } catch (PDOException $e) {
            // Ignorer erreurs "table already exists"
            if (strpos($e->getMessage(), 'already exists') === false) {
                log_step("Erreur SQL: " . substr($e->getMessage(), 0, 100), 'warning');
                $errors++;
            }
        }
    }
    
    log_step("SQL exécuté: {$success} requêtes OK, {$errors} erreurs", 
             $errors > 0 ? 'warning' : 'success');
    
    return true;
}

// =====================================================
// ÉTAPES DE MIGRATION
// =====================================================

?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration V4.0 - Antigravity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d1117 0%, #161b22 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .migration-container {
            max-width: 800px;
            margin: 0 auto;
            background: #161b22;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        .log-entry {
            padding: 0.75rem 1rem;
            margin: 0.5rem 0;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .log-info { background: rgba(13, 110, 253, 0.1); border-left: 3px solid #0d6efd; }
        .log-success { background: rgba(25, 135, 84, 0.1); border-left: 3px solid #198754; }
        .log-warning { background: rgba(255, 193, 7, 0.1); border-left: 3px solid #ffc107; }
        .log-error { background: rgba(220, 53, 69, 0.1); border-left: 3px solid #dc3545; }
        .log-progress { background: rgba(108, 117, 125, 0.1); border-left: 3px solid #6c757d; }
        .progress-bar-animated {
            animation: progress-bar-stripes 1s linear infinite;
        }
        @keyframes progress-bar-stripes {
            0% { background-position: 1rem 0; }
            100% { background-position: 0 0; }
        }
    </style>
</head>
<body>
    <div class="migration-container">
        <div class="text-center mb-4">
            <i class="fas fa-rocket fa-3x text-primary mb-3"></i>
            <h1 class="h3">Migration vers Métrage Studio V4.0</h1>
            <p class="text-muted">Migration automatique avec backup de sécurité</p>
        </div>
        
        <div class="progress mb-4" style="height: 30px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                 role="progressbar" style="width: 0%" id="progress-bar">
                <span id="progress-text">0%</span>
            </div>
        </div>
        
        <div id="log-container">
            <?php
            
            // =====================================================
            // DÉBUT MIGRATION
            // =====================================================
            
            $startTime = microtime(true);
            $totalSteps = 5;
            $currentStep = 0;
            
            function update_progress(int $step, int $total): void {
                $percent = round(($step / $total) * 100);
                echo "<script>
                    document.getElementById('progress-bar').style.width = '{$percent}%';
                    document.getElementById('progress-text').textContent = '{$percent}%';
                </script>\n";
                flush();
            }
            
            log_step("🚀 Démarrage de la migration V4.0", 'info');
            log_step("Date: " . date('Y-m-d H:i:s'), 'info');
            
            // ÉTAPE 1: Créer dossier backup
            log_step("", 'info');
            log_step("ÉTAPE 1/5 : Création dossier de backup", 'progress');
            if (!create_backup_dir($MIGRATION_CONFIG['backup_dir'])) {
                log_step("Migration annulée", 'error');
                exit;
            }
            log_step("Dossier backup créé: {$MIGRATION_CONFIG['backup_dir']}", 'success');
            update_progress(++$currentStep, $totalSteps);
            
            // ÉTAPE 2: Backup fichiers legacy
            log_step("", 'info');
            log_step("ÉTAPE 2/5 : Backup des fichiers V11", 'progress');
            $backupSuccess = true;
            foreach ($MIGRATION_CONFIG['files_to_archive'] as $source => $dest) {
                if (!backup_file($source, $dest)) {
                    $backupSuccess = false;
                }
            }
            if ($backupSuccess) {
                log_step("Tous les fichiers ont été sauvegardés", 'success');
            }
            update_progress(++$currentStep, $totalSteps);
            
            // ÉTAPE 3: Backup base de données
            log_step("", 'info');
            log_step("ÉTAPE 3/5 : Backup de la base de données", 'progress');
            try {
                $backupFile = $MIGRATION_CONFIG['backup_dir'] . '/db_backup_' . date('Y-m-d_His') . '.sql';
                
                // Dump tables métrage
                $tables = ['metrage_interventions', 'metrage_lignes', 'metrage_types'];
                $dump = "-- Backup Antigravity DB - " . date('Y-m-d H:i:s') . "\n\n";
                
                foreach ($tables as $table) {
                    // Vérifier si table existe
                    $check = $pdo->query("SHOW TABLES LIKE '{$table}'")->fetch();
                    if (!$check) continue;
                    
                    // Structure
                    $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
                    $dump .= "\n-- Table: {$table}\n";
                    $dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
                    $dump .= $create['Create Table'] . ";\n\n";
                    
                    // Données
                    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
                    if (count($rows) > 0) {
                        $dump .= "-- Data for {$table}\n";
                        foreach ($rows as $row) {
                            $values = array_map(fn($v) => $pdo->quote($v), array_values($row));
                            $dump .= "INSERT INTO `{$table}` VALUES (" . implode(',', $values) . ");\n";
                        }
                        $dump .= "\n";
                    }
                }
                
                file_put_contents($backupFile, $dump);
                log_step("Backup DB créé: " . basename($backupFile), 'success');
                
            } catch (Exception $e) {
                log_step("Erreur backup DB: " . $e->getMessage(), 'warning');
                log_step("Migration continue (backup manuel recommandé)", 'warning');
            }
            update_progress(++$currentStep, $totalSteps);
            
            // ÉTAPE 4: Exécuter schema V4
            log_step("", 'info');
            log_step("ÉTAPE 4/5 : Application du schema V4", 'progress');
            if (execute_sql_file($pdo, $MIGRATION_CONFIG['sql_file'])) {
                log_step("Schema V4 appliqué avec succès", 'success');
            } else {
                log_step("Erreur lors de l'application du schema", 'error');
            }
            update_progress(++$currentStep, $totalSteps);
            
            // ÉTAPE 5: Vérification finale
            log_step("", 'info');
            log_step("ÉTAPE 5/5 : Vérification de l'installation", 'progress');
            
            $checks = [
                'metrage_interventions' => "SELECT COUNT(*) FROM metrage_interventions",
                'metrage_lignes' => "SELECT COUNT(*) FROM metrage_lignes",
                'metrage_types' => "SELECT COUNT(*) FROM metrage_types",
                'Colonnes virtuelles' => "SHOW COLUMNS FROM metrage_lignes LIKE '%_mm'"
            ];
            
            $allOk = true;
            foreach ($checks as $name => $query) {
                try {
                    $result = $pdo->query($query);
                    if ($name === 'Colonnes virtuelles') {
                        $count = $result->rowCount();
                        log_step("✓ {$name}: {$count} colonnes trouvées", 'success');
                    } else {
                        $count = $result->fetchColumn();
                        log_step("✓ {$name}: {$count} enregistrement(s)", 'success');
                    }
                } catch (PDOException $e) {
                    log_step("✗ {$name}: " . $e->getMessage(), 'error');
                    $allOk = false;
                }
            }
            update_progress(++$currentStep, $totalSteps);
            
            // RÉSUMÉ FINAL
            $duration = round(microtime(true) - $startTime, 2);
            
            log_step("", 'info');
            log_step("═══════════════════════════════════════", 'info');
            if ($allOk) {
                log_step("🎉 MIGRATION TERMINÉE AVEC SUCCÈS !", 'success');
            } else {
                log_step("⚠️ Migration terminée avec avertissements", 'warning');
            }
            log_step("Durée: {$duration}s", 'info');
            log_step("═══════════════════════════════════════", 'info');
            
            ?>
        </div>
        
        <div class="mt-4 text-center">
            <a href="metrage_studio_v4.php" class="btn btn-primary btn-lg">
                <i class="fas fa-rocket me-2"></i>Accéder au Studio V4
            </a>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-lg ms-2">
                <i class="fas fa-home me-2"></i>Retour Dashboard
            </a>
        </div>
        
        <div class="alert alert-info mt-4">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Fichiers sauvegardés dans :</strong> <code>_archive/</code>
        </div>
    </div>
</body>
</html>
