<?php
// tools/inspect_articles_master.php
require_once __DIR__ . '/../db.php';

function inspect($pdo, $table) {
    echo "--- TABLE: $table ---\n";
    try {
        $stmt = $pdo->query("SELECT count(*) FROM $table");
        echo "Row Count: " . $stmt->fetchColumn() . "\n";
        
        $stmt = $pdo->query("DESCRIBE $table");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($cols as $c) {
            echo "{$c['Field']} ({$c['Type']})\n";
        }
        
        echo "\nSample Data:\n";
        $stmt = $pdo->query("SELECT * FROM $table LIMIT 1");
        print_r($stmt->fetch(PDO::FETCH_ASSOC));
        
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

inspect($pdo, 'articles');
inspect($pdo, 'articles_catalogue');
