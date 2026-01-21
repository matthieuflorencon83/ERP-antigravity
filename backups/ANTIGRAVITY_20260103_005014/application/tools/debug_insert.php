<?php
require_once __DIR__ . '/../db.php';

echo "--- TRIGGERS ---\n";
$stmt = $pdo->query("SHOW TRIGGERS LIKE 'commandes_achats'");
$triggers = $stmt->fetchAll();
if (empty($triggers)) echo "No triggers found.\n";
foreach($triggers as $trig) {
    echo "Trigger: " . $trig['Trigger'] . "\n";
}

echo "--- ISOLATED INSERT TEST ---\n";
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("INSERT INTO commandes_achats (designation) VALUES ('TEST_COL_EXISTENCE')");
    echo "✅ Insert OK (Column exists and works)\n";
    // Clean up
    $pdo->exec("DELETE FROM commandes_achats WHERE designation='TEST_COL_EXISTENCE'");
} catch (PDOException $e) {
    echo "❌ Insert Failed: " . $e->getMessage() . "\n";
}
