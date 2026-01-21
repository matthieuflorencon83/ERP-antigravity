<?php
require 'db.php';
$id = 1;
$stmt = $pdo->prepare("SELECT chemin_pdf_bdc FROM commandes_achats WHERE id = ?");
$stmt->execute([$id]);
$res = $stmt->fetchColumn();
echo "PATH DB: [" . $res . "]\n";
if (file_exists($res)) {
    echo "TYPE: " . (is_dir($res) ? 'DIR' : 'FILE') . "\n";
} else {
    echo "NOT FOUND ON DISK\n";
}
?>
