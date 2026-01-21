<?php
// tool_cleanup_mysql_db.php
require 'db.php';

echo "<h1>NETTOYAGE BASE 'mysql'</h1>";

try {
    // Connexion 'mysql'
    $pdo_sys = new PDO("mysql:host={$db_config['host']};dbname=mysql;charset=utf8mb4", $db_config['user'], $db_config['pass']);
    $pdo_sys->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Liste des Intrus Confirmés
    $intruders = [
        'affaires',
        'articles_catalogue',
        'besoins_chantier',
        'client_coordonnees',
        'clients',
        'commandes_achats',
        'fabricants',
        'familles',
        'finitions',
        'fournisseur_contacts',
        'fournisseurs',
        'historique_prix',
        'lignes_achat',
        'modeles_courriers',
        'modeles_profils',
        'parametres_generaux',
        'sous_familles',
        'utilisateurs'
    ];

    echo "<ul>";
    
    $pdo_sys->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    foreach ($intruders as $table) {
        // Verification si elle existe
        $check = $pdo_sys->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($check) {
            $pdo_sys->exec("DROP TABLE `$table`");
            echo "<li style='color:green'>SUPPRIMÉE : $table</li>";
        } else {
            echo "<li style='color:gray'>Ignorée (Non trouvée) : $table</li>";
        }
    }
    
    $pdo_sys->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo "</ul>";
    echo "<h2 style='color:green'>✅ OPÉRATION TERMINÉE</h2>";

} catch (Exception $e) {
    die("<div style='color:red;'>ERREUR : " . $e->getMessage() . "</div>");
}
?>
