<?php
require_once __DIR__ . '/../db.php';

function columnExists($pdo, $table, $col) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        return $stmt->fetch() !== false;
    } catch (Exception $e) { return false; }
}

try {
    echo "🔧 Patching metrage_types...\n";
    
    // 1. Slug
    if (!columnExists($pdo, 'metrage_types', 'slug')) {
        echo "Adding slug...\n";
        $pdo->exec("ALTER TABLE metrage_types ADD COLUMN `slug` VARCHAR(100) NULL AFTER `id`");
    }

    // 2. Description logic
    $hasDesc = columnExists($pdo, 'metrage_types', 'description');
    $hasTech = columnExists($pdo, 'metrage_types', 'description_technique');

    if (!$hasDesc && $hasTech) {
        echo "Renaming description_technique to description...\n";
        $pdo->exec("ALTER TABLE metrage_types CHANGE COLUMN `description_technique` `description` TEXT NULL");
    } elseif (!$hasDesc) {
        echo "Adding description...\n";
        $pdo->exec("ALTER TABLE metrage_types ADD COLUMN `description` TEXT NULL");
    }

    // 3. Actif
    if (!columnExists($pdo, 'metrage_types', 'actif')) {
        echo "Adding actif...\n";
        $pdo->exec("ALTER TABLE metrage_types ADD COLUMN `actif` BOOLEAN DEFAULT TRUE");
    }
    
    // 4. Schema SVG
    if (!columnExists($pdo, 'metrage_types', 'schema_svg')) {
        echo "Adding schema_svg...\n";
        $pdo->exec("ALTER TABLE metrage_types ADD COLUMN `schema_svg` TEXT NULL");
    }

    // 5. Workflow JSON
    if (!columnExists($pdo, 'metrage_types', 'workflow_json')) {
        echo "Adding workflow_json...\n";
        $pdo->exec("ALTER TABLE metrage_types ADD COLUMN `workflow_json` JSON NULL");
    }

    echo "✅ Patch applied successfully.\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
