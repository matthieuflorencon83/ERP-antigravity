<?php
require_once __DIR__ . '/../db.php';

try {
    echo "1. Ajout de la colonne montant_ht...\n";
    // Check if column exists first to avoid error
    $stm = $pdo->query("SHOW COLUMNS FROM commandes_achats LIKE 'montant_ht'");
    if ($stm->fetch()) {
        echo "   La colonne existe déjà.\n";
    } else {
        $pdo->exec("ALTER TABLE commandes_achats ADD COLUMN montant_ht DECIMAL(10,2) DEFAULT 0.00 AFTER designation");
        echo "   Colonne ajoutée.\n";
    }

    echo "2. Recalcul des montants existants...\n";
    $sql = "UPDATE commandes_achats c 
            SET montant_ht = (
                SELECT COALESCE(SUM(l.qte_commandee * l.prix_unitaire_achat), 0) 
                FROM lignes_achat l 
                WHERE l.commande_id = c.id
            )";
    $count = $pdo->exec($sql);
    echo "   Mise à jour effectuée sur $count commandes.\n";
    
    echo "Terminé avec succès.\n";

} catch (Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
}
