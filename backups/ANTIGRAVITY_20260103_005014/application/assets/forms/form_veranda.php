<?php
// form_veranda.php
?>
<div class="row g-4 fade-in-up">
    
    <!-- SECTION STRUCTURE -->
    <div class="col-12">
        <div class="card border-warning mb-3">
            <div class="card-header bg-warning text-dark fw-bold">
                <i class="fas fa-ruler-triangle me-2"></i>Contrôle Maçonnerie (Équerrage)
            </div>
            <div class="card-body row">
                <div class="col-md-4 text-center">
                    <!-- Placeholder SVG Diagonals -->
                    <svg width="100" height="80" viewBox="0 0 100 80" class="border p-2">
                        <rect x="10" y="10" width="80" height="60" fill="none" stroke="black"/>
                        <line x1="10" y1="10" x2="90" y2="70" stroke="red" stroke-dasharray="2"/>
                        <line x1="90" y1="10" x2="10" y2="70" stroke="blue" stroke-dasharray="2"/>
                        <text x="45" y="40" font-size="10">A/B</text>
                    </svg>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Diagonale A (mm)</label>
                    <input type="number" id="diag_a" name="fields[diag_a]" class="form-control" onkeyup="MetrageRules.checkEquerrage()">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Diagonale B (mm)</label>
                    <input type="number" id="diag_b" name="fields[diag_b]" class="form-control" onkeyup="MetrageRules.checkEquerrage()">
                </div>
                <div class="col-12 mt-2">
                    <div id="alert_equerrage" class="alert alert-danger py-1" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION TOITURE -->
    <div class="col-12">
        <h6 class="fw-bold text-primary border-bottom pb-2">Toiture & Remplissage</h6>
    </div>

    <div class="col-md-6">
        <label class="form-label">Type Remplissage</label>
        <select id="remplissage" name="fields[remplissage]" class="form-select mb-3" onchange="MetrageRules.checkToiture()">
            <option value="VITRAGE">Vitrage (Double/Triple)</option>
            <option value="PANNEAU">Panneau Sandwich (Akraplast)</option>
            <option value="POLYCARBONATE">Polycarbonate</option>
        </select>
        
        <label class="form-label">Couleur / Finition</label>
        <input type="text" name="fields[finition_toiture]" class="form-control" placeholder="Ex: Tuile / Blanc">
    </div>

    <div class="col-md-6">
        <label class="form-label">Profondeur Toiture (Avancée)</label>
        <div class="input-group mb-3">
            <input type="number" id="profondeur" name="fields[profondeur]" class="form-control" placeholder="mm" onkeyup="MetrageRules.checkToiture()">
            <span class="input-group-text">mm</span>
        </div>

        <label class="form-label">Largeur Totale</label>
        <input type="number" name="fields[largeur_totale]" class="form-control" placeholder="mm">
    </div>

</div>
