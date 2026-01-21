<!-- views/modules/commande_rapide/form_panneaux.php -->
<div class="card shadow border-0 animate__animated animate__fadeIn">
    <div class="card-header bg-warning text-dark py-2 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="fas fa-layer-group me-2"></i>COMMANDE PANNEAUX SANDWICH</h5>
        <button type="button" class="btn btn-sm btn-close" onclick="document.getElementById('form_container').innerHTML=''; document.getElementById('form_container').style.display='none';"></button>
    </div>
    <div class="card-body p-4">
        <form id="form_panneaux" onsubmit="return false;">
            <input type="hidden" name="module_type" value="PANNEAUX">
            
            <div class="row g-4">
                
                <!-- 1. USAGE & TECH -->
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-uppercase">Usage</label>
                    <select class="form-select" name="usage">
                        <option value="TOITURE" selected>Toiture Véranda</option>
                        <option value="REMPLISSAGE">Remplissage Menuiserie</option>
                        <option value="SOUBASSEMENT">Soubassement</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-uppercase">Épaisseur</label>
                    <select class="form-select border-primary fw-bold" name="epaisseur">
                        <option value="16">16 mm</option>
                        <option value="24">24 mm</option>
                        <option value="32" selected>32 mm</option>
                        <option value="55">55 mm (Thermique +)</option>
                        <option value="65">65 mm</option>
                        <option value="85">85 mm (Extrême)</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-uppercase">Performance / Type</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="perf_type" id="perf_std" value="STANDARD" checked>
                        <label class="btn btn-outline-secondary" for="perf_std">Standard</label>

                        <input type="radio" class="btn-check" name="perf_type" id="perf_phonic" value="PHONIQUE">
                        <label class="btn btn-outline-secondary" for="perf_phonic"><i class="fas fa-volume-mute me-1"></i>Phonique (Membrane)</label>

                        <input type="radio" class="btn-check" name="perf_type" id="perf_acoustic" value="ACOUSTIC">
                        <label class="btn btn-outline-secondary" for="perf_acoustic"><i class="fas fa-tree me-1"></i>Liège / Écologique</label>
                    </div>
                </div>

                <div class="col-12"><hr class="my-1 opacity-25"></div>

                <!-- 2. FINITIONS (SANDWICH) -->
                <div class="col-md-12">
                    <label class="form-label fw-bold small text-uppercase text-center w-100">Composition du Sandwich</label>
                    <div class="d-flex justify-content-center align-items-center gap-3 p-3 bg-light rounded">
                        
                        <!-- EXT -->
                        <div class="text-center">
                            <small class="text-muted d-block mb-1">FACE EXTÉRIEURE (Ciel)</small>
                            <select class="form-select" name="face_ext">
                                <option value="TUILE">Tuile (Terracotta)</option>
                                <option value="ARDOISE">Ardoise (Gris Foncé)</option>
                                <option value="ZINC">Zinc / Alu</option>
                                <option value="BLANC" selected>Blanc Brillant</option>
                                <option value="ECRU">Écru / Sable</option>
                            </select>
                        </div>

                        <div class="text-secondary"><i class="fas fa-grip-lines-vertical fa-2x"></i></div>

                        <!-- AME -->
                        <div class="text-center px-4" style="background: repeating-linear-gradient(45deg, #eee, #eee 10px, #fff 10px, #fff 20px); border:1px dashed #ccc;">
                            <small class="text-muted d-block py-2">ÂME ISOLANTE</small>
                        </div>

                        <div class="text-secondary"><i class="fas fa-grip-lines-vertical fa-2x"></i></div>

                        <!-- INT -->
                        <div class="text-center">
                            <small class="text-muted d-block mb-1">FACE INTÉRIEURE (Vue)</small>
                            <select class="form-select" name="face_int">
                                <option value="BLANC_BRI" selected>Blanc Brillant</option>
                                <option value="BLANC_MAT">Blanc Mat</option>
                                <option value="PLAXE_CHENE">Plaxé Chêne Doré</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 3. DIMENSIONS -->
                <div class="col-md-5">
                    <label class="form-label fw-bold small text-uppercase">Dimensions & Découpe</label>
                    <div class="card p-3">
                         <div class="form-check mb-2">
                             <input class="form-check-input" type="radio" name="mode_decoupe" id="cut_full" value="FULL" checked onclick="togglePanneauCut()">
                             <label class="form-check-label" for="cut_full">Plaque Complète (3000/4000 x 1200)</label>
                         </div>
                         <div class="form-check mb-3">
                             <input class="form-check-input" type="radio" name="mode_decoupe" id="cut_custom" value="CUSTOM" onclick="togglePanneauCut()">
                             <label class="form-check-label fw-bold" for="cut_custom">Débit Sur Mesure</label>
                         </div>
                         
                         <div id="panneau_dims" style="display:none;" class="row g-2">
                             <div class="col-6">
                                 <input type="number" class="form-control" name="longueur" placeholder="Pente (mm)">
                             </div>
                             <div class="col-6">
                                 <input type="number" class="form-control" name="largeur" placeholder="Largeur (mm)">
                             </div>
                         </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold small text-uppercase">Quantité</label>
                    <input type="number" class="form-control form-control-lg fw-bold text-center" value="1">
                </div>

                <div class="col-md-5 d-flex align-items-end justify-content-end">
                    <button type="button" class="btn btn-warning btn-lg rounded-pill px-5 shadow" onclick="submitModule('form_panneaux')">
                        <i class="fas fa-paper-plane me-2"></i>COMMANDER
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
    function togglePanneauCut() {
        const isCustom = document.getElementById('cut_custom').checked;
        const dims = document.getElementById('panneau_dims');
        dims.style.display = isCustom ? 'flex' : 'none';
        
        // Toggle required
        dims.querySelectorAll('input').forEach(i => i.required = isCustom);
    }
</script>
