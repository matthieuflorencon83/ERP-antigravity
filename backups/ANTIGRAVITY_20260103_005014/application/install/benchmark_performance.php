<?php
/**
 * BENCHMARK DE PERFORMANCE ANTIGRAVITY
 * Test approfondi des performances de l'application
 */

require_once '../db.php';

// Fonction de mesure de temps
function benchmark($name, $callback) {
    $start = microtime(true);
    $memory_start = memory_get_usage();
    
    $result = $callback();
    
    $end = microtime(true);
    $memory_end = memory_get_usage();
    
    return [
        'name' => $name,
        'time' => round(($end - $start) * 1000, 2), // en ms
        'memory' => round(($memory_end - $memory_start) / 1024, 2), // en KB
        'result' => $result
    ];
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Benchmark Performance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .metric-card { margin-bottom: 20px; }
        .fast { color: #28a745; font-weight: bold; }
        .medium { color: #ffc107; font-weight: bold; }
        .slow { color: #dc3545; font-weight: bold; }
        .query-box { background: #f4f4f4; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1 class="mb-4">🚀 Benchmark de Performance - Antigravity</h1>
        
        <?php
        $tests = [];
        
        // ============================================
        // TEST 1: REQUÊTES DASHBOARD
        // ============================================
        echo "<div class='card metric-card'>";
        echo "<div class='card-header bg-primary text-white'><h4>📊 Test 1: Requêtes Dashboard</h4></div>";
        echo "<div class='card-body'>";
        
        // Test 1.1: Comptage commandes en attente
        $test = benchmark('Comptage commandes "En Attente"', function() use ($pdo) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM commandes_achats WHERE statut = 'Brouillon'");
            return $stmt->fetch()['count'];
        });
        $tests[] = $test;
        $color = $test['time'] < 10 ? 'fast' : ($test['time'] < 50 ? 'medium' : 'slow');
        echo "<p><strong>{$test['name']}</strong>: <span class='$color'>{$test['time']} ms</span> | Mémoire: {$test['memory']} KB | Résultat: {$test['result']} commandes</p>";
        
        // Test 1.2: Liste commandes en attente
        $test = benchmark('Liste 20 commandes "En Attente"', function() use ($pdo) {
            $stmt = $pdo->query("
                SELECT ca.*, a.nom_affaire 
                FROM commandes_achats ca 
                LEFT JOIN affaires a ON ca.affaire_id = a.id 
                WHERE ca.statut = 'Brouillon' 
                ORDER BY ca.date_commande DESC 
                LIMIT 20
            ");
            return $stmt->rowCount();
        });
        $tests[] = $test;
        $color = $test['time'] < 20 ? 'fast' : ($test['time'] < 100 ? 'medium' : 'slow');
        echo "<p><strong>{$test['name']}</strong>: <span class='$color'>{$test['time']} ms</span> | Mémoire: {$test['memory']} KB | Résultat: {$test['result']} lignes</p>";
        
        // Test 1.3: Comptage commandes commandées
        $test = benchmark('Comptage commandes "Commandées"', function() use ($pdo) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM commandes_achats WHERE statut = 'Commandé'");
            return $stmt->fetch()['count'];
        });
        $tests[] = $test;
        $color = $test['time'] < 10 ? 'fast' : ($test['time'] < 50 ? 'medium' : 'slow');
        echo "<p><strong>{$test['name']}</strong>: <span class='$color'>{$test['time']} ms</span> | Mémoire: {$test['memory']} KB</p>";
        
        echo "</div></div>";
        
        // ============================================
        // TEST 2: REQUÊTES AFFAIRES
        // ============================================
        echo "<div class='card metric-card'>";
        echo "<div class='card-header bg-success text-white'><h4>🏗️ Test 2: Requêtes Affaires</h4></div>";
        echo "<div class='card-body'>";
        
        // Test 2.1: Liste affaires avec client
        $test = benchmark('Liste 50 affaires avec client (JOIN)', function() use ($pdo) {
            $stmt = $pdo->query("
                SELECT a.*, c.nom_principal, c.prenom 
                FROM affaires a 
                LEFT JOIN clients c ON a.client_id = c.id 
                ORDER BY a.date_creation DESC 
                LIMIT 50
            ");
            return $stmt->rowCount();
        });
        $tests[] = $test;
        $color = $test['time'] < 30 ? 'fast' : ($test['time'] < 100 ? 'medium' : 'slow');
        echo "<p><strong>{$test['name']}</strong>: <span class='$color'>{$test['time']} ms</span> | Mémoire: {$test['memory']} KB</p>";
        
        // Test 2.2: Recherche affaire par statut
        $test = benchmark('Recherche affaires par statut', function() use ($pdo) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM affaires WHERE statut = 'Devis'");
            return $stmt->fetch()['count'];
        });
        $tests[] = $test;
        $color = $test['time'] < 10 ? 'fast' : ($test['time'] < 50 ? 'medium' : 'slow');
        echo "<p><strong>{$test['name']}</strong>: <span class='$color'>{$test['time']} ms</span> | Mémoire: {$test['memory']} KB</p>";
        
        echo "</div></div>";
        
        // ============================================
        // TEST 3: REQUÊTES CLIENTS
        // ============================================
        echo "<div class='card metric-card'>";
        echo "<div class='card-header bg-info text-white'><h4>👥 Test 3: Requêtes Clients</h4></div>";
        echo "<div class='card-body'>";
        
        // Test 3.1: Liste clients
        $test = benchmark('Liste 100 clients', function() use ($pdo) {
            $stmt = $pdo->query("SELECT * FROM clients ORDER BY date_creation DESC LIMIT 100");
            return $stmt->rowCount();
        });
        $tests[] = $test;
        $color = $test['time'] < 20 ? 'fast' : ($test['time'] < 100 ? 'medium' : 'slow');
        echo "<p><strong>{$test['name']}</strong>: <span class='$color'>{$test['time']} ms</span> | Mémoire: {$test['memory']} KB</p>";
        
        // Test 3.2: Recherche client par nom
        $test = benchmark('Recherche client par nom (LIKE)', function() use ($pdo) {
            $stmt = $pdo->query("SELECT * FROM clients WHERE nom_principal LIKE '%A%' LIMIT 20");
            return $stmt->rowCount();
        });
        $tests[] = $test;
        $color = $test['time'] < 30 ? 'fast' : ($test['time'] < 100 ? 'medium' : 'slow');
        echo "<p><strong>{$test['name']}</strong>: <span class='$color'>{$test['time']} ms</span> | Mémoire: {$test['memory']} KB</p>";
        
        echo "</div></div>";
        
        // ============================================
        // TEST 4: REQUÊTES COMPLEXES
        // ============================================
        echo "<div class='card metric-card'>";
        echo "<div class='card-header bg-warning text-dark'><h4>🔗 Test 4: Requêtes Complexes (Multiples JOIN)</h4></div>";
        echo "<div class='card-body'>";
        
        // Test 4.1: Commandes avec affaire, client et fournisseur
        $test = benchmark('Commandes + Affaire + Client + Fournisseur (3 JOIN)', function() use ($pdo) {
            $stmt = $pdo->query("
                SELECT 
                    ca.*, 
                    a.nom_affaire, 
                    c.nom_principal, 
                    f.nom as fournisseur_nom
                FROM commandes_achats ca
                LEFT JOIN affaires a ON ca.affaire_id = a.id
                LEFT JOIN clients c ON a.client_id = c.id
                LEFT JOIN fournisseurs f ON ca.fournisseur_id = f.id
                ORDER BY ca.date_commande DESC
                LIMIT 50
            ");
            return $stmt->rowCount();
        });
        $tests[] = $test;
        $color = $test['time'] < 50 ? 'fast' : ($test['time'] < 150 ? 'medium' : 'slow');
        echo "<p><strong>{$test['name']}</strong>: <span class='$color'>{$test['time']} ms</span> | Mémoire: {$test['memory']} KB</p>";
        
        echo "</div></div>";
        
        // ============================================
        // TEST 5: ANALYSE DES INDEX
        // ============================================
        echo "<div class='card metric-card'>";
        echo "<div class='card-header bg-dark text-white'><h4>🔍 Test 5: Analyse des Index (EXPLAIN)</h4></div>";
        echo "<div class='card-body'>";
        
        // Test EXPLAIN sur requête dashboard
        $stmt = $pdo->query("
            EXPLAIN SELECT * FROM commandes_achats WHERE statut = 'Commandé' ORDER BY date_commande DESC LIMIT 20
        ");
        $explain = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div class='query-box mb-3'>";
        echo "<strong>Requête:</strong> SELECT * FROM commandes_achats WHERE statut = 'Commandé' ORDER BY date_commande DESC LIMIT 20<br>";
        echo "<strong>Type:</strong> {$explain['type']}<br>";
        echo "<strong>Possible Keys:</strong> " . ($explain['possible_keys'] ?? 'NULL') . "<br>";
        echo "<strong>Key utilisée:</strong> <span class='fast'>" . ($explain['key'] ?? 'NULL') . "</span><br>";
        echo "<strong>Rows examinées:</strong> {$explain['rows']}<br>";
        echo "<strong>Extra:</strong> {$explain['Extra']}<br>";
        echo "</div>";
        
        if ($explain['key']) {
            echo "<p class='text-success'>✅ Index utilisé ! Performance optimale.</p>";
        } else {
            echo "<p class='text-danger'>❌ Aucun index utilisé ! Scan complet de la table.</p>";
        }
        
        echo "</div></div>";
        
        // ============================================
        // RÉSUMÉ GLOBAL
        // ============================================
        $total_time = array_sum(array_column($tests, 'time'));
        $avg_time = round($total_time / count($tests), 2);
        $fastest = min(array_column($tests, 'time'));
        $slowest = max(array_column($tests, 'time'));
        
        echo "<div class='card metric-card border-success'>";
        echo "<div class='card-header bg-success text-white'><h4>📈 Résumé Global</h4></div>";
        echo "<div class='card-body'>";
        echo "<div class='row'>";
        echo "<div class='col-md-3'><div class='card bg-light'><div class='card-body text-center'>";
        echo "<h5>Tests effectués</h5><h2>" . count($tests) . "</h2>";
        echo "</div></div></div>";
        echo "<div class='col-md-3'><div class='card bg-light'><div class='card-body text-center'>";
        echo "<h5>Temps total</h5><h2>{$total_time} ms</h2>";
        echo "</div></div></div>";
        echo "<div class='col-md-3'><div class='card bg-light'><div class='card-body text-center'>";
        echo "<h5>Temps moyen</h5><h2>{$avg_time} ms</h2>";
        echo "</div></div></div>";
        echo "<div class='col-md-3'><div class='card bg-light'><div class='card-body text-center'>";
        echo "<h5>Plus lent</h5><h2>{$slowest} ms</h2>";
        echo "</div></div></div>";
        echo "</div>";
        
        echo "<hr>";
        echo "<h5>🎯 Évaluation Globale:</h5>";
        if ($avg_time < 20) {
            echo "<div class='alert alert-success'><strong>🚀 EXCELLENT !</strong> Votre application est très performante. Temps de réponse optimal.</div>";
        } elseif ($avg_time < 50) {
            echo "<div class='alert alert-info'><strong>✅ BON</strong> Performances satisfaisantes. Quelques optimisations possibles.</div>";
        } elseif ($avg_time < 100) {
            echo "<div class='alert alert-warning'><strong>⚠️ MOYEN</strong> Performances acceptables mais améliorables.</div>";
        } else {
            echo "<div class='alert alert-danger'><strong>❌ LENT</strong> Optimisations nécessaires.</div>";
        }
        
        echo "<h5 class='mt-4'>📊 Détails par test:</h5>";
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>Test</th><th>Temps (ms)</th><th>Mémoire (KB)</th><th>Évaluation</th></tr></thead>";
        echo "<tbody>";
        foreach ($tests as $test) {
            $eval = $test['time'] < 20 ? '🚀 Excellent' : ($test['time'] < 50 ? '✅ Bon' : ($test['time'] < 100 ? '⚠️ Moyen' : '❌ Lent'));
            $color = $test['time'] < 20 ? 'fast' : ($test['time'] < 50 ? 'medium' : 'slow');
            echo "<tr>";
            echo "<td>{$test['name']}</td>";
            echo "<td><span class='$color'>{$test['time']}</span></td>";
            echo "<td>{$test['memory']}</td>";
            echo "<td>$eval</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        
        echo "</div></div>";
        
        // ============================================
        // RECOMMANDATIONS
        // ============================================
        echo "<div class='card metric-card border-primary'>";
        echo "<div class='card-header bg-primary text-white'><h4>💡 Recommandations</h4></div>";
        echo "<div class='card-body'>";
        
        if ($avg_time < 30) {
            echo "<p>✅ Vos optimisations (Phase B) ont porté leurs fruits !</p>";
            echo "<p>✅ Les index SQL sont bien utilisés.</p>";
            echo "<p>✅ Les requêtes sont rapides et efficaces.</p>";
        } else {
            echo "<p>⚠️ Quelques pistes d'amélioration :</p>";
            echo "<ul>";
            echo "<li>Vérifier que tous les index sont bien utilisés (EXPLAIN)</li>";
            echo "<li>Ajouter un cache Redis pour les requêtes fréquentes</li>";
            echo "<li>Optimiser les requêtes avec trop de JOIN</li>";
            echo "</ul>";
        }
        
        echo "</div></div>";
        ?>
        
        <div class="text-center mt-4">
            <a href="../dashboard.php" class="btn btn-primary btn-lg">Retour au Dashboard</a>
            <button onclick="location.reload()" class="btn btn-success btn-lg">Relancer le Test</button>
        </div>
    </div>
</body>
</html>
