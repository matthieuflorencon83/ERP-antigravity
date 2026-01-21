<?php
require_once 'db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Reinstalling Tasks Tables</h1>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. DROP task_items first (foreign key constraint)
    echo "Dropping task_items... ";
    $pdo->exec("DROP TABLE IF EXISTS task_items");
    echo "OK<br>";

    // 2. DROP tasks
    echo "Dropping tasks... ";
    $pdo->exec("DROP TABLE IF EXISTS tasks");
    echo "OK<br>";

    // 3. CREATE tasks (Added user_id column based on header.php usage)
    echo "Creating tasks... ";
    $sql_tasks = "CREATE TABLE tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        importance ENUM('low', 'normal', 'high') DEFAULT 'normal',
        status ENUM('todo', 'done') DEFAULT 'todo',
        affaire_id INT DEFAULT NULL,
        commande_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_status (user_id, status),
        FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
        FOREIGN KEY (affaire_id) REFERENCES affaires(id) ON DELETE SET NULL,
        FOREIGN KEY (commande_id) REFERENCES commandes_achats(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_tasks);
    echo "OK<br>";

    // 4. CREATE task_items
    echo "Creating task_items... ";
    $sql_items = "CREATE TABLE task_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        content VARCHAR(255) NOT NULL,
        is_completed TINYINT(1) DEFAULT 0,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_items);
    echo "OK<br>";

    echo "<h2 style='color:green'>SUCCESS: Tables reinstalled!</h2>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>ERROR: " . $e->getMessage() . "</h2>";
}
?>
