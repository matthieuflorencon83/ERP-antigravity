<?php
// planning_fiche.php - Fiche d'Intervention (Métrage / Pose)
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$type = $_GET['type'] ?? ''; // METRAGE ou POSE
$id = $_GET['id'] ?? 0;

if (!$id || !$type) die("Paramètres manquants.");

// DATA FETCHING
$data = [];
$client = [];

if ($type === 'METRAGE') {
    $stmt = $pdo->prepare("
        SELECT m.*, a.nom_affaire, a.numero_prodevis, c.nom_principal, c.commentaire as client_adresse, 
               cc_tel.valeur as tel, cc_mail.valeur as email
        FROM metrage_interventions m
        JOIN affaires a ON m.affaire_id = a.id
        JOIN clients c ON a.client_id = c.id
        LEFT JOIN client_coordonnees cc_tel ON (c.id = cc_tel.client_id AND cc_tel.type_contact = 'Mobile')
        LEFT JOIN client_coordonnees cc_mail ON (c.id = cc_mail.client_id AND cc_mail.type_contact = 'Email')
        WHERE m.id = ?
    ");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    $titre_doc = "FICHE DE MÉTRAGE";
    $date_start = $data['date_prevue'];
    $date_end = $data['date_prevue'];
    $technicien = "Métreur";
    
} elseif ($type === 'POSE') {
    $stmt = $pdo->prepare("
        SELECT a.*, c.nom_principal, c.commentaire as client_adresse,
               cc_tel.valeur as tel, cc_mail.valeur as email
        FROM affaires a
        JOIN clients c ON a.client_id = c.id
        LEFT JOIN client_coordonnees cc_tel ON (c.id = cc_tel.client_id AND cc_tel.type_contact = 'Mobile')
        LEFT JOIN client_coordonnees cc_mail ON (c.id = cc_mail.client_id AND cc_mail.type_contact = 'Email')
        WHERE a.id = ?
    ");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    $titre_doc = "BON D'INTERVENTION / POSE";
    $date_start = $data['date_pose_debut'];
    $date_end = $data['date_pose_fin'];
    $technicien = $data['equipe_pose'];
}

if (!$data) die("Intervention introuvable.");

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $titre_doc ?> - <?= h($data['nom_principal']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #525659; } /* Dark background for preview like Acrobat */
        .page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 20mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            position: relative;
        }
        @media print {
            body { background: white; margin: 0; }
            .page { width: 100%; margin: 0; padding: 0; box-shadow: none; min-height: auto; }
            .no-print { display: none !important; }
        }
        .header-logo { font-size: 24px; font-weight: bold; color: #E67E22; }
        .box { border: 2px solid #eee; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .box-title { font-weight: bold; text-transform: uppercase; color: #555; border-bottom: 2px solid #eee; padding-bottom: 5px; margin-bottom: 10px; font-size: 0.9em; }
        .info-label { width: 120px; display: inline-block; color: #777; font-size: 0.9em; }
        .info-val { font-weight: bold; }
    </style>
</head>
<body>

    <div class="d-flex justify-content-center my-3 no-print gap-2">
        <button onclick="window.print()" class="btn btn-primary btn-lg shadow"><i class="fas fa-print me-2"></i>Imprimer / PDF</button>
        <button onclick="window.close()" class="btn btn-secondary btn-lg shadow">Fermer</button>
    </div>

    <div class="page">
        
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div class="header-logo">
                <i class="fas fa-cube me-2"></i>ARTSALU <span class="text-dark">Antigravity</span>
            </div>
            <div class="text-end">
                <h2 class="m-0 fw-bold"><?= $titre_doc ?></h2>
                <div class="text-muted">Affaire : <?= h($data['nom_affaire']) ?></div>
                <small class="text-muted">Ref: <?= h($data['numero_prodevis']) ?? 'N/A' ?></small>
            </div>
        </div>

        <div class="row">
            <!-- CLIENT / LIEU -->
            <div class="col-6">
                <div class="box h-100">
                    <div class="box-title"><i class="fas fa-map-marker-alt me-2"></i>Lieu d'Intervention</div>
                    <div class="fs-5 fw-bold mb-2"><?= h($data['nom_principal']) ?></div>
                    <div class="mb-3">
                        <?= nl2br(h($data['client_adresse'])) ?>
                    </div>
                    <?php if(!empty($data['tel'])): ?>
                        <div><i class="fas fa-phone me-2 text-muted"></i><?= h($data['tel']) ?></div>
                    <?php endif; ?>
                    <?php if(!empty($data['email'])): ?>
                        <div><i class="fas fa-envelope me-2 text-muted"></i><?= h($data['email']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DETAILS MISSION -->
            <div class="col-6">
                <div class="box h-100 bg-light border-0">
                    <div class="box-title"><i class="fas fa-info-circle me-2"></i>Détails Mission</div>
                    
                    <div class="mb-2">
                        <span class="info-label">Date Début :</span>
                        <span class="info-val fs-5"><?= date_fr($date_start ? substr($date_start, 0, 10) : '') ?></span>
                    </div>
                    <?php if($date_end && $date_end != $date_start): ?>
                    <div class="mb-2">
                        <span class="info-label">Date Fin :</span>
                        <span class="info-val"><?= date_fr(substr($date_end, 0, 10)) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-2">
                        <span class="info-label">Équipe / Tech :</span>
                        <span class="info-val badge bg-dark text-white"><?= h($technicien) ?></span>
                    </div>
                    
                    <?php if($type === 'METRAGE'): ?>
                        <div class="alert alert-warning p-2 mt-3 mb-0 small">
                            <i class="fas fa-exclamation-triangle me-1"></i> Vérifier accès et moyens de levage.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- NOTES / INSTRUCTIONS -->
        <div class="box mt-4">
            <div class="box-title"><i class="fas fa-clipboard-list me-2"></i>Instructions & Notes</div>
            <div style="min-height: 100px;">
                <?php if(!empty($data['infos_acces'])): ?>
                    <p><strong>Accès :</strong> <?= nl2br(h($data['infos_acces'])) ?></p>
                <?php endif; ?>
                <?php if(!empty($data['notes_generales'])): ?>
                    <p><strong>Notes :</strong> <?= nl2br(h($data['notes_generales'])) ?></p>
                <?php endif; ?>
                
                <div class="text-muted fst-italic mt-5">
                    Utiliser cet espace pour noter les observations chantier...
                </div>
                <hr class="my-4" style="border-style: dashed;">
                <hr class="my-4" style="border-style: dashed;">
            </div>
        </div>

        <!-- CHECKLIST RAPIDE -->
        <div class="row mt-4">
            <div class="col-6">
                <div class="box">
                    <div class="box-title">Validation Client</div>
                    <div style="height: 100px; border: 1px dashed #ccc; background: #f9f9f9;" class="d-flex align-items-center justify-content-center text-muted small">
                        Signature Client
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="box">
                    <div class="box-title">Validation Équipe</div>
                    <div style="height: 100px; border: 1px dashed #ccc; background: #f9f9f9;" class="d-flex align-items-center justify-content-center text-muted small">
                        Signature Chef d'Équipe
                    </div>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="text-center text-muted small mt-5 pt-5 border-top">
            Document généré par Antigravity le <?= date('d/m/Y H:i') ?>.<br>
            Merci de retourner ce document signé au bureau administratif.
        </div>

    </div>

</body>
</html>
