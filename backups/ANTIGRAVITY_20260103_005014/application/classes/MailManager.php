<?php
// classes/MailManager.php
// Gestionnaire d'emails avec SMTP/IMAP

class MailManager {
    private $config;
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->config = require __DIR__ . '/../config/mail_config.php';
    }
    
    /**
     * Envoyer un email via SMTP
     * 
     * @param string $to Destinataire
     * @param string $subject Sujet
     * @param string $body Corps HTML
     * @param array $attachments Pièces jointes [{path, name}]
     * @param int $affaireId ID affaire (optionnel)
     * @param int $clientId ID client (optionnel)
     * @return array ['success' => bool, 'message' => string]
     */
    public function sendEmail($to, $subject, $body, $attachments = [], $affaireId = null, $clientId = null) {
        try {
            // Validation
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'error' => 'Email invalide'];
            }
            
            // Check rate limit
            if (!$this->checkRateLimit()) {
                return ['success' => false, 'error' => 'Limite d\'envoi atteinte (50/heure)'];
            }
            
            // Préparer les headers
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $this->config['smtp']['from_name'] . ' <' . $this->config['smtp']['from_email'] . '>',
                'Reply-To: ' . $this->config['smtp']['from_email']
            ];
            
            // Note: Cette version utilise mail() PHP
            // Pour une version production, installer PHPMailer via Composer
            $result = mail($to, $subject, $body, implode("\r\n", $headers));
            
            if ($result) {
                // Sauvegarder dans l'historique
                $this->saveSentEmail($to, $subject, $body, $attachments, $affaireId, $clientId);
                return ['success' => true, 'message' => 'Email envoyé'];
            } else {
                return ['success' => false, 'error' => 'Erreur d\'envoi'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Récupérer la boîte de réception via IMAP
     * 
     * @param int $limit Nombre d'emails
     * @param int $offset Décalage
     * @return array Liste des emails
     */
    public function getInbox($limit = 50, $offset = 0) {
        // Vérifier le cache
        if ($this->config['cache']['enabled']) {
            $cached = $this->getCachedEmails('inbox', $limit, $offset);
            if ($cached) return $cached;
        }
        
        // Connexion IMAP
        $inbox = @imap_open(
            $this->config['imap']['host'],
            $this->config['imap']['username'],
            $this->config['imap']['password']
        );
        
        if (!$inbox) {
            return ['error' => 'Connexion IMAP impossible: ' . imap_last_error()];
        }
        
        $emails = [];
        $total = imap_num_msg($inbox);
        
        if ($total == 0) {
            imap_close($inbox);
            return [];
        }
        
        $start = max(1, $total - $offset - $limit + 1);
        $end = $total - $offset;
        
        for ($i = $end; $i >= $start; $i--) {
            $header = imap_headerinfo($inbox, $i);
            $structure = imap_fetchstructure($inbox, $i);
            
            $from = $header->from[0];
            $fromEmail = $from->mailbox . '@' . $from->host;
            $fromName = isset($from->personal) ? $this->decodeMimeStr($from->personal) : $fromEmail;
            
            $emails[] = [
                'id' => $i,
                'from' => $fromEmail,
                'from_name' => $fromName,
                'subject' => $this->decodeMimeStr($header->subject ?? '(Sans sujet)'),
                'date' => date('Y-m-d H:i:s', strtotime($header->date)),
                'seen' => !isset($header->Unseen),
                'has_attachment' => isset($structure->parts) && count($structure->parts) > 1,
                'size' => $header->Size
            ];
        }
        
        imap_close($inbox);
        
        // Mettre en cache
        if ($this->config['cache']['enabled']) {
            $this->cacheEmails($emails, 'inbox');
        }
        
        return $emails;
    }
    
    /**
     * Lire un email complet
     * 
     * @param int $emailId ID de l'email
     * @return array Détails de l'email
     */
    public function getEmail($emailId) {
        $inbox = @imap_open(
            $this->config['imap']['host'],
            $this->config['imap']['username'],
            $this->config['imap']['password']
        );
        
        if (!$inbox) {
            return ['error' => 'Connexion IMAP impossible'];
        }
        
        $header = imap_headerinfo($inbox, $emailId);
        $body = $this->getEmailBody($inbox, $emailId);
        
        // Marquer comme lu
        imap_setflag_full($inbox, $emailId, "\\Seen");
        
        $from = $header->from[0];
        $email = [
            'id' => $emailId,
            'subject' => $this->decodeMimeStr($header->subject ?? '(Sans sujet)'),
            'from' => $from->mailbox . '@' . $from->host,
            'from_name' => isset($from->personal) ? $this->decodeMimeStr($from->personal) : '',
            'date' => date('Y-m-d H:i:s', strtotime($header->date)),
            'body' => $this->sanitizeHTML($body)
        ];
        
        imap_close($inbox);
        return $email;
    }
    
    /**
     * Récupérer les emails envoyés (depuis la base locale)
     */
    public function getSentEmails($limit = 50, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM email_sent 
            ORDER BY sent_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les emails d'un client spécifique
     */
    public function getClientEmails($clientId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM email_sent 
            WHERE client_id = ? 
            ORDER BY sent_at DESC
        ");
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les emails d'une affaire spécifique
     */
    public function getAffaireEmails($affaireId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM email_sent 
            WHERE affaire_id = ? 
            ORDER BY sent_at DESC
        ");
        $stmt->execute([$affaireId]);
        return $stmt->fetchAll();
    }
    
    // ========== MÉTHODES PRIVÉES ==========
    
    private function saveSentEmail($to, $subject, $body, $attachments, $affaireId, $clientId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO email_sent (recipient, subject, body, sent_at, attachments, affaire_id, client_id, user_id)
            VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)
        ");
        $stmt->execute([
            $to,
            $subject,
            $body,
            json_encode($attachments),
            $affaireId,
            $clientId,
            $_SESSION['user_id'] ?? null
        ]);
    }
    
    private function checkRateLimit() {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM email_sent 
            WHERE sent_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'] < $this->config['limits']['max_emails_per_hour'];
    }
    
    private function getCachedEmails($folder, $limit, $offset) {
        $stmt = $this->pdo->prepare("
            SELECT email_data FROM email_cache 
            WHERE folder = ? 
            AND cached_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ORDER BY cached_at DESC LIMIT 1
        ");
        $stmt->execute([$folder, $this->config['cache']['ttl']]);
        $result = $stmt->fetch();
        
        if ($result) {
            return json_decode($result['email_data'], true);
        }
        return null;
    }
    
    private function cacheEmails($emails, $folder) {
        // Supprimer l'ancien cache
        $this->pdo->exec("DELETE FROM email_cache WHERE folder = '$folder'");
        
        // Insérer le nouveau
        $stmt = $this->pdo->prepare("
            INSERT INTO email_cache (folder, email_data, cached_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$folder, json_encode($emails)]);
    }
    
    private function getEmailBody($inbox, $emailId) {
        $structure = imap_fetchstructure($inbox, $emailId);
        
        if (!isset($structure->parts)) {
            // Email simple
            return imap_body($inbox, $emailId);
        }
        
        // Email multipart
        foreach ($structure->parts as $partNum => $part) {
            if ($part->subtype == 'HTML') {
                return imap_fetchbody($inbox, $emailId, $partNum + 1);
            }
        }
        
        // Fallback: texte brut
        return imap_fetchbody($inbox, $emailId, 1);
    }
    
    private function sanitizeHTML($html) {
        // Utiliser le sanitizer avancé
        require_once __DIR__ . '/../core/HTMLSanitizer.php';
        return HTMLSanitizer::clean($html, false); // Pas d'images pour la sécurité
    }
    
    private function decodeMimeStr($str) {
        $decoded = imap_mime_header_decode($str);
        $result = '';
        foreach ($decoded as $part) {
            $result .= $part->text;
        }
        return $result;
    }
}
