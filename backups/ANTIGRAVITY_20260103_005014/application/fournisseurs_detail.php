<?php
// fournisseurs_detail.php
require_once 'db.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$id = $_GET['id'] ?? null;
$new = $_GET['new'] ?? null;

// TRAITEMENT POST (Sauvegarde)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // VALIDATION SERVER-SIDE (silencieuse, HTML5 fait le travail)
    $email = $_POST['email_general'] ?? '';
    $site_web = $_POST['site_web'] ?? '';
    $code_postal = $_POST['code_postal'] ?? '';
    
    // Si invalide, on redirige sans message (HTML5 a déjà bloqué normalement)
    $valid = true;
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $valid = false;
    if (!empty($site_web) && !preg_match('/^https?:\/\/.+/', $site_web)) $valid = false;
    if (!empty($code_postal) && !preg_match('/^[0-9]{5}$/', $code_postal)) $valid = false;
    
    if (!$valid) {
        header("Location: fournisseurs_detail.php?id=" . ($id ?? 'new'));
        exit;
    }

    // TRAITEMENT POST (Sauvegarde)
    $nom = mb_strtoupper($_POST['nom'] ?? '', 'UTF-8');
    // ... autres champs ...
    
    if ($new) {
        $stmt = $pdo->prepare("INSERT INTO fournisseurs (nom, code_fou, email_commande, adresse_postale, code_postal, ville, pays, siret, tva_intra, condition_paiement, site_web, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $nom, 
            $_POST['code_fou'], 
            $_POST['email_general'],
            $_POST['adresse_postale'],
            $_POST['code_postal'],
            $_POST['ville'],
            $_POST['pays'],
            $_POST['siret'],
            $_POST['tva_intra'],
            $_POST['condition_paiement'],
            $_POST['site_web'],
            $_POST['notes']
        ]);
        $id = $pdo->lastInsertId();
        header("Location: fournisseurs_detail.php?id=$id&saved=1");
        exit;
    } else {
        $stmt = $pdo->prepare("UPDATE fournisseurs SET nom=?, code_fou=?, email_commande=?, adresse_postale=?, code_postal=?, ville=?, pays=?, siret=?, tva_intra=?, condition_paiement=?, site_web=?, notes=? WHERE id=?");
        $stmt->execute([
            $nom, 
            $_POST['code_fou'], 
            $_POST['email_general'],
            $_POST['adresse_postale'],
            $_POST['code_postal'],
            $_POST['ville'],
            $_POST['pays'],
            $_POST['siret'],
            $_POST['tva_intra'],
            $_POST['condition_paiement'],
            $_POST['site_web'],
            $_POST['notes'],
            $id
        ]);
        // Redirection pour éviter renvoi formulaire
        header("Location: fournisseurs_detail.php?id=$id&saved=1");
        exit;
    }
}

// RECUPERATION DONNEES
$fournisseur = null;
$contacts = [];
$commandes = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM fournisseurs WHERE id = ?");
    $stmt->execute([$id]);
    $fournisseur = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fournisseur) die("Fournisseur introuvable");
    
    // Mapping pour compatibilité affichage
    if (!isset($fournisseur['email_general']) && isset($fournisseur['email_commande'])) {
        $fournisseur['email_general'] = $fournisseur['email_commande'];
    }

    // Contacts
    $stmt = $pdo->prepare("SELECT * FROM fournisseur_contacts WHERE fournisseur_id = ? ORDER BY nom ASC");
    $stmt->execute([$id]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Commandes (Dernières 50)
    $stmt = $pdo->prepare("SELECT * FROM commandes_achats WHERE fournisseur_id = ? ORDER BY date_commande DESC LIMIT 50");
    $stmt->execute([$id]);
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Mode Création (Valeurs par défaut)
    $fournisseur = [
        'nom' => '', 'code_fou' => '', 'email_general' => '', // Sera mappé vers email_commande
        'adresse_postale' => '', 'code_postal' => '', 'ville' => '', 
        'pays' => 'France', 'siret' => '', 'tva_intra' => '', 
        'condition_paiement' => '30 jours fin de mois', 'site_web' => '', 'notes' => ''
    ];
}

