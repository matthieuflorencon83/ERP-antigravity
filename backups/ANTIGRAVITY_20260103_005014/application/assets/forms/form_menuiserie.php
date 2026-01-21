<?php
// form_menuiserie.php - V3 EXPERT SCENARIO
// Context: "Menuisier Senior"
?>

<!-- ETAPE 1 : DIAGNOSTIC (TYPE DE POSE) -->
<div class="ag-question-block fade-in-up" id="q-pose">
    <div class="ag-question-title">
        <i class="fas fa-home me-2 text-primary"></i>1. Quel est le type de pose ?
    </div>
    
    <div class="row g-3">
        <!-- NEUF -->
        <div class="col-6 col-md-3">
            <input type="radio" class="btn-check" name="fields[pose]" id="pose_applique" value="APPLIQUE" onchange="MetrageRules.updatePoseContext('APPLIQUE')">
            <label class="btn btn-outline-primary w-100 h-100 p-3 text-center" for="pose_applique">
                <i class="fas fa-layer-group fa-2x mb-2"></i><br>
                <strong>APPLIQUE</strong><br>
                <small class="text-muted">Neuf / Extension</small>
            </label>
        </div>
        <!-- RENO -->
        <div class="col-6 col-md-3">
            <input type="radio" class="btn-check" name="fields[pose]" id="pose_reno" value="RENOVATION" onchange="MetrageRules.updatePoseContext('RENOVATION')">
            <label class="btn btn-outline-warning w-100 h-100 p-3 text-center" for="pose_reno">
                <i class="fas fa-recycle fa-2x mb-2"></i><br>
                <strong>RÉNOVATION</strong><br>
                <small class="text-muted">Sur dormant bois</small>
            </label>
        </div>
        <!-- DEPOSE -->
        <div class="col-6 col-md-3">
            <input type="radio" class="btn-check" name="fields[pose]" id="pose_depose" value="DEPOSE_TOTALE" onchange="MetrageRules.updatePoseContext('DEPOSE_TOTALE')">
            <label class="btn btn-outline-danger w-100 h-100 p-3 text-center" for="pose_depose">
                <i class="fas fa-hammer fa-2x mb-2"></i><br>
                <strong>DÉPOSE TOTALE</strong><br>
                <small class="text-muted">Feuillure à nu</small>
            </label>
        </div>
        <!-- TUNNEL -->
        <div class="col-6 col-md-3">
            <input type="radio" class="btn-check" name="fields[pose]" id="pose_tunnel" value="TUNNEL" onchange="MetrageRules.updatePoseContext('TUNNEL')">
            <label class="btn btn-outline-secondary w-100 h-100 p-3 text-center" for="pose_tunnel">
                <i class="fas fa-dungeon fa-2x mb-2"></i><br>
                <strong>TUNNEL</strong><br>
                <small class="text-muted">Entre murs</small>
            </label>
        </div>
    </div>
</div>

