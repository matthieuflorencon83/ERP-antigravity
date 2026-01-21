<?php
require_once 'db.php';

function describeTable($pdo, $tableName) {
    try {
        $stmt = $pdo->query("DESCRIBE $tableName");
        echo "Table: $tableName\n";
        echo "Field | Type | Null | Key | Default | Extra\n";
        echo "---|---|---|---|---|---\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']} | {$row['Extra']}\n";
        }
        echo "\n";
    } catch (PDOException $e) {
        echo "Error describing $tableName: " . $e->getMessage() . "\n\n";
    }
}

describeTable($pdo, 'metrage_interventions');
describeTable($pdo, 'metrage_lignes');
