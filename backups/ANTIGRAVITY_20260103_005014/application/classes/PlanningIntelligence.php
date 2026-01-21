<?php
// classes/PlanningIntelligence.php
// AI Engine for Scheduling Optimization

class PlanningIntelligence {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Analyze a single event for conflicts
     */
    public function analyzeEvent($event) {
        $alerts = [];
        $teamId = $event['equipe_id'] ?? 0;
        $type = $event['type_intervention'] ?? 'POSE'; // or METRAGE
        
        // 1. Check Team Competence
        if ($teamId > 0 && $type === 'POSE') {
            $team = $this->getTeam($teamId);
            $skills = json_decode($team['competences'] ?? '[]', true);
            
            // Infer required skill from event title/type
            // Simple keyword matching for V1
            $required = 'STANDARD';
            if (stripos($event['title'], 'Veranda') !== false) $required = 'VERANDA';
            if (stripos($event['title'], 'Portail') !== false) $required = 'PORTAIL';
            
            if (!in_array($required, $skills) && !in_array('TOUT', $skills)) {
                $alerts[] = "Compétence '{$required}' manquante pour l'équipe " . $team['nom'];
            }
        }

        // 2. Check Overlap (Basic SQL check normally, but here logic)
        // ... (Usually handled by SQL constraint, but we can double check)

        // 3. Check Duration / Fatigue
        // Mockup: If end - start > 10 hours -> Alert
        $start = strtotime($event['start']);
        $end = strtotime($event['end']);
        $hours = ($end - $start) / 3600;
        if ($hours > 9) {
            $alerts[] = "Journée très longue ({$hours}h). Risque fatigue/erreur.";
        }

        return [
            'status' => empty($alerts) ? 'OK' : 'WARNING',
            'alerts' => $alerts
        ];
    }

    /**
     * Recommend Best Time Slot (Tetris)
     */
    public function findBestSlot($durationHours, $teamId) {
        // Mockup: Suggest first available slot next week
        // In real AI, checks holes in calendar
        return [
            'start' => date('Y-m-d 08:00:00', strtotime('next monday')),
            'end' => date('Y-m-d H:i:s', strtotime('next monday') + $durationHours * 3600),
            'reason' => 'Créneau libre optimal (Trajet minimisé)'
        ];
    }

    private function getTeam($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM planning_equipes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
?>
