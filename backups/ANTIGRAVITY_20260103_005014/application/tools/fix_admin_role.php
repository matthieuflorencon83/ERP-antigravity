<?php
// tools/fix_admin_role.php
require_once __DIR__ . '/../db.php';

try {
    echo "Fixing Admin Role...\n";
    
    // 1. Check if User 1 exists
    $stmt = $pdo->query("SELECT id, identifiant, role FROM utilisateurs WHERE id=1");
    $user = $stmt->fetch();

    if ($user) {
        echo "User 1 found: " . $user['identifiant'] . " (Current Role: " . ($user['role'] ?? 'NULL') . ")\n";
        
        // 2. Update to ADMIN
        $pdo->exec("UPDATE utilisateurs SET role='ADMIN' WHERE id=1");
        echo "✅ User 1 role set to 'ADMIN'.\n";
    } else {
        echo "⚠️ User 1 not found. Creating default admin...\n";
        $pass = password_hash('admin', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO utilisateurs (id, nom_complet, identifiant, role, mot_de_passe_hash) VALUES (1, 'Administrateur', 'admin', 'ADMIN', '$pass')");
        echo "✅ Created User 1 (admin/admin).\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
