<?php
require_once __DIR__ . '/../db.php';

echo "Creation du compte Admin (Schema Validé)...\n";

$identifiant = 'admin';
$pass = 'admin';
$hash = password_hash($pass, PASSWORD_DEFAULT);
$nom = 'Administrateur';
$role = 'ADMIN';

try {
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE identifiant = ?");
    $stmt->execute([$identifiant]);
    if($stmt->fetch()) {
        echo "L'utilisateur $identifiant existe deja.\n";
        $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe_hash = ? WHERE identifiant = ?");
        $stmt->execute([$hash, $identifiant]);
        echo "Mot de passe mis a jour.\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (identifiant, mot_de_passe_hash, nom_complet, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$identifiant, $hash, $nom, $role]);
        echo "Utilisateur cree avec succes !\n";
    }

} catch (PDOException $e) {
    echo "Erreur SQL : " . $e->getMessage() . "\n";
}
