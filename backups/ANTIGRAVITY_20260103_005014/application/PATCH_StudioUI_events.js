/**
 * StudioUI.js - PATCH pour événements boutons
 * 
 * Ajouter cette méthode après _buildAffaireSelector()
 */

// Dans la classe StudioUI, ajouter cette méthode :

_attachAffaireSelectorEvents() {
    setTimeout(() => {
        const btnLink = document.getElementById('btn-link-create');
        const btnLibre = document.getElementById('btn-libre');
        const select = document.getElementById('affaire-select');

        console.log('🔍 Attaching events:', { btnLink, btnLibre, select });

        if (btnLink) {
            btnLink.addEventListener('click', async () => {
                console.log('✅ Lier & Créer clicked');
                const affaireId = select.value;
                if (!affaireId) {
                    alert('Veuillez sélectionner une affaire');
                    return;
                }
                await this._createMetrageWithAffaire(parseInt(affaireId));
            });
            console.log('✅ Event attached to btnLink');
        } else {
            console.error('❌ btnLink not found');
        }

        if (btnLibre) {
            btnLibre.addEventListener('click', async () => {
                console.log('✅ Métrage Libre clicked');
                await this._createMetrageLibre();
            });
            console.log('✅ Event attached to btnLibre');
        } else {
            console.error('❌ btnLibre not found');
        }

        // Initialiser Select2 si disponible
        if (window.jQuery && window.jQuery.fn.select2) {
            window.jQuery('#affaire-select').select2({
                placeholder: 'Rechercher une affaire...',
                allowClear: true
            });
        }
    }, 200);
}