<!-- ETAPE 2 : SPECIFICITES (CONDITIONNEL) -->
<div id="zone-specificites">

            <!-- SCENARIO RENO -->
    <div class="ag-question-block bg-warning-subtle fade-in-up" id="bloc_reno" style="display:none;">
        <div class="ag-question-title text-warning-emphasis">
            <i class="fas fa-tools me-2"></i>Spécificités Rénovation
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <!-- TOOLTIP AILE -->
                <label class="form-label fw-bold">
                    Aile de Recouvrement <i class="fas fa-info-circle text-primary" data-bs-toggle="tooltip" title="Mesurer l'épaisseur du bois visible de l'ancien dormant. Standard = 40mm."></i>
                </label>
                <div class="input-group mb-2">
                     <span class="input-group-text"><i class="fas fa-ruler-horizontal"></i></span>
                     <select name="fields[aile_reno]" id="aile_reno_select" class="form-select" onchange="MetrageRules.checkRecouvrement()">
                        <option value="">-- Choisir --</option>
                        <option value="27">27 mm (Standard)</option>
                        <option value="40">40 mm (Large)</option>
                        <option value="70">70 mm (XXL - si dormant banané)</option>
                        <option value="0">Aucune (Cale pelable)</option>
                    </select>
                </div>
                
                <!-- SIMULATEUR RECOUVREMENT -->
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text bg-warning-subtle">Largeur Bois Existant</span>
                    <input type="number" class="form-control" id="larg_dormant_existant" name="fields[larg_dormant_existant]" placeholder="Ex: 48" onkeyup="MetrageRules.checkRecouvrement()">
                    <span class="input-group-text">mm</span>
                </div>
                <div id="alert_recouvrement" class="form-text small" style="display:none;"></div>
                
                <!-- SCHEMA RENO -->
                <div class="text-center mt-2">
                    <img src="assets/img/schema_pose_reno_aile.svg" alt="Schéma Aile" style="height:80px; opacity:0.7;">
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Habillage Extérieur</label>
                <select name="fields[habillage_ext]" class="form-select">
                    <option value="NON">Non</option>
                    <option value="CORNIERE">Cornières d'habillage</option>
                    <option value="BAVETTE">Bavette Alu</option>
                    <option value="KIT">Kit Complet</option>
                </select>
                
                <!-- MANDATORY PHOTO RENO -->
                <div id="photo_dormant_container" class="mt-3 p-2 bg-white rounded border border-warning">
                    <small class="text-danger fw-bold"><i class="fas fa-camera me-1"></i>Preuve Dormant OBLIGATOIRE</small>
                    <div class="d-grid mt-1">
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="$('#upload_photo_dormant').click()">📷 Coupe Dormant</button>
                    </div>
                    <input type="file" id="upload_photo_dormant" class="d-none" accept="image/*" onchange="MetrageMedia.handleUpload(this, 'photo_dormant')">
                    <input type="hidden" name="fields[photo_dormant]" id="photo_dormant">
                    <img id="thumb_photo_dormant" class="img-fluid mt-1" style="display:none; height:60px;">
                </div>
            </div>
            
            <!-- SECURITE SANITAIRE -->
            <div class="col-12 border-top pt-2 mt-2 border-warning">
                <label class="form-label fw-bold text-danger">État sanitaire du dormant ?</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="etat_dormant" id="etat_sain" value="SAIN">
                    <label class="btn btn-outline-success btn-sm" for="etat_sain">Sain</label>
                    <input type="radio" class="btn-check" name="etat_dormant" id="etat_pourri" value="POURRI" onclick="MetrageRules.checkRenoRisk(this)">
                    <label class="btn btn-outline-danger btn-sm" for="etat_pourri">Pourri (STOP)</label>
                </div>
            </div>
        </div>
    </div>

    <!-- SCENARIO NEUF -->
    <div class="ag-question-block bg-info-subtle fade-in-up" id="bloc_neuf" style="display:none;">
        <div class="ag-question-title text-info-emphasis">
            <i class="fas fa-ruler-vertical me-2"></i>Isolation (Doublage)
        </div>
        <div class="row">
            <div class="col-12">
                <!-- TOOLTIP TAPEE -->
                <label class="form-label">
                    Épaisseur Totale Isolation <i class="fas fa-info-circle text-primary" data-bs-toggle="tooltip" title="Correspond à l'épaisseur totale (Isolant + Placo + Colle). Standard = 100-160mm."></i>
                </label>
                <select name="fields[isolation]" class="form-select form-select-lg" onchange="MetrageAssistant.say('Vérifiez que la tapée du devis correspond à ' + this.value + 'mm.', 'warning')">
                    <option value="100">100 mm</option>
                    <option value="120">120 mm</option>
                    <option value="140">140 mm</option>
                    <option value="160">160 mm</option>
                    <option value="180">180 mm</option>
                    <option value="200">200 mm</option>
                </select>
            </div>
        </div>
    </div>

    <!-- SCENARIO DEPOSE -->
    <div class="ag-question-block bg-danger-subtle fade-in-up" id="bloc_depose" style="display:none;">
        <div class="ag-question-title text-danger-emphasis">
            <i class="fas fa-border-style me-2"></i>Maçonnerie à nu
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Profondeur Feuillure (mm)</label>
                <input type="number" name="fields[prof_feuillure]" class="form-control" placeholder="Ex: 40">
            </div>
            <div class="col-md-6">
                <label class="form-label">Hauteur Rejingot (mm)</label>
                <input type="number" name="fields[haut_rejingot]" class="form-control" placeholder="Standard: 25mm">
            </div>
        </div>
    </div>

    <!-- BLOC COMMUN : ACCES & ETAGE -->
    <div class="row g-3 mb-3">
        <label class="form-label fw-bold">Situation du Chantier</label>
        <div class="col-6">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-building"></i></span>
                <input type="number" class="form-control" name="fields[etage]" id="input_etage" placeholder="Étage (0=RDC)" onchange="MetrageRules.checkLogistics()">
            </div>
        </div>
        <div class="col-6">
             <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="check_ascenseur" name="fields[ascenseur_dispo]" onchange="MetrageRules.checkLogistics()">
                <label class="form-check-label" for="check_ascenseur">Ascenseur Dispo ?</label>
            </div>
        </div>
    </div>

    <!-- LOGISTIQUE (CONDITIONNEL MANUTENTION) -->
    <div class="ag-question-block bg-dark-subtle fade-in-up border-dark" id="bloc_manutention" style="display:none;">
        <div class="ag-question-title text-dark">
            <i class="fas fa-dolly me-2"></i>Logistique & Manutention
        </div>
        <div class="alert alert-warning small">
            <i class="fas fa-exclamation-triangle me-2"></i>Accès complexe détecté (Étage ou Poids Lourd).
        </div>
        
        <label class="form-label">Type d'Accès Escalier</label>
        <div class="btn-group w-100 mb-3" role="group">
             <input type="radio" class="btn-check" name="fields[acces_type]" id="acces_large" value="LARGE">
             <label class="btn btn-outline-secondary" for="acces_large">Large</label>
             <input type="radio" class="btn-check" name="fields[acces_type]" id="acces_etroit" value="ETROIT">
             <label class="btn btn-outline-secondary" for="acces_etroit">Étroit/Colimaçon</label>
             <input type="radio" class="btn-check" name="fields[acces_type]" id="acces_facade" value="FACADE">
             <label class="btn btn-outline-secondary" for="acces_facade">Façade (Corde)</label>
        </div>

        <div class="mb-2">
             <label class="form-label">Photo Cage Escalier (Obligatoire)</label>
             <button type="button" class="btn btn-sm btn-dark w-100" onclick="$('#upload_photo_escalier').click()"><i class="fas fa-camera me-2"></i>Preuve Accès</button>
             <input type="file" id="upload_photo_escalier" class="d-none" accept="image/*" onchange="MetrageMedia.handleUpload(this, 'photo_escalier')">
        </div>
    </div>

