<?php
// tools/check_colors_schema.php
require_once __DIR__ . '/../db.php';

function check($pdo, $table) {
    echo "--- $table ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) echo $c['Field']." (".$c['Type'].")\n";
    } catch(Exception $e) { echo "MISSING\n"; }
}

check($pdo, 'finitions');
check($pdo, 'couleurs');
check($pdo, 'modele_profils');
check($pdo, 'articles'); // Re-check ral col
