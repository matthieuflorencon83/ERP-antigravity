<?php
try {
    $dsn = "mysql:host=localhost;charset=utf8mb4"; // No dbname to avoid error if it's missing
    $pdo = new PDO($dsn, 'root', 'root');
    
    $stmt = $pdo->query("SELECT @@datadir as dir");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "CURRENT DATADIR: " . $row['dir'] . "\n";
    
    $stmt = $pdo->query("SHOW DATABASES");
    $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "DATABASES FOUND: " . implode(", ", $dbs) . "\n";
    
} catch (PDOException $e) {
    echo "CONNECTION FAILED: " . $e->getMessage();
}
