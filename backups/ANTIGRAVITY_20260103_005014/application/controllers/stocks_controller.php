<?php
declare(strict_types=1);

/**
 * controllers/stocks_controller.php
 * Gestion des Stocks & Mouvements
 */

require_once __DIR__ . '/../db.php';

class StocksController {
    
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Récupère l'inventaire complet avec les détails articles
     */
    public function getInventory(string $search = '', string $f_famille = '', string $f_fournisseur = ''): array {
        $sql = "SELECT ac.id as article_id, 
                       s.id as stock_id,
                       COALESCE(s.quantite, 0) as quantite,
                       COALESCE(s.emplacement, 'Atelier') as emplacement,
                       ac.ref_fournisseur, ac.reference_interne, ac.designation, ac.famille,
                       f.nom_couleur, f.code_ral,
                       fab.nom as fabricant_nom,
                       four.nom as nom_fournisseur
                FROM articles ac
                LEFT JOIN stocks s ON ac.id = s.article_id AND s.emplacement = 'Atelier'
                LEFT JOIN finitions f ON s.finition_id = f.id
                LEFT JOIN fabricants fab ON ac.fabricant_id = fab.id
                LEFT JOIN fournisseurs four ON ac.fournisseur_prefere_id = four.id
                WHERE 1=1";
        
        $params = [];

        if ($search) {
            $sql .= " AND (ac.ref_fournisseur LIKE ? OR ac.designation LIKE ? OR ac.reference_interne LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($f_famille) {
            $sql .= " AND ac.famille = ?";
            $params[] = $f_famille;
        }

        if ($f_fournisseur) {
            $sql .= " AND four.nom = ?";
            $params[] = $f_fournisseur;
        }
        
        $sql .= " ORDER BY ac.designation ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFamilies(): array {
        return $this->pdo->query("SELECT DISTINCT famille FROM articles WHERE famille IS NOT NULL ORDER BY famille")->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getFournisseurs(): array {
        return $this->pdo->query("SELECT DISTINCT nom FROM fournisseurs ORDER BY nom")->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getStockValue(): float {
        $sql = "SELECT SUM(s.quantite * ac.prix_achat_ht) as total_value
                FROM stocks s
                JOIN articles ac ON s.article_id = ac.id";
        return (float) $this->pdo->query($sql)->fetchColumn();
    }
    
    public function getArticleHistory(int $article_id, int $limit = 20): array {
        $sql = "SELECT sm.*, u.nom_complet as user_nom, a.nom_affaire
                FROM stocks_mouvements sm
                LEFT JOIN utilisateurs u ON sm.user_id = u.id
                LEFT JOIN affaires a ON sm.affaire_id = a.id
                WHERE sm.article_id = ?
                ORDER BY sm.date_mouvement DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$article_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStockByLocations(int $article_id): array {
        $sql = "SELECT emplacement, SUM(quantite) as qte
                FROM stocks
                WHERE article_id = ? AND quantite != 0
                GROUP BY emplacement
                ORDER BY emplacement";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$article_id]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Enregistre un mouvement de stock
     */
    public function createMovement(int $article_id, ?int $finition_id, string $type, float $qty, int $user_id, string $motif, ?int $affaire_id = null, string $emplacement = 'Atelier'): bool {
        if ($qty < 0 && $type !== 'INVENTAIRE') return false; 
        
        try {
            $this->pdo->beginTransaction();
            
            // Check existing stock
            $stmt = $this->pdo->prepare("SELECT id, quantite FROM stocks WHERE article_id=? AND (finition_id=? OR (finition_id IS NULL AND ? IS NULL)) AND emplacement=?");
            $stmt->execute([$article_id, $finition_id, $finition_id, $emplacement]);
            $existing_stock = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stock_id = $existing_stock['id'] ?? null;
            $current_qty = (float)($existing_stock['quantite'] ?? 0);

            $delta_qty = 0.0;

            if ($type === 'INVENTAIRE') {
                $delta_qty = $qty - $current_qty;
                if (abs($delta_qty) < 0.001) {
                    $this->pdo->rollBack();
                    return true;
                }
            } else {
                $delta_qty = ($type === 'SORTIE') ? -$qty : $qty;
            }

            // 1. Update Stock Table
            if ($stock_id) {
                $sql = "UPDATE stocks SET quantite = quantite + ?, date_derniere_maj = NOW() WHERE id=?";
                $this->pdo->prepare($sql)->execute([$delta_qty, $stock_id]);
            } else {
                $initial_qty = ($type === 'INVENTAIRE') ? $qty : $delta_qty;
                $sql = "INSERT INTO stocks (article_id, finition_id, quantite, emplacement, date_derniere_maj) VALUES (?, ?, ?, ?, NOW())";
                $this->pdo->prepare($sql)->execute([$article_id, $finition_id, $initial_qty, $emplacement]);
            }

            // 2. Trace Movement
            $log_type = $type;
            $log_qty = ($type === 'INVENTAIRE') ? abs($delta_qty) : $qty; 
            
            $sqlMvt = "INSERT INTO stocks_mouvements (article_id, finition_id, type_mouvement, quantite, date_mouvement, user_id, commentaire, affaire_id) 
                       VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)";
            $this->pdo->prepare($sqlMvt)->execute([$article_id, $finition_id, $log_type, $log_qty, $user_id, $motif, $affaire_id]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
