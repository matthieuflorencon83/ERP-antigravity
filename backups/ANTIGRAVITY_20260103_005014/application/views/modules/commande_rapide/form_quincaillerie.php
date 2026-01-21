<!-- views/modules/commande_rapide/form_quincaillerie.php -->
<div class="card shadow border-0 animate__animated animate__fadeIn">
    <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="fas fa-tools me-2"></i>COMMANDE QUINCAILLERIE & CONSOMMABLES</h5>
        <button type="button" class="btn btn-sm btn-close btn-close-white" onclick="document.getElementById('form_container').innerHTML=''; document.getElementById('form_container').style.display='none';"></button>
    </div>
    <div class="card-body p-4">
        <form id="form_quincaillerie" onsubmit="return false;">
            <input type="hidden" name="module_type" value="QUINCAILLERIE">
            
            <!-- 1. SELECTEUR CATALOGUE -->
            <div class="mb-4 text-center">
                <label class="form-label fw-bold small text-uppercase mb-3">Choisir le Catalogue Fournisseur</label>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    
                    <input type="radio" class="btn-check" name="fournisseur_cat" id="cat_foussier" value="FOUSSIER" checked>
                    <label class="btn btn-outline-dark px-4 py-3" for="cat_foussier">
                        <i class="fas fa-book-open fa-2x d-block mb-1"></i>
                        FOUSSIER
                    </label>

                    <input type="radio" class="btn-check" name="fournisseur_cat" id="cat_trenois" value="TRENOIS">
                    <label class="btn btn-outline-dark px-4 py-3" for="cat_trenois">
                        <i class="fas fa-wrench fa-2x d-block mb-1"></i>
                        TRENOIS
                    </label>

                    <input type="radio" class="btn-check" name="fournisseur_cat" id="cat_acdis" value="ACDIS">
                    <label class="btn btn-outline-dark px-4 py-3" for="cat_acdis">
                         <i class="fas fa-door-open fa-2x d-block mb-1"></i>
                         ACDIS
                    </label>

                    <input type="radio" class="btn-check" name="fournisseur_cat" id="cat_wurth" value="WURTH">
                    <label class="btn btn-outline-danger px-4 py-3" for="cat_wurth">
                         <i class="fas fa-hard-hat fa-2x d-block mb-1"></i>
                         WURTH
                    </label>
                </div>
            </div>

            <!-- 2. GRID SAISIE RAPIDE -->
            <div class="table-responsive bg-light p-3 rounded border">
                <table class="table table-borderless table-striped align-middle mb-0" id="grid_quincaillerie">
                    <thead class="text-secondary small text-uppercase">
                        <tr>
                            <th style="width: 20%;">Réf. Catalogue</th>
                            <th style="width: 40%;">Désignation</th>
                            <th style="width: 15%;">Qté</th>
                            <th style="width: 20%;">Unité</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="tbody_quincaillerie">
                        <!-- Ligne 1 (Default) -->
                        <tr class="row-item">
                            <td>
                                <input type="text" class="form-control fw-bold" name="lines[0][ref]" placeholder="ex: 12345">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="lines[0][designation]" placeholder="ex: Vis Inox 4x40">
                            </td>
                            <td>
                                <input type="number" class="form-control text-center fw-bold" name="lines[0][qty]" value="1" min="1">
                            </td>
                            <td>
                                <select class="form-select form-select-sm" name="lines[0][unit]">
                                    <option value="BOITE">Boîte(s)</option>
                                    <option value="PIECE">Pièce(s)</option>
                                    <option value="CARTON">Carton(s)</option>
                                    <option value="PAIRE">Paire(s)</option>
                                    <option value="CARTOUCHE">Cartouche(s)</option>
                                    <option value="ML">Mètre(s)</option>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger shadow-none disabled"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="addQuincaillerieRow()">
                        <i class="fas fa-plus-circle me-1"></i> AJOUTER UNE LIGNE
                    </button>
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="button" class="btn btn-success btn-lg rounded-pill px-5 shadow" onclick="submitModule('form_quincaillerie')">
                    <i class="fas fa-paper-plane me-2"></i>ENVOYER COMMANDE
                </button>
            </div>
            
        </form>
    </div>
</div>

<script>
    function addQuincaillerieRow() {
        const tbody = document.getElementById('tbody_quincaillerie');
        const index = tbody.children.length;
        
        const row = document.createElement('tr');
        row.className = 'row-item animate__animated animate__fadeIn';
        row.innerHTML = `
            <td>
                <input type="text" class="form-control fw-bold" name="lines[${index}][ref]" placeholder="...">
            </td>
            <td>
                <input type="text" class="form-control" name="lines[${index}][designation]" placeholder="...">
            </td>
            <td>
                <input type="number" class="form-control text-center fw-bold" name="lines[${index}][qty]" value="1" min="1">
            </td>
            <td>
                <select class="form-select form-select-sm" name="lines[${index}][unit]">
                    <option value="BOITE">Boîte(s)</option>
                    <option value="PIECE">Pièce(s)</option>
                    <option value="CARTON">Carton(s)</option>
                    <option value="PAIRE">Paire(s)</option>
                     <option value="CARTOUCHE">Cartouche(s)</option>
                    <option value="ML">Mètre(s)</option>
                </select>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger shadow-none" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button>
            </td>
        `;
        tbody.appendChild(row);
        
        // Focus first input of new row
        row.querySelector('input').focus();
    }
</script>
