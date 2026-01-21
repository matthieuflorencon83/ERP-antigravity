<?php
// classes/TemplateEngine.php
// Moteur de templates avec remplacement de variables

class TemplateEngine {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Récupérer tous les templates
     */
    public function getTemplates($categorie = null) {
        if ($categorie) {
            $stmt = $this->pdo->prepare("SELECT * FROM email_templates WHERE categorie = ? ORDER BY nom");
            $stmt->execute([$categorie]);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM email_templates ORDER BY categorie, nom");
        }
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer un template par ID
     */
    public function getTemplate($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM email_templates WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Rendre un template avec des variables
     * 
     * @param int $templateId ID du template
     * @param array $variables Variables à remplacer ['NOM_CLIENT' => 'Dupont', ...]
     * @return array ['subject' => string, 'body' => string]
     */
    public function render($templateId, $variables) {
        $template = $this->getTemplate($templateId);
        
        if (!$template) {
            return ['error' => 'Template introuvable'];
        }
        
        $subject = $template['sujet'];
        $body = $template['corps'];
        
        // Remplacer les variables
        foreach ($variables as $key => $value) {
            $placeholder = '{' . strtoupper($key) . '}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }
        
        return [
            'subject' => $subject,
            'body' => $body,
            'template_name' => $template['nom']
        ];
    }
    
    /**
     * Rendre un template pour un client spécifique
     * Récupère automatiquement les données du client
     */
    public function renderForClient($templateId, $clientId, $extraVars = []) {
        // Récupérer les données du client
        $stmt = $this->pdo->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch();
        
        if (!$client) {
            return ['error' => 'Client introuvable'];
        }
        
        // Variables par défaut du client
        $variables = [
            'NOM_CLIENT' => $client['nom_principal'],
            'EMAIL_CLIENT' => $client['email'],
            'TEL_CLIENT' => $client['telephone'],
            'ADRESSE_CLIENT' => $client['adresse']
        ];
        
        // Fusionner avec les variables supplémentaires
        $variables = array_merge($variables, $extraVars);
        
        return $this->render($templateId, $variables);
    }
    
    /**
     * Rendre un template pour une affaire spécifique
     * Récupère automatiquement les données de l'affaire et du client
     */
    public function renderForAffaire($templateId, $affaireId, $extraVars = []) {
        // Récupérer l'affaire avec le client
        $stmt = $this->pdo->prepare("
            SELECT a.*, c.nom_principal, c.email, c.telephone, c.adresse
            FROM affaires a
            JOIN clients c ON a.client_id = c.id
            WHERE a.id = ?
        ");
        $stmt->execute([$affaireId]);
        $affaire = $stmt->fetch();
        
        if (!$affaire) {
            return ['error' => 'Affaire introuvable'];
        }
        
        // Variables par défaut
        $variables = [
            'NOM_CLIENT' => $affaire['nom_principal'],
            'EMAIL_CLIENT' => $affaire['email'],
            'NOM_AFFAIRE' => $affaire['nom_affaire'],
            'ADRESSE_CHANTIER' => $affaire['adresse_chantier'] ?? $affaire['adresse'],
            'MONTANT_TTC' => number_format($affaire['montant_total_ttc'] ?? 0, 2, ',', ' ')
        ];
        
        // Fusionner avec les variables supplémentaires
        $variables = array_merge($variables, $extraVars);
        
        return $this->render($templateId, $variables);
    }
    
    /**
     * Créer un nouveau template
     */
    public function createTemplate($nom, $categorie, $sujet, $corps, $variables) {
        $stmt = $this->pdo->prepare("
            INSERT INTO email_templates (nom, categorie, sujet, corps, variables)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nom,
            $categorie,
            $sujet,
            $corps,
            json_encode($variables)
        ]);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Mettre à jour un template
     */
    public function updateTemplate($id, $nom, $categorie, $sujet, $corps, $variables) {
        $stmt = $this->pdo->prepare("
            UPDATE email_templates 
            SET nom = ?, categorie = ?, sujet = ?, corps = ?, variables = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $nom,
            $categorie,
            $sujet,
            $corps,
            json_encode($variables),
            $id
        ]);
    }
    
    /**
     * Supprimer un template
     */
    public function deleteTemplate($id) {
        $stmt = $this->pdo->prepare("DELETE FROM email_templates WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
