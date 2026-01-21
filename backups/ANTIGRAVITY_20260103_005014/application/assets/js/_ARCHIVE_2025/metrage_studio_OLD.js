/**
 * METRAGE STUDIO ENGINE V10
 * Assistant-Driven Edit Flow
 */

const Studio = {
    currentId: null,
    currentData: {},
    etapes: [],
    currentEtapeIdx: 0,
    tempData: {},
    categorie: null,
    products: [],
    editingProductId: null,
    editingProductIdx: null,
    expandedProductIdx: null,
    isEditMode: false,
    editingFieldName: null,

    init: () => {
        LIGNES.forEach((l, idx) => {
            const data = JSON.parse(l.details_json || '{}');
            Studio.products.push({
                id: l.id,
                idx: idx,
                name: data.type_nom || data.meta?.product_name || 'Produit',
                localisation: l.localisation || '',
                complete: true,
                data: data
            });
        });
        Studio.renderTree();
        Studio.showWelcome();
    },

    showWelcome: () => {
        $('#conversation_area').empty();
        const affaireName = INTERVENTION?.nom_affaire || 'Métrage Libre';
        const isLinked = affaireName && affaireName !== 'Métrage Libre';

        if (isLinked) {
            Studio.addBubble(`Bienvenue ! Affaire: <strong>${affaireName}</strong>`, 'assistant');
        } else {
            Studio.addBubble(`Bienvenue ! Métrage non lié à une affaire.`, 'assistant');
        }

        let options = [];
        if (Studio.products.length > 0) {
            Studio.addBubble(`${Studio.products.length} produit(s) enregistrés.`, 'assistant');
            options = [
                { label: "➕ Ajouter", icon: "fas fa-plus", action: () => Studio.startNewOuvrage() },
                { label: "✏️ Modifier", icon: "fas fa-edit", action: () => Studio.showProductList() }
            ];
        } else {
            options = [
                { label: "🔗 Lier à affaire", icon: "fas fa-link", action: () => Studio.askLinkAffaire() },
                { label: "📋 Commencer", icon: "fas fa-play", action: () => Studio.startNewOuvrage() }
            ];
        }
        Studio.showOptions(options);
    },

    askLinkAffaire: () => {
        Studio.addBubble("Entrez le numéro d'affaire :", 'assistant');
        $('#response_area').html(`
            <div class="response-input-group">
                <input type="text" class="response-input" id="affaire_search" placeholder="AFF-2024-001" autofocus>
                <button class="btn btn-primary btn-lg" onclick="Studio.searchAffaire()"><i class="fas fa-search"></i></button>
            </div>
            <button class="btn btn-outline-light mt-2" onclick="Studio.startNewOuvrage()">Continuer sans lier</button>
        `);
    },

    searchAffaire: () => {
        const q = $('#affaire_search').val();
        if (!q) return;
        Studio.addBubble(`Recherche: "${q}"...`, 'user');
        setTimeout(() => {
            Studio.addBubble("Recherche en développement. Mode libre.", 'assistant');
            setTimeout(() => Studio.startNewOuvrage(), 800);
        }, 500);
    },

    showProductList: () => {
        Studio.addBubble("Quel produit modifier ?", 'assistant');
        const opts = Studio.products.map((p, i) => ({
            label: `${p.name} (${p.localisation || 'sans loc.'})`,
            icon: "fas fa-box",
            action: () => Studio.editProduct(i)
        }));
        opts.push({ label: "Retour", icon: "fas fa-arrow-left", action: () => Studio.showWelcome() });
        Studio.showOptions(opts);
    },

    editProduct: (idx) => {
        const product = Studio.products[idx];
        if (!product) return;

        Studio.tempData = { ...product.data };
        Studio.tempData.type_nom = product.name;
        Studio.tempData.localisation = product.localisation;
        Studio.editingProductId = product.id;
        Studio.editingProductIdx = idx;
        Studio.isEditMode = true;
        Studio.expandedProductIdx = idx;
        Studio.renderTree();

        Studio.addBubble(`Modification de <strong>${product.name}</strong>. Quel champ ?`, 'assistant');

        if (Studio.tempData.type_id) {
            $.get('api/api_get_etapes.php', { type_id: Studio.tempData.type_id })
                .done(res => {
                    if (res.success && res.etapes) Studio.etapes = res.etapes;
                    Studio.showFieldsToEdit();
                })
                .fail(() => Studio.showFieldsToEdit());
        } else {
            Studio.showFieldsToEdit();
        }
    },

    showFieldsToEdit: () => {
        const d = Studio.tempData;
        let opts = [];

        for (const [key, val] of Object.entries(d)) {
            if (['type_id', 'categorie'].includes(key) || key.includes('_details')) continue;
            opts.push({
                label: `${key}: ${val || '(vide)'}`,
                icon: val ? 'fas fa-check text-success' : 'fas fa-circle',
                action: () => Studio.editFieldWithAssistant(key)
            });
        }

        opts.push({ label: "💾 Enregistrer", icon: "fas fa-save", action: () => Studio.saveEditedProduct() });
        opts.push({ label: "❌ Annuler", icon: "fas fa-times", action: () => Studio.cancelEdit() });
        Studio.showOptions(opts);
    },

    editFieldWithAssistant: (fieldName) => {
        const currentVal = Studio.tempData[fieldName] || '';
        Studio.editingFieldName = fieldName;
        const etape = Studio.etapes.find(e => e.code_etape === fieldName);

        if (etape) {
            if (currentVal) Studio.addBubble(`Actuel: <strong>${currentVal}</strong>`, 'user');
            Studio.addBubble(etape.message_assistant, 'assistant');
            Studio.addRappel(etape.rappel);
            Studio.addSchema(etape.schema_url);
            Studio.renderEditFieldInput(etape, currentVal);
        } else {
            Studio.addBubble(`Modifier <strong>${fieldName}</strong>`, 'assistant');
            if (currentVal) Studio.addBubble(`Actuel: ${currentVal}`, 'assistant');
            Studio.renderGenericEditInput(currentVal);
        }
    },

    renderEditFieldInput: (etape, currentVal) => {
        let html = '';
        switch (etape.type_saisie) {
            case 'liste':
                html = '<div class="response-options">';
                (etape.options || []).forEach(opt => {
                    const sel = (opt === currentVal) ? 'style="border:2px solid #4CAF50"' : '';
                    html += `<button class="response-btn" ${sel} onclick="Studio.submitEditChoice('${opt.replace(/'/g, "\\'")}')">${opt}</button>`;
                });
                html += '</div>';
                break;
            case 'binaire':
                html = '<div class="response-options">';
                (etape.options || ['Oui', 'Non']).forEach(opt => {
                    const ico = opt.toLowerCase().includes('oui') ? 'fa-check' : 'fa-times';
                    const sel = (opt === currentVal) ? 'style="border:2px solid #4CAF50"' : '';
                    html += `<button class="response-btn" ${sel} onclick="Studio.submitEditChoice('${opt}')"><i class="fas ${ico}"></i> ${opt}</button>`;
                });
                html += '</div>';
                break;
            case 'mm':
            case 'nombre':
                html = `<div class="response-input-group"><input type="number" class="response-input" id="edit_input" value="${currentVal}" autofocus><button class="btn btn-primary btn-lg" onclick="Studio.submitEditInput()"><i class="fas fa-check"></i></button></div>`;
                break;
            default:
                html = `<div class="response-input-group"><input type="text" class="response-input" id="edit_input" value="${currentVal}" autofocus><button class="btn btn-primary btn-lg" onclick="Studio.submitEditInput()"><i class="fas fa-check"></i></button></div>`;
        }
        html += `<div class="mt-2"><button class="btn btn-outline-light btn-sm" onclick="Studio.showFieldsToEdit()"><i class="fas fa-arrow-left"></i> Retour</button></div>`;
        $('#response_area').html(html);
    },

    renderGenericEditInput: (currentVal) => {
        $('#response_area').html(`
            <div class="response-input-group"><input type="text" class="response-input" id="edit_input" value="${currentVal}" autofocus><button class="btn btn-primary btn-lg" onclick="Studio.submitEditInput()"><i class="fas fa-check"></i></button></div>
            <div class="mt-2"><button class="btn btn-outline-light btn-sm" onclick="Studio.showFieldsToEdit()"><i class="fas fa-arrow-left"></i> Retour</button></div>
        `);
    },

    submitEditChoice: (val) => {
        Studio.tempData[Studio.editingFieldName] = val;
        Studio.addBubble(val, 'user');
        Studio.renderTree();
        Studio.addBubble('✅ Modifié ! Autre champ ?', 'assistant');
        Studio.showFieldsToEdit();
    },

    submitEditInput: () => {
        const val = $('#edit_input').val();
        Studio.tempData[Studio.editingFieldName] = val;
        Studio.addBubble(val, 'user');
        Studio.renderTree();
        Studio.addBubble('✅ Modifié ! Autre champ ?', 'assistant');
        Studio.showFieldsToEdit();
    },

    saveEditedProduct: () => {
        Studio.addBubble("Enregistrement...", 'assistant');
        const d = Studio.tempData;
        $.post('api/api_save_metrage.php', {
            metrage_id: METRAGE_ID,
            type_id: d.type_id || 1,
            ligne_id: Studio.editingProductId,
            'fields[localisation]': d.localisation || '',
            'fields[all_data]': JSON.stringify(d)
        })
            .done(res => {
                if (res?.success) {
                    if (Studio.editingProductIdx !== null) {
                        Studio.products[Studio.editingProductIdx].data = { ...d };
                        Studio.products[Studio.editingProductIdx].localisation = d.localisation;
                        Studio.products[Studio.editingProductIdx].name = d.type_nom;
                    }
                    Studio.resetEditState();
                    Studio.addBubble("✅ Enregistré !", 'assistant');
                    Studio.showWelcome();
                } else {
                    Studio.addBubble("❌ Erreur", 'assistant');
                }
            }).fail(() => Studio.addBubble("❌ Erreur serveur", 'assistant'));
    },

    cancelEdit: () => {
        Studio.resetEditState();
        Studio.addBubble("Annulé.", 'assistant');
        Studio.showWelcome();
    },

    resetEditState: () => {
        Studio.tempData = {};
        Studio.editingProductId = null;
        Studio.editingProductIdx = null;
        Studio.expandedProductIdx = null;
        Studio.isEditMode = false;
        Studio.etapes = [];
        Studio.renderTree();
    },

    // TREE
    renderTree: () => {
        let html = '';
        if (Studio.tempData.type_nom && !Studio.editingProductId) {
            html += Studio.renderProductNode({ name: Studio.tempData.type_nom, localisation: Studio.tempData.localisation || '...', active: true, etapes: Studio.etapes, currentEtapeIdx: Studio.currentEtapeIdx }, -1, true);
        }
        Studio.products.forEach((p, idx) => {
            const exp = (Studio.expandedProductIdx === idx) || (Studio.editingProductId === p.id);
            html += Studio.renderProductNode(p, idx, exp);
        });
        $('#tree_products').html(html || '<div class="text-muted small p-3">Aucun produit</div>');
    },

    renderProductNode: (product, idx, isExpanded) => {
        const icon = product.complete ? 'fas fa-check-circle text-success' : 'fas fa-spinner fa-spin';
        const chevron = isExpanded ? 'fa-chevron-down' : 'fa-chevron-right';
        let html = `<div class="tree-node tree-product ${isExpanded ? 'active' : ''}">
            <div class="node-header" onclick="Studio.toggleProduct(${idx})" style="cursor:pointer">
                <i class="fas ${chevron} me-2 opacity-50" style="font-size:0.7rem"></i>
                <i class="${icon} me-2"></i><span class="node-title">${product.name}</span>
            </div>
            <div class="node-meta">${product.localisation}</div>`;

        if (isExpanded) {
            if (product.etapes?.length > 0) {
                product.etapes.forEach((e, eIdx) => {
                    const status = eIdx < (product.currentEtapeIdx || 0) ? 'done' : (eIdx === (product.currentEtapeIdx || 0) ? 'current' : 'pending');
                    const ico = status === 'done' ? 'fas fa-check-circle' : (status === 'current' ? 'fas fa-arrow-right' : 'far fa-circle');
                    html += `<div class="tree-node tree-etape ${status}"><div class="node-header" onclick="Studio.goToEtape(${eIdx})"><i class="${ico} me-2"></i><span>${e.nom_etape}</span></div></div>`;
                });
            } else if (product.complete && product.data) {
                for (const [k, v] of Object.entries(product.data)) {
                    if (['type_id', 'categorie'].includes(k) || k.includes('_details') || !v) continue;
                    html += `<div class="tree-node tree-etape done"><div class="node-header" style="font-size:0.75rem;opacity:0.8"><i class="fas fa-check me-2"></i>${k}: <strong>${v}</strong></div></div>`;
                }
                html += `<div class="tree-node tree-etape"><div class="node-header" onclick="Studio.editProduct(${idx})" style="cursor:pointer"><i class="fas fa-edit me-2 text-warning"></i><span class="text-warning">Modifier</span></div></div>`;
            }
        }
        return html + '</div>';
    },

    toggleProduct: (idx) => {
        if (idx < 0) return;
        Studio.expandedProductIdx = Studio.expandedProductIdx === idx ? null : idx;
        Studio.renderTree();
    },

    goToEtape: (idx) => {
        if (idx < Studio.currentEtapeIdx) {
            Studio.currentEtapeIdx = idx;
            Studio.runCurrentEtape();
        }
    },

    // UI
    addBubble: (html, type = 'assistant') => {
        $('#conversation_area').append(`<div class="chat-bubble ${type}">${type === 'assistant' ? '<span class="bubble-icon"><i class="fas fa-robot"></i></span>' : ''}<div class="bubble-content">${html}</div></div>`);
        const area = document.getElementById('conversation_area');
        if (area) area.scrollTop = area.scrollHeight;
    },

    addRappel: (r) => { if (r) $('#conversation_area').append(`<div class="assistant-rappel">${r}</div>`); },
    addSchema: (url) => { if (url) $('#conversation_area').append(`<div class="assistant-schema"><img src="assets/img/${url}" onerror="this.parentElement.style.display='none'"></div>`); },

    showOptions: (options) => {
        let html = '<div class="response-options">';
        options.forEach((o, i) => {
            html += `<button class="response-btn" onclick="Studio.handleOption(${i})">${o.icon ? `<i class="${o.icon}"></i>` : ''}<span>${o.label}</span></button>`;
        });
        $('#response_area').html(html + '</div>');
        Studio.currentOptions = options;
    },

    handleOption: (i) => {
        const o = Studio.currentOptions[i];
        if (!o) return;
        Studio.addBubble(o.label, 'user');
        $('#response_area').html('<div class="text-center text-white-50"><i class="fas fa-spinner fa-spin"></i></div>');
        setTimeout(() => o.action(), 200);
    },

    // WORKFLOW
    startNewOuvrage: () => {
        Studio.tempData = {};
        Studio.currentEtapeIdx = 0;
        Studio.etapes = [];
        Studio.editingProductId = null;
        Studio.isEditMode = false;
        Studio.renderTree();
        Studio.askCategory();
    },

    askCategory: () => {
        const cats = [...new Set(TYPES.map(t => t.categorie))];
        if (!cats.length) { Studio.addBubble("⚠️ Aucune catégorie.", 'assistant'); return; }
        const opts = cats.map(c => ({ label: c, icon: Studio.getCategoryIcon(c), action: () => { Studio.categorie = c; Studio.tempData.categorie = c; Studio.askProduct(); } }));
        Studio.addBubble("Quelle <strong>catégorie</strong> ?", 'assistant');
        Studio.showOptions(opts);
    },

    askProduct: () => {
        const prods = TYPES.filter(t => t.categorie === Studio.categorie);
        const opts = prods.map(p => ({ label: p.nom, action: () => { Studio.tempData.type_id = p.id; Studio.tempData.type_nom = p.nom; Studio.renderTree(); Studio.loadEtapes(p.id); } }));
        Studio.addBubble(`Quel type de <strong>${Studio.categorie}</strong> ?`, 'assistant');
        Studio.showOptions(opts);
    },

    loadEtapes: (typeId) => {
        Studio.addBubble("<em>Chargement...</em>", 'assistant');
        $.get('api/api_get_etapes.php', { type_id: typeId })
            .done(res => {
                if (res.success && res.etapes?.length) {
                    Studio.etapes = res.etapes;
                    Studio.currentEtapeIdx = 0;
                    Studio.renderTree();
                    setTimeout(() => Studio.runCurrentEtape(), 300);
                } else Studio.runFallbackFlow();
            }).fail(() => Studio.runFallbackFlow());
    },

    runCurrentEtape: () => {
        const e = Studio.etapes[Studio.currentEtapeIdx];
        if (!e) { Studio.showRecap(); return; }
        Studio.renderTree();
        Studio.addBubble(`<span class="etape-progress">[${Studio.currentEtapeIdx + 1}/${Studio.etapes.length}]</span> ${e.message_assistant}`, 'assistant');
        Studio.addRappel(e.rappel);
        Studio.addSchema(e.schema_url);
        Studio.renderEtapeInput(e);
    },

    renderEtapeInput: (e) => {
        let html = '';
        switch (e.type_saisie) {
            case 'texte': html = `<div class="response-input-group"><input type="text" class="response-input" id="etape_input" autofocus><button class="btn btn-primary btn-lg" onclick="Studio.submitInput('${e.code_etape}')"><i class="fas fa-arrow-right"></i></button></div>`; break;
            case 'mm': case 'nombre': html = `<div class="response-input-group"><input type="number" class="response-input" id="etape_input" autofocus><button class="btn btn-primary btn-lg" onclick="Studio.submitInput('${e.code_etape}')"><i class="fas fa-arrow-right"></i></button></div>`; break;
            case 'multi_mm': html = `<div class="multi-input-group"><div class="multi-input-row"><label>M1</label><input type="number" class="response-input" id="i_m1"></div><div class="multi-input-row"><label>M2</label><input type="number" class="response-input" id="i_m2"></div><div class="multi-input-row"><label>M3</label><input type="number" class="response-input" id="i_m3"></div><button class="btn btn-primary btn-lg w-100 mt-2" onclick="Studio.submitMultiMm('${e.code_etape}')">Suivant</button></div>`; break;
            case 'liste': html = '<div class="response-options">'; (e.options || []).forEach(o => { html += `<button class="response-btn" onclick="Studio.submitChoice('${e.code_etape}','${o.replace(/'/g, "\\'")}')">${o}</button>`; }); html += '</div>'; break;
            case 'binaire': html = '<div class="response-options">'; (e.options || ['Oui', 'Non']).forEach(o => { const i = o.toLowerCase().includes('oui') ? 'fa-check' : 'fa-times'; html += `<button class="response-btn" onclick="Studio.submitChoice('${e.code_etape}','${o}')"><i class="fas ${i}"></i> ${o}</button>`; }); html += '</div>'; break;
            case 'photo': html = `<div class="photo-input-group"><label class="photo-upload-btn"><i class="fas fa-camera fa-2x"></i><span>Photo</span><input type="file" accept="image/*" capture="environment" onchange="Studio.handlePhoto('${e.code_etape}',this)" hidden></label><button class="btn btn-outline-light mt-2" onclick="Studio.skipEtape()">Passer</button></div>`; break;
            default: html = `<div class="response-input-group"><input type="text" class="response-input" id="etape_input" autofocus><button class="btn btn-primary btn-lg" onclick="Studio.submitInput('${e.code_etape}')"><i class="fas fa-arrow-right"></i></button></div>`;
        }
        if (Studio.currentEtapeIdx > 0) html += `<div class="mt-3"><button class="btn btn-outline-light btn-sm" onclick="Studio.prevEtape()"><i class="fas fa-arrow-left"></i> Retour</button></div>`;
        $('#response_area').html(html);
    },

    submitInput: (c) => { const v = $('#etape_input').val(); if (!v) return; Studio.tempData[c] = v; Studio.addBubble(v, 'user'); Studio.nextEtape(); },
    submitMultiMm: (c) => { const m1 = +$('#i_m1').val() || 0, m2 = +$('#i_m2').val() || 0, m3 = +$('#i_m3').val() || 0; if (!m1 && !m2 && !m3) return; const min = Math.min(m1 || 9999, m2 || 9999, m3 || 9999); Studio.tempData[c] = min; Studio.addBubble(`${m1}/${m2}/${m3} → ${min}mm`, 'user'); Studio.nextEtape(); },
    submitChoice: (c, v) => { Studio.tempData[c] = v; Studio.addBubble(v, 'user'); Studio.nextEtape(); },
    handlePhoto: (c, inp) => { const f = inp.files[0]; if (!f) return; Studio.tempData[c] = f.name; Studio.addBubble(`📷 ${f.name}`, 'user'); Studio.nextEtape(); },
    skipEtape: () => { Studio.addBubble('<em>Passé</em>', 'user'); Studio.nextEtape(); },
    nextEtape: () => { Studio.currentEtapeIdx++; Studio.renderTree(); setTimeout(() => Studio.runCurrentEtape(), 200); },
    prevEtape: () => { if (Studio.currentEtapeIdx > 0) { Studio.currentEtapeIdx--; Studio.renderTree(); Studio.runCurrentEtape(); } },

    showRecap: () => {
        let html = '<strong>📋 Récapitulatif:</strong><br>';
        for (const [k, v] of Object.entries(Studio.tempData)) { if (v && !k.includes('_details')) html += `• ${k}: <strong>${v}</strong><br>`; }
        Studio.addBubble(html, 'assistant');
        Studio.showOptions([{ label: "✅ Enregistrer", icon: "fas fa-save", action: () => Studio.save() }, { label: "🔄 Recommencer", icon: "fas fa-redo", action: () => Studio.startNewOuvrage() }]);
    },

    save: () => {
        Studio.addBubble("Enregistrement...", 'assistant');
        const d = Studio.tempData;
        $.post('api/api_save_metrage.php', { metrage_id: METRAGE_ID, type_id: d.type_id || 1, ligne_id: 0, 'fields[localisation]': d.localisation || '', 'fields[all_data]': JSON.stringify(d) })
            .done(res => {
                if (res?.success) {
                    Studio.products.push({ id: res.id, idx: Studio.products.length, name: d.type_nom || 'Produit', localisation: d.localisation || '', complete: true, data: d });
                    Studio.tempData = {}; Studio.etapes = []; Studio.renderTree();
                    Studio.addBubble("✅ Enregistré !", 'assistant');
                    Studio.showOptions([{ label: "➕ Ajouter", icon: "fas fa-plus", action: () => Studio.startNewOuvrage() }, { label: "🏠 Retour", icon: "fas fa-home", action: () => window.location.href = 'metrage_cockpit.php' }]);
                } else Studio.addBubble("❌ Erreur", 'assistant');
            }).fail(() => Studio.addBubble("❌ Erreur serveur", 'assistant'));
    },

    runFallbackFlow: () => { Studio.tempData.localisation = prompt("Localisation?") || "RDC"; Studio.tempData.largeur = prompt("Largeur (mm)?") || 1200; Studio.tempData.hauteur = prompt("Hauteur (mm)?") || 1400; Studio.showRecap(); },
    getCategoryIcon: (c) => { if (!c) return 'fas fa-cube'; const l = c.toLowerCase(); if (l.includes('fen')) return 'fas fa-window-maximize'; if (l.includes('porte')) return 'fas fa-door-open'; if (l.includes('volet')) return 'fas fa-blinds'; if (l.includes('store')) return 'fas fa-umbrella-beach'; if (l.includes('garage')) return 'fas fa-warehouse'; if (l.includes('portail')) return 'fas fa-dungeon'; if (l.includes('pergola')) return 'fas fa-sun'; return 'fas fa-cube'; }
};

$(document).ready(() => Studio.init());
