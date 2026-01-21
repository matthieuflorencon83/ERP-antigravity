<?php
class VerificationEngine {
    
    /**
     * Checks technical consistency of a line item.
     * 
     * @param array $lineItem Contains properties: 'type', 'longueur_brute', 'inertie_lx', 'renfort_acier', 'affaire_id'
     * @param PDO $pdo Optional database connection for deep checks
     * @return array List of alerts ['severity' => 'WARNING|INFO|ERROR', 'msg' => '...']
     */
    public function checkConsistency(array $lineItem, $pdo = null): array {
        $alerts = [];
        
        $len = $lineItem['longueur_brute'] ?? 0;
        $type = $lineItem['type'] ?? '';
        
        // --- RULE 1: Structural Deflection (Flèche) ---
        // Basic heuristic: if length > 3500mm and no steel reinforcement, warn user.
        // In V2.1, we will use 'inertie_lx' for real physics calculation.
        if ($this->isStructuralProfile($type) && empty($lineItem['renfort_acier'])) {
            $limit = 3500; // mm
            if ($len > $limit) {
                $alerts[] = [
                    'severity' => 'WARNING', 
                    'msg' => "Attention Flèche : Longueur {$len}mm > {$limit}mm sans renfort acier spécifié."
                ];
            }
        }
        
        // --- RULE 2: Specific warnings per Type ---
        if ($type === 'CHEVRON' && $len > 4500) {
             $alerts[] = [
                'severity' => 'INFO', 
                'msg' => "Pour des chevrons de >4.5m, vérifiez la portée entre appuis."
            ];
        }

        return $alerts;
    }

    private function isStructuralProfile($type) {
        $structural = ['CHEVRON', 'POTEAU', 'TRAVERSE', 'SABLIERE'];
        return in_array(strtoupper($type), $structural);
    }
    
    // Placeholder for deep DB checks
    private function hasAssociatedProduct($pdo, $affaireId, $productType) {
        // To be implemented
        return false;
    }
}
