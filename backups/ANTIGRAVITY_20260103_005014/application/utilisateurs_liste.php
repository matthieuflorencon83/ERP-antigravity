<?php
// utilisateurs_liste.php
require_once 'auth.php';
require_once 'db.php';
require_once 'core/functions.php';

// Only Admin
if (($_SESSION['user_role'] ?? '') !== 'ADMIN') {
    die("Accès refusé. Réservé aux administrateurs.");
}

$page_title = 'Gestion des Utilisateurs';
require_once 'header.php';

// Fetch users
$stmt = $pdo->query("SELECT * FROM utilisateurs ORDER BY nom_complet ASC");
$users = $stmt->fetchAll();
?>

<div class="main-content">
    <div class="container-fluid mt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="fas fa-users-cog me-2 text-primary"></i>Gestion des Utilisateurs</h2>
            <a href="utilisateurs_edit.php" class="btn btn-petrol shadow-sm rounded-pill">
                <i class="fas fa-plus me-2"></i>Nouveau Compte
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nom Complet</th>
                            <th>Identifiant / Login</th>
                            <th>Rôle</th>
                            <th>Couleur Planning</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td class="fw-bold">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3 text-white fw-bold d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px; border-radius: 50%; background-color: <?= $u['couleur_plan'] ?? '#ccc' ?>;">
                                        <?= strtoupper(substr($u['nom_complet'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <?= h($u['nom_complet']) ?>
                                        <div class="small text-muted"><?= h($u['email'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="font-monospace"><?= h($u['identifiant']) ?></td>
                            <td>
                                <?php if($u['role'] === 'ADMIN'): ?>
                                    <span class="badge bg-danger">ADMINISTRATEUR</span>
                                <?php elseif($u['role'] === 'POSEUR'): ?>
                                    <span class="badge bg-success">POSEUR / TERRAIN</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= h($u['role']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="d-inline-block border rounded me-2" style="width: 20px; height: 20px; background-color: <?= $u['couleur_plan'] ?>"></span>
                                    <span class="small text-muted"><?= $u['couleur_plan'] ?></span>
                                </div>
                            </td>
                            <td>
                                <a href="utilisateurs_edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-dark rounded-pill">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
<?php // Footer if needed ?>
</body>
</html>
