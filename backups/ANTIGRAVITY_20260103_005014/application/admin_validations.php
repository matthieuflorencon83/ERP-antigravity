<?php
// admin_validations.php
require_once 'auth.php';
require_once 'db.php';
require_once 'core/functions.php';

// Access Control
if (($_SESSION['user_role'] ?? '') !== 'ADMIN') {
    die("Accès réservé aux administrateurs.");
}

// Logic: Handle Actions (Approve/Reject)
if (isset($_POST['action']) && isset($_POST['id'])) {
    $req_id = (int)$_POST['id'];
    
    // Fetch Request
    $stmt = $pdo->prepare("SELECT * FROM admin_validations WHERE id = ?");
    $stmt->execute([$req_id]);
    $request = $stmt->fetch();

    if ($request && $request['statut'] === 'PENDING') {
        $data = json_decode($request['donnees_json'], true);
        
        if ($_POST['action'] === 'approve') {
            // EXECUTE SQL
            try {
                $sql = $data['sql'];
                $params = $data['params'];
                
                $pdo->prepare($sql)->execute($params);
                
                // Mark as Approved
                $pdo->prepare("UPDATE admin_validations SET statut='APPROVED', date_traitement=NOW(), admin_id=? WHERE id=?")->execute([$_SESSION['user_id'], $req_id]);
                $success = "Demande #$req_id validée et exécutée avec succès.";
            } catch (Exception $e) {
                $error = "Erreur lors de l'exécution : " . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'reject') {
            // Just Mark as Rejected
            $pdo->prepare("UPDATE admin_validations SET statut='REJECTED', date_traitement=NOW(), admin_id=? WHERE id=?")->execute([$_SESSION['user_id'], $req_id]);
            $success = "Demande #$req_id rejetée.";
        
        } elseif ($_POST['action'] === 'update_request') {
            // Update Data (Correction)
            $new_params = json_decode($_POST['params_json'], true);
            $new_desc = trim($_POST['description']);
            
            if ($new_params !== null) {
                $data['params'] = $new_params;
                $data['description'] = $new_desc;
                
                $new_json = json_encode($data);
                $pdo->prepare("UPDATE admin_validations SET donnees_json = ? WHERE id = ?")->execute([$new_json, $req_id]);
                $success = "Demande #$req_id mise à jour.";
            } else {
                $error = "JSON mal formé.";
            }
        }
    }
}

// Fetch Pending Requests
$stmt = $pdo->query("
    SELECT v.*, u.nom_complet, u.couleur_plan 
    FROM admin_validations v
    JOIN utilisateurs u ON v.user_id = u.id
    WHERE v.statut = 'PENDING'
    ORDER BY v.date_demande DESC
");
$pending = $stmt->fetchAll();

$page_title = "Validations en attente";
require_once 'header.php';
?>

<div class="main-content">
    <div class="container-fluid mt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-danger">
                <i class="fas fa-shield-alt me-2"></i>Validations Administratives
            </h2>
            <span class="badge bg-secondary rounded-pill fs-6 px-3">
                <?= count($pending) ?> demande(s) en attente
            </span>
        </div>

        <?php if(isset($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= $error ?></div>
        <?php endif; ?>

        <div class="card shadow border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date / Demandeur</th>
                            <th>Type Action</th>
                            <th>Description</th>
                            <th>Détails Techniques</th>
                            <th class="text-end">Décision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pending as $p): 
                            $details = json_decode($p['donnees_json'], true);
                            $params_json = json_encode($details['params'], JSON_PRETTY_PRINT);
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center mb-1">
                                    <div class="fw-bold me-2"><?= date_fr($p['date_demande'], true) ?></div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center me-2" 
                                         style="width:24px; height:24px; font-size:10px; background-color: <?= $p['couleur_plan'] ?>;">
                                        <?= strtoupper(substr($p['nom_complet'], 0, 1)) ?>
                                    </div>
                                    <span class="small text-muted"><?= h($p['nom_complet']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?= $p['type_action'] === 'DELETE' ? 'danger' : 'warning' ?>">
                                    <?= $p['type_action'] ?>
                                </span>
                                <div class="small text-muted mt-1"><?= $p['table_concernee'] ?></div>
                            </td>
                            <td>
                                <div class="fw-bold"><?= h($details['description'] ?? 'Aucune description') ?></div>
                                <div class="small text-muted">ID Enregistrement : #<?= $p['id_enregistrement'] ?></div>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-link text-decoration-none dropdown-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#details-<?= $p['id'] ?>">
                                    Voir SQL
                                </button>
                                <div class="collapse mt-2 p-2 bg-light border rounded small font-monospace" id="details-<?= $p['id'] ?>" style="max-width: 300px;">
                                    <?= h($details['sql']) ?>
                                    <hr class="my-1">
                                    Params: <?= h(json_encode($details['params'])) ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <!-- BOUTONS -->
                                <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 shadow-sm me-1" 
                                        onclick="openEditModal(<?= $p['id'] ?>, '<?= addslashes($details['description'] ?? '') ?>', '<?= addslashes($params_json) ?>')">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                
                                <form method="POST" class="d-inline-block">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm me-1" title="Valider">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm rounded-pill px-3" title="Refuser">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($pending)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted opacity-50">
                                <i class="fas fa-check-double fa-3x mb-3"></i>
                                <p>Tout est à jour ! Aucune demande en attente.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update_request">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Modifier la demande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description / Motif</label>
                        <input type="text" name="description" id="edit_description" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Paramètres (JSON)</label>
                        <textarea name="params_json" id="edit_params" class="form-control font-monospace" rows="5"></textarea>
                        <div class="form-text">Vous pouvez corriger les valeurs ici avant validation.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer les corrections</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(id, desc, params) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_description').value = desc;
    document.getElementById('edit_params').value = params;
    new bootstrap.Modal(document.getElementById('editRequestModal')).show();
}
</script>

</body>
</html>
