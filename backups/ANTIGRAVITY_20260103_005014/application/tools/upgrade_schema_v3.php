<?php
// tools/upgrade_schema_v3.php
require_once __DIR__ . '/../db.php';

try {
    // 1. Create `sous_familles_articles`
    $pdo->exec("CREATE TABLE IF NOT EXISTS `sous_familles_articles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `famille_id` INT NOT NULL,
        `designation` VARCHAR(100) NOT NULL,
        FOREIGN KEY (`famille_id`) REFERENCES `familles_articles`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'sous_familles_articles' created.<br>";

    // 2. Add `image_path` to `articles_catalogue`
    try {
        $pdo->exec("ALTER TABLE `articles_catalogue` ADD COLUMN `image_path` VARCHAR(255) DEFAULT NULL AFTER `designation_commerciale`");
        echo "Column 'image_path' added to 'articles_catalogue'.<br>";
    } catch (PDOException $e) { /* Column likely exists */ }

    // 3. Seed Sub-Families (Example)
    // Get Family IDs
    $fams = $pdo->query("SELECT id, designation FROM familles_articles")->fetchAll(PDO::FETCH_KEY_PAIR);
    // Profilés Alu -> id
    $profId = array_search('Profilés Alu', $fams);
    $accessId = array_search('Quincaillerie', $fams); // Assuming Quincaillerie exists

    if ($profId) {
        $subs = ['Chevrons', 'Poteaux', 'Traverses', 'Sablières', 'Capots'];
        $stmt = $pdo->prepare("INSERT INTO sous_familles_articles (famille_id, designation) VALUES (?, ?)");
        foreach ($subs as $s) {
            // Check existence
            $exists = $pdo->query("SELECT count(*) FROM sous_familles_articles WHERE designation = '$s' AND famille_id = $profId")->fetchColumn();
            if (!$exists) {
                $stmt->execute([$profId, $s]);
                echo "Inserted Sub-Family: $s<br>";
            }
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
