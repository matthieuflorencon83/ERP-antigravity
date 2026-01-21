<?php
// gestion_metrage_universal.php - SUPER MODULE UI V2
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php'; // Includes redirect() etc.

$page_title = "Métrage Universel";

// 1. FETCH TYPES & CATEGORIZATION
// On récupère tout et on trie en PHP selon les règles métier demandées
$stmt = $pdo->query("SELECT * FROM metrage_types ORDER BY nom");
$all_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// MAPPING CATEGORIES (8 FAMILIES - INDUSTRY STANDARD)
$visual_categories = [
    'EXTERIEUR' => ['icon' => 'fas fa-door-open', 'label' => 'Menuiserie Ext.', 'desc' => 'Fenêtres, Portes, Baies...'],
    'FERMETURE' => ['icon' => 'fas fa-blinds', 'label' => 'Fermeture', 'desc' => 'Volets Roulants, Battants...'],
    'GARAGE'    => ['icon' => 'fas fa-warehouse', 'label' => 'Garage', 'desc' => 'Portes de Garage, Indus...'],
    'CLOTURE'   => ['icon' => 'fas fa-torii-gate', 'label' => 'Extérieur', 'desc' => 'Portails, Clôtures...'],
    'SOLAIRE'   => ['icon' => 'fas fa-umbrella-beach', 'label' => 'P. Solaire', 'desc' => 'Stores Banne, Pergolas...'],
    'INTERIEUR' => ['icon' => 'fas fa-couch', 'label' => 'Intérieur', 'desc' => 'Bloc-portes, Verrières...'],
    'STRUCTURE' => ['icon' => 'fas fa-home', 'label' => 'Structure', 'desc' => 'Vérandas, SAS, Loggias...'],
    'MOUSTIQUAIRE' => ['icon' => 'fas fa-bug', 'label' => 'Moustiquaire', 'desc' => 'Cadres, Enroulables...']
];

$grouped_types = [];
foreach ($all_types as $t) {
    // USE DB COLUMN 'famille' if available, fallback to old logic ?
    // Since we reseeded, we trust 'famille'.
    $key = $t['famille'] ?? 'AUTRE';
    if (!isset($visual_categories[$key])) $key = 'EXTERIEUR'; // Fallback
    
    $grouped_types[$key][] = $t;
}

// Ensure all keys exist empty if none
foreach ($visual_categories as $k => $v) {
    if (!isset($grouped_types[$k])) $grouped_types[$k] = [];
}

require_once 'header.php';
?>

<!-- CSS SPECIFIQUE -->
<link rel="stylesheet" href="assets/css/metrage_universal.css?v=<?= time() ?>">

