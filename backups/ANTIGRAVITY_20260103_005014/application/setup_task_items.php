<?php
require_once 'db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS task_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        content VARCHAR(255) NOT NULL,
        is_completed TINYINT(1) DEFAULT 0,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    
    echo "Table 'task_items' created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
