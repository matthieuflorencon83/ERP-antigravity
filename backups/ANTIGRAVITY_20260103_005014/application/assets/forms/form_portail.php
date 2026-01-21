<?php
// form_portail.php
?>
<div class="row g-4 fade-in-up">
    <div class="col-md-12">
        <h5 class="fw-bold mb-4 text-center w-100 bg-light p-2 rounded">
            <i class="fas fa-torii-gate me-2"></i>Relevé de Piliers (Règle des 3 points)
        </h5>
    </div>

    <!-- VISUEL PILIERS -->
    <div class="col-md-4 text-center d-flex flex-column justify-content-center align-items-center">
        <!-- Placeholder SVG for Pillars -->
        <div style="position: relative; height: 300px; width: 200px; border: 2px solid #ccc; border-top: none; display: flex; justify-content: space-between;">
            <div style="width: 40px; background: #ddd; height: 100%;"></div>
            <div style="width: 40px; background: #ddd; height: 100%;"></div>
            
            <!-- Arrows -->
            <i class="fas fa-arrow-left text-danger" style="position: absolute; top: 20px; left: 45px;"></i>
            <i class="fas fa-arrow-right text-danger" style="position: absolute; top: 20px; right: 45px;"></i>
            
            <i class="fas fa-arrow-left text-danger" style="position: absolute; top: 50%; left: 45px;"></i>
            <i class="fas fa-arrow-right text-danger" style="position: absolute; top: 50%; right: 45px;"></i>
            
            <i class="fas fa-arrow-left text-danger" style="position: absolute; bottom: 20px; left: 45px;"></i>
            <i class="fas fa-arrow-right text-danger" style="position: absolute; bottom: 20px; right: 45px;"></i>
        </div>
        <p class="small text-muted mt-2">Vue intérieure</p>
    </div>

    <!-- SAISIE 3 POINTS -->
    <div class="col-md-4">
        <label class="form-label fw-bold">Largeurs entre piliers</label>
        
        <div class="input-group mb-2">
            <span class="input-group-text">HAUT</span>
            <input type="number" id="largeur_haut" name="fields[largeur_haut]" class="form-control" placeholder="mm" onkeyup="MetrageRules.checkPiliers()">
        </div>
        
        <div class="input-group mb-2">
            <span class="input-group-text">MILIEU</span>
            <input type="number" id="largeur_milieu" name="fields[largeur_milieu]" class="form-control" placeholder="mm" onkeyup="MetrageRules.checkPiliers()">
        </div>
        
        <div class="input-group mb-2">
            <span class="input-group-text">BAS</span>
            <input type="number" id="largeur_bas" name="fields[largeur_bas]" class="form-control" placeholder="mm" onkeyup="MetrageRules.checkPiliers()">
        </div>

        <div id="alert_pilier" class="alert alert-danger mt-3" style="display:none;"></div>
    </div>

    <!-- RESULTAT ET SOL -->
    <div class="col-md-4 bg-light p-3 rounded">
        <label class="form-label text-success fw-bold">Cote Passage Retenue</label>
        <input type="number" id="largeur_passage" name="fields[largeur_passage]" class="form-control is-valid fw-bold mb-4" readonly>

        <hr>

        <label class="form-label fw-bold">Type Ouverture</label>
        <select id="type_ouverture" name="fields[type_ouverture]" class="form-select mb-3">
            <option value="BATTANT">Battant</option>
            <option value="COULISSANT">Coulissant</option>
        </select>

        <label class="form-label">Pente du seuil (%)</label>
        <div class="input-group">
            <input type="number" id="pente_seuil" name="fields[pente_seuil]" class="form-control" placeholder="0" onkeyup="MetrageRules.checkPente(this)">
            <span class="input-group-text">%</span>
        </div>
    </div>
</div>