</div>

<!-- ETAPE 3 : MESURES -->
<div class="ag-question-block fade-in-up" id="q-mesures">
    <div class="ag-question-title">
        <i class="fas fa-ruler-combined me-2 text-success"></i>3. Prise de Cotes (Tableau)
    </div>
    
    <!-- GEOMETRY SWITCH -->
    <div class="mb-4 text-center">
        <div class="btn-group" role="group">
            <input type="radio" class="btn-check" name="fields[forme_type]" id="forme_rect" value="RECTANGLE" checked onchange="MetrageRules.toggleShape('RECTANGLE')">
            <label class="btn btn-outline-primary" for="forme_rect"><i class="far fa-square me-2"></i>Standard (Rectangle)</label>
            
            <input type="radio" class="btn-check" name="fields[forme_type]" id="forme_special" value="SPECIAL" onchange="MetrageRules.toggleShape('SPECIAL')">
            <label class="btn btn-outline-warning" for="forme_special"><i class="fas fa-draw-polygon me-2"></i>Forme Spéciale</label>
        </div>
    </div>

    <!-- BLOC SPECIAL SHAPES -->
    <div id="bloc_formes_speciales" style="display:none;" class="mb-4 bg-warning-subtle p-3 rounded">
        <h6 class="fw-bold mb-3"><i class="fas fa-shapes me-2"></i>Type de Forme</h6>
        <!-- GRID SELECTOR -->
        <div class="row g-2 mb-3">
             <div class="col-6 col-md-3">
                 <input type="radio" class="btn-check" name="fields[forme_subtype]" id="shape_trapeze" value="TRAPEZE" onchange="MetrageRules.selectShapeSubtype('TRAPEZE')">
                 <label class="ag-card-selector py-2" for="shape_trapeze"><i class="fas fa-vector-square"></i><br>Trapèze</label>
             </div>
             <div class="col-6 col-md-3">
                 <input type="radio" class="btn-check" name="fields[forme_subtype]" id="shape_triangle" value="TRIANGLE" onchange="MetrageRules.selectShapeSubtype('TRIANGLE')">
                 <label class="ag-card-selector py-2" for="shape_triangle"><i class="fas fa-play" style="transform: rotate(-90deg);"></i><br>Triangle</label>
             </div>
             <div class="col-6 col-md-3">
                 <input type="radio" class="btn-check" name="fields[forme_subtype]" id="shape_cintre" value="CINTRE" onchange="MetrageRules.selectShapeSubtype('CINTRE')">
                 <label class="ag-card-selector py-2" for="shape_cintre"><i class="fas fa-cloud"></i><br>Cintre</label>
             </div>
             <div class="col-6 col-md-3">
                 <input type="radio" class="btn-check" name="fields[forme_subtype]" id="shape_gabarit" value="GABARIT" onchange="MetrageRules.selectShapeSubtype('GABARIT')">
                 <label class="ag-card-selector py-2" for="shape_gabarit"><i class="fas fa-cut"></i><br>Gabarit</label>
             </div>
        </div>

        <!-- SPECIAL INPUTS -->
        <div id="inputs_special_dynamic">
             <!-- TRAPEZE -->
             <div id="inputs_trapeze" class="shape-inputs" style="display:none;">
                 <div class="alert alert-info py-1"><i class="fas fa-info-circle me-1"></i>Vue INTÉRIEURE. H1 = Petit Côté.</div>
                 <div class="row g-2">
                     <div class="col-4">
                         <label class="small">Larg (L)</label>
                         <input type="number" name="fields[cote_l]" class="form-control form-control-sm">
                     </div>
                     <div class="col-4">
                         <label class="small">H1 (Petit)</label>
                         <input type="number" name="fields[cote_h1]" class="form-control form-control-sm">
                     </div>
                     <div class="col-4">
                         <label class="small">H2 (Grand)</label>
                         <input type="number" name="fields[cote_h2]" class="form-control form-control-sm" onchange="MetrageRules.validateShape('TRAPEZE')">
                     </div>
                 </div>
             </div>
             <!-- CINTRE -->
             <div id="inputs_cintre" class="shape-inputs" style="display:none;">
                 <label class="small fw-bold">Hauteur sous Naissance (H1)</label>
                 <input type="number" name="fields[cote_h1_cintre]" class="form-control mb-2">
                 <label class="small fw-bold">Hauteur Totale (Au sommet H2)</label>
                 <input type="number" name="fields[cote_h2_cintre]" class="form-control">
             </div>
             <!-- GABARIT -->
             <div id="inputs_gabarit" class="shape-inputs" style="display:none;">
                 <div class="alert alert-danger py-2">
                     <strong>Arrêt Point de Contrôle :</strong><br>
                     Vous devez tracer un gabarit rigide.
                 </div>
                 <div class="form-check mb-2">
                     <input class="form-check-input" type="checkbox" name="fields[gabarit_trace]" id="check_gabarit" required>
                     <label class="form-check-label fw-bold" for="check_gabarit">Gabarit tracé et vérifié ?</label>
                 </div>
                 <button type="button" class="btn btn-dark w-100" onclick="$('#upload_photo_gabarit').click()"><i class="fas fa-camera me-2"></i>Photo Gabarit (Obligatoire)</button>
                 <input type="hidden" name="fields[photo_gabarit]" id="photo_gabarit">
                 <input type="file" id="upload_photo_gabarit" class="d-none" accept="image/*" onchange="MetrageMedia.handleUpload(this, 'photo_gabarit')">
             </div>
        </div>
    </div>

    <!-- BLOC STANDARD (RECTANGLE) -->
    <div id="bloc_standard_rect">
        <div class="row g-4">
            <!-- VISUEL 3 POINTS -->
            <div class="col-md-5 text-center">
                <div class="border rounded p-2 bg-light">
                    <!-- Dynamic Image for Shape? For standard keep 3points -->
                    <img src="assets/img/mesure_3points.svg" alt="Mesure 3 points" class="img-fluid" style="max-height:180px;">
                    <p class="small text-muted mt-2 fst-italic">Mesurez Largeur et Hauteur en 3 points.<br>Retenez la plus petite.</p>
                </div>
            </div>

            <div class="col-md-7">
                <!-- Largeur -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Largeur Tableau (mm)</label>
                    <div class="input-group">
                        <input type="number" id="largeur_tableau" name="fields[largeur_tableau]" class="form-control form-control-lg border-primary" placeholder="1200" onkeyup="MetrageRules.calcMenuiserie()">
                        <span class="input-group-text">mm</span>
                    </div>
                    <div class="form-text text-muted" id="res_largeur_fab">Fab brute: -</div>
                </div>

                <!-- Hauteur -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Hauteur Tableau (mm)</label>
                    <div class="input-group">
                        <input type="number" id="hauteur_tableau" name="fields[hauteur_tableau]" class="form-control form-control-lg border-primary" placeholder="2150" onkeyup="MetrageRules.calcMenuiserie()">
                        <span class="input-group-text">mm</span>
                    </div>
                    <div class="form-text text-muted" id="res_hauteur_fab">Fab brute: -</div>
                </div>
                
                <input type="hidden" name="fields[largeur_fab]" id="largeur_fab">
                <input type="hidden" name="fields[hauteur_fab]" id="hauteur_fab">
            </div>
        </div>
    </div>
