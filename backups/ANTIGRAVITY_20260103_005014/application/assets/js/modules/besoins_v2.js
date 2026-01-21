/**
 * Besoins V3 - The Funnel Logic (Deep Hierarchy)
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- ELEMENTS ---
    const steps = {
        1: document.getElementById('step_1'),
        2: document.getElementById('step_2'),
        3: document.getElementById('step_3'),
        4: document.getElementById('step_4'),
        5: document.getElementById('step_5')
    };

    const ui = {
        fournisseurSelect: document.getElementById('fournisseur_id'),
        familyGrid: document.getElementById('family_grid'),
        subFamilySelect: document.getElementById('sous_famille_id'),
        articleSelect: document.getElementById('article_id'),
        criteriaPanel: document.getElementById('criteria_panel'),
        form: document.getElementById('form_besoin'),
        tableBody: document.querySelector('#table_besoins tbody')
    };

    // --- STATE ---
    let state = {
        fournisseur: null,
        famille: null,
        sousFamille: null,
        article: null,
        finitions: [] // Cache for dropdown
    };

    // --- INIT ---
    loadFournisseurs();
    loadFamilles(); // Preload
    loadFinitions(); // New

    // --- EVENT LISTENERS ---

    // 1. Affaire Change -> Unlock Step 2
    document.getElementById('affaire_id').addEventListener('change', (e) => {
        if (e.target.value) activateStep(2);
        else deactivateStep(2);
    });

    // 2. Fournisseur Change -> Unlock Step 3 (Familles are generic, but we track vendor)
    ui.fournisseurSelect.addEventListener('change', (e) => {
        state.fournisseur = e.target.value;
        if (state.fournisseur) {
            activateStep(3);
            // Optionally filter families? For now, show all.
        } else {
            deactivateStep(3);
        }
    });

    // 3. Family Selection (Grid Click)
    ui.familyGrid.addEventListener('click', (e) => {
        if (e.target.classList.contains('family-btn')) {
            // UI Toggle
            document.querySelectorAll('.family-btn').forEach(b => b.classList.remove('active', 'btn-secondary'));
            e.target.classList.add('active', 'btn-secondary');

            state.famille = e.target.getAttribute('data-id');
            state.familleLabel = e.target.innerText; // Store for logic
            document.getElementById('famille_id').value = state.famille;

            loadSubFamilles(state.famille);
            activateStep(4);
        }
    });

    // 4. Sub-Family Change -> Load Articles
    ui.subFamilySelect.addEventListener('change', (e) => {
        state.sousFamille = e.target.value;
        if (state.sousFamille) {
            loadArticles(state.sousFamille, state.fournisseur, state.famille);
            activateStep(5);
        } else {
            deactivateStep(5);
        }
    });

    // 5. Article Change -> Show Details & Criteria on RIGHT PANEL
    ui.articleSelect.addEventListener('change', (e) => {
        state.article = e.target.value;
        const opt = e.target.selectedOptions[0];

        if (state.article && opt) {
            // Parse data attributes
            const data = {
                ref: opt.getAttribute('data-ref') || 'N/A',
                prix: opt.getAttribute('data-prix') || 'N/C',
                poids: opt.getAttribute('data-poids') || '-',
                longueur: opt.getAttribute('data-longueur') || '-',
                finition: opt.getAttribute('data-finition') || 'Standard',
                stock: opt.getAttribute('data-stock') || '0',
                image: opt.getAttribute('data-image') || ''
            };

            // Update Detail Header
            document.getElementById('detail_title').innerText = opt.text;
            document.getElementById('detail_ref').innerHTML = `
                <div class="row g-2 small">
                    <div class="col-6"><strong>Réf:</strong> ${data.ref}</div>
                    <div class="col-6"><strong>Prix HT/U:</strong> <span class="text-success fw-bold">${data.prix}</span></div>
                    <div class="col-6"><strong>Poids:</strong> ${data.poids} kg</div>
                    <div class="col-6"><strong>Longueur:</strong> ${data.longueur} mm</div>
                    <div class="col-12"><strong>Finition:</strong> <span class="badge bg-secondary">${data.finition}</span></div>
                    <div class="col-12"><strong>Stock:</strong> <span class="badge ${parseFloat(data.stock) > 0 ? 'bg-success' : 'bg-warning'}">${data.stock} U</span></div>
                </div>
            `;

            // Show Image if available
            const imgEl = document.getElementById('detail_img');
            const iconEl = document.getElementById('detail_icon');
            if (data.image && data.image !== '') {
                imgEl.src = data.image;
                imgEl.classList.remove('d-none');
                iconEl.classList.add('d-none');
            } else {
                imgEl.classList.add('d-none');
                iconEl.classList.remove('d-none');
            }

            // Show Criteria
            renderCriteria(opt);
        } else {
            ui.criteriaPanel.classList.add('d-none');
            document.getElementById('detail_title').innerText = "Sélectionnez un article...";
            document.getElementById('detail_ref').innerHTML = '-';
            document.getElementById('detail_img').classList.add('d-none');
            document.getElementById('detail_icon').classList.remove('d-none');
        }
    });

    // --- HELPER FUNCTIONS ---

    function activateStep(n) {
        steps[n].classList.remove('disabled');
        // Auto scroll?
        // steps[n].scrollIntoView({behavior: 'smooth', block: 'center'});
    }

    function deactivateStep(n) {
        for (let i = n; i <= 5; i++) {
            steps[i].classList.add('disabled');
            // Reset inputs if needed
        }
    }

    async function fetchAPI(action, params = {}) {
        const qs = new URLSearchParams({ ...params, action }).toString();
        const res = await fetch(`ajax/get_funnel_data.php?${qs}`);
        return await res.json();
    }

    function loadFournisseurs() {
        fetchAPI('get_fournisseurs').then(data => {
            let html = '<option value="">Choisir Fournisseur...</option>';
            data.forEach(f => {
                html += `<option value="${f.id}">${f.nom}</option>`;
            });
            ui.fournisseurSelect.innerHTML = html;
        });
    }

    function loadFamilles() {
        fetchAPI('get_familles').then(data => {
            let html = '';
            data.forEach(f => {
                html += `<button type="button" class="btn btn-outline-secondary btn-sm family-btn mb-2" data-id="${f.id}"><i class="fas fa-${f.icon || 'box'} me-1"></i>${f.designation}</button>`;
            });
            ui.familyGrid.innerHTML = html;
        });
    }

    function loadFinitions() {
        fetchAPI('get_finitions').then(data => { state.finitions = data; });
    }

    function loadSubFamilles(famId) {
        ui.subFamilySelect.innerHTML = '<option>Chargement...</option>';
        fetchAPI('get_sous_familles', { famille_id: famId }).then(data => {
            let html = '<option value="">Choisir...</option>';
            data.forEach(s => {
                html += `<option value="${s.id}">${s.designation}</option>`;
            });
            ui.subFamilySelect.innerHTML = html;
        });
    }

    function loadArticles(sfId, fId, famId) {
        ui.articleSelect.innerHTML = '<option>Chargement...</option>';
        fetchAPI('get_articles', { sous_famille_id: sfId, fournisseur_id: fId, famille_id: famId }).then(data => {
            let html = '<option value="">Sélectionner Produit...</option>';
            if (data.length === 0) html = '<option value="">Aucun article trouvé</option>';

            data.forEach(a => {
                html += `<option value="${a.id}" 
                            data-lengths='${JSON.stringify(a.stock_lengths || [])}' 
                            data-type="${a.type_vente}"
                            data-ref="${a.reference_interne || a.ref_fournisseur || ''}"
                            data-prix="${a.prix_display || 'N/C'}"
                            data-poids="${a.poids_kg || '0'}"
                            data-longueur="${a.longueur_barre || '-'}"
                            data-finition="${a.finition_display || 'Standard'}"
                            data-stock="${a.stock_actuel || '0'}"
                            data-image="${a.image_path || ''}">
                            ${a.designation}
                         </option>`;
            });
            ui.articleSelect.innerHTML = html;
        });
    }

    function renderCriteria(optionRec) {
        ui.criteriaPanel.classList.remove('d-none');
        ui.criteriaPanel.innerHTML = ''; // Clear

        let html = '<h6 class="border-bottom pb-2 mb-3">Configuration</h6>';

        // 1. Color / Finish Selection (Loop over state.finitions)
        let finishOptions = '<option value="">Brut / Standard</option>';
        state.finitions.forEach(f => {
            finishOptions += `<option value="${f.id}">${f.code_ral} - ${f.nom_couleur} (${f.aspect})</option>`;
        });

        const finishSelect = `
            <div class="col-12 mb-3">
                <label class="form-label fw-bold small">Finition / Couleur</label>
                <select class="form-select" name="finition_id" id="finition_id">
                    ${finishOptions}
                </select>
            </div>`;

        // Logic based on Family
        const isVitrage = (state.familleLabel && state.familleLabel.includes('Vitrage'));
        const isTole = (state.familleLabel && state.familleLabel.includes('Tôles'));

        if (isVitrage || isTole) {
            html += `
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label small">Largeur (mm)</label>
                    <input type="number" class="form-control" name="largeur" required>
                </div>
                <div class="col-6">
                    <label class="form-label small">Hauteur (mm)</label>
                    <input type="number" class="form-control" name="hauteur" required>
                </div>
                ${finishSelect}
            </div>`;
        } else {
            // Profile logic
            html += `
            <div class="row g-2">
                <div class="col-8">
                    <label class="form-label small">Longueur Pièce (mm)</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="longueur" id="longueur" required>
                        <span class="input-group-text">mm</span>
                    </div>
                    <small class="text-success small fst-italic" id="opt_msg"></small>
                </div>
                <div class="col-4">
                     <label class="form-label small">Quantité</label>
                     <input type="number" class="form-control fw-bold" name="quantite" value="1" min="1">
                </div>
                <!-- Color Full Width -->
                ${finishSelect}
                <div class="col-12 mt-2">
                     <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="renfort" name="renfort_acier">
                        <label class="form-check-label small" for="renfort">Renfort Acier Requis</label>
                     </div>
                </div>
            </div>`;
        }

        // Add Submit Button INSIDE the criteria panel for better flow
        html += `
        <div class="mt-3 text-end">
             <button type="submit" class="btn btn-primary" onclick="submitForm()">
                <i class="fas fa-plus-circle me-1"></i> AJOUTER AU PANIER
             </button>
        </div>`;

        ui.criteriaPanel.innerHTML = html;

        // Re-attach listeners for optimization feedback if length exists
        if (document.getElementById('longueur')) {
            document.getElementById('longueur').focus();
            document.getElementById('longueur').addEventListener('input', checkOptimization);
        }
    }

    function checkOptimization(e) {
        const val = parseInt(e.target.value);
        const msg = document.getElementById('opt_msg');
        if (val > 0) {
            msg.classList.remove('d-none');
            msg.innerText = "Calcul auto chute..."; // Placeholder for real logic
        }
    }

    // Submit Wrapper because button is now dynamic
    window.submitForm = function () {
        ui.form.dispatchEvent(new Event('submit'));
    }

    // --- SUBMIT ---
    ui.form.addEventListener('submit', (e) => {
        e.preventDefault();
        const fd = new FormData(ui.form);

        // Manually get values from Criteria Panel since they are OUTSIDE the left form in DOM? 
        // Wait, Form wraps Left Card. Criteria Panel is in Right Col (Outside Form).
        // ERROR: The DOM structure in PHP has inputs in Right Col (col-md-8), but <form> tag is in Left Col (col-md-4).
        // FIX: We need to gather data manually or move form tag to wrap everything.
        // Quick Fix: Gather data from ID access since inputs have names.

        const payload = new FormData();
        payload.append('affaire_id', document.getElementById('affaire_id').value);
        payload.append('article_id', document.getElementById('article_id').value);

        // Grab dynamic inputs by name from the entire document
        ['longueur', 'largeur', 'hauteur', 'finition_id', 'quantite', 'renfort_acier'].forEach(k => {
            const el = document.getElementsByName(k)[0];
            if (el) {
                if (el.type === 'checkbox') payload.append(k, el.checked ? 1 : 0);
                else payload.append(k, el.value);
            }
        });

        const artText = ui.articleSelect.options[ui.articleSelect.selectedIndex].text.trim();
        payload.append('designation', artText);

        fetch('ajax/save_besoin.php', {
            method: 'POST',
            body: payload
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Get finish text
                    const finSel = document.getElementById('finition_id');
                    const finText = finSel && finSel.value ? finSel.options[finSel.selectedIndex].text : 'Standard';

                    const criteria = payload.get('largeur') ? `${payload.get('largeur')}x${payload.get('hauteur')}` : `${payload.get('longueur')}mm`;

                    const row = `<tr>
                    <td class="fw-bold text-primary">${artText}</td>
                    <td>${criteria} <br><small class="text-muted"><i class="fas fa-paint-brush"></i> ${finText}</small></td>
                    <td><span class="badge bg-dark">${payload.get('quantite')}</span></td>
                    <td>${data.optimization.waste_percent ? '<span class="badge bg-success">' + data.optimization.waste_percent + '% Chute</span>' : '-'}</td>
                     <td><button class="btn btn-sm btn-light text-danger"><i class="fas fa-trash"></i></button></td>
                </tr>`;

                    const tbody = document.querySelector('#table_besoins tbody');
                    if (tbody.querySelector('td[colspan]')) tbody.innerHTML = '';
                    tbody.insertAdjacentHTML('afterbegin', row);

                    // Reset Length/Qty for rapid entry
                    if (document.getElementsByName('longueur')[0]) {
                        document.getElementsByName('longueur')[0].value = '';
                        document.getElementsByName('longueur')[0].focus();
                    }

                } else {
                    alert("Erreur: " + data.messages.join(' '));
                }
            });
    });

    // Expose reset
    window.resetFunnel = function () {
        location.reload();
    };

});
