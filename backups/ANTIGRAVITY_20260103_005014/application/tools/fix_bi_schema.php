<?php
require_once __DIR__ . '/../db.php';

function addCol($pdo, $table, $col, $def) {
    try {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
        echo "✅ Added $table.$col\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ $table.$col already exists.\n";
        } else {
            echo "❌ Error adding $table.$col: " . $e->getMessage() . "\n";
        }
    }
}

try {
    echo "🔧 Repairing BI Schema...\n";

    // 1. Affaires
    addCol($pdo, 'affaires', 'montant_ht', 'DECIMAL(10,2) DEFAULT 0.00');
    addCol($pdo, 'affaires', 'date_signature', 'DATE DEFAULT NULL');
    addCol($pdo, 'affaires', 'statut', "ENUM('Brouillon','Signé','Terminé','Facturé','Annulé') DEFAULT 'Brouillon'");

    // 2. Commandes Achats (used in KPI #2)
    addCol($pdo, 'commandes_achats', 'date_commande', 'DATE DEFAULT NULL');
    addCol($pdo, 'commandes_achats', 'statut', "VARCHAR(50) DEFAULT 'Brouillon'");

    // 3. Lignes Achat (used in KPI #2)
    addCol($pdo, 'lignes_achat', 'prix_unitaire_achat', 'DECIMAL(10,2) DEFAULT 0.00');
    addCol($pdo, 'lignes_achat', 'qte_commandee', 'DECIMAL(10,2) DEFAULT 1.00');

    echo "🏁 BI Schema patched.\n";

} catch (Exception $e) {
    echo "❌ FATAL: " . $e->getMessage();
}
