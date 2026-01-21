<?php
// form_occultation.php - V3 EXPERT SCENARIO
// Context: "Storiste & Electricien"
?>

<!-- ETAPE 1 : IMPLANTATION -->
<div class="ag-question-block fade-in-up" id="q-implant">
    <div class="ag-question-title">
        <i class="fas fa-map-marker-alt me-2 text-primary"></i>1. Implantation & Support
    </div>
    
    <div class="row g-3">
        <!-- POSE -->
        <div class="col-12 mb-3">
            <label class="form-label fw-bold">Type de Pose</label>
            <div class="row g-2">
                <div class="col-4">
                    <input type="radio" class="btn-check" name="fields[pose]" id="pose_facade" value="FACADE" checked>
                    <label class="btn btn-outline-primary w-100 p-2" for="pose_facade">
                        <i class="fas fa-arrows-alt-h fa-2x mb-2"></i><br>Façade
                    </label>
                </div>
                <div class="col-4">
                    <input type="radio" class="btn-check" name="fields[pose]" id="pose_plafond" value="PLAFOND">
                    <label class="btn btn-outline-primary w-100 p-2" for="pose_plafond">
                        <i class="fas fa-arrow-up fa-2x mb-2"></i><br>Plafond
                    </label>
                </div>
                <div class="col-4">
                    <input type="radio" class="btn-check" name="fields[pose]" id="pose_linteau" value="SOUS_LINTEAU">
                    <label class="btn btn-outline-primary w-100 p-2" for="pose_linteau">
                        <i class="fas fa-compress-arrows-alt fa-2x mb-2"></i><br>Sous Linteau
                    </label>
                </div>
            </div>
        </div>

        <!-- SUPPORT / ITE -->
        <div class="col-12">
            <label class="form-label fw-bold text-danger">Nature du Support (Mur)</label>
            <select name="fields[support]" class="form-select form-select-lg" onchange="MetrageRules.checkOccultationSupport(this)">
                <option value="">-- Choisir --</option>
                <option value="BETON">Béton Plein</option>
                <option value="BRIQUE">Brique Creuse/Parpaing</option>
                <option value="BOIS">Bois</option>
                <option value="ITE">ITE (Isolation Extérieure)</option>
            </select>
        </div>
    </div>
</div>

<!-- ETAPE 2 : DIMENSIONS -->
<div class="ag-question-block fade-in-up" id="q-dims">
    <div class="ag-question-title">
        <i class="fas fa-ruler me-2 text-success"></i>2. Dimensions & Encombrement
    </div>
    <div class="row g-3">
        <div class="col-6">
            <label class="form-label">Largeur Hors Tout (mm)</label>
            <input type="number" name="fields[largeur_ht]" class="form-control form-control-lg border-primary" placeholder="Largeur">
        </div>
        <div class="col-6">
            <label class="form-label">Avancée / Hauteur (mm)</label>
            <input type="number" name="fields[hauteur_ht]" class="form-control form-control-lg border-primary" placeholder="Projection">
        </div>
        
        <!-- WARNINGS -->
        <div class="col-12 mt-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="check_coffre">
                <label class="form-check-label" for="check_coffre">J'ai vérifié l'espace coffre (min 250mm)</label>
            </div>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="check_poignee" onchange="if(this.checked) MetrageAssistant.say('Attention : Si la poignée dépasse, prévoyez des coulisses écartées !', 'warning')">
                <label class="form-check-label" for="check_poignee">La poignée de fenêtre dépasse ?</label>
            </div>
        </div>
    </div>
</div>

