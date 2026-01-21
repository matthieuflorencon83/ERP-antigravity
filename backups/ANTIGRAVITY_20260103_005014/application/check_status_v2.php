<?php
try {
    $dsn = "mysql:host=localhost;charset=utf8mb4";
    // Try root/root first, then root/''
    try {
        $pdo = new PDO($dsn, 'root', 'root');
    } catch (PDOException $e) {
        $pdo = new PDO($dsn, 'root', '');
    }

    echo "--- DIAGNOSTIC START ---\n";
    
    $stmt = $pdo->query("SELECT @@version as ver, @@datadir as dir, @@port as port");
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "VERSION: " . $info['ver'] . "\n";
    echo "DATADIR: " . $info['dir'] . "\n";
    echo "PORT:    " . $info['port'] . "\n";
    
    $stmt = $pdo->query("SHOW DATABASES");
    $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "DATABASES: " . implode(", ", $dbs) . "\n";
    
    echo "--- DIAGNOSTIC END ---\n";

} catch (PDOException $e) {
    echo "CONNECTION FAILED: " . $e->getMessage();
}
