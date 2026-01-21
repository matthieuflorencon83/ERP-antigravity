<?php
// core/RateLimiter.php
// Système de limitation de taux (Rate Limiting)

class RateLimiter {
    private $pdo;
    private $maxRequests;
    private $windowSeconds;
    
    public function __construct($pdo, $maxRequests = 100, $windowSeconds = 60) {
        $this->pdo = $pdo;
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }
    
    /**
     * Vérifie si l'utilisateur a dépassé la limite
     * @param string $identifier Identifiant unique (user_id, IP, etc.)
     * @param string $action Action spécifique (optionnel)
     * @return bool True si autorisé, False si limite atteinte
     */
    public function check($identifier, $action = 'global') {
        $this->cleanup();
        
        $key = $this->getKey($identifier, $action);
        
        // Compter les requêtes dans la fenêtre de temps
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM rate_limits 
            WHERE rate_key = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$key, $this->windowSeconds]);
        $result = $stmt->fetch();
        
        if ($result['count'] >= $this->maxRequests) {
            return false;
        }
        
        // Enregistrer la requête
        $stmt = $this->pdo->prepare("
            INSERT INTO rate_limits (rate_key, created_at) 
            VALUES (?, NOW())
        ");
        $stmt->execute([$key]);
        
        return true;
    }
    
    /**
     * Obtient le nombre de requêtes restantes
     * @param string $identifier
     * @param string $action
     * @return int
     */
    public function remaining($identifier, $action = 'global') {
        $key = $this->getKey($identifier, $action);
        
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM rate_limits 
            WHERE rate_key = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$key, $this->windowSeconds]);
        $result = $stmt->fetch();
        
        return max(0, $this->maxRequests - $result['count']);
    }
    
    /**
     * Génère une clé unique pour le rate limiting
     */
    private function getKey($identifier, $action) {
        return hash('sha256', $identifier . ':' . $action);
    }
    
    /**
     * Nettoie les anciennes entrées
     */
    private function cleanup() {
        // Nettoyer les entrées de plus de 1 heure (pour éviter la croissance infinie)
        $this->pdo->exec("
            DELETE FROM rate_limits 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
    }
    
    /**
     * Bloque une requête et retourne une réponse HTTP 429
     */
    public function block() {
        http_response_code(429);
        header('Content-Type: application/json');
        die(json_encode([
            'error' => 'Trop de requêtes. Veuillez réessayer dans quelques instants.',
            'retry_after' => $this->windowSeconds
        ]));
    }
}
