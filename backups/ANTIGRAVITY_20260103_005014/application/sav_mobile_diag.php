<?php
// sav_mobile_diag.php - Interface Mobile Technicien
session_start();
require_once 'auth.php';
require_once 'db.php';
// Pas de header standard pour le mobile pour maximiser l'espace, ou un header simplifié
// On va inclure un titre simple
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SAV Mobile</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .mobile-card { border-radius: 16px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 16px; }
        .btn-xl { padding: 16px; font-size: 1.2rem; border-radius: 12px; font-weight: bold; width: 100%; margin-bottom: 12px; }
        .status-badge { font-size: 0.9rem; padding: 6px 12px; border-radius: 20px; }
    </style>
</head>
<body class="pb-5">

<?php
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("
    SELECT t.*, c.nom_principal as nom, c.ville, c.adresse_postale as adresse, COALESCE(c.telephone_mobile, c.telephone_fixe) as telephone, c.code_postal
    FROM sav_tickets t
    LEFT JOIN clients c ON t.client_id = c.id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) die("<div class='p-4 text-center'>Ticket introuvable</div>");

// Nom à afficher (Client ou Prospect)
$nom = $ticket['nom'] ?? $ticket['prospect_nom'];
$ville = $ticket['ville'] ?? $ticket['prospect_ville'];
$tel = $ticket['telephone'] ?? $ticket['prospect_telephone'];
$adresse_complete = ($ticket['adresse'] ?? '') . ' ' . ($ticket['code_postal'] ?? '') . ' ' . ($ticket['ville'] ?? '');
// Si prospect, adresse est juste la ville, on fait au mieux
if (empty($ticket['adresse'])) $adresse_complete = $ville;
?>

<!-- HEADER MOBILE -->
<div class="bg-primary text-white p-3 mb-3 sticky-top shadow-sm">
    <div class="d-flex justify-content-between align-items-center">
        <a href="sav_fil.php" class="text-white"><i class="fas fa-arrow-left fa-lg"></i></a>
        <h5 class="mb-0 fw-bold">SAV #<?= $ticket['numero_ticket'] ?></h5>
        <div class="badge bg-white text-primary"><?= $ticket['statut'] ?></div>
    </div>
</div>

<div class="container px-3">

    <!-- INFO CLIENT & ACTIONS RAPIDES -->
    <div class="card mobile-card">
        <div class="card-body">
            <h4 class="fw-bold mb-1"><?= htmlspecialchars($nom) ?></h4>
            <p class="text-muted mb-3"><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($ville) ?></p>
            
            <div class="row g-2">
                <div class="col-6">
                    <a href="tel:<?= htmlspecialchars($tel) ?>" class="btn btn-outline-primary w-100 py-3">
                        <i class="fas fa-phone fa-lg mb-1 d-block"></i>Appeler
                    </a>
                </div>
                <div class="col-6">
                    <a href="https://waze.com/ul?q=<?= urlencode($adresse_complete) ?>" target="_blank" class="btn btn-outline-info w-100 py-3">
                        <i class="fab fa-waze fa-lg mb-1 d-block"></i>Y aller
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- DESCRIPTION PROBLEME -->
    <div class="card mobile-card border-start border-4 border-warning">
        <div class="card-body">
            <h6 class="text-uppercase text-secondary small fw-bold mb-2">Le Problème</h6>
            <div class="fw-bold fs-5 mb-2"><?= $ticket['type_panne'] ?></div>
            <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($ticket['description_initiale'])) ?></p>
        </div>
    </div>

    <!-- FORMULAIRE DIAGNOSTIC -->
    <h5 class="fw-bold mt-4 mb-3 ps-2">Rapport d'Intervention</h5>
    
    <form action="sav_actions.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_diagnostic">
        <input type="hidden" name="ticket_id" value="<?= $id ?>">

        <div class="card mobile-card">
            <div class="card-body">
                
                <div class="mb-4">
                    <label class="form-label fw-bold">1. Cause identifiée</label>
                    <select name="origine_panne" class="form-select form-select-lg" required>
                        <option value="" disabled selected>Choisir...</option>
                        <option value="USURE">Usure normale</option>
                        <option value="CASSE_CLIENT">Casse client / Mauvaise utilisation</option>
                        <option value="DEFAUT_PRODUIT">Défaut Produit (Garantie)</option>
                        <option value="POSE">Problème de Pose</option>
                        <option value="INCONNU">Non identifié</option>
                    </select>
                </div>

                <div class="mb-4">
                    <div class="form-check form-switch p-0">
                        <label class="form-check-label fw-bold d-block mb-2" for="switchPiece">2. Pièce à remplacer ?</label>
                        <div class="d-flex gap-2">
                            <input class="btn-check" type="radio" name="besoin_piece" id="pieceNon" value="0" checked onchange="togglePiece(false)">
                            <label class="btn btn-outline-success flex-fill p-3" for="pieceNon">NON (Réglage)</label>

                            <input class="btn-check" type="radio" name="besoin_piece" id="pieceOui" value="1" onchange="togglePiece(true)">
                            <label class="btn btn-outline-danger flex-fill p-3" for="pieceOui">OUI (Commander)</label>
                        </div>
                    </div>
                </div>

                <!-- ZONE PIECE (Masquée par défaut) -->
                <div id="zonePiece" class="d-none bg-light p-3 rounded mb-4">
                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase fw-bold">Quelle Pièce ?</label>
                        <input type="text" name="designation_piece" class="form-control form-control-lg" placeholder="Ex: Moteur Somfy...">
                    </div>
                    <div class="mb-3">
                        <label class="btn btn-light border w-100 py-3 text-start text-primary">
                            <i class="fas fa-camera fa-lg me-2"></i>Photo Étiquette / Casse
                            <input type="file" name="photo_diag" accept="image/*" capture="environment" class="d-none" onchange="previewImage(this)">
                        </label>
                        <div id="imgPreview" class="mt-2 text-center text-muted small fst-italic">Aucune photo</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Note Technique (Optionnel)</label>
                    <textarea name="note_technique" class="form-control" rows="3" placeholder="Détails pour le bureau..."></textarea>
                </div>

            </div>
        </div>

        <!-- CONCLUSION -->
        <div class="fixed-bottom bg-white border-top p-3 shadow-lg">
            <button type="submit" class="btn btn-primary btn-xl shadow mb-0">
                <i class="fas fa-check-circle me-2"></i>Valider Intervention
            </button>
        </div>
        
    </form>
    
    <div style="height: 100px;"></div> <!-- Espace pour le footer fixe -->

</div>

<script>
function togglePiece(show) {
    const zone = document.getElementById('zonePiece');
    if (show) zone.classList.remove('d-none');
    else zone.classList.add('d-none');
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        document.getElementById('imgPreview').innerText = "📸 Photo sélectionnée : " + input.files[0].name;
        document.getElementById('imgPreview').classList.add('text-success');
    }
}
</script>

</body>
</html>
