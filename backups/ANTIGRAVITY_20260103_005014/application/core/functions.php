<?php
/**
 * functions.php
 * Boîte à outils commune pour Antigravity.
 * 
 * REFACTORED (Audit 2025-12-25):
 * - Ajout CSRF protection
 * - Ajout formatage prix
 * - Ajout génération référence
 * - Types de retour PHP 8
 * - Ajout En-têtes de Sécurité (Secure Headers)
 * 
 * @project Antigravity
 * @version 2.1 (Hardened + Headers)
 */

// ============================================
// FORMATAGE DATES
// ============================================

/**
 * Formate une date SQL (YYYY-MM-DD) en format Français (DD/MM/YYYY)
 * Gère aussi les notations de semaine (2025-W12)
 */
function date_fr(?string $date_sql): string {
    if (!$date_sql) return '-';
    
    // Gestion Semaine
    if (str_contains($date_sql, 'W')) {
        return "Sem. " . substr($date_sql, -2);
    }
    
    $timestamp = strtotime($date_sql);
    return $timestamp ? date('d/m/Y', $timestamp) : '-';
}

/**
 * Formate une date avec l'heure
 */
function datetime_fr(?string $datetime_sql): string {
    if (!$datetime_sql) return '-';
    $timestamp = strtotime($datetime_sql);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
}

// ============================================
// FORMATAGE MONÉTAIRE
// ============================================

/**
 * Formate un prix en € avec séparateur français
 */
function prix_fr(float|int|null $amount, int $decimals = 2): string {
    if ($amount === null) return '-';
    return number_format((float)$amount, $decimals, ',', ' ') . ' €';
}

/**
 * Formate un nombre avec séparateur de milliers
 */
function number_fr(float|int|null $number, int $decimals = 0): string {
    if ($number === null) return '-';
    return number_format((float)$number, $decimals, ',', ' ');
}

// ============================================
// BADGES & UI
// ============================================

/**
 * Génère un badge HTML Bootstrap pour un statut donné
 * Version avec icônes pour correspondre à la timeline
 */
function badge_statut(?string $statut): string {
    if (!$statut) return '';
    
    // Mapping des statuts avec icônes et couleurs
    $config = match($statut) {
        'Brouillon' => ['icon' => 'file-alt', 'class' => 'secondary'],
        'En Attente' => ['icon' => 'clock', 'class' => 'warning'],
        'Commandée' => ['icon' => 'shopping-cart', 'class' => 'primary'],
        'ARC Reçu' => ['icon' => 'file-signature', 'class' => 'info'],
        'Livraison Prévue' => ['icon' => 'calendar-check', 'class' => 'success'],
        'Livrée' => ['icon' => 'check-circle', 'class' => 'success'],
        
        // Anciens statuts (rétrocompatibilité)
        'Signé', 'Livré', 'Terminé', 'VALIDE', 'OK' => ['icon' => 'check', 'class' => 'success'],
        'Devis', 'A_SCANNER' => ['icon' => 'file', 'class' => 'secondary'],
        'Commandé', 'En cours' => ['icon' => 'shopping-cart', 'class' => 'primary'],
        'A_VERIFIER' => ['icon' => 'exclamation-triangle', 'class' => 'warning'],
        'Partiel' => ['icon' => 'adjust', 'class' => 'info'],
        'MISMATCH_COULEUR', 'MISMATCH_REF', 'MISMATCH_QTE' => ['icon' => 'times-circle', 'class' => 'danger'],
        default => ['icon' => 'circle', 'class' => 'light text-dark border']
    };
    
    $icon = $config['icon'];
    $class = $config['class'];
    
    return "<span class='badge bg-$class'><i class='fas fa-$icon me-1'></i>" . htmlspecialchars($statut) . "</span>";
}

// ============================================
// FICHIERS & SÉCURITÉ
// ============================================

/**
 * Nettoie une chaîne pour en faire un nom de fichier sûr
 */
function safe_filename(string $str): string {
    // Translittération des accents
    $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
    // Remplacement des caractères non-alphanumériques
    $str = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $str);
    // Suppression des tirets multiples
    $str = preg_replace('/-+/', '-', $str);
    // Trim et lowercase
    return strtolower(trim($str, '-'));
}

/**
 * Alias pour safe_filename (rétro-compatibilité)
 */
function clean_filename(string $str): string {
    return safe_filename($str);
}

// ============================================
// GÉNÉRATION DE RÉFÉRENCES
// ============================================

/**
 * Génère un numéro de référence unique (PREFIX-YYYY-XXX)
 * 
 * @param string $prefix Préfixe (CMD, DEV, FAC...)
 * @param PDO $pdo Connexion base de données
 * @param string $table Nom de la table
 * @param string $column Nom de la colonne de référence
 * @return string Référence générée
 */
