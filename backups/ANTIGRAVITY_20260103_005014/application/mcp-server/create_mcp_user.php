<?php
/**
 * create_mcp_user.php - Création automatique de l'utilisateur SQL MCP
 * À exécuter UNE SEULE FOIS : http://localhost/antigravity/mcp-server/create_mcp_user.php
 */

require_once '../db.php';

try {
    // Créer l'utilisateur
    $pdo->exec("CREATE USER IF NOT EXISTS 'antigravity_mcp_reader'@'localhost' IDENTIFIED BY 'MCP_Reader_2026!Secure'");
    
    // Accorder droits SELECT uniquement
    $pdo->exec("GRANT SELECT ON antigravity.* TO 'antigravity_mcp_reader'@'localhost'");
    
    // Appliquer
    $pdo->exec("FLUSH PRIVILEGES");
    
    echo "<h2>✅ Utilisateur SQL créé avec succès !</h2>";
    echo "<p><strong>Utilisateur :</strong> antigravity_mcp_reader</p>";
    echo "<p><strong>Droits :</strong> SELECT uniquement sur la base 'antigravity'</p>";
    
    // Vérification
    $stmt = $pdo->query("SHOW GRANTS FOR 'antigravity_mcp_reader'@'localhost'");
    $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Droits accordés :</h3>";
    echo "<ul>";
    foreach ($grants as $grant) {
        echo "<li>" . htmlspecialchars($grant) . "</li>";
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<h3>Prochaine étape :</h3>";
    echo "<p>Dans PowerShell, exécutez :</p>";
    echo "<pre>cd c:\\laragon\\www\\antigravity\\mcp-server\nnpm run dev</pre>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Erreur</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
