<!-- views/modules/commande_rapide/form_profil.php -->
<div class="card shadow border-0 animate__animated animate__fadeIn">
    <div class="card-header bg-secondary text-white py-2 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="fas fa-bars me-2"></i>COMMANDE PROFIL ALU</h5>
        <button type="button" class="btn btn-sm btn-close btn-close-white" onclick="document.getElementById('form_container').innerHTML=''; document.getElementById('form_container').style.display='none';"></button>
    </div>
    <div class="card-body p-4">
        <form id="form_profil" onsubmit="return false;">
            <input type="hidden" name="module_type" value="PROFIL">
            
            <div class="row g-4">
                
                <!-- 1. FOURNISSEUR -->
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-uppercase">Gammiste / Fournisseur</label>
                    <select class="form-select border-primary" name="fournisseur" id="prof_fournisseur" onchange="checkProfilAlerts()">
                        <option value="INSTALLUX" selected>INSTALLUX (Menuiserie)</option>
                        <option value="SEPALUMIC">SEPALUMIC</option>
                        <option value="AKRAPLAST">AKRAPLAST (Toiture)</option>
                        <option value="SAPA">SAPA</option>
                        <option value="REYNAERS">REYNAERS</option>
                    </select>
                    <!-- Alerte Akraplast dynamique -->
                    <div id="alert_akraplast" class="alert alert-warning mt-2 small py-1" style="display:none;">
                        <i class="fas fa-exclamation-triangle me-1"></i> Vérifier Besoin : Porteur ou Capot ?
                    </div>
                </div>

                <!-- 2. ARTICLE -->
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-uppercase">Référence Profil</label>
                    <input type="text" class="form-control fw-bold" name="reference" placeholder="ex: 3200-10" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-uppercase">Désignation</label>
                    <input type="text" class="form-control" name="designation" placeholder="ex: Traverse Basse Galaxie">
                </div>

                <div class="col-12"><hr class="my-1 opacity-25"></div>

                <!-- 3. FINITION (BICOLORATION) -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label fw-bold small text-uppercase mb-0">Couleur & Finition</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="switch_bicoloration" onchange="toggleBicoloration()">
                            <label class="form-check-label small fw-bold text-primary" for="switch_bicoloration">Bicoloration ?</label>
                        </div>
                    </div>
                    
                    <div class="p-3 bg-light rounded border">
                        <!-- MONO -->
                        <div id="group_mono">
                            <label class="form-label small">Couleur Unique</label>
                            <select class="form-select" name="couleur_mono">
                                <option value="7016S">Gris 7016 Satiné</option>
                                <option value="9010B">Blanc 9010 Brillant</option>
                                <option value="9005T">Noir 9005 Texturé</option>
                                <option value="ANODISE">Anodisé Naturel</option>
                                <option value="BRUT">Brut</option>
                                <option value="AUTRE">Autre (Préciser en note)</option>
                            </select>
                        </div>

                        <!-- BICO (Hidden) -->
                        <div id="group_bico" style="display:none;">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small text-muted">Intérieur</label>
                                    <select class="form-select form-select-sm" name="couleur_int">
                                        <option value="9010B">Blanc 9010 B</option>
                                        <option value="9016S">Blanc 9016 S</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted">Extérieur</label>
                                    <select class="form-select form-select-sm" name="couleur_ext">
                                        <option value="7016S">Gris 7016 S</option>
                                        <option value="9005T">Noir 9005 T</option>
                                        <option value="ANODISE">Anodisé</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. CONDITIONNEMENT -->
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-uppercase">Conditionnement</label>
                    <div class="p-3 bg-light rounded border">
                        <div class="btn-group w-100 mb-3" role="group">
                            <input type="radio" class="btn-check" name="type_cond" id="cond_barre" value="BARRE" checked onchange="toggleCoupeMode()">
                            <label class="btn btn-outline-dark btn-sm" for="cond_barre"><i class="fas fa-arrows-alt-h me-1"></i>Barre Entière</label>

                            <input type="radio" class="btn-check" name="type_cond" id="cond_coupe" value="COUPE" onchange="toggleCoupeMode()">
                            <label class="btn btn-outline-dark btn-sm" for="cond_coupe"><i class="fas fa-cut me-1"></i>Coupe SAV</label>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Quantité</label>
                                <input type="number" class="form-control text-center fw-bold" name="quantite" value="1" min="1">
                                <span class="form-text small" id="unit_display">barres de 6.5m</span>
                            </div>
                            <div class="col-6" id="input_longueur_coupe" style="display:none;">
                                <label class="form-label small fw-bold text-danger">Longueur (mm)</label>
                                <input type="number" class="form-control border-danger" name="longueur_sav" placeholder="ex: 1250">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4 text-center">
                    <button type="button" class="btn btn-secondary btn-lg rounded-pill px-5 shadow" onclick="submitModule('form_profil')">
                        <i class="fas fa-paper-plane me-2"></i>COMMANDER
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>
