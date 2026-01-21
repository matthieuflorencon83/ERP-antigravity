<!-- views/modules/commande_rapide/form_vitrage.php -->
<div class="card shadow border-0 animate__animated animate__fadeIn">
    <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="fas fa-border-all me-2"></i>COMMANDE VITRAGE</h5>
        <button type="button" class="btn btn-sm btn-close btn-close-white" onclick="document.getElementById('form_container').innerHTML=''; document.getElementById('form_container').style.display='none';"></button>
    </div>
    <div class="card-body p-4">
        <form id="form_vitrage" onsubmit="return false;">
            <input type="hidden" name="module_type" value="VITRAGE">
            
            <div class="row g-3">
                
                <!-- 1. TYPE & COMPOSITION -->
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-uppercase">Type de Vitrage</label>
                    <select class="form-select" name="type_vitrage" id="vit_type" onchange="updateThicknessHelper()">
                        <option value="DOUBLE" selected>Double Vitrage</option>
                        <option value="TRIPLE">Triple Vitrage</option>
                        <option value="FEUILLETE">Feuilleté (Stadip)</option>
                        <option value="SIMPLE">Simple Vitrage</option>
                        <option value="TREMPE">Trempé / Securit</option>
                    </select>
                </div>
                
                <div class="col-md-5">
                    <label class="form-label fw-bold small text-uppercase">Composition (ex: 4/16/4)</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="composition" list="list_compos" placeholder="Saisir ou choisir..." required onchange="calculateWeight()">
                        <button class="btn btn-outline-secondary" type="button"><i class="fas fa-list"></i></button>
                    </div>
                    <datalist id="list_compos">
                        <option value="4/16/4 Argon FE">
                        <option value="44.2/16/4 Antelio">
                        <option value="SP10/14/4">
                        <option value="6 Trempé">
                    </datalist>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small text-uppercase">Options Techniques</label>
                    <div class="bg-light p-2 rounded border d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="opt_argon" id="opt_argon" checked>
                            <label class="form-check-label small" for="opt_argon">Argon</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="opt_we" id="opt_we">
                            <label class="form-check-label small" for="opt_we">Warm-Edge (Noir)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="opt_fe" id="opt_fe" checked>
                            <label class="form-check-label small" for="opt_fe">FE / ITR</label>
                        </div>
                    </div>
                </div>

                <div class="col-12"><hr class="my-1 text-muted opacity-25"></div>

                <!-- 2. GEO & DIMENSIONS -->
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-uppercase">Forme</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" role="switch" id="switch_forme" onchange="toggleFormeMode()">
                        <label class="form-check-label fw-bold" for="switch_forme">Spéciale ?</label>
                    </div>
                </div>

                <!-- Mode RECTANGLE -->
                <div class="col-md-3 group-rect">
                    <label class="form-label fw-bold small text-uppercase">Largeur (mm)</label>
                    <input type="number" class="form-control fw-bold" name="largeur" id="vit_largeur" placeholder="L" oninput="calculateWeight()" required>
                </div>
                <div class="col-md-3 group-rect">
                    <label class="form-label fw-bold small text-uppercase">Hauteur (mm)</label>
                    <input type="number" class="form-control fw-bold" name="hauteur" id="vit_hauteur" placeholder="H" oninput="calculateWeight()" required>
                </div>

                <!-- Mode FORME (Hidden by default) -->
                <div class="col-md-6 group-forme" style="display:none;">
                    <label class="form-label fw-bold small text-uppercase">Type de Forme & Croquis</label>
                    <div class="input-group">
                        <select class="form-select" name="type_forme">
                            <option value="TRAPEZE">Trapèze</option>
                            <option value="TRIANGLE">Triangle</option>
                            <option value="CINTRE">Cintré</option>
                        </select>
                        <input type="file" class="form-control" name="croquis">
                    </div>
                    <div class="form-text text-danger">Joindre obligatoirement un schéma (vue intérieure).</div>
                </div>

                <div class="col-md-4 text-end">
                    <!-- CALCULATEUR RESULTAT -->
                    <div class="alert alert-info py-1 mb-0 d-inline-block text-start border-info" style="min-width: 200px;">
                        <input type="hidden" name="poids_estime" id="input_poids_estime">
                        <div class="small text-muted">Estimation <i class="fas fa-calculator"></i></div>
                        <div class="fw-bold text-dark" id="calc_result">Surface: 0.00 m² <br> Poids: 0 kg</div>
                    </div>
                </div>

                <!-- 3. FINITIONS -->
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-uppercase">Intercalaire</label>
                    <select class="form-select form-select-sm" name="intercalaire">
                        <option value="NOIR" selected>Noir (Standard)</option>
                        <option value="ALU">Aluminium</option>
                        <option value="BLANC">Blanc</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-uppercase">Petits Bois</label>
                    <select class="form-select form-select-sm" name="petits_bois">
                        <option value="">Aucun</option>
                        <option value="LAITON">Laiton</option>
                        <option value="BLANC">Blanc Incorporé</option>
                        <option value="ALU">Alu Incorporé</option>
                    </select>
                </div>

                <div class="col-12 mt-4 text-center">
                    <button type="button" class="btn btn-primary btn-lg rounded-pill px-5 shadow" onclick="submitModule('form_vitrage')">
                        <i class="fas fa-paper-plane me-2"></i>COMMANDER
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>