<!-- CONTENEUR PRINCIPAL (ARCHITECTURE HYBRIDE) -->
<div class="ag-universal-grid">

    <!-- ========== COLONNE GAUCHE (INTERACTIVE) ========== -->
    <div class="zone-saisie">
        
        <!-- HEADER MOBILE (FOCUS MODE TRIGGER) -->
        <div class="d-md-none mb-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold m-0 text-primary">Nouveau Métrage</h5>
            <button class="btn btn-sm btn-light border" onclick="toggleSidebarMobile()">
                <i class="fas fa-info-circle"></i> Recap
            </button>
        </div>

        <!-- VUE 1 : SELECTEUR (HOME) -->
        <div id="view-selector" class="fade-in-up">
            <h2 class="fw-bold mb-4">Que souhaitez-vous métrer ?</h2>
            
            <div class="category-grid">
                <?php foreach ($visual_categories as $key => $meta): ?>
                <div class="cat-card" onclick="showSubtypes('<?= $key ?>')">
                    <i class="<?= $meta['icon'] ?>"></i>
                    <h5><?= $meta['label'] ?></h5>
                    <small><?= $meta['desc'] ?></small>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- SUBTYPES CONTAINERS (Hidden by default) -->
            <?php foreach ($grouped_types as $key => $types): ?>
            <div id="subtypes-<?= $key ?>" class="mt-4 subtype-container" style="display:none;">
                <div class="d-flex align-items-center mb-3">
                    <button class="btn btn-sm btn-outline-secondary me-3" onclick="backToCategories()"><i class="fas fa-arrow-left"></i> Retour</button>
                    <h5 class="fw-bold m-0 text-primary">Modèles : <?= $visual_categories[$key]['label'] ?></h5>
                </div>
                
                <div class="subtype-grid">
                    <?php 
                    if (empty($types)) echo "<p class='text-muted'>Aucun modèle disponible.</p>";
                    foreach ($types as $t): 
                    ?>
                        <button class="subtype-btn" onclick="startWizard(<?= $t['id'] ?>, '<?= h($t['nom']) ?>')">
                            <div class="fw-bold"><?= h($t['nom']) ?></div>
                            <small class="text-muted"><?= h($t['categorie']) ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- VUE 2 : WIZARD V3 (GUIDED) -->
        <div id="view-wizard" style="display:none;">
            
            <!-- STEPPER V3 -->
            <div class="ag-stepper">
                <div class="step-item active" id="step-1">
                    <div class="step-circle">1</div>
                    <div class="step-label">Produit</div>
                </div>
                <div class="step-item" id="step-2">
                    <div class="step-circle">2</div>
                    <div class="step-label">Pose/Env.</div>
                </div>
                <div class="step-item" id="step-3">
                    <div class="step-circle">3</div>
                    <div class="step-label">Cotes</div>
                </div>
                <div class="step-item" id="step-4">
                    <div class="step-circle">4</div>
                    <div class="step-label">Détails</div>
                </div>
            </div>

            <!-- DYNAMIC CONTAINER -->
            <div id="wizard-container" class="mt-4">
                <!-- AJAX CONTENT -->
            </div>

        </div>

        </div>

    <!-- ========== COLONNE DROITE (ASSISTANT VIRTUEL) ========== -->
    <div class="zone-sidebar shadow-sm" id="mainSidebar">
        
        <!-- HEADER ASSISTANT -->
        <div class="d-flex align-items-center mb-4 border-bottom pb-3">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                <i class="fas fa-robot"></i>
            </div>
            <div>
                <h6 class="fw-bold m-0">L'Assistant</h6>
                <small class="text-success"><i class="fas fa-circle small me-1"></i>En veille active</small>
            </div>
        </div>

        <!-- FLUX DE MESSAGES (ALERTS) -->
        <div id="assistant-stream" class="flex-grow-1 overflow-auto mb-3" style="min-height: 200px;">
            <!-- JS injects alerts here -->
        </div>

        <!-- CHECKLIST ANTI-OUBLI -->
        <div class="card border-0 bg-light rounded-3 mt-auto">
            <div class="card-header bg-transparent border-0 fw-bold text-muted">
                <i class="fas fa-tasks me-2"></i>Points de vigilance
            </div>
            <div class="card-body pt-0" id="assistant-checklist-body">
                <p class="text-muted small fst-italic">En attente de sélection...</p>
            </div>
        </div>
        
        <!-- MEDIA PREVIEW PLACEHOLDER (Moved from top) -->
        <div id="live-preview" class="mt-3 text-center" style="display:none;">
            <!-- Svg render -->
        </div>
    </div>

    <!-- STICKY FOOTER MOBILE (THUMB ZONE) -->
    <div class="sticky-footer-controls">
        <button class="btn btn-light btn-nav text-muted border" onclick="prevStepMobile()"><i class="fas fa-chevron-left"></i></button>
        <button class="btn btn-primary btn-fab-center" onclick="openPhotoMobile()"><i class="fas fa-camera"></i></button>
        <button class="btn btn-primary btn-nav shadow-sm" onclick="nextStepMobile()">Suivant <i class="fas fa-chevron-right ms-2"></i></button>
    </div>

    <!-- HTML for Mobile Sidebar Overlay (Drawer Close Button) -->
    <div class="d-lg-none" style="position:fixed; top:10px; right:10px; z-index:1060;" id="mobile-sidebar-close" hidden>
        <button class="btn btn-light shadow-sm rounded-circle" onclick="toggleSidebarMobile()"><i class="fas fa-times"></i></button>
    </div>

</div>

