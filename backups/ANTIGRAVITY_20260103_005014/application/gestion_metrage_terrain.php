<?php
// gestion_metrage_terrain.php - INTERFACE MOBILE MÉTREUR
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

// 1. FETCH MISSION SPECIFIQUE (Force Load)
if (isset($_GET['id'])) {
    $force_id = (int)$_GET['id'];
    $sql = "
        SELECT mi.*, a.nom_affaire, a.numero_prodevis, c.ville, c.nom_principal, c.adresse_postale
        FROM metrage_interventions mi
        JOIN affaires a ON mi.affaire_id = a.id
        JOIN clients c ON a.client_id = c.id
        WHERE mi.id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$force_id]);
    $active_mission = $stmt->fetch();
    
    // Si trouvée, on vide la liste globale pour ne pas polluer (ou on garde, au choix logic)
    // Ici on simule le mode "Focus"
    $missions = $active_mission ? [$active_mission] : [];
} 
else {
    // 2. FETCH MISSIONS DU JOUR (ou toutes celles assignées)
    // Pour la démo, on prend tout ce qui est A PLANIFIER ou PLANIFIE
    $sql = "
        SELECT mi.*, a.nom_affaire, a.numero_prodevis, c.ville, c.nom_principal, c.adresse_postale
        FROM metrage_interventions mi
        JOIN affaires a ON mi.affaire_id = a.id
        JOIN clients c ON a.client_id = c.id
        WHERE mi.statut IN ('PLANIFIE', 'EN_COURS')
        ORDER BY mi.date_prevue ASC
    ";
    $missions = $pdo->query($sql)->fetchAll();
    
    $active_mission = null;
    foreach($missions as $m) {
        if ($m['statut'] === 'EN_COURS') {
            $active_mission = $m;
            break;
        }
    }
}

