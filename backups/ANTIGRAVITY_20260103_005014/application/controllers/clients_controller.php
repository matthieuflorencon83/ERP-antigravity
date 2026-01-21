<?php
/**
 * controllers/clients_controller.php
 * Logique de gestion des Clients (CRUD)
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Vérification de sécurité de base
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($pdo)) {
    require_once __DIR__ . '/../db.php';
}

require_once __DIR__ . '/../functions.php';

$error = null;
$success = null;
$client = null;
$clients_list = [];

// ==========================================
// 1. TRAITEMENT DES ACTIONS (POST/GET)
// ==========================================

// --- SUPPRESSION (GET) ---
if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    try {
        $id = $_GET['del'];
        // Vérifier dépendances (Affaires)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM affaires WHERE client_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Impossible de supprimer ce client : des affaires y sont liées.";
        } else {
            $pdo->prepare("DELETE FROM clients WHERE id = ?")->execute([$id]);
            // Redirection pour éviter re-submit
            header("Location: ../clients_liste.php?msg=deleted");
            exit;
        }
    } catch (Exception $e) {
        $error = "Erreur de suppression : " . $e->getMessage();
    }
}

// --- ENREGISTREMENT (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_client') {
    try {
        // Récupération & Nettoyage
        $id = !empty($_POST['id']) ? $_POST['id'] : null;
        $civilite = trim($_POST['civilite'] ?? 'M.');
        // NOM : En majuscules
        $nom = mb_strtoupper(trim($_POST['nom_principal']), 'UTF-8');
        // PRÉNOM : Première lettre majuscule
        $prenom = ucfirst(strtolower(trim($_POST['prenom'] ?? '')));
        
        // EMAIL : Validation
        $email = trim($_POST['email_principal']);
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("L'adresse email n'est pas valide.");
        }
        
        // TÉLÉPHONE : Nettoyage et formatage basique (optionnel)
        $tel = trim($_POST['telephone_fixe']);
        /* 
           Norme : On pourrait forcer un format 0X XX XX XX XX ici.
           Pour l'instant, on check juste que ça contient des chiffres.
        */
        if (!empty($tel) && !preg_match('/^[0-9\s\+\-\.]+$/', $tel)) {
             throw new Exception("Le numéro de téléphone contient des caractères invalides.");
        }

        $ville = mb_strtoupper(trim($_POST['ville']), 'UTF-8'); // Ville souvent en majuscule aussi
        $cp = trim($_POST['code_postal']);
        $adresse = trim($_POST['adresse_postale']);

        if (empty($nom)) throw new Exception("Le nom du client (ou raison sociale) est obligatoire.");

        if ($id) {
            // UPDATE
            $sql = "UPDATE clients SET civilite=?, nom_principal=?, prenom=?, email_principal=?, telephone_fixe=?, ville=?, code_postal=?, adresse_postale=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$civilite, $nom, $prenom, $email, $tel, $ville, $cp, $adresse, $id]);
            $success = "Client mis à jour avec succès.";
            
            // Recharger les données
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
            $stmt->execute([$id]);
            $client = $stmt->fetch();
            
        } else {
            // INSERT
            // Génération Code Client (ex: CLI-001) si besoin, ou auto-incrément géré par BDD.
            // Ici table simple, pas de code_client dans le seed data insert, mais il y a une colonne code_client dans check_schema output?
            // Vérifions le schéma tasks output... Clients: id, civilite, nom_principal, prenom, code_client...
            // Le seed data mettait nom, email, tel, adresse, ville, cp. 
            // Ah, le seed data insert: INSERT INTO clients (nom_principal, email_principal, telephone_fixe, adresse_postale, ville, code_postal)
            // Donc 'code_client' est nullable ou pas rempli. On va le gérer optionnellement ou le générer.
            
            $sql = "INSERT INTO clients (civilite, nom_principal, prenom, email_principal, telephone_fixe, ville, code_postal, adresse_postale, date_creation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$civilite, $nom, $prenom, $email, $tel, $ville, $cp, $adresse]);
            $new_id = $pdo->lastInsertId();
            
            header("Location: clients_liste.php?msg=created");
            exit;
        }

    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
        // En cas d'erreur, on garde les données saisies pour réaffichage
        $client = $_POST; 
        if(isset($_POST['nom_principal'])) $client['nom_principal'] = $_POST['nom_principal']; 
        // ... (simplification : le formulaire utilisera $client['champ'] ?? '')
    }
}

// ==========================================
// 2. RECUPERATION DES DONNEES (SELECT)
// ==========================================

$current_page = basename($_SERVER['PHP_SELF']);

// --- LISTE (clients_liste.php) ---
if ($current_page === 'clients_liste.php') {
    // Filtre de recherche simple
    $search = $_GET['q'] ?? '';
    $sql = "SELECT * FROM clients";
    $params = [];
    
    if ($search) {
        $sql .= " WHERE nom_principal LIKE ? OR email_principal LIKE ? OR ville LIKE ?";
        $params = ["%$search%", "%$search%", "%$search%"];
    }
    
    $sql .= " ORDER BY nom_principal ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clients_list = $stmt->fetchAll();
}

// --- FICHE INDIVIDUELLE (clients_fiche.php) ---
if ($current_page === 'clients_fiche.php') {
    // Si on a un ID et qu'on n'a pas déjà chargé le client (suite à un update POST)
    if (isset($_GET['id']) && is_numeric($_GET['id']) && !$client) {
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $client = $stmt->fetch();
        
        if (!$client) {
            $error = "Client introuvable.";
        } else {
            // FERRARI UPGRADE : Chargement des données liées
            
            // 1. CONTACTS
            $stmt = $pdo->prepare("SELECT * FROM client_contacts WHERE client_id = ? ORDER BY nom ASC");
            $stmt->execute([$client['id']]);
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. ADRESSES
            $stmt = $pdo->prepare("SELECT * FROM client_adresses WHERE client_id = ? ORDER BY type_adresse ASC");
            $stmt->execute([$client['id']]);
            $adresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. AFFAIRES (Historique)
            $stmt = $pdo->prepare("SELECT * FROM affaires WHERE client_id = ? ORDER BY date_creation DESC LIMIT 20");
            $stmt->execute([$client['id']]);
            $affaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}
?>