<!-- MODAL ANNOTATEUR (PLEIN ECRAN) -->
<div id="modal-annotator" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:9999;">
    
    <!-- TOOLBAR -->
    <div class="d-flex justify-content-between align-items-center p-3 text-white bg-dark">
        <div>
            <button class="btn btn-outline-danger me-2" onclick="MetrageMedia.setColor('red')"><i class="fas fa-circle text-danger"></i></button>
            <button class="btn btn-outline-warning me-2" onclick="MetrageMedia.setColor('yellow')"><i class="fas fa-circle text-warning"></i></button>
            <button class="btn btn-outline-light" onclick="MetrageMedia.setTool('pen')"><i class="fas fa-pen"></i></button>
        </div>
        <h5 class="m-0">Annotation</h5>
        <div>
            <button class="btn btn-secondary me-2" onclick="$('#modal-annotator').fadeOut()">Annuler</button>
            <button class="btn btn-success" onclick="MetrageMedia.save()"><i class="fas fa-save me-2"></i>Garder</button>
        </div>
    </div>

    <!-- CANVAS AREA -->
    <div class="d-flex justify-content-center align-items-center h-100 pb-5">
        <canvas id="annotation-canvas" style="background:white; cursor:crosshair;"></canvas>
    </div>
</div>

<!-- SCRIPTS -->
<script src="assets/js/metrage_media.js?v=<?= time() ?>"></script>
<script src="assets/js/metrage_assistant.js?v=<?= time() ?>"></script>
<script>
// NAVIGATION
function showSubtypes(catKey) {
    // Hide Categories
    $('.category-grid').hide();
    $('.subtype-container').hide();
    
    // Show Subtypes
    $('#subtypes-' + catKey).fadeIn();
}

function backToCategories() {
    $('.subtype-container').hide();
    $('.category-grid').fadeIn();
}

function startWizard(typeId, typeName) {
    // Switch View
    $('#view-selector').hide();
    $('#view-wizard').fadeIn();
    
    // Update State
    $('#wizard-container').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p>Chargement du module ' + typeName + '...</p></div>');
    
    // FETCH FORM (PHASE 3 ACTIVE)
    $.ajax({
        url: 'gestion_metrage_ajax.php',
        method: 'GET',
        data: { action: 'get_form', type_id: typeId },
        success: function(response) {
            $('#wizard-container').html(response);
            $('#wizard-container').hide().fadeIn(); // Smooth transition
        },
        error: function() {
            $('#wizard-container').html('<div class="alert alert-danger">Erreur de chargement du formulaire.</div>');
        }
    });

    // Mobile Focus Mode
    if (window.innerWidth < 1024) {
        $('body').addClass('mobile-focus');
        // Hide Main Site Header if possible
        $('header').hide(); 
    }
}

function resetView() {
    $('#view-wizard').hide();
    $('#view-selector').fadeIn();
    backToCategories();
    $('header').show(); // Restore
}

// MOBILE SIDEBAR
function toggleSidebarMobile() {
    $('#mainSidebar').toggleClass('active');
    $('#mobile-sidebar-close').attr('hidden', !$('#mainSidebar').hasClass('active'));
    // If active, show overlay?
}

// STICKY FOOTER ACTIONS (PROXY)
function nextStepMobile() {
    // Trigger the real 'Next' button inside the form (assumed ID #btn-next-step)
    // Or call the JS function if defined
    if (typeof nextStep === 'function') nextStep(); 
    else $('.btn-next').click(); // Fallback selector
}

function prevStepMobile() {
    if (typeof prevStep === 'function') prevStep(); 
    else $('.btn-prev').click();
}

function openPhotoMobile() {
    // Trigger the first visible photo button or open generic media modal
    // For now, let's open the Media Modal if available or scroll to media section
    // Or ideally, open the "Required Photo" if one is pending
    const pendingPhoto = $('.btn-photo-required').first();
    if (pendingPhoto.length) {
        pendingPhoto.click();
        // Scroll to it?
        $('html, body').animate({
            scrollTop: pendingPhoto.offset().top - 100
        }, 500);
    } else {
         alert("Aucune photo requise pour l'instant. Utilisez le bouton 'Ajouter Média' dans le formulaire.");
    }
}
</script>

<?php require_once 'footer.php'; ?>

