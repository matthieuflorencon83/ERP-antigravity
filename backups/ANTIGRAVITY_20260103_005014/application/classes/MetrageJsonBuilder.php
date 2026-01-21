<?php
// classes/MetrageJsonBuilder.php
// "Architecte de Données" pour le format JSON V3

class MetrageJsonBuilder {
    private $data = [
        'meta' => [],
        'dimensions' => [],
        'environnement' => [],
        'specs_techniques' => [],
        'media_evidence' => [],
        'validation' => []
    ];

    public function __construct() {
        // Init default structure
        $this->data['validation'] = [
            'alertes_acquittees' => [],
            'checklists_completed' => false,
            'ecart_prix_valide' => true
        ];
    }

    public function setMeta($productSlug, $productName, $userId = 0) {
        $this->data['meta'] = [
            'product_slug' => $productSlug,
            'product_name' => $productName,
            'version_schema' => '3.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $userId
        ];
    }

    // Populate Dimensions mostly from numeric fields
    public function setDimensions($postData) {
        // Filter keys starting with 'largeur', 'hauteur', 'prof', 'avancee', 'cote', 'retombee', 'ecoincon'
        foreach ($postData as $k => $v) {
            if ($this->isDimensionKey($k) && $v !== '') {
                $this->data['dimensions'][$k] = floatval($v); // Force float for calc
            }
        }
    }

    // Populate Environnement mostly from select/radio context
    public function setEnvironnement($postData) {
        $envKeys = ['pose', 'support', 'acces', 'etat_dormant', 'seuil', 'obstacle', 'isolation', 'vmc'];
        foreach ($postData as $k => $v) {
            if ($this->matchesKeys($k, $envKeys) && $v !== '') {
                $this->data['environnement'][$k] = $v;
            }
        }
    }

    // Populate Specs (Motorisation, Couleur, Options)
    public function setSpecs($postData) {
        $specKeys = ['moteur', 'manoeuvre', 'couleur', 'coloris', 'option', 'vitrage', 'remplissage', 'aile', 'habillage'];
        foreach ($postData as $k => $v) {
            if ($this->matchesKeys($k, $specKeys) && $v !== '') {
                $this->data['specs_techniques'][$k] = $v;
            }
        }
    }

    // Populate Geometry (Shape, Subtype, Specific Dimensions)
    public function setGeometry($postData) {
        if (!empty($postData['forme_type']) && $postData['forme_type'] === 'SPECIAL') {
            $this->data['geometrie'] = [
                'type' => $postData['forme_subtype'] ?? 'INCONNU',
                'cotes' => [],
                'is_special' => true
            ];
            
            // Extract geometry specific dims (h1, h2, fleche, etc.)
            foreach ($postData as $k => $v) {
                if (strpos($k, 'cote_') === 0 || $k === 'fleche') {
                    $this->data['geometrie']['cotes'][$k] = floatval($v);
                }
            }
            // Check Gabarit
            if (!empty($postData['gabarit_trace'])) {
                $this->data['geometrie']['gabarit_ready'] = true;
            }
        } else {
             $this->data['geometrie'] = ['type' => 'RECTANGLE', 'is_special' => false];
        }
    }

    // Populate Quality & Finishings (VMC, Obstacles)
    public function setQuality($postData) {
        // VMC
        if (isset($postData['vmc']) && $postData['vmc'] === 'OUI') {
            $this->data['qualite']['vmc'] = [
                'active' => true,
                'piece' => $postData['vmc_piece'] ?? '', // Note: Need to capture this in JS/POST
                'surface' => floatval($postData['vmc_surface'] ?? 0),
                'debit_calcule' => $postData['vmc_debit_ref'] ?? '',
                'couleur' => $postData['vmc_couleur'] ?? 'BLANC'
            ];
        }
        
        // Obstacles
        if (!empty($postData['obstacle_plinthe']) || !empty($postData['obstacle_radiateur'])) {
            $this->data['qualite']['obstacles'] = [
                'plinthe_mm' => floatval($postData['obstacle_plinthe'] ?? 0),
                'radiateur_mm' => floatval($postData['obstacle_radiateur'] ?? 0)
            ];
        }

        // Simu Reno
        if (!empty($postData['larg_dormant_existant'])) {
             $this->data['qualite']['reno_dormant'] = [
                 'existant' => floatval($postData['larg_dormant_existant']),
                 'aile_choisie' => floatval($postData['aile_reno'] ?? 0),
                 'alerte_declenchee' => (floatval($postData['larg_dormant_existant']) > floatval($postData['aile_reno'] ?? 0))
             ];
        }
    }

    // Populate Media (Photos, Base64) with basic validation
    public function setMedia($postData) {
        foreach ($postData as $k => $v) {
            if (strpos($k, 'photo_') !== false && !empty($v)) {
                // Should ideally check if it is valid base64 or URL
                $this->data['media_evidence'][$k] = $v;
            }
        }
    }
    
    // Explicit Validation Flags
    public function setValidation($alerts, $checklistStatus) {
        if (is_array($alerts)) $this->data['validation']['alertes_acquittees'] = $alerts;
        $this->data['validation']['checklists_completed'] = (bool)$checklistStatus;
    }
    
    // --- UPDATED HELPERS ---
    public function setGeneric($postData) {
        // Catch-all for fields not manually mapped, useful for future proofing
        $this->data['form_fields'] = $postData; 
    }

    public function build() {
        return json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // --- Helpers ---
    private function isDimensionKey($k) {
        $keywords = ['largeur', 'hauteur', 'prof', 'avancee', 'cote', 'retombee', 'ecoincon', 'passage', 'tableau', 'fabrication'];
        foreach ($keywords as $kw) {
            if (stripos($k, $kw) !== false) return true;
        }
        return false;
    }

    private function matchesKeys($k, $keywords) {
        foreach ($keywords as $kw) {
            if (stripos($k, $kw) !== false) return true;
        }
        return false;
    }
}
?>