function generate_ref(string $prefix, PDO $pdo, string $table, string $column): string {
    $year = date('Y');
    $pattern = "$prefix-$year-%";
    
    $stmt = $pdo->prepare("SELECT $column FROM $table WHERE $column LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$pattern]);
    $last = $stmt->fetchColumn();
    
    if ($last) {
        $seq = (int)substr($last, -3) + 1;
    } else {
        $seq = 1;
    }
    
    return sprintf("%s-%s-%03d", $prefix, $year, $seq);
}

// ============================================
// PROTECTION CSRF
// ============================================

/**
 * Génère ou retourne le token CSRF de la session
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Génère un champ input hidden avec le token CSRF
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Vérifie un token CSRF (à appeler sur les POST)
 * @return bool True si valide
 */
function csrf_verify(?string $token = null): bool {
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Vérifie CSRF et die si invalide
 */
function csrf_require(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
        http_response_code(403);
        die("Erreur de sécurité : Token CSRF invalide. Veuillez rafraîchir la page.");
    }
}

// ============================================
// VALIDATION & SANITIZATION
// ============================================

/**
 * Nettoie une chaîne pour affichage HTML
 */
function h(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Récupère une valeur POST nettoyée
 */
function post(string $key, mixed $default = ''): mixed {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

/**
 * Récupère une valeur GET nettoyée
 */
function get(string $key, mixed $default = ''): mixed {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

/**
 * Récupère un entier positif de POST
 */
function post_int(string $key, int $default = 0): int {
    return max(0, (int)($_POST[$key] ?? $default));
}

/**
 * Récupère un float de POST
 */
function post_float(string $key, float $default = 0.0): float {
    $val = str_replace(',', '.', $_POST[$key] ?? '');
    return is_numeric($val) ? (float)$val : $default;
}

// ============================================
// HTTP SECURITY HEADERS
// ============================================

/**
 * Applique les headers de sécurité standard
 */
function secure_headers(): void {
    if (headers_sent()) return;

    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // Force le téléchargement pour les fichiers .env, .git, etc si jamais accessibles
    if (preg_match('/\.(env|git|htaccess|ini)$/i', $_SERVER['REQUEST_URI'])) {
        http_response_code(403);
        die("Access Denied");
    }
}

// ============================================
// NOTIFICATIONS & ALERTES GLOBALES
// ============================================

/**
 * Récupère les alertes pour la cloche et le ticker
 * @param PDO $pdo
 * @return array
 */
function getGlobalAlerts(PDO $pdo): array {
    $alertes = [];
    
    try {
        // 1. Livraisons J-1
        $stmt = $pdo->query("
            SELECT ca.ref_interne, f.nom as fournisseur_nom
            FROM commandes_achats ca
            JOIN fournisseurs f ON ca.fournisseur_id = f.id
            WHERE DATEDIFF(ca.date_livraison_prevue, CURDATE()) = 1
            AND ca.date_livraison_reelle IS NULL
        ");
        foreach ($stmt->fetchAll() as $row) {
            $alertes[] = [
                'type' => 'warning',
                'icon' => 'truck',
                'message' => "Livraison demain : {$row['fournisseur_nom']} - {$row['ref_interne']}"
            ];
        }

        // 2. Livraisons aujourd'hui
        $stmt = $pdo->query("
            SELECT ca.ref_interne, f.nom as fournisseur_nom
            FROM commandes_achats ca
            JOIN fournisseurs f ON ca.fournisseur_id = f.id
            WHERE DATE(ca.date_livraison_prevue) = CURDATE()
            AND ca.date_livraison_reelle IS NULL
        ");
        foreach ($stmt->fetchAll() as $row) {
            $alertes[] = [
                'type' => 'info',
                'icon' => 'calendar-check',
                'message' => "Livraison aujourd'hui : {$row['fournisseur_nom']} - {$row['ref_interne']}"
            ];
        }

        // 3. Retards de livraison (> 0 jours)
        $stmt = $pdo->query("
            SELECT ca.ref_interne, f.nom as fournisseur_nom, DATEDIFF(CURDATE(), ca.date_livraison_prevue) as jours_retard
            FROM commandes_achats ca
            JOIN fournisseurs f ON ca.fournisseur_id = f.id
            WHERE ca.date_livraison_prevue < CURDATE()
            AND ca.date_livraison_reelle IS NULL
            AND ca.statut NOT IN ('Annulée', 'Reçue')
            ORDER BY ca.date_livraison_prevue ASC
            LIMIT 5
        ");
        foreach ($stmt->fetchAll() as $row) {
            $alertes[] = [
                'type' => 'danger',
                'icon' => 'exclamation-triangle',
                'message' => "Retard {$row['jours_retard']}j : {$row['fournisseur_nom']} - {$row['ref_interne']}"
            ];
        }

        // 4. Commandes en Attente (Date En Attente mais pas Date Commande)
        $stmt = $pdo->query("
            SELECT ca.ref_interne, f.nom as fournisseur_nom, a.nom_affaire 
            FROM commandes_achats ca
            JOIN fournisseurs f ON ca.fournisseur_id = f.id
            LEFT JOIN affaires a ON ca.affaire_id = a.id
            WHERE ca.date_en_attente IS NOT NULL AND ca.date_commande IS NULL
            ORDER BY ca.date_en_attente DESC LIMIT 5
        ");
        foreach ($stmt->fetchAll() as $row) {
             $nom_affaire = $row['nom_affaire'] ? " | {$row['nom_affaire']}" : "";
             $alertes[] = [
                'type' => 'warning',
                'icon' => 'hourglass-half',
                'message' => "En Attente : {$row['fournisseur_nom']}{$nom_affaire}"
            ];
        }

        // 5. Livraisons Prévues (Futures avec date_livraison_prevue)
        $stmt = $pdo->query("
             SELECT ca.ref_interne, f.nom as fournisseur_nom, ca.date_livraison_prevue, a.nom_affaire
             FROM commandes_achats ca
             JOIN fournisseurs f ON ca.fournisseur_id = f.id
             LEFT JOIN affaires a ON ca.affaire_id = a.id
             WHERE ca.date_livraison_prevue >= CURDATE()
             AND ca.date_livraison_reelle IS NULL
             ORDER BY ca.date_livraison_prevue ASC LIMIT 5
        ");
        foreach ($stmt->fetchAll() as $row) {
             $date_liv = date('d/m', strtotime($row['date_livraison_prevue']));
             $nom_affaire = $row['nom_affaire'] ? " | {$row['nom_affaire']}" : "";
             $alertes[] = [
                'type' => 'success',
                'icon' => 'truck',
                'message' => "[{$date_liv}] Livraison Prévue : {$row['fournisseur_nom']}{$nom_affaire}"
            ];
        }

        // 6. Besoins Brouillon / A_CALCULER
        // On check si la table existe d'abord pour éviter crash si module pas install
        try {
            $stmt = $pdo->query("
                SELECT bc.id, a.nom_affaire, mp.nom as modele_nom, bc.quantite
                FROM besoins_chantier bc
                JOIN affaires a ON bc.affaire_id = a.id
                LEFT JOIN modeles_profils mp ON bc.modele_profil_id = mp.id
                WHERE bc.statut IN ('BROUILLON', 'A_CALCULER', 'A_VERIFIER')
                ORDER BY bc.id DESC LIMIT 5
            ");
            foreach ($stmt->fetchAll() as $row) {
                $alertes[] = [
                    'type' => 'info',
                    'icon' => 'drafting-compass',
                    'message' => "Besoin à traiter : {$row['nom_affaire']} ({$row['quantite']}u {$row['modele_nom']})"
                ];
            }
        } catch (Exception $e) { /* Table maybe missing, ignore */ }

        // 7. ALERTE STOCK BAS
        try {
            // On vérifie les articles catalgue avec stock géré
            $stmt = $pdo->query("
                SELECT designation_commerciale, stock_actuel, stock_minimum 
                FROM articles_catalogue 
                WHERE stock_actuel < stock_minimum
                AND stock_minimum > 0
                LIMIT 5
            ");
            foreach ($stmt->fetchAll() as $row) {
                 $alertes[] = [
                    'type' => 'danger',
                    'icon' => 'battery-empty',
                    'message' => "Stock Critique : {$row['designation_commerciale']} ({$row['stock_actuel']} < {$row['stock_minimum']})"
                ];
            }
        } catch (Exception $e) {}
        
    } catch (Exception $e) {
    }
    
    return $alertes;
}

// ============================================
// NAVIGATION & REDIRECTION
// ============================================

/**
 * Redirige vers une URL interne ou externe et arrête le script
 */
function redirect(string $url): void {
    if (!headers_sent()) {
        header("Location: " . $url);
    } else {
        echo "<script>window.location.href='$url';</script>";
    }
    exit;
}

// ============================================
// STATUT DYNAMIQUE COMMANDES
// ============================================

/**
 * Calcule le statut d'une commande en fonction des dates renseignées
 * Logique: Le statut le plus avancé basé sur les dates disponibles
 * 
 * @param array $commande Tableau associatif avec les champs de dates
 * @return string Statut calculé
 */
function calculate_order_status(array $commande): string {
    // Ordre de priorité (du plus avancé au moins avancé)
    if (!empty($commande['date_livraison_reelle'])) {
        return 'Livrée';
    }
    
    if (!empty($commande['date_livraison_prevue'])) {
        return 'Livraison Prévue';
    }
    
    if (!empty($commande['date_arc_recu'])) {
        return 'ARC Reçu';
    }
    
    if (!empty($commande['date_commande'])) {
        return 'Commandée';
    }
    
    // Toutes les commandes ont au minimum une date de création
    // Donc le statut par défaut est "En Attente"
    return 'En Attente';
}

