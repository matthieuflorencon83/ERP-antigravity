<?php
// ged_init_structure.php
// Script utilitaire pour initialiser l'arborescence GED physique
// À exécuter une fois via le navigateur : http://localhost/antigravity/ged_init_structure.php

require_once 'db.php';
// On n'inclut PAS functions.php ici pour éviter des conflits si header.php l'inclut déjà, 
// mais on s'assure que header.php est bien là.
include 'header.php';

// --- CONFIGURATION ---
// IMPORTANT : Assurez-vous que Laragon/Apache a les droits d'écriture ici.
// Si C:/ARTSALU bloque, essayez un chemin dans le dossier du projet pour tester, ex: __DIR__ . '/ged_test'
// On crée un dossier "ged_docs" directement à la racine du site Antigravity
// GED_ROOT est défini dans db.php (c.f. C:/ARTSALU)
// define('GED_ROOT', __DIR__ . '/ged_docs'); 

// --- FONCTIONS ---
function sanitize_folder_name($str) {
    if (!$str) return 'INCONNU';
    $str = strip_tags($str); 
    $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
    $str = trim($str);
    // Enlever les accents pour éviter les soucis Windows/Encoding
    $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    // Garder uniquement alphanumérique, tirets et underscores
    $str = preg_replace('/[^a-zA-Z0-9\-\_ ]/', '', $str);
    return trim($str);
}

$logs = [];
$stats = ['created' => 0, 'exists' => 0, 'errors' => 0, 'updated_db' => 0];

// 1. Vérification de la racine
if (!is_dir(GED_ROOT)) {
    if (!mkdir(GED_ROOT, 0777, true)) {
        echo "<div class='alert alert-danger'>Erreur critique : Impossible de créer la racine " . GED_ROOT . ". Vérifiez les permissions.</div>";
        exit;
    }
}

// 2. Récupération des affaires
$sql = "
    SELECT a.id, a.nom_affaire, a.date_creation, c.nom_principal as nom_client, a.chemin_dossier_ged
    FROM affaires a
    JOIN clients c ON a.client_id = c.id
    ORDER BY a.date_creation DESC
";
$stmt = $pdo->query($sql);
$affaires = $stmt->fetchAll();

// 3. Traitement
foreach ($affaires as $aff) {
    // Détermination de l'année
    $annee = $aff['date_creation'] ? date('Y', strtotime($aff['date_creation'])) : date('Y');
    
    // Nettoyage
    $client_clean = sanitize_folder_name($aff['nom_client']);
    $affaire_clean = sanitize_folder_name($aff['nom_affaire']);
    
    // Construction chemin : ANNEE / CLIENT / AFFAIRE
    $path_annee = GED_ROOT . '/' . $annee;
    $path_client = $path_annee . '/' . $client_clean;
    $path_finale = $path_client . '/' . $affaire_clean;
    
    $status = "";
    $css_class = "";

    // Création récursive
    if (!is_dir($path_finale)) {
        if (mkdir($path_finale, 0777, true)) {
            $status = "✅ Créé";
            $css_class = "text-success fw-bold";
            $stats['created']++;
            
            // Sous-dossiers standards
            @mkdir($path_finale . '/PLANS', 0777, true);
            @mkdir($path_finale . '/DEVIS', 0777, true);
            @mkdir($path_finale . '/PHOTOS', 0777, true);
        } else {
            $status = "❌ Erreur Droits";
            $css_class = "text-danger fw-bold";
            $stats['errors']++;
        }
    } else {
        $status = "ℹ️ Existant";
        $css_class = "text-muted";
        $stats['exists']++;
    }

    // Mise à jour BDD
    // On met à jour si le chemin est vide OU différent
    if ($aff['chemin_dossier_ged'] !== $path_finale) {
        $upd = $pdo->prepare("UPDATE affaires SET chemin_dossier_ged = ? WHERE id = ?");
        $upd->execute([$path_finale, $aff['id']]);
        $stats['updated_db']++;
        if($status == "ℹ️ Existant") $status .= " + SQL MAJ";
    }

    $logs[] = [
        'annee' => $annee,
        'client' => $client_clean,
        'affaire' => $affaire_clean,
        'full_path' => $path_finale,
        'status' => $status,
        'css' => $css_class
    ];
}
?>

<div class="container py-4">
    <div class="card-ag">
        <div class="card-ag-header">
            <h5 class="mb-0"><i class="fas fa-network-wired me-2"></i>Migration Structure GED</h5>
            <span class="badge bg-white text-primary"><?= count($affaires) ?> Affaires</span>
        </div>
        <div class="card-body">
            
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="p-3 rounded border text-center bg-light">
                        <div class="fs-2 text-success fw-bold"><?= $stats['created'] ?></div>
                        <div class="text-muted small text-uppercase">Dossiers Créés</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded border text-center bg-light">
                        <div class="fs-2 text-primary fw-bold"><?= $stats['updated_db'] ?></div>
                        <div class="text-muted small text-uppercase">SQL Mis à jour</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded border text-center bg-light">
                        <div class="fs-2 text-secondary fw-bold"><?= $stats['exists'] ?></div>
                        <div class="text-muted small text-uppercase">Déjà Existants</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded border text-center bg-light">
                        <div class="fs-2 text-danger fw-bold"><?= $stats['errors'] ?></div>
                        <div class="text-muted small text-uppercase">Erreurs</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Année</th>
                            <th>Client</th>
                            <th>Dossier Affaire</th>
                            <th>Chemin Système</th>
                            <th>État</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= $log['annee'] ?></td>
                            <td><?= $log['client'] ?></td>
                            <td><?= $log['affaire'] ?></td>
                            <td class="small text-muted font-monospace"><?= str_replace('/', '\\', $log['full_path']) ?></td>
                            <td class="<?= $log['css'] ?>"><?= $log['status'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 text-end">
                <a href="affaires_liste.php" class="btn btn-primary">
                    <i class="fas fa-arrow-right me-2"></i>Retour aux Affaires
                </a>
            </div>

        </div>
    </div>
</div>
