<?php
require_once 'db.php';
try {
    $stmt = $pdo->query("DESCRIBE commandes_achats");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
