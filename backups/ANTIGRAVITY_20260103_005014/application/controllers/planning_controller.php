<?php
// controllers/planning_controller.php

require_once __DIR__ . '/../db.php';

class PlanningController {
    
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Récupère tous les événements (Métrages + Chantiers) pour une plage donnée
     */
    public function getEvents($start, $end) {
        $events = [];

        // 1. MÉTRAGES (Bleu)
        $sqlM = "SELECT m.id, m.date_prevue, m.statut, c.nom_principal, c.commentaire as ville
                 FROM metrage_interventions m
                 JOIN affaires a ON m.affaire_id = a.id
                 JOIN clients c ON a.client_id = c.id
                 WHERE m.date_prevue BETWEEN ? AND ? 
                 AND m.statut != 'TERMINE'";
        $stmtM = $this->pdo->prepare($sqlM);
        $stmtM->execute([$start, $end]);
        
        while ($row = $stmtM->fetch()) {
            $events[] = [
                'id' => 'MET_' . $row['id'],
                'title' => '📐 Métrage: ' . $row['nom_principal'],
                'start' => $row['date_prevue'],
                'color' => '#3788d8', // Bleu standard
                'url' => 'gestion_metrage_planning.php?id=' . $row['id'],
                'extendedProps' => [
                    'type' => 'METRAGE',
                    'ville' => $row['ville'],
                    'statut' => $row['statut'],
                    'url_fiche' => 'planning_fiche.php?type=METRAGE&id=' . $row['id']
                ]
            ];
        }

        // 2. POSE / CHANTIERS (Vert)
        // On exclut les chantiers sans date
        $sqlP = "SELECT a.id, a.nom_affaire, a.date_pose_debut, a.date_pose_fin, a.equipe_pose, c.nom_principal
                 FROM affaires a
                 JOIN clients c ON a.client_id = c.id
                 WHERE a.date_pose_debut <= ? AND a.date_pose_fin >= ?
                 AND a.statut_chantier NOT IN ('Terminé', 'Facturé')";
        
        // Note: Logic overlap dates is tricky in SQL simple, simplified coverage here
        // We look for any project active in the range
        $stmtP = $this->pdo->prepare($sqlP);
        $stmtP->execute([$end, $start]); // Intersection logic: Start <= EndReq AND End >= StartReq

        while ($row = $stmtP->fetch()) {
            
            // Si pas de date fin, on met date debut (1 jour)
            $endEvent = $row['date_pose_fin'] ? date('Y-m-d', strtotime($row['date_pose_fin'] . ' +1 day')) : $row['date_pose_debut'];

            $events[] = [
                'id' => 'POSE_' . $row['id'],
                'title' => '🔨 Pose: ' . $row['nom_principal'],
                'start' => $row['date_pose_debut'],
                'end' => $endEvent,
                'color' => '#28a745', // Vert Bootstrap
                'url' => 'affaires_detail.php?id=' . $row['id'],
                'extendedProps' => [
                    'type' => 'POSE',
                    'equipe' => $row['equipe_pose'] ?? 'Non assigné',
                    'url_fiche' => 'planning_fiche.php?type=POSE&id=' . $row['id']
                ]
            ];
        }

        return $events;
    }
}
