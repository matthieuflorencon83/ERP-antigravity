<?php
// clients_fiche.php - Fiche Client V4 (FERRARI)
$page_title = isset($_GET['id']) ? "Fiche Client" : "Nouveau Client";
require_once 'controllers/clients_controller.php';
require_once 'header.php';

$is_edit = isset($client['id']);
$tab = $_GET['tab'] ?? 'infos'; // Onglet actif par défaut
?>

<div class="main-content">
    <div class="container-fluid px-2 px-md-4 mt-3">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="clients_liste.php" class="btn btn-light shadow-sm"><i class="fas fa-arrow-left"></i> Retour Liste</a>
            </div>
            <div>
                <h2 class="mt-1 fw-bold text-center">
                    <?php if ($is_edit): ?>
                        <i class="fas fa-user-tie text-primary"></i> <?= h($client['nom_principal']) ?>
                    <?php else: ?>
                        <i class="fas fa-plus-circle text-primary"></i> Nouveau Client
                    <?php endif; ?>
                </h2>
            </div>
            <div>
                <?php if($is_edit): ?>
                    <a href="affaires_nouveau.php?client_id=<?= $client['id'] ?>" class="btn btn-primary shadow-sm me-2">
                        <i class="fas fa-folder-plus"></i> Affaire
                    </a>
                <?php endif; ?>
                <button type="submit" form="formClient" class="btn btn-petrol shadow-sm">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </div>

        <!-- ALERTES -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success || isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= $success ?? 'Opération effectuée avec succès.' ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- STRUCTURE ONGLETS (STANDALONE) -->
        <form id="formClient" method="POST" action="clients_fiche.php<?= $is_edit ? '?id='.$client['id'] : '' ?>">
            <input type="hidden" name="action" value="save_client">
            <?php if($is_edit): ?><input type="hidden" name="id" value="<?= $client['id'] ?>"><?php endif; ?>

            <ul class="nav nav-tabs mb-4" id="clientTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link <?= $tab === 'infos' ? 'active fw-bold' : '' ?>" id="infos-tab" data-bs-toggle="tab" data-bs-target="#infos" type="button" role="tab">
                        <i class="fas fa-id-card me-2"></i>Identité
                    </button>
                </li>
                <?php if ($is_edit): ?>
                <li class="nav-item">
                    <button class="nav-link <?= $tab === 'contacts' ? 'active fw-bold' : '' ?>" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button" role="tab">Contacts (<?= count($contacts ?? []) ?>)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?= $tab === 'adresses' ? 'active fw-bold' : '' ?>" id="adresses-tab" data-bs-toggle="tab" data-bs-target="#adresses" type="button" role="tab">Adresses</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?= $tab === 'affaires' ? 'active fw-bold' : '' ?>" id="affaires-tab" data-bs-toggle="tab" data-bs-target="#affaires" type="button" role="tab">Historique Affaires</button>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="tab-content" id="clientTabsContent">
                
                <!-- ONGLET 1 : INFOS (Formulaire Principal) -->
                <div class="tab-pane fade <?= $tab === 'infos' ? 'show active' : '' ?>" id="infos" role="tabpanel">
                            <input type="hidden" name="action" value="save_client">
                            <?php if($is_edit): ?><input type="hidden" name="id" value="<?= $client['id'] ?>"><?php endif; ?>

                            <div class="row g-4">
                                <!-- Identité -->
                                <div class="col-md-6">
                                    <div class="card h-100 shadow-sm border-0">
                                        <div class="card-header bg-primary text-white py-3">
                                            <h5 class="mb-0 fw-bold"><i class="fas fa-user-tie me-2"></i>Identité</h5>
                                        </div>
                                        <div class="card-body p-4">
                                            <div class="row mb-3">
                                                <div class="col-3">
                                                    <label class="form-label fw-bold">Civilité</label>
                                                    <select class="form-select" name="civilite">
                                                        <option value="M." <?= ($client['civilite'] ?? '') == 'M.' ? 'selected' : '' ?>>M.</option>
                                                        <option value="Mme" <?= ($client['civilite'] ?? '') == 'Mme' ? 'selected' : '' ?>>Mme</option>
                                                        <option value="SCI" <?= ($client['civilite'] ?? '') == 'SCI' ? 'selected' : '' ?>>SCI</option>
                                                        <option value="Ste" <?= ($client['civilite'] ?? '') == 'Ste' ? 'selected' : '' ?>>Ste</option>
                                                    </select>
                                                </div>
                                                <div class="col-9">
                                                    <label class="form-label fw-bold">Nom / Raison Sociale *</label>
                                                    <input type="text" class="form-control fw-bold" name="nom_principal" value="<?= h($client['nom_principal'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Prénom</label>
                                                <input type="text" class="form-control" name="prenom" value="<?= h($client['prenom'] ?? '') ?>">
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Code Client</label>
                                                    <input type="text" class="form-control bg-light" value="<?= h($client['code_client'] ?? 'AUTO') ?>" readonly>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Email Principal</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                                                        <input type="email" class="form-control" name="email_principal" value="<?= h($client['email_principal'] ?? '') ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Téléphone Principal</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light"><i class="fas fa-phone text-muted"></i></span>
                                                    <input type="tel" class="form-control" name="telephone_fixe" value="<?= h($client['telephone_fixe'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Adresse -->
                                <div class="col-md-6">
                                    <div class="card h-100 shadow-sm border-0">
                                        <div class="card-header bg-secondary text-white py-3">
                                            <h5 class="mb-0 fw-bold"><i class="fas fa-map-marker-alt me-2"></i>Adresse Principale & Notes</h5>
                                        </div>
                                        <div class="card-body p-4">
                                            <div class="mb-3">
                                                <label class="form-label">Mobile</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light"><i class="fas fa-mobile-alt text-muted"></i></span>
                                                    <input type="tel" class="form-control" name="telephone_mobile" value="<?= h($client['telephone_mobile'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Numéro et Rue</label>
                                                <textarea class="form-control" name="adresse_postale" rows="2"><?= h($client['adresse_postale'] ?? '') ?></textarea>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Code Postal</label>
                                                    <input type="text" class="form-control" name="code_postal" value="<?= h($client['code_postal'] ?? '') ?>" placeholder="75000">
                                                </div>
                                                <div class="col-md-8 mb-3">
                                                    <label class="form-label">Ville</label>
                                                    <input type="text" class="form-control" name="ville" value="<?= h($client['ville'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Pays</label>
                                                <input type="text" class="form-control" name="pays" value="<?= h($client['pays'] ?? 'France') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Notes -->
                                <div class="col-12">
                                     <div class="card shadow-sm border-0">
                                        <div class="card-header">Notes Interne</div>
                                        <div class="card-body p-0">
                                            <textarea name="notes" class="form-control border-0" rows="4" placeholder="Notes privées sur ce client..."><?= h($client['notes'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                    </div>

                    <!-- ONGLET 2 : CONTACTS -->
                    <?php if($is_edit): ?>
                    <div class="tab-pane fade <?= $tab === 'contacts' ? 'show active' : '' ?>" id="contacts" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="m-0 text-muted">Contacts Supplémentaires</h5>
                            <button class="btn btn-outline-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAddContact">
                                <i class="fas fa-plus me-1"></i> Ajouter Contact
                            </button>
                        </div>

                        <?php if(empty($contacts)): ?>
                            <div class="text-center py-5 text-muted bg-light rounded">
                                <i class="fas fa-users mb-2" style="font-size:2rem; opacity:0.3"></i><br>
                                Aucun contact secondaire.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light"><tr><th>Nom</th><th>Rôle</th><th>Tél</th><th>Email</th><th class="text-end">Action</th></tr></thead>
                                    <tbody>
                                        <?php foreach($contacts as $c): ?>
                                            <tr>
                                                <td class="fw-bold"><?= h($c['nom']) ?></td>
                                                <td><span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25"><?= h($c['role']) ?></span></td>
                                                <td>
                                                    <?php if($c['telephone_mobile']): ?><div class="small"><i class="fas fa-mobile-alt me-1 text-muted"></i><?= h($c['telephone_mobile']) ?></div><?php endif; ?>
                                                    <?php if($c['telephone_fixe']): ?><div class="small"><i class="fas fa-phone me-1 text-muted"></i><?= h($c['telephone_fixe']) ?></div><?php endif; ?>
                                                </td>
                                                <td><?= h($c['email']) ?></td>
                                                <td class="text-end">
                                                    <a href="client_actions.php?action=del_contact&id=<?= $c['id'] ?>&cli=<?= $client['id'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Supprimer ce contact ?')"><i class="fas fa-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- ONGLET 3 : ADRESSES -->
                    <div class="tab-pane fade <?= $tab === 'adresses' ? 'show active' : '' ?>" id="adresses" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="m-0 text-muted">Adresses (Chantiers, Livraisons...)</h5>
                            <button class="btn btn-outline-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAddAddress">
                                <i class="fas fa-map-marker-alt me-1"></i> Ajouter Adresse
                            </button>
                        </div>
                        
                        <?php if(empty($adresses)): ?>
                            <div class="text-center py-5 text-muted bg-light rounded">
                                <i class="fas fa-map mb-2" style="font-size:2rem; opacity:0.3"></i><br>
                                Aucune adresse secondaire.
                            </div>
                        <?php else: ?>
                            <div class="row">
                            <?php foreach($adresses as $a): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 border-light shadow-sm">
                                        <div class="card-body position-relative">
                                            <a href="client_actions.php?action=del_address&id=<?= $a['id'] ?>&cli=<?= $client['id'] ?>" class="position-absolute top-0 end-0 m-3 text-danger" onclick="return confirm('Supprimer cette adresse ?')"><i class="fas fa-trash"></i></a>
                                            <h6 class="card-title fw-bold text-primary mb-3">
                                                <i class="fas fa-map-pin me-2"></i><?= h($a['type_adresse']) ?>
                                            </h6>
                                            <p class="mb-1 text-dark fw-medium"><?= h($a['contact_sur_place']) ?></p>
                                            <p class="mb-1 text-muted small"><?= nl2br(h($a['adresse'])) ?></p>
                                            <p class="mb-2 text-muted small fw-bold"><?= h($a['code_postal'].' '.$a['ville']) ?></p>
                                            <?php if($a['telephone']): ?>
                                                <p class="mb-0 small"><i class="fas fa-phone me-1"></i><?= h($a['telephone']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ONGLET 4 : HISTORIQUE -->
                    <div class="tab-pane fade <?= $tab === 'affaires' ? 'show active' : '' ?>" id="affaires" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-striped">
                                <thead><tr><th>Affaire</th><th>Date</th><th>Statut</th><th>Montant HT</th><th>Action</th></tr></thead>
                                <tbody>
                                <?php foreach($affaires as $aff): ?>
                                    <tr>
                                        <td><?= h($aff['nom_affaire']) ?> <small class="text-muted d-block"><?= h($aff['numero_prodevis']) ?></small></td>
                                        <td><?= date('d/m/Y', strtotime($aff['date_creation'])) ?></td>
                                        <td><?= badge_statut($aff['statut']) ?></td>
                                        <td class="text-end"><?= number_format($aff['montant_ht'], 2) ?> €</td>
                                        <td class="text-end"><a href="affaires_detail.php?id=<?= $aff['id'] ?>" class="btn btn-xs btn-outline-petrol"><i class="fas fa-eye"></i></a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL ADD CONTACT -->
<div class="modal fade" id="modalAddContact" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" data-bs-theme="light">
            <form action="client_actions.php" method="POST">
                <input type="hidden" name="action" value="add_contact">
                <input type="hidden" name="client_id" value="<?= $client['id'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Nouveau Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-dark">Nom Complet *</label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Rôle (ex: Conjoint, Gardien...)</label>
                        <input type="text" name="role" class="form-control" list="roleList">
                        <datalist id="roleList">
                            <option value="Conjoint">
                            <option value="Gardien">
                            <option value="Architecte">
                            <option value="Syndic">
                            <option value="Locataire">
                        </datalist>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label text-dark">Mobile</label>
                            <input type="text" name="mobile" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label text-dark">Fixe</label>
                            <input type="text" name="telephone" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL ADD ADDRESS -->
<div class="modal fade" id="modalAddAddress" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" data-bs-theme="light">
            <form action="client_actions.php" method="POST">
                <input type="hidden" name="action" value="add_address">
                <input type="hidden" name="client_id" value="<?= $client['id'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle Adresse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-dark">Type (ex: Chantier)</label>
                        <input type="text" name="type_adresse" class="form-control" value="Chantier" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Adresse *</label>
                        <textarea name="adresse" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="form-label text-dark">CP</label>
                            <input type="text" name="code_postal" class="form-control">
                        </div>
                        <div class="col-8 mb-3">
                            <label class="form-label text-dark">Ville</label>
                            <input type="text" name="ville" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Contact sur place (Nom)</label>
                        <input type="text" name="contact_sur_place" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Téléphone sur place</label>
                        <input type="text" name="telephone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Infos d'accès (Digicode...)</label>
                        <textarea name="commentaires" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
<!-- Script Tabs Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- FIX MODAL BACKDROP -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Déplace les modales à la racine du body pour éviter les conflits de z-index/backdrop
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        document.body.appendChild(modal);
    });
});
</script>
</body>
</html>
