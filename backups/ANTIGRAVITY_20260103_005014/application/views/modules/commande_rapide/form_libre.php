<!-- views/modules/commande_rapide/form_libre.php -->
<div class="card shadow border-0 animate__animated animate__fadeIn">
    <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="fas fa-pen-fancy me-2"></i>COMMANDE SPÉCIALE / TEXTE LIBRE</h5>
        <button type="button" class="btn btn-sm btn-close btn-close-white" onclick="document.getElementById('form_container').innerHTML=''; document.getElementById('form_container').style.display='none';"></button>
    </div>
    <div class="card-body p-5">
        <form id="form_libre" onsubmit="return false;">
            <input type="hidden" name="module_type" value="LIBRE">
            
            <div class="row g-4 justify-content-center">
                
                <div class="col-md-8">
                    <div class="alert alert-light border shadow-sm mb-4">
                        <i class="fas fa-info-circle text-primary me-2"></i>Utilisez ce module pour toute demande hors-standard (Location de benne, Palette de bois, Outillage spécifique, etc.).
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Fournisseur Concerné</label>
                        <!-- Should be a Select2 ideally, simplified here -->
                        <select class="form-select" name="fournisseur_id">
                            <option value="">-- Sélectionner ou Taper Autre --</option>
                            <option value="POINT_P">POINT P</option>
                            <option value="LOXAM">LOXAM (Location)</option>
                            <option value="MANUTAN">MANUTAN</option>
                            <option value="AUTRE">AUTRE (Préciser dans le texte)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Votre Demande (Détails)</label>
                        <textarea class="form-control" name="details_texte" rows="6" placeholder="Bonjour, merci de commander une benne de 8m3 pour le chantier..." required></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase">Pièce Jointe (Devis, Photo...)</label>
                        <input type="file" class="form-control" name="attachment">
                        <div class="form-text">PDF, JPG, PNG acceptés.</div>
                    </div>
                    
                    <div class="text-center">
                        <button type="button" class="btn btn-primary btn-lg rounded-pill px-5 shadow" onclick="submitModule('form_libre')">
                            <i class="fas fa-paper-plane me-2"></i>ENVOYER DEMANDE
                        </button>
                    </div>

                </div>

            </div>
        </form>
    </div>
</div>