</div>

<!-- ETAPE 4 : CHECKLIST TUEURS -->
<div class="ag-question-block fade-in-up" id="q-details">
    <div class="ag-question-title">
        <i class="fas fa-check-double me-2 text-danger"></i>4. Points de Vigilance (Obligatoire)
    </div>

    <!-- 1. VMC -->
    <!-- 1. VMC / GRILLES -->
    <div class="mb-3 border-bottom pb-3">
        <label class="form-label fw-bold">Besoin d'une entrée d'air (Grille VMC) ?</label>
        <div class="btn-group d-block mb-2" role="group">
            <input type="radio" class="btn-check" name="fields[vmc]" id="vmc_non" value="NON" checked onchange="$('#bloc_vmc_calc').slideUp()">
            <label class="btn btn-outline-secondary" for="vmc_non">Non (Cuisine/SDB)</label>
            <input type="radio" class="btn-check" name="fields[vmc]" id="vmc_oui" value="OUI" onchange="$('#bloc_vmc_calc').slideDown()">
            <label class="btn btn-outline-primary" for="vmc_oui">OUI (Calcul Débit)</label>
        </div>
        
        <!-- CALCULATEUR DEBIT -->
        <div id="bloc_vmc_calc" style="display:none;" class="bg-info-subtle p-2 rounded">
             <div class="row g-2">
                 <div class="col-6">
                     <select class="form-select form-select-sm" id="vmc_piece" onchange="MetrageRules.calcVMC()">
                         <option value="CHAMBRE">Chambre</option>
                         <option value="SEJOUR">Séjour</option>
                         <option value="BUREAU">Bureau</option>
                     </select>
                 </div>
                 <div class="col-6">
                     <div class="input-group input-group-sm">
                         <input type="number" class="form-control" id="vmc_surface" placeholder="Surf. m²" onkeyup="MetrageRules.calcVMC()">
                         <span class="input-group-text">m²</span>
                     </div>
                 </div>
             </div>
             <div id="res_vmc_debit" class="mt-2 text-center fw-bold text-primary small">
                 Débit conseillé : -- m³/h
             </div>
             <input type="hidden" name="fields[vmc_debit_ref]" id="vmc_debit_ref">
        </div>
        
        <!-- Couleur Check -->
        <div id="div_couleur_grille" class="mt-2 text-muted small">
            Couleur : 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="fields[vmc_couleur]" id="vmc_blanc" value="BLANC" checked>
                <label class="form-check-label" for="vmc_blanc">Blanche</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="fields[vmc_couleur]" id="vmc_couleur_men" value="COULEUR">
                <label class="form-check-label" for="vmc_couleur_men">Couleur Menuiserie</label>
            </div>
        </div>
    </div>
    
    <!-- 1b. OBSTACLES (BAS DE MENUISERIE) -->
    <div class="mb-3 border-bottom pb-3">
         <label class="form-label fw-bold">Interférences au Sol ?</label>
         <div class="form-check">
             <input class="form-check-input" type="checkbox" id="check_obstacles" onchange="$('#bloc_obstacles').toggle()">
             <label class="form-check-label" for="check_obstacles">Plinthes, Radiateurs, Tuyaux...</label>
         </div>
         <div id="bloc_obstacles" style="display:none;" class="mt-2 ps-3 border-start border-3 border-warning">
             <div class="mb-2">
                 <label class="small">Épaisseur Plinthe (mm)</label>
                 <input type="number" name="fields[obstacle_plinthe]" class="form-control form-control-sm" placeholder="ex: 15" onkeyup="MetrageRules.checkObstacles()">
             </div>
             <div class="mb-2">
                 <label class="small">Profondeur Radiateur (mm)</label>
                 <input type="number" name="fields[obstacle_radiateur]" class="form-control form-control-sm" placeholder="ex: 120" onkeyup="MetrageRules.checkObstacles()">
             </div>
             <div id="res_obstacle_action" class="text-danger small fw-bold"></div>
         </div>
    </div>

    <!-- 2. SEUIL (PORTES) -->
    <div class="mb-3 border-bottom pb-3">
        <label class="form-label fw-bold">Type de Seuil (Si Porte) ?</label>
        <select name="fields[seuil_type]" class="form-select">
             <option value="NA">-- Non Concerné (Fenêtre) --</option>
             <option value="ALU_20MM">Seuil Alu 20mm (PMR Standard)</option>
             <option value="PLINTHE_AUTO">Plinthe Automatique (Intérieur)</option>
             <option value="SANS_SEUIL">Sans Seuil (Attention Étanchéité !)</option>
        </select>
    </div>

    <!-- 2. POIGNEE -->
    <div class="mb-3 border-bottom pb-3">
        <label class="form-label fw-bold">Hauteur de Poignée (Allège) ?</label>
         <div class="input-group">
            <input type="number" name="fields[ht_poignee]" class="form-control" placeholder="Standard">
            <span class="input-group-text">mm du sol</span>
        </div>
        <div class="form-text">Important si PMR ou Garde-Corps.</div>
    </div>

    <!-- 3. VOLET -->
    <div class="mb-3">
        <label class="form-label fw-bold">Volet Roulant conservé ?</label>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="fields[vr_conserve]" id="vr_conserve" value="OUI" onchange="if(this.checked) MetrageAssistant.say('Attention épaisseur coulisses ! Vérifiez si besoin de tapées réduites.', 'warning')">
            <label class="form-check-label" for="vr_conserve">Oui, je garde le volet existant</label>
        </div>
    </div>
    
    <!-- MEDIA BUTTONS (INJECTED) -->
     <h6 class="fw-bold text-muted mt-4 mb-3"><i class="fas fa-camera me-2"></i>Preuves Photos</h6>
    <div class="row g-2">
        <div class="col-4 text-center">
             <div class="border rounded p-3 bg-white shadow-sm btn-upload" onclick="$('#upload_photo_int').click()">
                 <i class="fas fa-camera fa-2x text-primary mb-2"></i><br><small>Intérieur</small>
             </div>
             <input type="file" id="upload_photo_int" class="d-none" accept="image/*" capture="environment" onchange="MetrageMedia.handleUpload(this, 'photo_interieur')">
             <img id="thumb_photo_interieur" src="" class="img-fluid rounded mt-2 border border-danger" style="display:none;" onclick="MetrageMedia.openAnnotator(this.src)">
             <input type="hidden" name="fields[photo_interieur]" id="photo_interieur">
        </div>
        <!-- Add others as needed -->
    </div>

    <!-- FINAL ACTIONS -->
    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
        <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()"><i class="fas fa-times me-2"></i>Annuler</button>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" onclick="MetrageV3.save(true)">
                <i class="fas fa-copy me-2"></i>Enregistrer & Ajouter le même
            </button>
            <button type="button" class="btn btn-success btn-lg" onclick="MetrageV3.save(false)">
                <i class="fas fa-check me-2"></i>TERMINER
            </button>
        </div>
    </div>

</div>

<!-- Auto-Include V3 Script if not already loaded (Safety) -->
<script>
    if(typeof MetrageV3 === 'undefined') $.getScript('assets/js/metrage_v3.js');
</script>