$page_title = $id ? $fournisseur['nom'] : 'Nouveau Fournisseur';
require_once 'header.php';
?>

    <div class="main-content">
        <div class="container mt-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="fournisseurs_liste.php" class="btn btn-light shadow-sm"><i class="fas fa-arrow-left"></i> Retour Liste</a>
                </div>
                <div>
                    <h2 class="mt-1 fw-bold text-center">
                        <?php if ($id): ?>
                            <i class="fas fa-building text-primary"></i> <?= htmlspecialchars($fournisseur['nom']) ?>
                        <?php else: ?>
                            <i class="fas fa-plus-circle text-primary"></i> Nouveau Fournisseur
                        <?php endif; ?>
                    </h2>
                </div>
                <div>
                    <button type="submit" form="formFournisseur" class="btn btn-petrol shadow-sm">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </div>

            <?php if (isset($_GET['saved'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle me-2"></i>Fournisseur enregistré avec succès !
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Formulaire Principal -->
            <form id="formFournisseur" method="POST">
                
                <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="infos-tab" data-bs-toggle="tab" data-bs-target="#infos" type="button" role="tab">
                            <i class="fas fa-id-card me-2"></i>Identité & Adresse
                        </button>
                    </li>
                    <?php if ($id): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button" role="tab">Contacts (<?= count($contacts) ?>)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="adresses-tab" data-bs-toggle="tab" data-bs-target="#adresses" type="button" role="tab">Adresses</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="commandes-tab" data-bs-toggle="tab" data-bs-target="#commandes" type="button" role="tab">Historique Commandes</button>
                    </li>
                    <?php endif; ?>
                </ul>

                <div class="tab-content" id="myTabContent">
                    <!-- ONGLET 1 : INFOS -->
                    <div class="tab-pane fade show active" id="infos" role="tabpanel">
                        <!-- ... (Contenu Infos inchangé) ... -->
                        <div class="row g-4">
                            <!-- Identité -->
                            <div class="col-md-6">
                                <div class="card h-100 shadow-sm border-0">
                                    <div class="card-header bg-primary text-white py-3">
                                        <h5 class="mb-0 fw-bold"><i class="fas fa-building me-2"></i>Identité</h5>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Raison Sociale (Nom officiel de l'entreprise) *</label>
                                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($fournisseur['nom']) ?>" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Code Fournisseur</label>
                                                <input type="text" name="code_fou" class="form-control bg-light" value="<?= htmlspecialchars($fournisseur['code_fou']) ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email Général (Commandes)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                                                    <input type="email" name="email_general" class="form-control" value="<?= htmlspecialchars($fournisseur['email_general']) ?>" placeholder="contact@entreprise.com" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Format: nom@domaine.com">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Site Web</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                                <input type="url" name="site_web" class="form-control" value="<?= htmlspecialchars($fournisseur['site_web']) ?>" placeholder="https://www.exemple.com" pattern="https?://.+" title="Doit commencer par http:// ou https://">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Adresse -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-secondary text-white">Adresse & Admin</div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Adresse Postale</label>
                                            <textarea name="adresse_postale" class="form-control" rows="2"><?= htmlspecialchars($fournisseur['adresse_postale']) ?></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Code Postal</label>
                                                <input type="text" name="code_postal" class="form-control" value="<?= htmlspecialchars($fournisseur['code_postal']) ?>" pattern="[0-9]{5}" title="5 chiffres obligatoires">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Ville</label>
                                                <input type="text" name="ville" class="form-control" value="<?= htmlspecialchars($fournisseur['ville']) ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Pays</label>
                                                <input type="text" name="pays" class="form-control" value="<?= htmlspecialchars($fournisseur['pays']) ?>">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">SIRET</label>
                                                <input type="text" name="siret" class="form-control" value="<?= htmlspecialchars($fournisseur['siret']) ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">TVA Intra</label>
                                                <input type="text" name="tva_intra" class="form-control" value="<?= htmlspecialchars($fournisseur['tva_intra']) ?>">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Conditions Paiement</label>
                                            <select name="condition_paiement" class="form-select">
                                                <option value="Comptant" <?= $fournisseur['condition_paiement'] == 'Comptant' ? 'selected' : '' ?>>Comptant</option>
                                                <option value="A réception" <?= $fournisseur['condition_paiement'] == 'A réception' ? 'selected' : '' ?>>A réception</option>
                                                <option value="30 jours fin de mois" <?= $fournisseur['condition_paiement'] == '30 jours fin de mois' ? 'selected' : '' ?>>30 jours fin de mois</option>
                                                <option value="45 jours fin de mois" <?= $fournisseur['condition_paiement'] == '45 jours fin de mois' ? 'selected' : '' ?>>45 jours fin de mois</option>
                                                <option value="60 jours date de facture" <?= $fournisseur['condition_paiement'] == '60 jours date de facture' ? 'selected' : '' ?>>60 jours date de facture</option>
                                                <option value="Acompte 30% commande, Solde réception" <?= $fournisseur['condition_paiement'] == 'Acompte 30% commande, Solde réception' ? 'selected' : '' ?>>Acompte 30% commande, Solde réception</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Notes -->
                            <div class="col-12">
                                 <div class="card">
                                    <div class="card-header">Notes Interne</div>
                                    <div class="card-body p-0">
                                        <textarea name="notes" class="form-control border-0" rows="4" placeholder="Notes privées sur ce fournisseur..."><?= htmlspecialchars($fournisseur['notes']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ONGLET 2 : CONTACTS -->
                    <div class="tab-pane fade" id="contacts" role="tabpanel">
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddContact">
                                <i class="fas fa-plus"></i> Ajouter Contact
                            </button>
                        </div>
                        <?php if (empty($contacts)): ?>
                            <div class="alert alert-info">Aucun contact spécifique enregistré (utilise l'email général).</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead><tr><th>Nom</th><th>Rôle</th><th>Email</th><th>Tél</th><th>Actions</th></tr></thead>
                                    <tbody>
                                    <?php foreach($contacts as $c): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($c['nom']) ?></td>
                                            <td><?= htmlspecialchars($c['role']) ?></td>
                                            <td><?= htmlspecialchars($c['email']) ?></td>
                                            <td><?= htmlspecialchars(($c['telephone_mobile'] ?? '') . ' ' . ($c['telephone_fixe'] ?? '')) ?></td>
                                            <td>
                                                <a href="fournisseur_actions.php?action=del_contact&id=<?= $c['id'] ?>&fou=<?= $id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Sur ?')"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ONGLET 3 : ADRESSES -->
                    <div class="tab-pane fade" id="adresses" role="tabpanel">
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddAddress">
                                <i class="fas fa-plus"></i> Ajouter Adresse
                            </button>
                        </div>
                        <?php
                            // Récup Adresses
                            $stmt = $pdo->prepare("SELECT * FROM fournisseur_adresses WHERE fournisseur_id = ?");
                            $stmt->execute([$id]);
                            $adresses = $stmt->fetchAll();
                        ?>
                        <?php if (empty($adresses)): ?>
                            <div class="alert alert-info">Aucune adresse secondaire (utilise l'adresse principale).</div>
                        <?php else: ?>
                            <div class="row">
                            <?php foreach($adresses as $a): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <?= htmlspecialchars($a['type_adresse']) ?>
                                            <a href="fournisseur_actions.php?action=del_address&id=<?= $a['id'] ?>&fou=<?= $id ?>" class="float-end text-danger" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash"></i></a>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-1"><strong><?= htmlspecialchars($a['contact_sur_place']) ?></strong></p>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($a['adresse'])) ?></p>
                                            <p class="mb-0"><?= htmlspecialchars($a['code_postal'] . ' ' . $a['ville']) ?></p>
                                            <p class="mb-0 text-muted"><small><?= htmlspecialchars($a['telephone']) ?></small></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ONGLET 4 : COMMANDES -->
                    <div class="tab-pane fade" id="commandes" role="tabpanel">
                        <div class="table-responsive">
                             <table class="table table-sm table-hover">
                                <thead><tr><th>N°</th><th>Date</th><th>Statut</th><th>Action</th></tr></thead>
                                <tbody>
                                <?php foreach($commandes as $cmd): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cmd['ref_interne']) ?></td>
                                        <td><?= date_fr($cmd['date_commande']) ?></td>
                                        <td><?= badge_statut($cmd['statut']) ?></td>
                                        <td><a href="commandes_detail.php?id=<?= $cmd['id'] ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-search"></i></a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL AJOUT CONTACT -->
    <div class="modal fade" id="modalAddContact" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" data-bs-theme="light">
                <form action="fournisseur_actions.php" method="POST">
                    <input type="hidden" name="action" value="add_contact">
                    <input type="hidden" name="fournisseur_id" value="<?= $id ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau Contact</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-dark">Nom *</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-dark">Rôle</label>
                            <select name="role" class="form-select">
                                <option value="Commercial">Commercial</option>
                                <option value="Comptable">Comptable</option>
                                <option value="ADV / Logistique">ADV / Logistique</option>
                                <option value="Direction">Direction</option>
                                <option value="Technique">Technique</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-dark">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="exemple@domaine.com" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Format invalide (ex: nom@site.com)">
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label text-dark">Tél Fixe (01..)</label>
                                <input type="tel" name="telephone" class="form-control" pattern="^(?:(?:\+|00)33|0)[1-59](?:[\s.-]*\d{2}){4}$" title="Format: 01 22 33 44 55">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label text-dark">Mobile (06/07..)</label>
                                <input type="tel" name="mobile" class="form-control" pattern="^(?:(?:\+|00)33|0)[67](?:[\s.-]*\d{2}){4}$" title="Format: 06 12 34 56 78">
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
            <div class="modal-content" data-bs-theme="light">
                <form action="fournisseur_actions.php" method="POST">
                    <input type="hidden" name="action" value="add_address">
                    <input type="hidden" name="fournisseur_id" value="<?= $id ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouvelle Adresse</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-dark">Type d'adresse</label>
                            <select name="type_adresse" class="form-select">
                                <option>Livraison</option>
                                <option>Enlèvement</option>
                                <option>Facturation</option>
                                <option>Siège</option>
                                <option>Autre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-dark">Adresse *</label>
                            <textarea name="adresse" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-4 mb-3">
                                <label class="form-label text-dark">CP</label>
                                <input type="text" name="code_postal" class="form-control" pattern="[0-9]{5}" title="5 chiffres obligatoires">
                            </div>
                            <div class="col-8 mb-3">
                                <label class="form-label text-dark">Ville</label>
                                <input type="text" name="ville" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-dark">Contact sur place</label>
                            <input type="text" name="contact_sur_place" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-dark">Téléphone sur place</label>
                            <input type="tel" name="telephone" class="form-control" pattern="^(?:(?:\+|00)33|0)[1-9](?:[\s.-]*\d{2}){4}$" title="Format: 01 22 33 44 55">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    
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
<!-- Script validation externalisé -->
<script src="assets/js/form-validation.js"></script>
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
