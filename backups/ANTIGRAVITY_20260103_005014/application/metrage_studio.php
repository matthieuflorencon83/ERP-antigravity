<?php
/**
 * metrage_studio.php - Interface Studio Métrage V4.0 (DEPLOYED)
 * 
 * Architecture : Clean, Modulaire, Responsive
 * Constitution v3.0 : Split View (Desktop) / Focus Mode (Mobile)
 * 
 * @version 4.0.0
 */

require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$metrage_id = $_GET['id'] ?? 0;

// Données métrage
$intervention = ['nom_affaire' => 'Métrage Libre', 'client_nom' => 'Non lié'];
$lignes = [];
$types = [];
$affaires = [];

// Charger affaires (pour Select2)
try {
    $stmtAff = $pdo->query("SELECT a.id, a.nom_affaire, c.nom_principal as client_nom 
        FROM affaires a 
        LEFT JOIN clients c ON a.client_id = c.id 
        ORDER BY a.id DESC LIMIT 1000");
    $affaires = $stmtAff->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Si métrage existant, charger données
if ($metrage_id) {
    try {
        $stmt = $pdo->prepare("SELECT i.*, a.nom_affaire, c.nom_principal as client_nom 
            FROM metrage_interventions i 
            LEFT JOIN affaires a ON i.affaire_id = a.id 
            LEFT JOIN clients c ON a.client_id = c.id 
            WHERE i.id = ?");
        $stmt->execute([$metrage_id]);
        $result = $stmt->fetch();
        if ($result) $intervention = $result;
        
        $stmt = $pdo->prepare("SELECT l.*, t.nom AS type_nom, t.categorie, t.slug AS type_slug
            FROM metrage_lignes l
            JOIN metrage_types t ON l.metrage_type_id = t.id
            WHERE l.intervention_id = ?
            ORDER BY l.ordre ASC");
        $stmt->execute([$metrage_id]);
        $lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Décoder JSON
        foreach ($lignes as &$ligne) {
            $ligne['donnees_json'] = json_decode($ligne['donnees_json'], true);
        }
    } catch (PDOException $e) {}
}

// Charger types produits
try {
    $types = $pdo->query("SELECT id, slug, nom, categorie, famille
        FROM metrage_types
        ORDER BY categorie, nom")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading metrage_types: " . $e->getMessage());
    $types = [];
}

$page_title = "Studio Métrage V4";
require_once 'header.php';
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Animate.css (pour animations) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<!-- Studio CSS V4 -->
<link rel="stylesheet" href="assets/css/metrage_studio_v4.css?v=<?= time() ?>">

<!-- ====================================== -->
<!-- LAYOUT PRINCIPAL (3 ZONES)            -->
<!-- ====================================== -->
<div class="studio-wrapper-v4">
    
    <!-- ZONE 1: SIDEBAR (Contexte & Arborescence) -->
    <aside class="studio-sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-project-diagram me-2"></i>Projet</h3>
            <button class="btn-close-sidebar d-lg-none" onclick="toggleSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Contexte Affaire -->
        <div class="context-card">
            <div class="context-label">AFFAIRE</div>
            <div class="context-value" id="context-affaire">
                <?= htmlspecialchars($intervention['nom_affaire'] ?? 'Métrage Libre') ?>
            </div>
            <div class="context-label mt-2">CLIENT</div>
            <div class="context-value" id="context-client">
                <?= htmlspecialchars($intervention['client_nom'] ?? 'Non lié') ?>
            </div>
        </div>
        
        <!-- Arborescence Produits -->
        <div class="tree-section">
            <div class="tree-header">
                <span>Produits</span>
                <span class="badge bg-primary" id="product-count">0</span>
            </div>
            <div class="tree-container" id="tree_products">
                <!-- Rempli par JS -->
            </div>
        </div>
    </aside>
    
    <!-- ZONE 2: CENTRE (Assistant & Inputs) -->
    <main class="studio-main">
        <!-- Top Bar -->
        <div class="studio-topbar">
            <button class="btn-toggle-sidebar d-lg-none" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="studio-title">Studio V4</h1>
            <button class="btn-toggle-recap" onclick="toggleRecap()">
                <i class="fas fa-info-circle"></i>
            </button>
        </div>
        
        <!-- Assistant Stream (Messages) -->
        <div class="assistant-stream" id="assistant_scroll">
            <div class="assistant-container">
                <div id="assistant_messages">
                    <!-- Messages assistant remplis par JS -->
                </div>
            </div>
            
            <!-- Zone Input (Sticky Bottom) -->
            <div class="input-zone" id="input_zone_wrapper" style="display:none;">
                <div class="input-container" id="input_container">
                    <!-- Inputs dynamiques rendus par JS -->
                </div>
            </div>
        </div>
    </main>
    
    <!-- ZONE 3: RECAP (Mémos & Alertes) -->
    <aside class="studio-recap" id="recap">
        <div class="recap-header">
            <h3><i class="fas fa-clipboard-list me-2"></i>Récap</h3>
            <button class="btn-close-recap d-lg-none" onclick="toggleRecap()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Mémos -->
        <div class="memo-section" id="knowledge_memos">
            <div class="memo-item memo-info">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Astuce</strong>
                    <p>Toutes vos données sont sauvegardées automatiquement</p>
                </div>
            </div>
        </div>
        
        <!-- Alertes -->
        <div class="alert-section" id="alerts_container">
            <!-- Alertes dynamiques -->
        </div>
    </aside>
</div>

<!-- ====================================== -->
<!-- INJECTION DONNÉES PHP → JAVASCRIPT    -->
<!-- ====================================== -->
<script>
// Variables globales (accessibles par modules ES6)
window.METRAGE_ID = <?= $metrage_id ?>;
window.INTERVENTION = <?= json_encode($intervention) ?>;
window.LIGNES = <?= json_encode($lignes) ?>;
window.TYPES = <?= json_encode($types) ?>;
window.AFFAIRES = <?= json_encode($affaires) ?>;

// Fonctions toggle (legacy, à migrer dans UI module)
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}
function toggleRecap() {
    document.getElementById('recap').classList.toggle('open');
}
</script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Module ES6 Principal -->
<script type="module" src="assets/js/modules/main.js?v=<?= time() ?>_v4"></script>

<?php require_once 'footer.php'; ?>
