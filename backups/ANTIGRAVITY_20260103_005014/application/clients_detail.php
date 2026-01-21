<?php
/**
 * clients_detail.php - Fiche Client Complète (CRM)
 * Gestion complète des clients avec contacts, adresses, téléphones, emails
 */

require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
// session_start() déjà appelé dans auth.php

$id = $_GET['id'] ?? null;
$new = ($id === null || $id === 'new');

// TRAITEMENT POST (Sauvegarde)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // VALIDATION SERVER-SIDE (silencieuse, HTML5 fait le travail)
    $email = $_POST['email_principal'] ?? '';
    $code_postal = $_POST['code_postal'] ?? '';
    
    $valid = true;
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $valid = false;
    if (!empty($code_postal) && !preg_match('/^[0-9]{5}$/', $code_postal)) $valid = false;
    
    if (!$valid) {
        header("Location: clients_detail.php?id=" . ($id ?? 'new'));
        exit;
    }

    $nom_principal = strtoupper($_POST['nom_principal'] ?? '');
    $prenom = ucwords(strtolower($_POST['prenom'] ?? ''));
    
    // Génération automatique du code client si vide
    $code_client = $_POST['code_client'] ?? '';
    if (empty($code_client)) {
        // Format: 3 premières lettres du nom + 3 premières lettres du prénom + nombre aléatoire
        $code_nom = substr(preg_replace('/[^A-Z]/', '', strtoupper($nom_principal)), 0, 3);
        $code_prenom = substr(preg_replace('/[^A-Z]/', '', strtoupper($prenom)), 0, 3);
        $code_client = $code_nom . $code_prenom . rand(100, 999);
    }
    
    if ($new) {
        $stmt = $pdo->prepare("INSERT INTO clients (civilite, nom_principal, prenom, code_client, email_principal, telephone_fixe, telephone_mobile, adresse_postale, code_postal, ville, pays, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['civilite'],
            $nom_principal,
            $prenom,
            $code_client,
            $_POST['email_principal'],
            $_POST['telephone_fixe'],
            $_POST['telephone_mobile'],
            $_POST['adresse_postale'],
            $_POST['code_postal'],
            $_POST['ville'],
            $_POST['pays'],
            $_POST['notes']
        ]);
        $id = $pdo->lastInsertId();
        header("Location: clients_detail.php?id=$id&saved=1");
        exit;
    } else {
        $stmt = $pdo->prepare("UPDATE clients SET civilite=?, nom_principal=?, prenom=?, code_client=?, email_principal=?, telephone_fixe=?, telephone_mobile=?, adresse_postale=?, code_postal=?, ville=?, pays=?, notes=? WHERE id=?");
        $stmt->execute([
            $_POST['civilite'],
            $nom_principal,
            $prenom,
            $code_client,
            $_POST['email_principal'],
            $_POST['telephone_fixe'],
            $_POST['telephone_mobile'],
            $_POST['adresse_postale'],
            $_POST['code_postal'],
            $_POST['ville'],
            $_POST['pays'],
            $_POST['notes'],
            $id
        ]);
        header("Location: clients_detail.php?id=$id&saved=1");
        exit;
    }
}

