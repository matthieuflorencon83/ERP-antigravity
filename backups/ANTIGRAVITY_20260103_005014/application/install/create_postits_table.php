<?php
require_once __DIR__ . '/../db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS dashboard_postits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        color ENUM('jaune', 'bleu', 'vert', 'rose') NOT NULL DEFAULT 'jaune',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        position_order INT DEFAULT 0,
        KEY idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Table 'dashboard_postits' created successfully.";
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