// FETCH TYPES FOR WIZARD
$types = $pdo->query("SELECT categorie, id, nom, icone, description_technique FROM metrage_types ORDER BY categorie, nom")->fetchAll(PDO::FETCH_GROUP);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Assistant Métrage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-bottom: 80px; } /* Space for fixed bottom bar */
        .mission-card { border-left: 5px solid #0d6efd; }
        .wizard-step { display: none; }
        .wizard-step.active { display: block; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Categories Grid */
        .cat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .type-btn { 
            background: white; border: 1px solid #dee2e6; border-radius: 12px; padding: 15px; 
            text-align: center; height: 100%; display: flex; flex-direction: column; 
            align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }
        .type-btn:active { transform: scale(0.95); background-color: #e9ecef; }
        .type-btn i { font-size: 2rem; margin-bottom: 10px; color: #0d6efd; }
        
        /* Bottom Action Bar */
        .bottom-bar {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: white; border-top: 1px solid #dee2e6;
            padding: 10px 20px; z-index: 1000;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
    </style>
</head>
<body>

<!-- HEADER MOBILE -->
<nav class="navbar navbar-dark bg-primary sticky-top shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-chevron-left me-2"></i>Métrage</a>
    <span class="navbar-text text-white small" id="headerTitle">
        <?= $active_mission ? 'Mission en cours' : 'Mes Missions' ?>
    </span>
    <div>
        <a href="gestion_metrage_planning.php" class="text-white"><i class="fas fa-desktop"></i></a>
    </div>
  </div>
</nav>

<div class="container mt-3" id="mainContainer">
    
    <!-- VUE 1 : LISTE DES MISSIONS (Si aucune active) -->
    <?php if (!$active_mission): ?>
        <h6 class="text-muted fw-bold mb-3 ps-1">AUJOURD'HUI</h6>
        <?php foreach($missions as $m): ?>
            <div class="card shadow-sm border-0 mb-3 mission-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title fw-bold mb-1"><?= h($m['nom_principal']) ?></h5>
                            <p class="text-muted small mb-2"><?= h($m['nom_affaire']) ?></p>
                            <p class="mb-2"><i class="fas fa-map-marker-alt text-danger me-2"></i><?= h($m['ville']) ?></p>
                        </div>
                        <span class="badge bg-primary rounded-pill"><?= date('H:i', strtotime($m['date_prevue'])) ?></span>
                    </div>
                    
                    <?php if($m['adresse_postale']): ?>
                        <div class="d-grid gap-2 d-md-block mt-3">
                             <a href="https://waze.com/ul?q=<?= urlencode($m['adresse_postale'] . ' ' . $m['ville']) ?>" target="_blank" class="btn btn-outline-info btn-sm rounded-pill">
                                <i class="fab fa-waze me-2"></i>Y aller
                             </a>
                             <button onclick="startMission(<?= $m['id'] ?>)" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">
                                <i class="fas fa-play me-2"></i>Démarrer
                             </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if(empty($missions)): ?>
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3 opacity-50"></i>
                <p class="text-muted">Aucune mission planifiée.</p>
            </div>
        <?php endif; ?>

    <!-- VUE 2 : MISSION ACTIVE (WIZARD) -->
    <?php else: ?>
        <input type="hidden" id="missionId" value="<?= $active_mission['id'] ?>">
        
        <!-- STEP 1 : CHECK-IN -->
        <div id="step-0" class="wizard-step active">
            <div class="text-center py-4">
                <i class="fas fa-map-marked-alt fa-3x text-primary mb-3"></i>
                <h4>Arrivé sur site ?</h4>
                <p class="text-muted"><?= h($active_mission['nom_principal']) ?></p>
                <button class="btn btn-lg btn-primary rounded-pill w-100 mb-3" onclick="nextStep(1)">
                    <i class="fas fa-check me-2"></i>Oui, je commence
                </button>
                <div class="card bg-light border-0 text-start p-3">
                    <h6 class="fw-bold"><i class="fas fa-info-circle me-2"></i>Notes Bureau :</h6>
                    <p class="small mb-0"><?= nl2br(h($active_mission['notes_generales'] ?? 'Aucune note.')) ?></p>
                </div>
            </div>
        </div>

        <!-- STEP 2 : MENU PRINCIPAL (LIST) -->
        <div id="step-1" class="wizard-step">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Relevé</h5>
                <button class="btn btn-sm btn-outline-danger" onclick="closeMission()"><i class="fas fa-times"></i></button>
            </div>
            
            <div id="lignesList" class="mb-3">
                <!-- AJAX CONTENT HERE -->
                <div class="text-center text-muted py-3 small">
                    <i class="fas fa-arrow-down mb-2"></i><br>Ajoutez votre premier ouvrage
                </div>
            </div>

            <button class="btn btn-outline-primary w-100 py-3 rounded-3 border-dashed mb-5" onclick="nextStep(2)">
                <i class="fas fa-plus-circle fa-lg mb-1"></i><br>Ajouter un ouvrage
            </button>
        </div>

        <!-- STEP 3 : CHOIX TYPE -->
        <div id="step-2" class="wizard-step">
            <h5 class="fw-bold mb-3"><button class="btn btn-sm btn-light me-2" onclick="prevStep(1)"><i class="fas fa-arrow-left"></i></button> Choisir Ouvrage</h5>
            
            <?php foreach($types as $cat => $cat_types): ?>
                <h6 class="text-muted small fw-bold mt-3 text-uppercase"><?= $cat ?></h6>
                <div class="cat-grid">
                    <?php foreach($cat_types as $t): ?>
                        <div class="type-btn" data-id="<?= $t['id'] ?>" data-name="<?= h($t['nom']) ?>" onclick="selectType(this)">
                            <i class="<?= $t['icone'] ?>"></i>
                            <span class="small fw-bold lh-sm"><?= h($t['nom']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- STEP 4 : FORMULAIRE DYNAMIQUE -->
        <div id="step-3" class="wizard-step">
             <h5 class="fw-bold mb-3">
                 <button class="btn btn-sm btn-light me-2" onclick="prevStep(2)"><i class="fas fa-arrow-left"></i></button> 
                 <span id="formTitle">Saisie</span>
             </h5>
             
             <form id="dynamicForm">
                 <input type="hidden" name="metrage_type_id" id="inputTypeId">
                 
                 <div class="mb-3">
                     <label class="form-label fw-bold small">Localisation</label>
                     <input type="text" name="localisation" class="form-control" placeholder="Ex: Salon, Ch. 1" required x-webkit-speech>
                 </div>

                 <div id="formFields"></div>
                 
                 <div class="mb-3">
                     <label class="form-label fw-bold small">Notes / Observations</label>
                     <textarea name="notes_observateur" class="form-control" rows="2" placeholder="Dictée vocale possible..."></textarea>
                 </div>

                 <button type="button" class="btn btn-success btn-lg w-100 rounded-pill mt-3 shadow" onclick="saveLigne()">
                     <i class="fas fa-save me-2"></i>Enregistrer
                 </button>
             </form>
        </div>

    <?php endif; ?>
</div>

<!-- BOTTOM BAR (Validation) -->
<?php if ($active_mission): ?>
<div class="bottom-bar" id="validateBar" style="display:none;">
    <div class="small fw-bold text-muted"><span id="countLignes">0</span> ouvrages</div>
    <button class="btn btn-primary rounded-pill btn-sm px-4" onclick="finishMission()">
        Terminer <i class="fas fa-check ms-2"></i>
    </button>
</div>
<?php endif; ?>

<!-- SCRIPT LOGIQUE WIZARD -->
<script src="assets/js/jquery.min.js"></script>
<script>
    // ETAT
    let currentStep = 0;
    
    function startMission(id) {
        // Simple reload to activate mission (In real app, update DB via AJAX first)
        // For demo, we assume clicking start refreshes page into active mode
        // Let's do a quick post
        $.post('gestion_metrage_ajax.php', { action: 'start_mission', id: id }, function(res) {
            window.location.reload();
        });
    }

    function selectType(element) {
        try {
            const id = element.getAttribute('data-id');
            const name = element.getAttribute('data-name');
            // alert("Debug: Clicked " + name + " (ID: " + id + ")");
            loadForm(id, name);
        } catch(e) {
            alert("Error selectType: " + e.message);
        }
    }

    function nextStep(step) {
        $('.wizard-step').removeClass('active');
        $('#step-' + step).addClass('active');
        currentStep = step;
        if(step === 1) {
            refreshLignes();
            $('#validateBar').show();
        } else {
            $('#validateBar').hide(); // Hide validation when in sub-forms
        }
    }

    function prevStep(step) {
        nextStep(step);
    }
    
    // LOAD DYNAMIC FIELDS
    function loadForm(typeId, typeName) {
        try {
            $('#inputTypeId').val(typeId);
            $('#formTitle').text(typeName);
            $('#formFields').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
            
            nextStep(3); // Go to form
            
            // alert("Debug: Launching AJAX for Type " + typeId);
            
            $.get('gestion_metrage_ajax.php', { action: 'get_form', type_id: typeId })
             .done(function(data) {
                 // alert("Debug: AJAX Success (" + data.length + " bytes)");
                 $('#formFields').html(data);
             })
             .fail(function(jqXHR, textStatus, errorThrown) {
                 alert("AJAX ERROR: " + textStatus + " - " + errorThrown);
                 $('#formFields').html('<div class="alert alert-danger">Erreur chargement: ' + textStatus + '</div>');
             });
        } catch(e) {
            alert("Error loadForm: " + e.message);
        }
    }

    // SAVE LIGNE
    function saveLigne() {
        const data = $('#dynamicForm').serialize();
        const missionId = $('#missionId').val();
         
        $.post('gestion_metrage_ajax.php?action=save_ligne&mission_id=' + missionId, data, function(res) {
            // Reset form
            $('#dynamicForm')[0].reset();
            nextStep(1); // Back to list
        });
    }

    // UPDATE LIST
    function refreshLignes() {
        const missionId = $('#missionId').val();
        $.get('gestion_metrage_ajax.php', { action: 'list_lignes', mission_id: missionId }, function(html) {
            $('#lignesList').html(html);
            // Update count logic here potentially
        });
    }
    
    function finishMission() {
        if(confirm("Valider le métrage et générer le rapport ?")) {
             const missionId = $('#missionId').val();
             $.post('gestion_metrage_ajax.php', { action: 'finish_mission', mission_id: missionId }, function() {
                 window.location.href = 'gestion_metrage_planning.php';
             });
        }
    }

    // Initial Load
    <?php if($active_mission): ?>
        $('#validateBar').show();
    <?php endif; ?>

</script>

</body>
</html>
