<?php
/**
 * depenses_upload_ocr.php
 * Module : Saisie Rapide Factures (OCR)
 */
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$page_title = 'Saisie Rapide Factures';
require_once 'header.php';
?>

<div class="main-content">
    <div class="container-fluid px-4 mt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Ajout Dépense Rapide (OCR)</h2>
            <a href="commandes_liste.php" class="btn btn-outline-secondary">Retour Liste</a>
        </div>

        <div class="row g-4">
            
            <!-- ZONE UPLOAD -->
            <div class="col-md-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-5 d-flex flex-column justify-content-center align-items-center" 
                         id="ocr-dropzone" 
                         style="border: 2px dashed #dee2e6; border-radius: 10px; cursor: pointer; transition: all 0.2s;">
                        
                        <div class="mb-4">
                            <span class="fa-stack fa-3x">
                                <i class="fas fa-circle fa-stack-2x text-light"></i>
                                <i class="fas fa-magic fa-stack-1x text-primary"></i>
                            </span>
                        </div>
                        <h5 class="fw-bold mb-3">Glissez une Facture PDF ici</h5>
                        <p class="text-muted small mb-4">Ou cliquez pour sélectionner un fichier.<br>Analyse automatique par IA Gemini.</p>
                        
                        <input type="file" id="ocr-file-input" class="d-none" accept=".pdf">
                        <button class="btn btn-primary rounded-pill px-4 fw-bold">
                            <i class="fas fa-upload me-2"></i>Sélectionner
                        </button>
                    </div>
                </div>
            </div>

            <!-- ZONE RESULTAT IA -->
            <div class="col-md-7">
                <div id="ocr-result-container" class="card border-0 shadow-sm h-100 d-none">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-success"><i class="fas fa-check-circle me-2"></i>Analyse Terminée</h5>
                    </div>
                    <div class="card-body">
                        
                        <!-- FORMULAIRE VALIDATION -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Nom Fournisseur</label>
                                <div class="input-group">
                                    <input type="text" id="ocr-fournisseur" class="form-control fw-bold">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-search"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end" id="fournisseur-suggestions">
                                        <!-- Injecté par JS -->
                                    </ul>
                                </div>
                                <input type="hidden" id="ocr-fournisseur-id">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-bold">Date Facture</label>
                                <input type="date" id="ocr-date" class="form-control">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-bold text-success">Total HT (€)</label>
                                <input type="number" step="0.01" id="ocr-total" class="form-control fw-bold border-success text-success">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label text-muted small fw-bold">Numéro Facture / Réf</label>
                                <input type="text" id="ocr-ref" class="form-control">
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="fw-bold text-muted mb-3">Détail des lignes détectées</h6>
                        <div class="table-responsive bg-light rounded border p-2 mb-3" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm table-borderless mb-0 small">
                                <thead class="text-muted border-bottom">
                                    <tr>
                                        <th>Désignation</th>
                                        <th class="text-end">Qté</th>
                                        <th class="text-end">PU</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="ocr-lines-body"></tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-link text-muted text-decoration-none" onclick="resetOCR()">Annuler</button>
                            <button type="button" class="btn btn-success fw-bold px-4" id="btn-valider-creation" onclick="createExpense()">
                                <i class="fas fa-save me-2"></i>CRÉER LA DÉPENSE
                            </button>
                        </div>

                    </div>
                </div>
                
                <!-- LOADING STATE -->
                <div id="ocr-loading" class="text-center d-none py-5">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                    <h5 class="fw-bold text-muted">Analyse du document en cours...</h5>
                    <p class="text-secondary small">Extraction du fournisseur, dates et montants.</p>
                </div>

                <!-- EMPTY STATE -->
                <div id="ocr-empty" class="text-center py-5 opacity-50">
                    <i class="fas fa-arrow-left fa-3x mb-3 text-muted"></i>
                    <p class="lead">Le résultat s'affichera ici.</p>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="assets/js/ocr_upload.js?v=<?= time() ?>"></script>

<?php require_once 'footer.php'; ?>