<!-- ETAPE 3 : ELECTRICITE -->
<div class="ag-question-block fade-in-up" id="q-elec">
    <div class="ag-question-title">
        <i class="fas fa-bolt me-2 text-warning"></i>3. Électricité & Manœuvre
    </div>
    
    <div class="mb-3">
        <label class="form-label fw-bold">Type de Manœuvre</label>
        <select name="fields[manoeuvre]" class="form-select" onchange="MetrageRules.checkElec(this)">
            <option value="MANUEL">Manivelle / Sangle</option>
            <option value="FILAIRE">Électrique Filaire</option>
            <option value="RADIO">Électrique Radio (Somfy/ZoFy)</option>
            <option value="SOLAIRE">Solaire (Autonome)</option>
        </select>
    </div>

    <!-- BLOC ELEC -->
    <div id="bloc_elec_details" style="display:none;" class="border-start border-4 border-warning ps-3 bg-light py-2">
        <div class="row g-3">
             <!-- VUE INTERIEURE SCHEMA -->
            <div class="col-12 text-center my-2">
                 <img src="assets/img/schema_elec_gauche_droite.svg" alt="Vue Intérieure Elec" class="img-fluid" style="height:120px;">
                 <p class="small fst-italic text-muted">Vue Intérieure</p>
            </div>

            <div class="col-12">
                <label class="form-label text-muted small text-uppercase fw-bold">Coté Alimentation (Vue Intérieure)</label><br>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="fields[coté_alim]" id="alim_gauche" value="GAUCHE">
                    <label class="btn btn-outline-secondary" for="alim_gauche"><i class="fas fa-arrow-left me-1"></i>Gauche</label>
                    
                    <input type="radio" class="btn-check" name="fields[coté_alim]" id="alim_droit" value="DROITE">
                    <label class="btn btn-outline-secondary" for="alim_droit">Droite<i class="fas fa-arrow-right ms-1"></i></label>
                </div>
            </div>
            
            <div class="col-12">
                <!-- MANDATORY PHOTO ELEC -->
                <div id="photo_elec_container" class="mb-3 p-2 bg-white rounded border border-warning shadow-sm">
                    <small class="text-warning fw-bold"><i class="fas fa-bolt me-1"></i>Photo Arrivée Courant</small>
                    <div class="d-grid mt-1">
                        <button type="button" class="btn btn-sm btn-warning text-white" onclick="$('#upload_photo_elec').click()">📷 Photo Câble</button>
                    </div>
                    <input type="file" id="upload_photo_elec" class="d-none" accept="image/*" onchange="MetrageMedia.handleUpload(this, 'photo_elec')">
                    <input type="hidden" name="fields[photo_elec]" id="photo_elec">
                    <img id="thumb_photo_elec" class="img-fluid mt-1" style="display:none; height:60px;">
                </div>

                <label class="form-label mt-2">Type de Sortie Câble</label>
                <select name="fields[sortie_cable]" class="form-select">
                    <option value="DIRECTE">Directe (Derrière coffre)</option>
                    <option value="COFFRE">Dans le coffre</option>
                    <option value="FACADE">En façade (Goulotte)</option>
                </select>
            </div>
            
            <div class="col-12">
                <div class="alert alert-warning py-2 mb-0 small">
                    <i class="fas fa-exclamation-triangle me-1"></i> Avez-vous repéré la boîte de dérivation ?
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ETAPE 4 : FINITIONS -->
<div class="ag-question-block fade-in-up" id="q-finitions">
    <div class="ag-question-title">
        <i class="fas fa-paint-brush me-2 text-secondary"></i>4. Finitions & Options
    </div>
    <div class="row g-3">
        <div class="col-6">
            <label class="form-label">Coloris Armature</label>
            <input type="text" name="fields[coloris_armature]" class="form-control" placeholder="Ex: RAL 7016">
        </div>
        <div class="col-6">
            <label class="form-label">Référence Toile/Tablier</label>
            <input type="text" name="fields[coloris_toile]" class="form-control" placeholder="Ref Toile">
        </div>
        <div class="col-12 mt-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="fields[opt_vent]" id="opt_vent" value="OUI">
                <label class="form-check-label fw-bold" for="opt_vent">Option Capteur Vent (Eolis)</label>
                <div class="form-text">Recommandé pour stores > 4m.</div>
            </div>
        </div>
    </div>
    
    <!-- MEDIA PLACEHOLDER -->
    <div class="mt-3 text-center">
         <button type="button" class="btn btn-outline-dark btn-sm" onclick="$('#upload_photo_int').click()"><i class="fas fa-camera me-2"></i>Ajouter Photo Façade</button>
         <input type="file" id="upload_photo_int" class="d-none" accept="image/*" capture="environment" onchange="MetrageMedia.handleUpload(this, 'photo_facade')">
         <input type="hidden" name="fields[photo_facade]" id="photo_facade">
         <img id="thumb_photo_facade" src="" class="img-fluid rounded mt-2" style="display:none; max-height:100px;">
    </div>
</div>
