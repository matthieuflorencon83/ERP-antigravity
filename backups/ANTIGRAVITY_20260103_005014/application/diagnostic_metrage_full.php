<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Diagnostic Métrage Studio</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .error { color: #f48771; font-weight: bold; }
        .success { color: #89d185; }
        .warning { color: #dcdcaa; }
        pre { background: #2d2d2d; padding: 10px; border-radius: 5px; overflow-x: auto; }
        h2 { color: #4ec9b0; border-bottom: 2px solid #4ec9b0; padding-bottom: 5px; }
    </style>
</head>
<body>
    <h1>🔍 DIAGNOSTIC MÉTRAGE STUDIO - CAPTURE COMPLÈTE</h1>
    <div id="output"></div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <?php
    require_once 'db.php';
    $metrage_id = $_GET['id'] ?? 0;
    
    // Charger données
    $intervention = ['nom_affaire' => 'Métrage Libre', 'client_nom' => 'Non lié'];
    $lignes = [];
    $types = [];
    $affaires = [];
    
    try {
        $stmtAff = $pdo->query("SELECT a.id, a.nom_affaire, c.nom_principal as client_nom 
            FROM affaires a 
            LEFT JOIN clients c ON a.client_id = c.id 
            ORDER BY a.id DESC LIMIT 100");
        $affaires = $stmtAff->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
    
    if ($metrage_id) {
        try {
            $stmt = $pdo->prepare("SELECT i.*, a.nom_affaire, c.nom_principal as client_nom 
                FROM metrage_interventions i 
                LEFT JOIN affaires a ON i.affaire_id = a.id 
                LEFT JOIN clients c ON a.client_id = c.id 
                WHERE i.id = ?");
            $stmt->execute([$metrage_id]);
            $result = $stmt->fetch();
            if ($result) $intervention = $result;
        } catch (PDOException $e) {}
        
        $stmt = $pdo->prepare("SELECT * FROM metrage_lignes WHERE intervention_id = ?");
        $stmt->execute([$metrage_id]);
        $lignes = $stmt->fetchAll();
    }
    
    try {
        $types = $pdo->query("SELECT id, nom, categorie FROM metrage_types ORDER BY categorie, nom")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
    ?>
    
    <script>
    const METRAGE_ID = <?= $metrage_id ?>;
    const INTERVENTION = <?= json_encode($intervention) ?>;
    const LIGNES = <?= json_encode($lignes) ?>;
    const TYPES = <?= json_encode($types) ?>;
    const AFFAIRES = <?= json_encode($affaires) ?>;
    
    const output = document.getElementById('output');
    
    function log(message, type = 'info') {
        const className = type === 'error' ? 'error' : type === 'success' ? 'success' : 'warning';
        output.innerHTML += `<div class="${className}">${message}</div>`;
    }
    
    function logSection(title) {
        output.innerHTML += `<h2>${title}</h2>`;
    }
    
    // Intercepter TOUTES les erreurs
    window.onerror = function(msg, url, line, col, error) {
        log(`❌ ERREUR GLOBALE: ${msg}`, 'error');
        log(`   Fichier: ${url}`, 'error');
        log(`   Ligne: ${line}, Colonne: ${col}`, 'error');
        if (error && error.stack) {
            log(`   Stack: <pre>${error.stack}</pre>`, 'error');
        }
        return false;
    };
    
    // Test 1: Variables PHP injectées
    logSection('1. VARIABLES PHP INJECTÉES');
    log(`METRAGE_ID: ${METRAGE_ID}`);
    log(`INTERVENTION: ${JSON.stringify(INTERVENTION, null, 2)}`);
    log(`LIGNES: ${LIGNES.length} ligne(s)`);
    log(`TYPES: ${TYPES.length} type(s)`);
    log(`AFFAIRES: ${AFFAIRES.length} affaire(s)`);
    
    // Test 2: DOM Elements
    logSection('2. ÉLÉMENTS DOM CRITIQUES');
    const requiredIds = [
        'assistant_messages',
        'input_container', 
        'input_zone_wrapper',
        'tree_products',
        'knowledge_memos',
        'recap',
        'sidebar'
    ];
    
    requiredIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            log(`✓ #${id} existe`, 'success');
        } else {
            log(`❌ #${id} MANQUANT`, 'error');
        }
    });
    
    // Test 3: Charger le JS principal et capturer les erreurs
    logSection('3. CHARGEMENT METRAGE_STUDIO_V11.JS');
    
    const script = document.createElement('script');
    script.src = 'assets/js/metrage_studio_v11.js?v=' + Date.now();
    script.onload = function() {
        log('✓ Script chargé', 'success');
        
        // Test 4: Vérifier objet Studio
        logSection('4. OBJET STUDIO');
        if (typeof Studio !== 'undefined') {
            log('✓ Studio existe', 'success');
            log(`Méthodes: ${Object.keys(Studio).join(', ')}`);
            
            // Test 5: Essayer init()
            logSection('5. TENTATIVE INIT()');
            try {
                Studio.init();
                log('✓ Studio.init() exécuté sans erreur', 'success');
            } catch (e) {
                log(`❌ Studio.init() a crashé: ${e.message}`, 'error');
                log(`Stack: <pre>${e.stack}</pre>`, 'error');
            }
        } else {
            log('❌ Studio non défini', 'error');
        }
    };
    
    script.onerror = function() {
        log('❌ Échec chargement script', 'error');
    };
    
    document.body.appendChild(script);
    </script>
</body>
</html>
