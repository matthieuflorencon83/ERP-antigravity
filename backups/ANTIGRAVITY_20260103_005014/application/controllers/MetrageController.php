<?php
/**
 * MetrageController.php - Contrôleur Backend Unifié V4.0
 * 
 * Architecture: Clean MVC avec validation stricte
 * Sécurité: PDO prepared statements, typage strict, sanitization
 * Offline: Support LocalStorage sync
 * 
 * @version 4.0.0
 * @date 2026-01-01
 */

declare(strict_types=1);

class MetrageController {
    
    private PDO $pdo;
    private int $userId;
    
    /**
     * Constructor
     */
    public function __construct(PDO $pdo, int $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }
    
    // =====================================================
    // CRÉATION & INITIALISATION
    // =====================================================
    
    /**
     * Créer une nouvelle intervention métrage
     * 
     * @param int|null $affaireId NULL = Métrage Libre
     * @return array {success: bool, id?: int, error?: string}
     */
    public function createIntervention(?int $affaireId = null): array {
        try {
            $this->pdo->beginTransaction();
            
            // Validation: Si affaire_id fourni, vérifier qu'elle existe
            if ($affaireId !== null) {
                $stmt = $this->pdo->prepare("SELECT id FROM affaires WHERE id = ?");
                $stmt->execute([$affaireId]);
                if (!$stmt->fetch()) {
                    throw new Exception("Affaire #{$affaireId} introuvable");
                }
            }
            
            // Insertion
            $stmt = $this->pdo->prepare("
                INSERT INTO metrage_interventions 
                (affaire_id, statut, technicien_id) 
                VALUES (?, 'A_PLANIFIER', ?)
            ");
            
            $stmt->execute([$affaireId, $this->userId]);
            $newId = (int) $this->pdo->lastInsertId();
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'id' => $newId,
                'message' => $affaireId ? "Métrage lié à l'affaire #{$affaireId}" : "Métrage libre créé"
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Lier un métrage existant à une affaire
     * 
     * @param int $interventionId
     * @param int $affaireId
     * @return array {success: bool, error?: string}
     */
    public function linkToAffaire(int $interventionId, int $affaireId): array {
        try {
            // Vérifier que l'intervention existe
            $stmt = $this->pdo->prepare("SELECT id FROM metrage_interventions WHERE id = ?");
            $stmt->execute([$interventionId]);
            if (!$stmt->fetch()) {
                throw new Exception("Intervention #{$interventionId} introuvable");
            }
            
            // Vérifier que l'affaire existe
            $stmt = $this->pdo->prepare("SELECT id FROM affaires WHERE id = ?");
            $stmt->execute([$affaireId]);
            if (!$stmt->fetch()) {
                throw new Exception("Affaire #{$affaireId} introuvable");
            }
            
            // Mise à jour
            $stmt = $this->pdo->prepare("
                UPDATE metrage_interventions 
                SET affaire_id = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$affaireId, $interventionId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // =====================================================
    // GESTION PRODUITS (LIGNES)
    // =====================================================
    
    /**
     * Ajouter un produit mesuré
     * 
     * @param int $interventionId
     * @param int $typeId
     * @param string $localisation
     * @param array $donneesJson Structure V3 complète
     * @return array {success: bool, id?: int, error?: string}
     */
    public function addLigne(
        int $interventionId, 
        int $typeId, 
        string $localisation, 
        array $donneesJson
    ): array {
        try {
            $this->pdo->beginTransaction();
            
            // Validation: Intervention existe
            $stmt = $this->pdo->prepare("SELECT id FROM metrage_interventions WHERE id = ?");
            $stmt->execute([$interventionId]);
            if (!$stmt->fetch()) {
                throw new Exception("Intervention #{$interventionId} introuvable");
            }
            
            // Validation: Type existe
            $stmt = $this->pdo->prepare("SELECT id FROM metrage_types WHERE id = ? AND actif = TRUE");
            $stmt->execute([$typeId]);
            if (!$stmt->fetch()) {
                throw new Exception("Type de produit #{$typeId} invalide ou inactif");
            }
            
            // Validation JSON V3
            $this->validateJsonV3($donneesJson);
            
            // Sanitization localisation
            $localisation = htmlspecialchars(trim($localisation), ENT_QUOTES, 'UTF-8');
            if (empty($localisation)) {
                throw new Exception("La localisation est obligatoire");
            }
            
            // Ordre automatique (dernier + 1)
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(MAX(ordre), 0) + 1 AS next_ordre 
                FROM metrage_lignes 
                WHERE intervention_id = ?
            ");
            $stmt->execute([$interventionId]);
            $ordre = (int) $stmt->fetchColumn();
            
            // Insertion
            $stmt = $this->pdo->prepare("
                INSERT INTO metrage_lignes 
                (intervention_id, metrage_type_id, localisation, ordre, donnees_json) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $jsonEncoded = json_encode($donneesJson, JSON_UNESCAPED_UNICODE);
            $stmt->execute([$interventionId, $typeId, $localisation, $ordre, $jsonEncoded]);
            
            $newId = (int) $this->pdo->lastInsertId();
            
            // Mise à jour timestamp intervention
            $this->pdo->prepare("UPDATE metrage_interventions SET updated_at = NOW() WHERE id = ?")
                      ->execute([$interventionId]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'id' => $newId
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Mettre à jour un produit existant
     * 
     * @param int $ligneId
     * @param array $donneesJson Structure V3 complète
     * @return array {success: bool, error?: string}
     */
    public function updateLigne(int $ligneId, array $donneesJson): array {
        try {
            // Validation JSON V3
            $this->validateJsonV3($donneesJson);
            
            // Vérifier existence
            $stmt = $this->pdo->prepare("SELECT intervention_id FROM metrage_lignes WHERE id = ?");
            $stmt->execute([$ligneId]);
            $result = $stmt->fetch();
            if (!$result) {
                throw new Exception("Ligne #{$ligneId} introuvable");
            }
            
            // Mise à jour
            $stmt = $this->pdo->prepare("
                UPDATE metrage_lignes 
                SET donnees_json = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $jsonEncoded = json_encode($donneesJson, JSON_UNESCAPED_UNICODE);
            $stmt->execute([$jsonEncoded, $ligneId]);
            
            // Mise à jour timestamp intervention
            $this->pdo->prepare("UPDATE metrage_interventions SET updated_at = NOW() WHERE id = ?")
                      ->execute([$result['intervention_id']]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Supprimer un produit
     * 
     * @param int $ligneId
     * @return array {success: bool, error?: string}
     */
    public function deleteLigne(int $ligneId): array {
        try {
            $this->pdo->beginTransaction();
            
            // Récupérer intervention_id avant suppression
            $stmt = $this->pdo->prepare("SELECT intervention_id FROM metrage_lignes WHERE id = ?");
            $stmt->execute([$ligneId]);
            $result = $stmt->fetch();
            if (!$result) {
                throw new Exception("Ligne #{$ligneId} introuvable");
            }
            
            // Suppression
            $stmt = $this->pdo->prepare("DELETE FROM metrage_lignes WHERE id = ?");
            $stmt->execute([$ligneId]);
            
            // Mise à jour timestamp intervention
            $this->pdo->prepare("UPDATE metrage_interventions SET updated_at = NOW() WHERE id = ?")
                      ->execute([$result['intervention_id']]);
            
            $this->pdo->commit();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // =====================================================
    // RÉCUPÉRATION DONNÉES
    // =====================================================
    
    /**
     * Récupérer une intervention complète
     * 
     * @param int $interventionId
     * @return array|null
     */
    public function getIntervention(int $interventionId): ?array {
        $stmt = $this->pdo->prepare("
            SELECT 
                i.*,
                a.nom_affaire,
                a.numero_prodevis,
                c.nom_principal AS client_nom,
                c.telephone_mobile AS client_tel,
                u.login AS technicien_nom
            FROM metrage_interventions i
            LEFT JOIN affaires a ON i.affaire_id = a.id
            LEFT JOIN clients c ON a.client_id = c.id
            LEFT JOIN utilisateurs u ON i.technicien_id = u.id
            WHERE i.id = ?
        ");
        
        $stmt->execute([$interventionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }
    
    /**
     * Récupérer toutes les lignes d'une intervention
     * 
     * @param int $interventionId
     * @return array
     */
    public function getLignes(int $interventionId): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                l.*,
                t.nom AS type_nom,
                t.categorie,
                t.slug AS type_slug
            FROM metrage_lignes l
            JOIN metrage_types t ON l.metrage_type_id = t.id
            WHERE l.intervention_id = ?
            ORDER BY l.ordre ASC, l.id ASC
        ");
        
        $stmt->execute([$interventionId]);
        $lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Décoder JSON pour chaque ligne
        foreach ($lignes as &$ligne) {
            $ligne['donnees_json'] = json_decode($ligne['donnees_json'], true);
        }
        
        return $lignes;
    }
    
    /**
     * Récupérer tous les types de produits actifs
     * 
     * @return array
     */
    public function getTypes(): array {
        $stmt = $this->pdo->query("
            SELECT id, slug, nom, categorie, description
            FROM metrage_types
            WHERE actif = TRUE
            ORDER BY categorie, nom
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // =====================================================
    // VALIDATION & SÉCURITÉ
    // =====================================================
    
    /**
     * Valider la structure JSON V3 (APEX - Strict Validation)
     * 
     * @param array $json
     * @throws Exception si invalide
     */
    private function validateJsonV3(array $json): void {
        // Vérifier sections obligatoires
        $requiredSections = ['dimensions', 'technique', 'metadata'];
        foreach ($requiredSections as $section) {
            if (!isset($json[$section]) || !is_array($json[$section])) {
                throw new Exception("Section '{$section}' manquante ou invalide dans JSON V3");
            }
        }
        
        // APEX: Validation stricte dimensions
        if (!isset($json['dimensions']['largeur']) || !isset($json['dimensions']['hauteur'])) {
            throw new Exception("Dimensions largeur/hauteur obligatoires");
        }
        
        $largeur = $json['dimensions']['largeur'];
        $hauteur = $json['dimensions']['hauteur'];
        
        // Vérifier types numériques
        if (!is_numeric($largeur) || !is_numeric($hauteur)) {
            throw new Exception("Les dimensions doivent être numériques");
        }
        
        // APEX: Limites physiques (DTU)
        $largeur = (int) $largeur;
        $hauteur = (int) $hauteur;
        
        if ($largeur < 100 || $largeur > 10000) {
            throw new Exception("Largeur hors limites (100-10000mm)");
        }
        
        if ($hauteur < 100 || $hauteur > 10000) {
            throw new Exception("Hauteur hors limites (100-10000mm)");
        }
        
        // APEX: Validation forme
        $formesValides = ['rectangle', 'trapeze', 'cintre'];
        if (isset($json['dimensions']['forme']) && !in_array($json['dimensions']['forme'], $formesValides, true)) {
            throw new Exception("Forme invalide (autorisées: " . implode(', ', $formesValides) . ")");
        }
        
        // APEX: Sanitization metadata (XSS Protection)
        if (isset($json['metadata']['notes'])) {
            $notes = $json['metadata']['notes'];
            if (!is_string($notes)) {
                throw new Exception("metadata.notes doit être une chaîne");
            }
            
            // Limite longueur
            if (strlen($notes) > 500) {
                throw new Exception("metadata.notes max 500 caractères");
            }
            
            // Sanitize (protection XSS)
            $json['metadata']['notes'] = htmlspecialchars($notes, ENT_QUOTES, 'UTF-8');
        }
        
        // Vérifier version schema
        if (!isset($json['metadata']['version_schema']) || $json['metadata']['version_schema'] !== '3.0') {
            throw new Exception("Version schema JSON doit être '3.0'");
        }
        
        // APEX: Validation technique (si présente)
        if (isset($json['technique']['materiau'])) {
            $materiauxValides = ['alu', 'pvc', 'acier', 'bois'];
            if (!in_array($json['technique']['materiau'], $materiauxValides, true)) {
                throw new Exception("Matériau invalide");
            }
        }
        
        if (isset($json['technique']['vitrage'])) {
            $vitragesValides = ['simple', 'double', 'triple'];
            if (!in_array($json['technique']['vitrage'], $vitragesValides, true)) {
                throw new Exception("Vitrage invalide");
            }
        }
    }
    
    /**
     * Vérifier les permissions utilisateur
     * 
     * @param int $interventionId
     * @return bool
     */
    public function canEdit(int $interventionId): bool {
        $stmt = $this->pdo->prepare("
            SELECT technicien_id 
            FROM metrage_interventions 
            WHERE id = ?
        ");
        $stmt->execute([$interventionId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        // Admin peut tout éditer, sinon seulement ses propres métrages
        // TODO: Vérifier rôle admin depuis session
        return $result['technicien_id'] === $this->userId || $this->isAdmin();
    }
    
    /**
     * Vérifier si l'utilisateur est admin
     * 
     * @return bool
     */
    private function isAdmin(): bool {
        // TODO: Implémenter vérification rôle depuis session
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'ADMIN';
    }
    
    /**
     * Vérifier les permissions de lecture (moins strict que canEdit)
     * 
     * @param int $interventionId
     * @return bool
     */
    public function canView(int $interventionId): bool {
        $stmt = $this->pdo->prepare("
            SELECT i.technicien_id, a.client_id
            FROM metrage_interventions i
            LEFT JOIN affaires a ON i.affaire_id = a.id
            WHERE i.id = ?
        ");
        $stmt->execute([$interventionId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        // Admin peut tout voir
        if ($this->isAdmin()) {
            return true;
        }
        
        // Technicien peut voir ses propres métrages
        if ($result['technicien_id'] === $this->userId) {
            return true;
        }
        
        // TODO: Vérifier si l'utilisateur est commercial de l'affaire
        // Pour l'instant, autoriser lecture si métrage lié à une affaire
        return $result['client_id'] !== null;
    }
    
    /**
     * Vérifier permissions édition d'une ligne spécifique
     * 
     * @param int $ligneId
     * @return bool
     */
    public function canEditLigne(int $ligneId): bool {
        $stmt = $this->pdo->prepare("
            SELECT i.technicien_id
            FROM metrage_lignes l
            JOIN metrage_interventions i ON l.intervention_id = i.id
            WHERE l.id = ?
        ");
        $stmt->execute([$ligneId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        // Admin ou propriétaire de l'intervention
        return $result['technicien_id'] === $this->userId || $this->isAdmin();
    }
}
