<?php
require_once 'db.php';

// 1. Get Data from Singular Table
$stmt = $pdo->query("SELECT * FROM stock_mouvements");
$data_singular = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Get Data from Plural Table
$stmt = $pdo->query("SELECT * FROM stocks_mouvements");
$data_plural = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Generate SQL Dump
$dump = "-- BACKUP DATE: " . date('Y-m-d H:i:s') . "\n";
$dump .= "-- TABLE: stock_mouvements (Singular - " . count($data_singular) . " rows)\n";

if ($data_singular) {
    $dump .= "INSERT INTO stock_mouvements (id, article_id, user_id, quantite, type_mouvement, date_mouvement, commentaire) VALUES \n";
    $lines = [];
    foreach ($data_singular as $row) {
        $lines[] = sprintf("(%d, %d, %s, '%s', '%s', '%s', '%s')",
            $row['id'], $row['article_id'], 
            $row['user_id'] ? $row['user_id'] : 'NULL',
            $row['quantite'], $row['type_mouvement'], $row['date_mouvement'], 
            addslashes($row['commentaire'] ?? '')
        );
    }
    $dump .= implode(",\n", $lines) . ";\n\n";
}

$dump .= "-- TABLE: stocks_mouvements (Plural - " . count($data_plural) . " rows)\n";
if ($data_plural) {
    // Note: Plural table has more columns. We verify columns dynamically in real dump but here we presume schema.
    // article_id, finition_id, type_mouvement, quantite, date_mouvement, user_id, commentaire, commande_achat_id, affaire_id
    $dump .= "INSERT INTO stocks_mouvements (id, article_id, finition_id, type_mouvement, quantite, date_mouvement, user_id, commentaire, commande_achat_id, affaire_id) VALUES \n";
    $lines = [];
    foreach ($data_plural as $row) {
        $lines[] = sprintf("(%d, %d, %s, '%s', '%s', '%s', %s, '%s', %s, %s)",
            $row['id'], $row['article_id'], 
            $row['finition_id'] ? $row['finition_id'] : 'NULL',
            $row['type_mouvement'], $row['quantite'], $row['date_mouvement'], 
            $row['user_id'] ? $row['user_id'] : 'NULL',
            addslashes($row['commentaire'] ?? ''),
            $row['commande_achat_id'] ? $row['commande_achat_id'] : 'NULL',
            $row['affaire_id'] ? $row['affaire_id'] : 'NULL'
        );
    }
    $dump .= implode(",\n", $lines) . ";\n";
}

// Save to file
file_put_contents('backups/backup_stock_mouvements_FULL.sql', $dump);
echo "Backup created at backups/backup_stock_mouvements_FULL.sql (Rows: Singular=".count($data_singular)." / Plural=".count($data_plural).")";
?>
