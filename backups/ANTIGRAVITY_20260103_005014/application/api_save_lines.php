<?php
// api_save_lines.php - Enregistre le résultat de l'IA dans la BDD
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit(json_encode(['success' => false, 'message' => 'Méthode non autorisée']));
}

// On récupère le JSON brut envoyé par le Javascript
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['commande_id'])) {
    exit(json_encode(['success' => false, 'message' => 'Données invalides']));
}

$cmd_id = $data['commande_id'];
$lignes = $data['lignes'] ?? [];
$infos  = $data['infos'] ?? [];

try {
    $pdo->beginTransaction();

    // 1. MISE À JOUR DE L'EN-TÊTE COMMANDE (Date, etc.)
    if (!empty($infos['date_document']) || !empty($infos['numero_document'])) {
        $sql = "UPDATE commandes_achats SET 
                statut_ia = 'VALIDÉ',
                ref_arc_fournisseur = :ref,
                date_commande = :date_cmd
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ref' => $infos['numero_document'] ?? null,
            ':date_cmd' => $infos['date_document'] ?? null,
            ':id' => $cmd_id
        ]);
    }

    // 2. GESTION DES LIGNES (Mode : On remplace tout pour cette commande)
    // On supprime les anciennes lignes pour éviter les doublons lors d'un ré-import
    $del = $pdo->prepare("DELETE FROM lignes_achat WHERE commande_id = ?");
    $del->execute([$cmd_id]);

    // On insère les nouvelles
    $insert = $pdo->prepare("INSERT INTO lignes_achat 
        (commande_id, ref_fournisseur, designation_article, qte_commandee, prix_unitaire_achat) 
        VALUES (:cid, :ref, :des, :qte, :pu)");

    foreach ($lignes as $ligne) {
        $insert->execute([
            ':cid' => $cmd_id,
            ':ref' => $ligne['reference'] ?? 'DIV',
            ':des' => $ligne['designation'] ?? 'Article sans nom',
            ':qte' => floatval($ligne['quantite'] ?? 0),
            ':pu'  => floatval($ligne['prix_unitaire'] ?? 0)
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => count($lignes) . ' lignes importées avec succès !']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()]);
}
?>
