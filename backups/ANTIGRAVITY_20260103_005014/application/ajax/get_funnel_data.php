<?php
// ajax/get_funnel_data.php
require_once '../db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$response = [];

try {
    switch ($action) {
        case 'get_fournisseurs':
            $stmt = $pdo->query("SELECT id, nom, code_fou FROM fournisseurs ORDER BY nom");
            $response = $stmt->fetchAll();
            break;

        case 'get_familles':
            $stmt = $pdo->query("SELECT id, designation, icon FROM familles_articles ORDER BY ordre, designation");
            $response = $stmt->fetchAll();
            break;

        case 'get_sous_familles':
            $famId = intval($_GET['famille_id'] ?? 0);
            if (!$famId) throw new Exception("famille_id missing");
            
            $stmt = $pdo->prepare("SELECT id, designation FROM sous_familles_articles WHERE famille_id = ? ORDER BY designation");
            $stmt->execute([$famId]);
            $response = $stmt->fetchAll();
            break;

        case 'get_finitions':
            $stmt = $pdo->query("SELECT id, code_ral, nom_couleur, aspect FROM finitions ORDER BY code_ral");
            $response = $stmt->fetchAll();
            break;

        case 'get_articles':
            $sfId = intval($_GET['sous_famille_id'] ?? 0);
            $fId = intval($_GET['famille_id'] ?? 0);
            $fouId = intval($_GET['fournisseur_id'] ?? 0);
            
            // Query with JOIN to finitions
            $sql = "SELECT 
                        a.id, 
                        a.reference_interne, 
                        a.designation, 
                        a.ref_fournisseur, 
                        a.image_path, 
                        a.longueur_barre, 
                        a.unite_stock,
                        a.prix_achat_ht,
                        a.poids_kg,
                        a.finition_id,
                        a.stock_actuel,
                        f.code_ral,
                        f.nom_couleur,
                        f.aspect
                    FROM articles a
                    LEFT JOIN finitions f ON a.finition_id = f.id
                    WHERE 1=1";
            $params = [];

            if ($fId) {
                $sql .= " AND a.famille_id = ?";
                $params[] = $fId;
            }
            if ($sfId) {
                $sql .= " AND a.sous_famille_id = ?";
                $params[] = $sfId;
            }
            if ($fouId) {
                $sql .= " AND a.fournisseur_prefere_id = ?";
                $params[] = $fouId;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $response = $stmt->fetchAll();
            
            // Enrich with computed fields
            foreach($response as &$row) {
                $row['type_vente'] = ($row['unite_stock'] == 'U') ? 'PIECE' : 'BARRE';
                if($row['longueur_barre'] > 0) {
                     $row['stock_lengths'] = [$row['longueur_barre']];
                }
                
                // Format price for display
                $row['prix_display'] = $row['prix_achat_ht'] ? number_format($row['prix_achat_ht'], 2, ',', ' ') . ' €' : 'N/C';
                
                // Format finition display (already joined)
                if($row['code_ral']) {
                    $row['finition_display'] = "RAL {$row['code_ral']} - {$row['nom_couleur']} ({$row['aspect']})";
                } else {
                    $row['finition_display'] = 'Brut / Standard';
                }
            }
            break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    http_response_code(400);
    $response = ['error' => $e->getMessage()];
}

echo json_encode($response);
