<?php
/**
 * commandes_actions.php
 * Contrôleur AJAX pour les actions sur les commandes
 */

header('Content-Type: application/json');
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

// Sécurité : Vérification de la session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

// Récupération du corps de la requête (JSON)
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Action manquante ou JSON invalide']);
    exit;
}

$action = $input['action'];

try {
    if ($action === 'save_ai_lines') {
        // --- SAUVEGARDE DES LIGNES ISSUES DE L'IA ---
        $commande_id = (int)($input['commande_id'] ?? 0);
        $lignes = $input['lignes'] ?? [];
        
        if ($commande_id === 0) throw new Exception("ID Commande invalide");
        
        $pdo->beginTransaction();
        
        // 1. Mise à jour des infos globales (si fournies)
        if (isset($input['numero_document']) || isset($input['date_document']) || isset($input['montant_total_ht']) || isset($input['date_livraison_prevue'])) {
            $sql_update = "UPDATE commandes_achats SET ";
            $params = [];
            
            // Check TYPE
            $type_doc = $input['type_document'] ?? 'COMMANDE';
            
            if (!empty($input['numero_document'])) {
                // Si ARC, ça peut être la Ref ARC, sinon Ref BL/Commande
                // Pour simplifier, on stocke tout dans numero_bl_fournisseur pour l'instant
                $sql_update .= "numero_bl_fournisseur = ?, ";
                $params[] = $input['numero_document']; 
            }
            
            if (!empty($input['date_document'])) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['date_document'])) {
                    if ($type_doc === 'ARC') {
                        $sql_update .= "date_arc_recu = ?, "; 
                        // LOGIQUE DEPLACEMENT FICHIER : Si on valide un ARC, et que le fichier est actuellement dans 'chemin_pdf_bdc' (comportement par défaut upload), on le bascule.
                        // On doit vérifier si un fichier vient d'être uploadé ? Non, on suppose que le fichier analysé EST le fichier actif.
                        // On va interroger la BDD pour voir où est le fichier actuel.
                        // Mais ici on est dans une transaction, attention.
                        // On fait ça plus bas après le commit ou dans une requête séparée.
                        // Pour simplifier : on met à jour la colonne chemin_pdf_arc avec la valeur de chemin_pdf_bdc, et on vide chemin_pdf_bdc.
                        // SAUF SI le chemin_pdf_bdc est "vieux" ? Non, si on vient de faire une analyse, c'est sur le fichier courant.
                        // Risque : Si l'utilisateur a uploadé un ARC sur un BDC existant, le BDC est déjà écrasé physiquement (dans le dossier ou juste le path BDD).
                        // On va assumer : TRANSFERT DE COLONNE.
                         $pdo->exec("UPDATE commandes_achats SET chemin_pdf_arc = chemin_pdf_bdc, chemin_pdf_bdc = NULL WHERE id = $commande_id AND chemin_pdf_bdc IS NOT NULL");
                    } else {
                        $sql_update .= "date_commande = ?, ";
                    }
                    $params[] = $input['date_document'];
                }
            }

            if (!empty($input['date_livraison_prevue'])) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['date_livraison_prevue'])) {
                     $sql_update .= "date_livraison_prevue = ?, ";
                     $params[] = $input['date_livraison_prevue'];
                }
            }
            
            // On retire la virgule finale s'il y a eu des ajouts
            if (!empty($params)) {
                $sql_update = rtrim($sql_update, ", ") . " WHERE id = ?";
                $params[] = $commande_id;
                $stmt = $pdo->prepare($sql_update);
                $stmt->execute($params);
            }
        }
        
        // 2. Insertion des lignes
        if (!empty($lignes)) {
            $stmt_insert = $pdo->prepare("
                INSERT INTO lignes_achat 
                (commande_id, reference_fournisseur, designation, qte_commandee, prix_unitaire_achat) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($lignes as $l) {
                // Nettoyage basique
                $ref = trim($l['reference'] ?? '');
                $des = trim($l['designation'] ?? '');
                $qte = (float)($l['quantite'] ?? 0);
                $pu  = (float)($l['prix_unitaire'] ?? 0);
                
                if (empty($des) && empty($ref)) continue; // Ligne vide
                
                $stmt_insert->execute([$commande_id, $ref, $des, $qte, $pu]);
            }
        }
        
        // 3. Recalcul montant total HT global de la commande
        // (Optionnel si on veut le stocker en dur, mais on le fait souvent dynamiquement. 
        //  Je le fais ici pour info si la colonne existe dans commandes_achats, ce qui est le cas maintenant)
        $pdo->query("
            UPDATE commandes_achats c 
            SET montant_ht = (
                SELECT COALESCE(SUM(l.qte_commandee * l.prix_unitaire_achat), 0) 
                FROM lignes_achat l 
                WHERE l.commande_id = c.id
            )
            WHERE c.id = $commande_id
        ");
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Importation réussie']);
    } 
    elseif ($action === 'delete_commande') {
        // --- SUPPRESSION D'UNE COMMANDE ---
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID absent ou invalide");

        $pdo->beginTransaction();
        try {
            // Suppression des lignes d'abord
            $pdo->exec("DELETE FROM lignes_achat WHERE commande_id = $id");
            // Suppression de la commande
            $stmt = $pdo->prepare("DELETE FROM commandes_achats WHERE id = ?");
            $stmt->execute([$id]);
            
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    else {
        throw new Exception("Action inconnue : " . $action);
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}
