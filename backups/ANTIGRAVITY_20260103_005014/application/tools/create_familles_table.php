<?php
// tools/create_familles_table.php
require_once __DIR__ . '/../db.php';

try {
    // 1. Create Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `familles_articles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `designation` VARCHAR(100) NOT NULL,
        `icon` VARCHAR(50) DEFAULT 'box',
        `ordre` INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo "Table 'familles_articles' created.<br>";

    // 2. Check if empty
    $count = $pdo->query("SELECT count(*) FROM familles_articles")->fetchColumn();
    
    if ($count == 0) {
        // 3. Seed Data
        $data = [
            ['Profilés Alu', 'ruler-vertical', 10],
            ['Tôles & Pliages', 'layer-group', 20],
            ['Vitrages', 'window-maximize', 30],
            ['Quincaillerie', 'tools', 40],
            ['Joints', 'ring', 50],
            ['Consommables', 'spray-can', 60]
        ];

        $stmt = $pdo->prepare("INSERT INTO familles_articles (designation, icon, ordre) VALUES (?, ?, ?)");
        foreach ($data as $row) {
            $stmt->execute($row);
            echo "Inserted: {$row[0]}<br>";
        }
    } else {
        echo "Table already has data.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
