<?php
// tools/run_optimize_schema.php
require __DIR__ . '/../db.php';

echo "🚀 Démarrage du Turbo Boost (Optimisation BDD)...\n";

$indexes_to_check = [
    'affaires' => [
        'idx_affaires_client' => ['client_id'],
        'idx_affaires_statut' => ['statut'],
        'idx_affaires_date'   => ['date_creation']
    ],
    'commandes_achats' => [
        'idx_cmd_fournisseur' => ['fournisseur_id'],
        'idx_cmd_affaire'     => ['affaire_id'],
        'idx_cmd_statut'      => ['statut'],
        'idx_cmd_date'        => ['date_commande']
    ],
    'lignes_achat' => [
        'idx_lignes_cmd'      => ['commande_id'],
        'idx_lignes_article'  => ['article_id'],
        'idx_lignes_statut'   => ['statut']
    ],
    'stocks_mouvements' => [
        'idx_mvt_article'     => ['article_id'],
        'idx_mvt_date'        => ['date_mouvement']
    ],
    'articles' => [
        'idx_art_famille'     => ['famille_id'],
        'idx_art_ref'         => ['reference_fournisseur']
    ]
];

$added_count = 0;

foreach ($indexes_to_check as $table => $indexes) {
    echo "\n🔍 Analyse de la table '$table'...\n";
    
    foreach ($indexes as $index_name => $columns) {
        // Vérifier si l'index existe déjà
        $stmt = $pdo->prepare("
            SELECT COUNT(1) 
            FROM information_schema.STATISTICS 
            WHERE table_schema = DATABASE() 
            AND table_name = ? 
            AND index_name = ?
        ");
        $stmt->execute([$table, $index_name]);
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            echo "   ✅ Index '$index_name' existe déjà.\n";
        } else {
            echo "   ⚡ Création de l'index '$index_name'...\n";
            try {
                $cols_sql = implode(',', $columns);
                $sql = "ALTER TABLE `$table` ADD INDEX `$index_name` ($cols_sql)";
                $pdo->exec($sql);
                echo "      [OK] Index créé avec succès.\n";
                $added_count++;
            } catch (Exception $e) {
                echo "      [ERREUR] " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "\n🏁 Terminé ! $added_count index(s) ont été ajoutés.\n";
echo "Votre base de données est maintenant optimisée.\n";
