/**
 * assets/js/ocr_upload.js
 * Logique pour l'upload et l'analyse OCR des factures
 */

document.addEventListener('DOMContentLoaded', () => {
    const dropzone = document.getElementById('ocr-dropzone');
    const input = document.getElementById('ocr-file-input');
    const resultContainer = document.getElementById('ocr-result-container');
    const loadingState = document.getElementById('ocr-loading');
    const emptyState = document.getElementById('ocr-empty');

    // Suggestion Fournisseur
    const inputFournisseur = document.getElementById('ocr-fournisseur');
    const idFournisseur = document.getElementById('ocr-fournisseur-id');
    const listSuggestions = document.getElementById('fournisseur-suggestions');

    // --- 1. DRAG & DROP ---
    if (dropzone) {
        dropzone.addEventListener('click', () => input.click());
        input.addEventListener('change', () => {
            if (input.files.length) handleFileUpload(input.files[0]);
        });
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.style.background = '#e9ecef';
        });
        dropzone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropzone.style.background = '';
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.style.background = '';
            if (e.dataTransfer.files.length) handleFileUpload(e.dataTransfer.files[0]);
        });
    }

    // --- 2. UPLOAD ET ANALYSE ---
    function handleFileUpload(file) {
        if (file.type !== 'application/pdf') {
            alert('Seuls les fichiers PDF sont acceptés.');
            return;
        }

        // Show Loading
        dropzone.classList.add('d-none'); // Hide Dropzone temporarily? No, maybe simpler UI
        // Actually, let's keep dropzone but show loading in the right panel
        emptyState.classList.add('d-none');
        resultContainer.classList.add('d-none');
        loadingState.classList.remove('d-none');

        const formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'upload_analyze');

        // On appelle un controleur intermédiaire qui va : 
        // 1. Upload le fichier dans TEMP
        // 2. Appeler ai_parser.php (ou inclure sa logique)
        fetch('controllers/depenses_actions.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                loadingState.classList.add('d-none');

                if (data.success) {
                    showResults(data.data, data.temp_file);
                } else {
                    alert("Erreur: " + data.message);
                    emptyState.classList.remove('d-none');
                }
            })
            .catch(err => {
                console.error(err);
                loadingState.classList.add('d-none');
                emptyState.classList.remove('d-none');
                alert("Erreur technique");
            });
    }

    // --- 3. AFFICHAGE RESULTATS ---
    function showResults(data, tempFile) {
        resultContainer.classList.remove('d-none');

        // Remplissage Champs
        document.getElementById('ocr-fournisseur').value = data.nom_fournisseur || '';
        document.getElementById('ocr-date').value = data.date_document || '';
        document.getElementById('ocr-total').value = data.montant_total_ht || '';
        document.getElementById('ocr-ref').value = data.numero_document || '';

        // Stocker le chemin temporaire
        resultContainer.dataset.tempFile = tempFile;

        // Auto-Recherche Fournisseur si nom détecté
        if (data.nom_fournisseur) {
            searchFournisseurDb(data.nom_fournisseur);
        }

        // Tableau Lignes
        const tbody = document.getElementById('ocr-lines-body');
        tbody.innerHTML = '';
        if (data.lignes_articles && data.lignes_articles.length > 0) {
            data.lignes_articles.forEach(line => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${line.designation || 'Article sans nom'}</td>
                    <td class="text-end fw-bold">${line.quantite || 1}</td>
                    <td class="text-end">${line.prix_unitaire || 0} €</td>
                    <td class="text-end fw-bold">${(line.quantite * line.prix_unitaire).toFixed(2)} €</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Aucune ligne détaillée. Seul le total sera importé.</td></tr>';
        }
    }

    // --- 4. RECHERCHE FOURNISSEUR ---
    let searchTimeout;
    inputFournisseur.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        idFournisseur.value = ''; // Reset ID on change
        const q = inputFournisseur.value;
        if (q.length < 2) return;

        searchTimeout = setTimeout(() => searchFournisseurDb(q), 300);
    });

    function searchFournisseurDb(query) {
        fetch(`controllers/depenses_actions.php?action=search_fournisseur&q=${encodeURIComponent(query)}`)
            .then(r => r.json())
            .then(data => {
                // Si correspondance exacte trouvée par le backend (pour l'auto-select)
                // Ici on fait simple : on affiche la liste
                listSuggestions.innerHTML = '';
                if (data.length > 0) {
                    // Auto-Match si 1 seul résultat et score élevé ? 
                    // Pour l'instant on laisse l'user choisir sauf si c'est EXACT
                    data.forEach(f => {
                        const li = document.createElement('li');
                        li.innerHTML = `<a class="dropdown-item" href="#" data-id="${f.id}">${f.nom}</a>`;
                        li.querySelector('a').addEventListener('click', (e) => {
                            e.preventDefault();
                            inputFournisseur.value = f.nom;
                            idFournisseur.value = f.id;
                        });
                        listSuggestions.appendChild(li);
                    });
                    // Ouvrir dropdown ? (Compliqué sans bootstrap JS api direct parfois)
                    // On peut juste colorer en vert si valide
                }

                // Si on a un ID renvoyé directement dans le data (optimisation backend)
                // On verra plus tard.
            });
    }

    // --- 5. CREATION DEPENSE ---
    window.createExpense = function () { // Attach to window for onclick
        const fournisseurId = idFournisseur.value;
        const tempFile = resultContainer.dataset.tempFile;
        // ... gather values
        const payload = {
            action: 'create_expense',
            fournisseur_id: fournisseurId,
            fournisseur_nom: inputFournisseur.value, // Au cas où on doit créer
            date_document: document.getElementById('ocr-date').value,
            montant_ht: document.getElementById('ocr-total').value,
            numero_document: document.getElementById('ocr-ref').value,
            temp_file: tempFile,
            // On peut envoyer les lignes aussi si on veut les éditer, mais ici on prend le JSON IA tel quel ?
            // Simplification V1 : On envoie juste le header, le backend reprendra le parsing ou on store le JSON data dans le DOM
        };

        if (!payload.montant_ht) { alert("Montant manquant"); return; }
        if (!payload.fournisseur_nom) { alert("Fournisseur manquant"); return; }

        const btn = document.getElementById('btn-valider-creation');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création...';

        fetch('controllers/depenses_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'commandes_liste.php?highlight=' + data.id;
                } else {
                    alert("Erreur: " + data.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save me-2"></i>CRÉER LA DÉPENSE';
                }
            });
    };

    window.resetOCR = function () {
        location.reload();
    }

});
