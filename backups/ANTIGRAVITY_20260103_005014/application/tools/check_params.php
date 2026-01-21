<?php
require 'db.php';
$stmt = $pdo->query("SELECT * FROM parametres_generaux WHERE cle_config='chemin_achats'");
$res = $stmt->fetch(PDO::FETCH_ASSOC);
if ($res) {
    echo "FOUND: " . $res['valeur_config'];
} else {
    echo "NOT FOUND";
}
?>
