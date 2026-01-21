<?php
require_once 'db.php';

try {
    echo "Adding due_date column to tasks table...<br>";
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM tasks LIKE 'due_date'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE tasks ADD COLUMN due_date DATE DEFAULT NULL AFTER status");
        echo "Column 'due_date' added successfully.<br>";
    } else {
        echo "Column 'due_date' already exists.<br>";
    }

    echo "Done.";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