// RÉCUPÉRATION DES DONNÉES
if (!$new) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    $client = $stmt->fetch();
    
    if (!$client) {
        die("Client introuvable.");
    }
    
    // Contacts
    $stmt = $pdo->prepare("SELECT * FROM client_contacts WHERE client_id = ? ORDER BY nom ASC");
    $stmt->execute([$id]);
    $contacts = $stmt->fetchAll();
    
    // Adresses
    $stmt = $pdo->prepare("SELECT * FROM client_adresses WHERE client_id = ? ORDER BY type_adresse ASC");
    $stmt->execute([$id]);
    $adresses = $stmt->fetchAll();
    
    // Téléphones
    $stmt = $pdo->prepare("SELECT * FROM client_telephones WHERE client_id = ? ORDER BY principal DESC, type_telephone ASC");
    $stmt->execute([$id]);
    $telephones = $stmt->fetchAll();
    
    // Emails
    $stmt = $pdo->prepare("SELECT * FROM client_emails WHERE client_id = ? ORDER BY principal DESC, type_email ASC");
    $stmt->execute([$id]);
    $emails = $stmt->fetchAll();
    
    // Affaires liées
    $stmt = $pdo->prepare("SELECT * FROM affaires WHERE client_id = ? ORDER BY date_creation DESC");
    $stmt->execute([$id]);
    $affaires = $stmt->fetchAll();
    
} else {
    $client = [
        'civilite' => 'M.',
        'nom_principal' => '',
        'prenom' => '',
        'code_client' => '',
        'email_principal' => '',
        'telephone_fixe' => '',
        'telephone_mobile' => '',
        'adresse_postale' => '',
        'code_postal' => '',
        'ville' => '',
        'pays' => 'France',
        'siret' => '',
        'tva_intra' => '',
        'notes' => ''
    ];
    $contacts = [];
    $adresses = [];
    $telephones = [];
    $emails = [];
    $affaires = [];
}

$page_title = $new ? 'Nouveau Client' : 'Fiche Client';
require_once 'header.php';
?>

