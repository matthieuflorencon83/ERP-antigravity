<?php
// catalogue_detail.php - Création et Édition d'un article
session_start();
require_once 'db.php';
require_once 'functions.php'; // Pour clean_filename()

$id = $_GET['id'] ?? 0;
$message = "";

// 1. TRAITEMENT DU FORMULAIRE (SAUVEGARDE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération des champs
    $ref_interne = trim($_POST['reference_interne']);
    $designation = trim($_POST['designation']);
    $famille     = $_POST['famille'];
    $fournisseur_id = !empty($_POST['fournisseur_id']) ? $_POST['fournisseur_id'] : null;
    $ref_fournisseur = trim($_POST['ref_fournisseur'] ?? '');
    
    $prix_achat  = floatval($_POST['prix_achat_ht']);
    
    // Données techniques
    $poids_kg    = floatval($_POST['poids_kg'] ?? 0);
    $poids_ml    = floatval($_POST['poids_metre_lineaire'] ?? 0);
    $inertie     = floatval($_POST['inertie_lx'] ?? 0);
    $longueur    = intval($_POST['longueur_barre'] ?? 0);
    $unite       = $_POST['unite_stock'] ?? 'U';
    $type_vente  = $_POST['type_vente'] ?? 'PIECE';
    $cond_qte    = intval($_POST['conditionnement_qte'] ?? 1);

    // Stock
    $stock_min   = intval($_POST['stock_min'] ?? 0);
    $stock_phys  = floatval($_POST['stock_physique'] ?? 0);

    // GESTION IMAGE
    $image_path = $_POST['current_image'] ?? null;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $dossier_img = "uploads/catalogue/";
        if (!is_dir($dossier_img)) mkdir($dossier_img, 0777, true);

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $nom_fichier = "ART_" . time() . "_" . clean_filename($ref_interne) . "." . $ext;
            $cible = $dossier_img . $nom_fichier;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $cible)) {
                $image_path = $cible;
            }
        }
    }

    try {
        if ($id > 0) {
            // UPDATE
            $sql = "UPDATE articles SET 
                    reference_interne=?, designation=?, famille=?, fournisseur_prefere_id=?, ref_fournisseur=?,
                    prix_achat_ht=?, poids_kg=?, poids_metre_lineaire=?, inertie_lx=?, longueur_barre=?, 
                    unite_stock=?, type_vente=?, conditionnement_qte=?,
                    seuil_alerte_stock=?, stock_physique=?, image_path=? 
                    WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $ref_interne, $designation, $famille, $fournisseur_id, $ref_fournisseur,
                $prix_achat, $poids_kg, $poids_ml, $inertie, $longueur,
                $unite, $type_vente, $cond_qte,
                $stock_min, $stock_phys, $image_path, $id
            ]);
            $message = "<div class='alert alert-success'>Article mis à jour !</div>";
        } else {
            // INSERT
            $sql = "INSERT INTO articles 
                    (reference_interne, designation, famille, fournisseur_prefere_id, ref_fournisseur,
                     prix_achat_ht, poids_kg, poids_metre_lineaire, inertie_lx, longueur_barre, 
                     unite_stock, type_vente, conditionnement_qte,
                     seuil_alerte_stock, stock_physique, image_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $ref_interne, $designation, $famille, $fournisseur_id, $ref_fournisseur,
                $prix_achat, $poids_kg, $poids_ml, $inertie, $longueur,
                $unite, $type_vente, $cond_qte,
                $stock_min, $stock_phys, $image_path
            ]);
            $id = $pdo->lastInsertId();
            $message = "<div class='alert alert-success'>Nouvel article créé !</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Erreur SQL : " . $e->getMessage() . "</div>";
    }
}

// 2. CHARGEMENT DES DONNÉES
$article = [
    'reference_interne' => '', 'designation' => '', 'famille' => 'PROFIL', 
    'fournisseur_prefere_id' => '', 'ref_fournisseur' => '', 'prix_achat_ht' => 0, 
    'poids_kg' => 0, 'poids_metre_lineaire' => 0, 'inertie_lx' => 0, 'longueur_barre' => 6500, 
    'unite_stock' => 'U', 'type_vente' => 'PIECE', 'conditionnement_qte' => 1,
    'seuil_alerte_stock' => 5, 'stock_physique' => 0, 'image_path' => ''
];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    $trouve = $stmt->fetch();
    if ($trouve) $article = $trouve;
}

// Liste des fournisseurs pour le menu déroulant
$fournisseurs = $pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom")->fetchAll();
$fournisseurs = $pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom")->fetchAll();

$page_title = $id > 0 ? 'Édition Article' : 'Nouvel Article';
require_once 'header.php';
?>

