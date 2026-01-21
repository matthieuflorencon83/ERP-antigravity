<?php
// core/approval.php
require_once __DIR__ . '/../db.php';

class ActionManager {
    
    private $pdo;
    private $user_id;
    private $user_role;

    public function __construct($pdo, $user_id, $user_role) {
        $this->pdo = $pdo;
        $this->user_id = $user_id;
        $this->user_role = $user_role;
    }

    /**
     * Determines if the action should be executed immediately or queued.
     * 
     * @param string $actionType 'DELETE', 'UPDATE', 'INSERT'
     * @param string $table Target table
     * @param int $id Target ID (or 0 if INSERT)
     * @param string $sql The SQL to execute if authorized
     * @param array $params Params for the SQL
     * @param string $description Optional description for the admin
     * 
     * @return array ['executed' => bool, 'pending' => bool, 'message' => string]
     */
    public function requestOrExecute($actionType, $table, $id, $sql, $params, $description = "") {
        
        // 1. ADMIN EXECUTE DIRECTLY
        if ($this->user_role === 'ADMIN') {
            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return ['executed' => true, 'pending' => false, 'message' => 'Action effectuée avec succès.'];
            } catch (Exception $e) {
                return ['executed' => false, 'pending' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()];
            }
        }

        // 2. OTHERS (POSEUR, SECRETAIRE) -> QUEUE REQUEST
        // Prepare JSON payload of what they WANTED to do
        $payload = json_encode([
            'sql' => $sql,
            'params' => $params,
            'description' => $description
        ]);

        try {
            $sqlReq = "INSERT INTO admin_validations (user_id, type_action, table_concernee, id_enregistrement, donnees_json, statut) 
                       VALUES (?, ?, ?, ?, ?, 'PENDING')";
            $stmt = $this->pdo->prepare($sqlReq);
            $stmt->execute([$this->user_id, $actionType, $table, $id, $payload]);
            
            return ['executed' => false, 'pending' => true, 'message' => '⚠️ Action soumise à validation administrateur.'];
        } catch (Exception $e) {
            return ['executed' => false, 'pending' => false, 'message' => 'Erreur lors de la demande : ' . $e->getMessage()];
        }
    }
}
