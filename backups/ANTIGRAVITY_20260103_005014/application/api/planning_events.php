<?php
// api/planning_events.php
// API Unified Planning Control Tower V3
// - Rendez-Vous (Metrage/Pose) via CENTRAL table
// - SAV via RESTORED LEGACY table (sav_interventions)
// - Livraisons via commandes_achats (Reel + Prevu)

require_once '../db.php';
require_once '../functions.php';

header('Content-Type: application/json; charset=utf-8');

// --- 1. LECTURE (GET) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $events = [];
    $start = $_GET['start'] ?? date('Y-m-d');
    $end = $_GET['end'] ?? date('Y-m-d');

    $startStr = explode('T', $start)[0];
    $endStr = explode('T', $end)[0];

    // 1. RENDEZ-VOUS (Metrage & Pose)
    try {
        // NOTE: Table rendez_vous n'a PAS de technicien_id.
        $stmt = $pdo->prepare("
            SELECT r.*, a.nom_affaire, 
                   c.nom_principal as client_nom, c.ville as client_ville, 
                   COALESCE(c.telephone_mobile, c.telephone_fixe) as client_tel, 
                   c.adresse_postale as client_adresse
            FROM rendez_vous r
            LEFT JOIN affaires a ON r.affaire_id = a.id
            LEFT JOIN clients c ON a.client_id = c.id
            WHERE r.date_rdv BETWEEN ? AND ?
        ");
        $stmt->execute([$startStr . ' 00:00:00', $endStr . ' 23:59:59']);
        
        while($row = $stmt->fetch()) {
            $isMetrage = $row['type'] === 'metrage';
            $isPose = $row['type'] === 'pose';
            
            $color = '#6c757d'; 
            if ($isMetrage) $color = '#0d6efd';
            if ($isPose) $color = '#198754';
            
            $titlePrefix = $isMetrage ? '📏 ' : ($isPose ? '🔨 ' : '📅 ');
            
            $start = $row['date_rdv'];
            $end = $row['heure_fin'] ? date('Y-m-d', strtotime($start)) . ' ' . $row['heure_fin'] : date('Y-m-d H:i:s', strtotime($start . ' + 2 hours'));
            
            if ($row['heure_debut']) {
                $start = date('Y-m-d', strtotime($start)) . ' ' . $row['heure_debut'];
            }

            $events[] = [
                'id' => 'RDV_' . $row['id'],
                'resourceId' => 'UNASSIGNED',
                'title' => $titlePrefix . ($row['client_nom'] ?? 'Client Inconnu') . ' - ' . ($row['nom_affaire'] ?? ''),
                'start' => $start,
                'end' => $end,
                'color' => $color,
                'extendedProps' => [
                    'type' => strtoupper($row['type']),
                    'db_id' => $row['id'],
                    'affaire_id' => $row['affaire_id'],
                    'description' => $row['notes'] ?? '',
                    'client_ville' => $row['client_ville'],
                    'client_adresse' => $row['client_adresse'],
                    'client_tel' => $row['client_tel'],
                    'technicien_nom' => 'Non assigné', // Pas dans la db
                    'link' => 'affaires_detail.php?id=' . $row['affaire_id']
                ]
            ];
        }
    } catch (Exception $e) {}

    // 2. SAV RESTAURÉ (Note: date_intervention, duree_prevue)
    try {
        // NOTE: SAV a bien technicien_id
        $stmt = $pdo->prepare("
            SELECT s.id, s.date_intervention, s.duree_prevue, s.technicien_id, 
                   t.numero_ticket, t.type_panne, t.description_initiale,
                   COALESCE(c.nom_principal, t.prospect_nom) as nom_client,
                   COALESCE(c.ville, t.prospect_ville) as ville,
                   COALESCE(c.telephone_mobile, c.telephone_fixe, t.prospect_telephone) as tel,
                   COALESCE(c.adresse_postale, '') as adresse,
                   u.nom_complet as technicien_nom
            FROM sav_interventions s
            JOIN sav_tickets t ON s.ticket_id = t.id
            LEFT JOIN clients c ON t.client_id = c.id
            LEFT JOIN utilisateurs u ON s.technicien_id = u.id
            WHERE s.date_intervention BETWEEN ? AND ?
        ");
        $stmt->execute([$startStr . ' 00:00:00', $endStr . ' 23:59:59']);

        while($row = $stmt->fetch()) {
            $start = $row['date_intervention'];
            $minutes = $row['duree_prevue'] ?: 60;
            $end = date('Y-m-d H:i:s', strtotime($start . " + $minutes minutes"));

            $events[] = [
                'id' => 'SAV_' . $row['id'],
                'resourceId' => $row['technicien_id'] ?? 'UNASSIGNED',
                'title' => '🚑 SAV: ' . $row['nom_client'],
                'start' => $start,
                'end' => $end,
                'color' => '#dc3545', // Danger Red
                'extendedProps' => [
                    'type' => 'SAV',
                    'db_id' => $row['id'],
                    'description' => $row['type_panne'] . ' - ' . $row['description_initiale'],
                    'client_ville' => $row['ville'],
                    'client_adresse' => $row['adresse'],
                    'client_tel' => $row['tel'],
                    'technicien_nom' => $row['technicien_nom'],
                    'link' => 'sav_mobile_diag.php?id=' . $row['ticket_id'] // Or fil
                ]
            ];
        }
    } catch(Exception $e) {}

    // 3. LIVRAISONS (Reelles + Prevues)
    try {
        $stmt = $pdo->prepare("
            SELECT id, date_livraison_reelle, date_livraison_prevue, ref_interne, fournisseur_id,
                   f.nom as fournisseur_nom
            FROM commandes_achats 
            LEFT JOIN fournisseurs f ON fournisseur_id = f.id
            WHERE (date_livraison_reelle BETWEEN ? AND ?) 
               OR (date_livraison_prevue BETWEEN ? AND ? AND date_livraison_reelle IS NULL)
        ");
        $stmt->execute([$startStr, $endStr, $startStr, $endStr]);
        while($row = $stmt->fetch()) {
            $isReal = !empty($row['date_livraison_reelle']);
            $date = $isReal ? $row['date_livraison_reelle'] : $row['date_livraison_prevue'];
            
            if (!$date) continue;

            $events[] = [
                'id' => 'LIV_' . $row['id'],
                'resourceId' => 'ATELIER',
                'title' => ($isReal ? '📦 Livré: ' : '🚚 Prévu: ') . $row['ref_interne'],
                'start' => $date, 
                'color' => $isReal ? '#ffc107' : '#fff3cd', 
                'textColor' => $isReal ? '#000000' : '#856404',
                'borderColor' => '#ffc107',
                'allDay' => true,
                'extendedProps' => [
                    'type' => 'LIVRAISON',
                    'status' => $isReal ? 'delivered' : 'planned',
                    'db_id' => $row['id'],
                    'description' => 'Fournisseur: ' . $row['fournisseur_nom'],
                    'link' => 'commandes_liste.php'
                ]
            ];
        }
    } catch(Exception $e) {}

    echo json_encode($events);
    exit;
}

// --- 2. MISE A JOUR (POST - Drag & Drop) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $fullId = $input['id']; 
    $newStart = $input['start'];
    $newEnd = $input['end'] ?? null;
    $resourceId = $input['resourceId'] ?? null;

    $parts = explode('_', $fullId);
    $type = $parts[0];
    $dbId = $parts[1];

    try {
        if ($type === 'RDV') {
            // Mise à jour TABLE: rendez_vous
            $tsStart = strtotime($newStart);
            $dateSql = date('Y-m-d', $tsStart);
            $heureDebut = date('H:i:s', $tsStart);
            
            if ($newEnd) {
                $tsEnd = strtotime($newEnd);
                $heureFin = date('H:i:s', $tsEnd);
            } else {
                $heureFin = date('H:i:s', $tsStart + 7200); 
            }

            $sql = "UPDATE rendez_vous SET date_rdv = ?, heure_debut = ?, heure_fin = ? WHERE id = ?";
            $params = [$dateSql, $heureDebut, $heureFin, $dbId];

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

        } elseif ($type === 'SAV') {
            // Mise à jour TABLE: sav_interventions (Format RESTAURÉ)
            // Colonnes: date_intervention, duree_prevue
            
            $startTs = strtotime($newStart);
            $dateIntervention = date('Y-m-d H:i:s', $startTs);
            
            // Calcul Durée
            $duree = 60;
            if ($newEnd) {
                $endTs = strtotime($newEnd);
                $duree = round(($endTs - $startTs) / 60);
            }
            if ($duree < 15) $duree = 15; // Minimum

            $sql = "UPDATE sav_interventions SET date_intervention = ?, duree_prevue = ?";
            $params = [$dateIntervention, $duree];

            if ($resourceId && is_numeric($resourceId)) {
                $sql .= ", technicien_id = ?";
                $params[] = $resourceId;
            }
            $sql .= " WHERE id = ?";
            $params[] = $dbId;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
        
        echo json_encode(['status' => 'success']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
?>