<div class="main-content">
    <?= $message ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="catalogue_liste.php" class="btn btn-light shadow-sm me-2"><i class="fas fa-arrow-left"></i> Retour</a>
            </div>
            <button type="submit" class="btn btn-petrol btn-lg shadow rounded-pill">
                <i class="fas fa-save me-2"></i> Enregistrer
            </button>
        </div>

        <div class="row">
            <!-- COLONNE GAUCHE : PHOTO & ID -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-primary text-white fw-bold py-3">
                        <i class="fas fa-id-card me-2"></i> Identification
                    </div>
                    <div class="card-body">
                        <div class="mb-3 border rounded bg-light d-flex align-items-center justify-content-center mx-auto" style="width: 200px; height: 200px; overflow: hidden;">
                            <?php if ($article['image_path']): ?>
                                <img src="<?= htmlspecialchars($article['image_path']) ?>" class="w-100 h-100 object-fit-cover">
                            <?php else: ?>
                                <i class="fas fa-camera fa-3x text-muted opacity-50"></i>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="current_image" value="<?= htmlspecialchars($article['image_path']) ?>">
                        <input type="file" name="image" class="form-control mb-3" accept="image/*">

                        <hr>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Référence (Interne) *</label>
                            <input type="text" name="reference_interne" class="form-control fw-bold font-monospace border-primary-subtle" required 
                                   value="<?= htmlspecialchars($article['reference_interne']) ?>" placeholder="Ex: P-7016">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Référence Fournisseur</label>
                            <input type="text" name="ref_fournisseur" class="form-control" 
                                   value="<?= htmlspecialchars($article['ref_fournisseur']) ?>" placeholder="REF-FOU-123">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Famille</label>
                            <select name="famille" class="form-select">
                                <option value="PROFIL" <?= $article['famille']=='PROFIL'?'selected':'' ?>>Profilé Alu</option>
                                <option value="ACCESSOIRE" <?= $article['famille']=='ACCESSOIRE'?'selected':'' ?>>Accessoire / Joint</option>
                                <option value="VITRAGE" <?= $article['famille']=='VITRAGE'?'selected':'' ?>>Vitrage / Remplissage</option>
                                <option value="QUINCAILLERIE" <?= $article['famille']=='QUINCAILLERIE'?'selected':'' ?>>Quincaillerie</option>
                                <option value="VISSERIE" <?= $article['famille']=='VISSERIE'?'selected':'' ?>>Visserie / Fixation</option>
                                <option value="CONSOMMABLE" <?= $article['famille']=='CONSOMMABLE'?'selected':'' ?>>Consommable (Silicone...)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLONNE DROITE : DETAILS -->
            <div class="col-md-8 mb-4">
                
                <!-- 1. DONNEES TECHNIQUES -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white fw-bold py-3">
                        <i class="fas fa-ruler-combined me-2"></i> Données Techniques
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label fw-bold">Désignation Complète *</label>
                                <input type="text" name="designation" class="form-control" required 
                                       value="<?= htmlspecialchars($article['designation']) ?>" placeholder="Ex: Dormant frappe rupture de pont...">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Fournisseur Pref.</label>
                                <select name="fournisseur_id" class="form-select">
                                    <option value="">-- Aucun --</option>
                                    <?php foreach($fournisseurs as $fourn): ?>
                                        <option value="<?= $fourn['id'] ?>" <?= $article['fournisseur_prefere_id']==$fourn['id']?'selected':'' ?>>
                                            <?= htmlspecialchars($fourn['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row bg-light p-3 rounded mb-3 border">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Type Vente</label>
                                <select name="type_vente" class="form-select">
                                    <option value="PIECE" <?= $article['type_vente']=='PIECE'?'selected':'' ?>>Pce (À la pièce)</option>
                                    <option value="BARRE" <?= $article['type_vente']=='BARRE'?'selected':'' ?>>Barre (Lg fixe)</option>
                                    <option value="METRE" <?= $article['type_vente']=='METRE'?'selected':'' ?>>Mètre (Coupe)</option>
                                    <option value="M2" <?= $article['type_vente']=='M2'?'selected':'' ?>>M² (Surface)</option>
                                    <option value="BOITE" <?= $article['type_vente']=='BOITE'?'selected':'' ?>>Boîte (Lot)</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Unité Stock</label>
                                <select name="unite_stock" class="form-select">
                                    <option value="U" <?= $article['unite_stock']=='U'?'selected':'' ?>>Unité (U)</option>
                                    <option value="ML" <?= $article['unite_stock']=='ML'?'selected':'' ?>>Mètre Linéaire (ML)</option>
                                    <option value="M2" <?= $article['unite_stock']=='M2'?'selected':'' ?>>Mètre Carré (M2)</option>
                                    <option value="KG" <?= $article['unite_stock']=='KG'?'selected':'' ?>>Kilogramme (KG)</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cond. (Qté/Boite)</label>
                                <input type="number" name="conditionnement_qte" class="form-control" value="<?= $article['conditionnement_qte'] ?? 1 ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Longueur (mm)</label>
                                <input type="number" name="longueur_barre" class="form-control" value="<?= $article['longueur_barre'] ?>" placeholder="Ex: 6500">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Poids (kg/U)</label>
                                <input type="number" step="0.001" name="poids_kg" class="form-control" value="<?= $article['poids_kg'] ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Poids (kg/ml)</label>
                                <input type="number" step="0.001" name="poids_metre_lineaire" class="form-control" value="<?= $article['poids_metre_lineaire'] ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Inertie (cm4)</label>
                                <input type="number" step="0.01" name="inertie_lx" class="form-control" value="<?= $article['inertie_lx'] ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                     <!-- 2. PRIX -->
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white fw-bold py-3 text-primary border-bottom">
                                <i class="fas fa-euro-sign me-2"></i> Prix Achat
                            </div>
                            <div class="card-body">
                                <label class="form-label text-muted">Prix Achat HT</label>
                                <div class="input-group mb-2">
                                    <input type="number" step="0.01" name="prix_achat_ht" class="form-control fs-4 fw-bold text-end text-dark" 
                                           value="<?= $article['prix_achat_ht'] ?>">
                                    <span class="input-group-text">€</span>
                                </div>
                                <div class="small text-muted fst-italic">Prix catalogue par défaut</div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. STOCK -->
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white fw-bold py-3 text-success border-bottom">
                                <i class="fas fa-boxes me-2"></i> Stock & Inventaire
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Stock Physique Actuel</label>
                                    <input type="number" step="0.01" name="stock_physique" class="form-control fw-bold border-success" 
                                           value="<?= $article['stock_physique'] ?>">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Seuil Alerte</label>
                                    <input type="number" name="stock_min" class="form-control" 
                                           value="<?= $article['seuil_alerte_stock'] ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>


</body>
</html>