<div class="main-content">
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-end align-items-center mb-3">
        <div>
            <?php if (!$new): ?>
                <button type="button" id="btnEdit" class="btn btn-warning" onclick="toggleEditMode()">
                    <i class="fas fa-edit"></i> Modifier
                </button>
                <button type="submit" form="formClient" id="btnSave" class="btn btn-petrol" style="display:none;">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" id="btnCancel" class="btn btn-secondary" style="display:none;" onclick="cancelEdit()">
                    <i class="fas fa-times"></i> Annuler
                </button>
            <?php else: ?>
                <button type="submit" form="formClient" class="btn btn-petrol">
                    <i class="fas fa-save"></i> Créer
                </button>
            <?php endif; ?>
            <a href="clients_liste.php" class="btn btn-outline-dark">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fas fa-check-circle"></i> Client enregistré avec succès !
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" id="formClient">
        <ul class="nav nav-tabs mb-4 nav-tabs-mobile-scroll" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" id="infos-tab" data-bs-toggle="tab" data-bs-target="#infos" type="button" role="tab">
                    <i class="fas fa-id-card me-2"></i>Identité & Adresse
                </button>
            </li>
            <?php if (!$new): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button" role="tab">Contacts (<?= count($contacts) ?>)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="adresses-tab" data-bs-toggle="tab" data-bs-target="#adresses" type="button" role="tab">Adresses (<?= count($adresses) ?>)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="telephones-tab" data-bs-toggle="tab" data-bs-target="#telephones" type="button" role="tab">Téléphones & Emails</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="affaires-tab" data-bs-toggle="tab" data-bs-target="#affaires" type="button" role="tab">Affaires (<?= count($affaires) ?>)</button>
            </li>
            <?php endif; ?>
        </ul>

        <div class="tab-content">
            <!-- ONGLET IDENTITÉ & ADRESSE -->
            <div class="tab-pane fade show active" id="infos" role="tabpanel">
                <div class="row g-4">
                    <!-- Colonne Gauche - Identité -->
                    <div class="col-md-6">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-header bg-primary text-white py-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-user me-2"></i>Identité</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Civilité *</label>
                                        <select name="civilite" class="form-select" required>
                                            <option value="M." <?= ($client['civilite'] ?? 'M.') == 'M.' ? 'selected' : '' ?>>M.</option>
                                            <option value="Mme" <?= ($client['civilite'] ?? 'M.') == 'Mme' ? 'selected' : '' ?>>Mme</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label fw-bold">Nom *</label>
                                        <input type="text" name="nom_principal" id="nom_principal" class="form-control text-uppercase" value="<?= htmlspecialchars($client['nom_principal'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Prénom *</label>
                                        <input type="text" name="prenom" id="prenom" class="form-control text-capitalize" value="<?= htmlspecialchars($client['prenom'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Code Client <small class="text-muted">(auto)</small></label>
                                        <input type="text" name="code_client" id="code_client" class="form-control bg-light" value="<?= htmlspecialchars($client['code_client'] ?? '') ?>" readonly>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Principal</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                                        <input type="email" name="email_principal" class="form-control" value="<?= htmlspecialchars($client['email_principal'] ?? '') ?>" placeholder="contact@exemple.com" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Format: nom@domaine.com">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Téléphone Fixe</label>
                                        <input type="tel" name="telephone_fixe" class="form-control" value="<?= htmlspecialchars($client['telephone_fixe'] ?? '') ?>" pattern="^(?:(?:\+|00)33|0)[1-59](?:[\s.-]*\d{2}){4}$" title="Format: 01 22 33 44 55">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Téléphone Mobile</label>
                                        <input type="tel" name="telephone_mobile" class="form-control" value="<?= htmlspecialchars($client['telephone_mobile'] ?? '') ?>" pattern="^(?:(?:\+|00)33|0)[67](?:[\s.-]*\d{2}){4}$" title="Format: 06 12 34 56 78">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Colonne Droite - Adresse & Admin -->
                    <div class="col-md-6">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-header bg-primary text-white py-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-map-marker-alt me-2"></i>Adresse</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Adresse Postale</label>
                                    <textarea name="adresse_postale" class="form-control" rows="2"><?= htmlspecialchars($client['adresse_postale'] ?? '') ?></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Code Postal</label>
                                        <input type="text" name="code_postal" class="form-control" value="<?= htmlspecialchars($client['code_postal'] ?? '') ?>" pattern="[0-9]{5}" title="5 chiffres obligatoires">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Ville</label>
                                        <input type="text" name="ville" class="form-control" value="<?= htmlspecialchars($client['ville'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Pays</label>
                                        <input type="text" name="pays" class="form-control" value="<?= htmlspecialchars($client['pays'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Notes (Code porte, étage, etc.)</label>
                                    <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($client['notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$new): ?>
            <!-- ONGLET CONTACTS -->
            <div class="tab-pane fade" id="contacts" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Contacts Secondaires</span>
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalAddContact">
                            <i class="fas fa-plus"></i> Ajouter Contact
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (count($contacts) > 0): ?>
                            <table class="table table-hover table-mobile-cards">
                                <thead>
                                    <tr>
                                        <th>Civilité</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Rôle</th>
                                        <th>Email</th>
                                        <th>Téléphones</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contacts as $c): ?>
                                        <tr>
                                            <td data-label="Civilité"><?= htmlspecialchars($c['civilite']) ?></td>
                                            <td data-label="Nom"><?= htmlspecialchars($c['nom']) ?></td>
                                            <td data-label="Prénom"><?= htmlspecialchars($c['prenom']) ?></td>
                                            <td data-label="Rôle"><?= htmlspecialchars($c['role']) ?></td>
                                            <td data-label="Email"><?= htmlspecialchars($c['email']) ?></td>
                                            <td data-label="Téléphones"><?= htmlspecialchars(($c['telephone_mobile'] ?? '') . ' ' . ($c['telephone_fixe'] ?? '')) ?></td>
                                            <td data-label="Actions">
                                                <a href="client_actions.php?action=del_contact&id=<?= $c['id'] ?>&client_id=<?= $id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce contact ?')"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">Aucun contact secondaire enregistré.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ONGLET ADRESSES -->
            <div class="tab-pane fade" id="adresses" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Adresses Multiples</span>
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalAddAddress">
                            <i class="fas fa-plus"></i> Ajouter Adresse
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (count($adresses) > 0): ?>
                            <div class="row">
                                <?php foreach ($adresses as $a): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <div class="card-header bg-info text-white d-flex justify-content-between">
                                                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($a['type_adresse']) ?></span>
                                                <a href="client_actions.php?action=del_address&id=<?= $a['id'] ?>&client_id=<?= $id ?>" class="btn btn-sm btn-outline-light" onclick="return confirm('Supprimer cette adresse ?')"><i class="fas fa-trash"></i></a>
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-1"><strong><?= nl2br(htmlspecialchars($a['adresse'])) ?></strong></p>
                                                <p class="mb-1"><?= htmlspecialchars($a['code_postal']) ?> <?= htmlspecialchars($a['ville']) ?></p>
                                                <p class="mb-1"><?= htmlspecialchars($a['pays']) ?></p>
                                                <?php if ($a['contact_sur_place']): ?>
                                                    <p class="mb-1"><i class="fas fa-user"></i> <?= htmlspecialchars($a['contact_sur_place']) ?></p>
                                                <?php endif; ?>
                                                <?php if ($a['telephone']): ?>
                                                    <p class="mb-1"><i class="fas fa-phone"></i> <?= htmlspecialchars($a['telephone']) ?></p>
                                                <?php endif; ?>
                                                <?php if ($a['instructions']): ?>
                                                    <p class="mb-0 text-muted small"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($a['instructions']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">Aucune adresse secondaire enregistrée.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ONGLET TÉLÉPHONES & EMAILS -->
            <div class="tab-pane fade" id="telephones" role="tabpanel">
                <div class="row">
                    <!-- Téléphones -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Téléphones</span>
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalAddPhone">
                                    <i class="fas fa-plus"></i> Ajouter
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (count($telephones) > 0): ?>
                                    <table class="table table-sm table-mobile-cards">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Numéro</th>
                                                <th>Libellé</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($telephones as $t): ?>
                                                <tr>
                                                    <td data-label="Type"><?= htmlspecialchars($t['type_telephone']) ?> <?= $t['principal'] ? '<span class="badge bg-primary">Principal</span>' : '' ?></td>
                                                    <td data-label="Numéro"><?= htmlspecialchars($t['numero']) ?></td>
                                                    <td data-label="Libellé"><?= htmlspecialchars($t['libelle']) ?></td>
                                                    <td data-label="Actions">
                                                        <a href="client_actions.php?action=del_phone&id=<?= $t['id'] ?>&client_id=<?= $id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">Aucun téléphone enregistré.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Emails -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <!-- BOUTON AJOUT EMAIL -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 fw-bold">Emails</h6>
                                    <?php if ($client['id']): ?>
                                        <button type="button" class="btn btn-sm btn-outline-dark rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAddEmail">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (count($emails) > 0): ?>
                                    <table class="table table-sm table-mobile-cards">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Email</th>
                                                <th>Libellé</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($emails as $e): ?>
                                                <tr>
                                                    <td data-label="Type"><?= htmlspecialchars($e['type_email']) ?> <?= $e['principal'] ? '<span class="badge bg-primary">Principal</span>' : '' ?></td>
                                                    <td data-label="Email"><?= htmlspecialchars($e['email']) ?></td>
                                                    <td data-label="Libellé"><?= htmlspecialchars($e['libelle']) ?></td>
                                                    <td data-label="Actions">
                                                        <a href="client_actions.php?action=del_email&id=<?= $e['id'] ?>&client_id=<?= $id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">Aucun email enregistré.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ONGLET AFFAIRES -->
            <div class="tab-pane fade" id="affaires" role="tabpanel">
                <div class="card">
                    <div class="card-header">Affaires Liées</div>
                    <div class="card-body">
                        <?php if (count($affaires) > 0): ?>
                            <table class="table table-hover table-mobile-cards">
                                <thead>
                                    <tr>
                                        <th>N° ProDevis</th>
                                        <th>Nom Affaire</th>
                                        <th>Statut</th>
                                        <th>Date Création</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($affaires as $aff): ?>
                                        <tr>
                                            <td data-label="ProDevis"><?= htmlspecialchars($aff['numero_prodevis']) ?></td>
                                            <td data-label="Affaire"><?= htmlspecialchars($aff['nom_affaire']) ?></td>
                                            <td data-label="Statut"><span class="badge bg-info"><?= htmlspecialchars($aff['statut']) ?></span></td>
                                            <td data-label="Date"><?= date('d/m/Y', strtotime($aff['date_creation'])) ?></td>
                                            <td data-label="Actions">
                                                <a href="affaires_detail.php?id=<?= $aff['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> Voir</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">Aucune affaire liée à ce client.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>
</div>

<?php if (!$new): ?>
<!-- MODAL AJOUT CONTACT -->
<div class="modal fade" id="modalAddContact" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="client_actions.php" method="POST">
                <input type="hidden" name="action" value="add_contact">
                <input type="hidden" name="client_id" value="<?= $id ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Nouveau Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Civilité</label>
                        <select name="civilite" class="form-select">
                            <option value="M.">M.</option>
                            <option value="Mme">Mme</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label>Nom *</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label>Prénom</label>
                            <input type="text" name="prenom" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Rôle</label>
                        <select name="role" class="form-select">
                            <option value="Conjoint">Conjoint(e)</option>
                            <option value="Assistant">Assistant(e)</option>
                            <option value="Comptable">Comptable</option>
                            <option value="Responsable Chantier">Responsable Chantier</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" placeholder="exemple@domaine.com" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Format invalide">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label>Tél Fixe</label>
                            <input type="tel" name="telephone_fixe" class="form-control" pattern="^(?:(?:\+|00)33|0)[1-59](?:[\s.-]*\d{2}){4}$" title="Format: 01 22 33 44 55">
                        </div>
                        <div class="col-6 mb-3">
                            <label>Mobile</label>
                            <input type="tel" name="telephone_mobile" class="form-control" pattern="^(?:(?:\+|00)33|0)[67](?:[\s.-]*\d{2}){4}$" title="Format: 06 12 34 56 78">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL AJOUT ADRESSE -->
<div class="modal fade" id="modalAddAddress" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="client_actions.php" method="POST">
                <input type="hidden" name="action" value="add_address">
                <input type="hidden" name="client_id" value="<?= $id ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle Adresse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Type d'adresse</label>
                        <select name="type_adresse" class="form-select">
                            <option>Domicile</option>
                            <option>Travail</option>
                            <option>Chantier</option>
                            <option>Facturation</option>
                            <option>Livraison</option>
                            <option>Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Adresse *</label>
                        <textarea name="adresse" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-4 mb-3">
                            <label>CP</label>
                            <input type="text" name="code_postal" class="form-control" pattern="[0-9]{5}" title="5 chiffres obligatoires">
                        </div>
                        <div class="col-8 mb-3">
                            <label>Ville</label>
                            <input type="text" name="ville" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Pays</label>
                        <input type="text" name="pays" class="form-control" value="France">
                    </div>
                    <div class="mb-3">
                        <label>Contact sur place</label>
                        <input type="text" name="contact_sur_place" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Téléphone sur place</label>
                        <input type="tel" name="telephone" class="form-control" pattern="^(?:(?:\+|00)33|0)[1-9](?:[\s.-]*\d{2}){4}$" title="Format: 01 22 33 44 55">
                    </div>
                    <div class="mb-3">
                        <label>Instructions (Code porte, étage, etc.)</label>
                        <textarea name="instructions" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL AJOUT TÉLÉPHONE -->
<div class="modal fade" id="modalAddPhone" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="client_actions.php" method="POST">
                <input type="hidden" name="action" value="add_phone">
                <input type="hidden" name="client_id" value="<?= $id ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Nouveau Téléphone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Type</label>
                        <select name="type_telephone" class="form-select">
                            <option>Bureau</option>
                            <option>Domicile</option>
                            <option>Portable</option>
                            <option>Fax</option>
                            <option>Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Numéro *</label>
                        <input type="tel" name="numero" class="form-control" required pattern="^(?:(?:\+|00)33|0)[1-9](?:[\s.-]*\d{2}){4}$" title="Format: 01 22 33 44 55">
                    </div>
                    <div class="mb-3">
                        <label>Libellé</label>
                        <input type="text" name="libelle" class="form-control" placeholder="Ex: Portable Pro">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="principal" value="1" class="form-check-input" id="checkPrincipal">
                            <label class="form-check-label" for="checkPrincipal">Numéro principal</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL AJOUT EMAIL -->
<div class="modal fade" id="modalAddEmail" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="client_actions.php" method="POST">
                <input type="hidden" name="action" value="add_email">
                <input type="hidden" name="client_id" value="<?= $id ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvel Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Type</label>
                        <select name="type_email" class="form-select">
                            <option>Principal</option>
                            <option>Secondaire</option>
                            <option>Professionnel</option>
                            <option>Facturation</option>
                            <option>Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required placeholder="exemple@domaine.com" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Format invalide">
                    </div>
                    <div class="mb-3">
                        <label>Libellé</label>
                        <input type="text" name="libelle" class="form-control" placeholder="Ex: Email Pro">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="principal" value="1" class="form-check-input" id="checkEmailPrincipal">
                            <label class="form-check-label" for="checkEmailPrincipal">Email principal</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Script Mode Édition -->
<script>
let isEditMode = <?= $new ? 'true' : 'false' ?>;

function toggleEditMode() {
    isEditMode = true;
    document.getElementById('btnEdit').style.display = 'none';
    document.getElementById('btnSave').style.display = 'inline-block';
    document.getElementById('btnCancel').style.display = 'inline-block';
    
    document.querySelectorAll('#formClient input, #formClient textarea, #formClient select').forEach(field => {
        field.removeAttribute('readonly');
        field.removeAttribute('disabled');
    });
}

function cancelEdit() {
    location.reload();
}

document.addEventListener('DOMContentLoaded', function() {
    // Mode lecture seule uniquement pour les clients existants
    if (isEditMode === false) {
        document.querySelectorAll('#formClient input, #formClient textarea, #formClient select').forEach(field => {
            field.setAttribute('readonly', 'readonly');
            if (field.tagName === 'SELECT') {
                field.setAttribute('disabled', 'disabled');
            }
        });
    }
    
    // Validation en temps réel
    const emailInputs = document.querySelectorAll('input[type="email"]');
    const telInputs = document.querySelectorAll('input[type="tel"]');
    
    function validateInput(input) {
        if (input.value === '') {
            input.classList.remove('is-invalid', 'is-valid');
            return true;
        }
        
        const isValid = input.checkValidity();
        if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
        }
        return isValid;
    }
    
    [...emailInputs, ...telInputs].forEach(input => {
        input.addEventListener('blur', () => validateInput(input));
        input.addEventListener('input', () => {
            if (input.classList.contains('is-invalid')) {
                validateInput(input);
            }
        });
    });
    
    // Génération automatique du code client
    const nomInput = document.getElementById('nom_principal');
    const prenomInput = document.getElementById('prenom');
    const codeClientInput = document.getElementById('code_client');
    
    function generateClientCode() {
        if (!nomInput || !prenomInput || !codeClientInput) return;
        
        const nom = nomInput.value.replace(/[^A-Z]/g, '').substring(0, 3);
        const prenom = prenomInput.value.replace(/[^A-Za-z]/g, '').toUpperCase().substring(0, 3);
        const random = Math.floor(Math.random() * 900) + 100;
        
        if (nom.length > 0 || prenom.length > 0) {
            codeClientInput.value = nom + prenom + random;
        }
    }
    
    if (nomInput && prenomInput) {
        nomInput.addEventListener('blur', generateClientCode);
        prenomInput.addEventListener('blur', generateClientCode);
    }
    
    // Bloquer soumission si invalide
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const invalidInputs = form.querySelectorAll(':invalid');
            if (invalidInputs.length > 0) {
                e.preventDefault();
                invalidInputs.forEach(input => input.classList.add('is-invalid'));
                invalidInputs[0].reportValidity();
                return false;
            }
        });
    });
});
</script>

<style>
.is-invalid {
    border-color: #dc3545 !important;
    background-color: #fff5f5 !important;
}
.is-valid {
    border-color: #28a745 !important;
    background-color: #f0fff4 !important;
}
input[readonly], textarea[readonly], select[disabled] {
    background-color: #f8f9fa !important;
    cursor: not-allowed;
    border-color: #dee2e6 !important;
}
</style>


</body>
</html>
