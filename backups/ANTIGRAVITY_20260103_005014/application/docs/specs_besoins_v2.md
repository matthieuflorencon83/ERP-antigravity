# Spécifications Architecture : Liste de Besoins V2 (Module Industriel)

## 1. Structure de Données (SQL)

### Mise à jour : `articles_catalogue` (Enrichissement Technique)

```sql
ALTER TABLE `articles_catalogue`
ADD COLUMN `longueurs_possibles_json` JSON DEFAULT NULL COMMENT 'Ex: [4700, 6500, 7000]',
ADD COLUMN `poids_metre_lineaire` DECIMAL(10,3) DEFAULT NULL COMMENT 'kg/ml',
ADD COLUMN `inertie_lx` DECIMAL(10,2) DEFAULT NULL COMMENT 'cm4 pour calcul flèche',
ADD COLUMN `articles_lies_json` JSON DEFAULT NULL COMMENT 'Ids accessoires suggérés';
```

### Création : `besoins_lignes` (Table Pivot Optimisée)

```sql
CREATE TABLE `besoins_lignes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `affaire_id` INT NOT NULL,
    `zone_chantier` VARCHAR(50) DEFAULT 'Non défini' COMMENT 'Façade, Toiture...',
    
    -- Le Besoin Brut (Ce que veut le client)
    `designation_besoin` VARCHAR(255) NOT NULL,
    `quantite_brute` INT NOT NULL DEFAULT 1,
    `longueur_unitaire_brute_mm` INT NOT NULL,
    
    -- La Solution (Ce qu''on achète)
    `article_catalogue_id` INT DEFAULT NULL,
    `modele_profil_id` INT DEFAULT NULL,
    `finition_id` INT DEFAULT NULL,
    
    -- Résultat Optimisation
    `longueur_barre_choisie_mm` INT DEFAULT NULL,
    `taux_chute` DECIMAL(5,2) DEFAULT NULL,
    
    `statut` ENUM('BROUILLON', 'OPTIMISE', 'VALIDE', 'COMMANDE') DEFAULT 'BROUILLON',
    `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`affaire_id`) REFERENCES `affaires`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 2. Algorithmes (Pseudo-Code)

### A. Moteur de Calepinage (`BarOptimization.php`)

```php
class BarOptimization {
    
    /**
     * Trouve la barre standard la plus économe pour un besoin donné
     */
    public function optimize(int $neededLength, array $availableLengths): array {
        // 1. Filtrer les barres trop courtes
        $candidates = array_filter($availableLengths, fn($l) => $l >= $neededLength);
        
        if (empty($candidates)) {
            return ['status' => 'ERROR', 'msg' => 'Aucune barre assez longue'];
        }
        
        // 2. Trier par longueur croissante (Cheapest First heuristic)
        sort($candidates);
        $bestBar = $candidates[0];
        
        // 3. Calculer chute
        $waste = $bestBar - $neededLength;
        $wastePercent = ($waste / $bestBar) * 100;
        
        return [
            'status' => 'OK',
            'recommended_bar' => $bestBar,
            'waste_mm' => $waste,
            'waste_percent' => round($wastePercent, 2)
        ];
    }
    
    /**
     * Optimisation de Groupe (Bin Packing 1D)
     * Pour commander moins de barres en combinant plusieurs coupes
     */
    public function optimizeBatch(array $cuts, array $stockLengths) {
        // Algorithme Best Fit Decreasing
        // ... (À implémenter en V2.1)
    }
}
```

### B. Assistant de Cohérence (`VerificationEngine.php`)

```php
class VerificationEngine {
    
    public function checkConsistency(array $lineItem): array {
        $alerts = [];
        
        // Règle 1 : Flèche Structurelle
        if ($this->isStructuralProfile($lineItem) && empty($lineItem['renfort_acier'])) {
            $limit = 3500; // mm
            if ($lineItem['longueur_brute'] > $limit) {
                $alerts[] = [
                    'severity' => 'WARNING', 
                    'msg' => "Risque de flèche : Longueur {$lineItem['longueur_brute']}mm > {$limit}mm sans renfort."
                ];
            }
        }
        
        // Règle 2 : Associations Manquantes
        if ($lineItem['type'] === 'CHEVRON') {
            // Check si des embouts existent dans la commande globale
            if (!$this->hasAssociatedProduct($lineItem['affaire_id'], 'EMBOUT_CHEVRON')) {
                 $alerts[] = ['severity' => 'INFO', 'msg' => "Pensez aux embouts de chevron !"];
            }
        }
        
        return $alerts;
    }
}
```

## 3. Analyse UX (Saisie Haute Performance)

Pour permettre la saisie de 200 lignes sans fatigue :

1. **Architecture "Entonnoir" (Funnel Input)** :
    * Ne jamais afficher tous les articles d'un coup.
    * Séquence : `Zone` (Global) -> `Famille` (Icones) -> `Article` (Liste filtrée).

2. **Navigation au Clavier (`Keyboard First`)** :
    * `Tab` pour passer champ à champ.
    * `Entrée` valide la ligne et **rouvre immédiatement** une nouvelle ligne vide avec les mêmes paramètres de Zone/Famille (pour enchaîner les saisies similaires).

3. **Feedback Visuel Immédiat** :
    * Dès la saisie de la longueur, le calcul de chute s'affiche en petit à côté (Feedback positif : "Optimisé !").
    * Code couleur : Vert (OK), Orange (Info/Conseil), Rouge (Erreur bloquante).

4. **Mode "Duplication Rapide"** :
    * Bouton `x2` ou `Dupliquer` en bout de ligne pour cloner un besoin complexe (ex: Traverse avec usinage spécial) sans tout retaper.
