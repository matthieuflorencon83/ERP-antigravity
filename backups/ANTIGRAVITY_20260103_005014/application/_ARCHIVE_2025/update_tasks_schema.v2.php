<?php
require_once 'db.php';

function addColumnIfNotExists($pdo, $table, $column, $definition) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
        if ($stmt->fetch()) {
            echo "Column '$column' already exists.<br>";
        } else {
            $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
            echo "Column '$column' added.<br>";
        }
    } catch (PDOException $e) {
        echo "Error checking/adding $column: " . $e->getMessage() . "<br>";
    }
}

addColumnIfNotExists($pdo, 'tasks', 'affaire_id', 'INT DEFAULT NULL');
addColumnIfNotExists($pdo, 'tasks', 'commande_id', 'INT DEFAULT NULL');

try {
    $pdo->exec("CREATE INDEX idx_tasks_affaire ON tasks(affaire_id)");
    $pdo->exec("CREATE INDEX idx_tasks_commande ON tasks(commande_id)");
    echo "Indexes created (or failed if existed).<br>";
} catch (Exception $e) {
    echo "Index error (ignorable): " . $e->getMessage() . "<br>";
}

echo "Done.";
?>
