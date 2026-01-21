<?php
// tools/assign_code_fou.php
require_once __DIR__ . '/../db.php';

// Assign sequential codes to suppliers without code_fou
$stmt = $pdo->query("SELECT id, nom, code_fou FROM fournisseurs ORDER BY id");
$fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nextCode = 1;
foreach($fournisseurs as $f) {
    if(empty($f['code_fou'])) {
        // Find next available code
        while($pdo->query("SELECT COUNT(*) FROM fournisseurs WHERE code_fou = '$nextCode'")->fetchColumn() > 0) {
            $nextCode++;
        }
        
        $stmt = $pdo->prepare("UPDATE fournisseurs SET code_fou = ? WHERE id = ?");
        $stmt->execute([$nextCode, $f['id']]);
        echo "✓ {$f['nom']} → code_fou = $nextCode<br>";
        $nextCode++;
    } else {
        echo "○ {$f['nom']} → code_fou = {$f['code_fou']} (déjà défini)<br>";
    }
}

echo "<hr><strong>Tous les fournisseurs ont maintenant un code_fou unique</strong>";
