<?php
require_once 'db.php';

try {
    $pdo->exec("RENAME TABLE stock_mouvements TO _archive_stock_mouvements");
    echo "Table 'stock_mouvements' renamed to '_archive_stock_mouvements'.";
} catch (PDOException $e) {
    echo "Error renaming table: " . $e->getMessage();
}
?>
