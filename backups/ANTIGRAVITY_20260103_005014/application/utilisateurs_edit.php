<?php
// utilisateurs_edit.php
require_once 'auth.php';
require_once 'db.php';
require_once 'core/functions.php';

if (($_SESSION['user_role'] ?? '') !== 'ADMIN') die("Accès refusé.");

$id = $_GET['id'] ?? 0;
$user = null;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) die("Utilisateur introuvable.");
}

// TRAITEMENT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    
    $nom = $_POST['nom_complet'];
    $identifiant = $_POST['identifiant'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $color = $_POST['couleur_plan'];
    $password = $_POST['password']; // Si vide, on ne change pas

    if ($id > 0) {
        // UPDATE
        $sql = "UPDATE utilisateurs SET nom_complet=?, identifiant=?, email=?, role=?, couleur_plan=? WHERE id=?";
        $params = [$nom, $identifiant, $email, $role, $color, $id];
        $pdo->prepare($sql)->execute($params);

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE utilisateurs SET mot_de_passe_hash=? WHERE id=?")->execute([$hash, $id]);
        }
        
        header("Location: utilisateurs_liste.php?success=updated");
        exit;
    } else {
        // CREATE
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO utilisateurs (nom_complet, identifiant, email, role, couleur_plan, mot_de_passe_hash, date_creation) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $pdo->prepare($sql)->execute([$nom, $identifiant, $email, $role, $color, $hash]);
        
        header("Location: utilisateurs_liste.php?success=created");
        exit;
    }
}

$page_title = $user ? "Modifier Utilisateur" : "Nouvel Utilisateur";
require_once 'header.php';
?>

<div class="main-content">
    <div class="container mt-5" style="max-width: 600px;">
        
        <div class="card shadow border-0">
            <div class="card-header bg-petrol text-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-user-shield me-2"></i><?= $page_title ?>
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nom Complet</label>
                        <input type="text" name="nom_complet" class="form-control" value="<?= h($user['nom_complet'] ?? '') ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Identifiant (Login)</label>
                            <input type="text" name="identifiant" class="form-control" value="<?= h($user['identifiant'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= h($user['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Rôle</label>
                        <select name="role" class="form-select">
                            <option value="ADMIN" <?= ($user['role'] ?? '') === 'ADMIN' ? 'selected' : '' ?>>ADMINISTRATEUR (Accès Total)</option>
                            <option value="POSEUR" <?= ($user['role'] ?? '') === 'POSEUR' ? 'selected' : '' ?>>POSEUR (Vue Totale + Validations)</option>
                            <option value="SECRETAIRE" <?= ($user['role'] ?? '') === 'SECRETAIRE' ? 'selected' : '' ?>>SECRETAIRE (Gestion Administrative)</option>
                        </select>
                        <div class="form-text">Détermine les accès et le menu disponible.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Couleur Planning</label>
                        <input type="color" name="couleur_plan" class="form-control form-control-color w-100" value="<?= $user['couleur_plan'] ?? '#3788d8' ?>">
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-danger">Mot de passe</label>
                        <input type="password" name="password" class="form-control" placeholder="<?= $user ? 'Laisser vide pour ne pas changer' : 'Créer un mot de passe' ?>" <?= $user ? '' : 'required' ?>>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="utilisateurs_liste.php" class="btn btn-light">Annuler</a>
                        <button type="submit" class="btn btn-petrol px-5 fw-bold">Enregistrer</button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>
