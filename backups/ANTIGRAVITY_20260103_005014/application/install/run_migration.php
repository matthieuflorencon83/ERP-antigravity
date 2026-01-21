<?php
require_once '../db.php';

try {
    $sql = file_get_contents('update_sav_orders.sql');
    $pdo->exec($sql);
    echo "Migration OK : Colonne ticket_id ajoutée.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Migration déjà effectuée (Colonne existe).";
    } else {
        echo "Erreur : " . $e->getMessage();
    }
}
?>
