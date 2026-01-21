/**
 * stock_cockpit.js
 * Module "Apex" pour la gestion de stock unifiée.
 */

export class StockCockpit {
    constructor() {
        this.initEventListeners();
        this.selectedArticleId = null;
    }

    initEventListeners() {
        // Sélection Ligne Tableau
        document.querySelectorAll('.stock-row').forEach(row => {
            row.addEventListener('click', (e) => {
                // Ne pas déclencher si click sur bouton action
                if (e.target.closest('.btn-action')) return;

                this.selectArticle(row.dataset.id);
            });
        });

        // Bouton Fermer Panel
        document.getElementById('close-panel')?.addEventListener('click', () => {
            this.closePanel();
        });

        // Formulaire Mouvement (Intercept Submit)
        const formMvt = document.getElementById('form-mouvement');
        if (formMvt) {
            formMvt.addEventListener('submit', (e) => this.handleMovementSubmit(e));
        }

        // Boutons Action Rapide (sur la ligne)
        document.querySelectorAll('.btn-quick-mvt').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Empêche l'ouverture classique du panel si conflit
                const type = btn.dataset.type; // ENTREE / SORTIE
                const row = btn.closest('tr');
                const id = row.dataset.id;

                // On ouvre le panel et on pré-sélectionne le type
                this.selectArticle(id, type);
            });
        });

        // Bouton Inventaire Global (Header)
        document.getElementById('btn-inventory-mode')?.addEventListener('click', () => {
            Swal.fire({
                title: 'Mode Inventaire Global',
                text: "Cette fonctionnalité permettra de saisir l'inventaire directement dans le tableau (mode éditable).",
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Activer le mode (Bientôt)',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#0f4c75'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('À Suivre', 'Je développe ce mode "Excel-like" dans la prochaine mise à jour !', 'success');
                }
            });
        });
    }

    async selectArticle(id, preselectType = null) {
        this.selectedArticleId = id;

        // Highlight visuel
        document.querySelectorAll('.stock-row').forEach(r => r.classList.remove('table-active'));
        document.querySelector(`.stock-row[data-id="${id}"]`)?.classList.add('table-active');

        // Ouvrir Panel
        const panel = document.getElementById('details-panel');
        panel.classList.remove('d-none');

        // Loader
        document.getElementById('panel-content').innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';

        try {
            // Fetch Details (AJAX)
            const response = await fetch(`api/stock_cockpit_api.php?action=get_details&id=${id}`);
            const html = await response.text();
            document.getElementById('panel-content').innerHTML = html;

            // Re-attach events du panel (ex: boutons dans le panel)
            this.initPanelEvents();

            // Pré-sélection du type si demandé
            if (preselectType) {
                const selectType = document.querySelector('select[name="type"]');
                if (selectType) {
                    selectType.value = preselectType;
                    // Focus sur le champ quantité pour enchaîner
                    setTimeout(() => document.querySelector('input[name="quantite"]')?.focus(), 200);
                }
            }
        } catch (error) {
            console.error("Erreur chargement détails", error);
            document.getElementById('panel-content').innerHTML = '<div class="alert alert-danger">Erreur de chargement.</div>';
        }
    }

    initPanelEvents() {
        // Formulaire Mouvement (Dynamique)
        const formMvt = document.getElementById('form-mouvement');
        if (formMvt) {
            // Remove old listener to avoid duplicates if any (though innerHTML wipes them)
            formMvt.removeEventListener('submit', this.handleMovementSubmit);
            formMvt.addEventListener('submit', (e) => this.handleMovementSubmit(e));
        }
    }

    closePanel() {
        document.getElementById('details-panel').classList.add('d-none');
        document.querySelectorAll('.stock-row').forEach(r => r.classList.remove('table-active'));
        this.selectedArticleId = null;
    }

    async handleMovementSubmit(e) {
        e.preventDefault();
        if (!this.selectedArticleId) return;

        const form = e.target;
        const formData = new FormData(form);
        formData.append('article_id', this.selectedArticleId);
        formData.append('action', 'move_stock');

        try {
            const res = await fetch('api/stock_cockpit_api.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                // Refresh Panel
                this.selectArticle(this.selectedArticleId);

                // Update Row Quantity
                const row = document.querySelector(`.stock-row[data-id="${this.selectedArticleId}"]`);
                if (row) {
                    const badge = row.querySelector('.badge');
                    if (badge && data.new_total !== undefined) {
                        badge.textContent = data.new_total;
                        // Update color
                        badge.classList.remove('bg-danger', 'bg-success');
                        badge.classList.add(data.new_total <= 0 ? 'bg-danger' : 'bg-success');
                    }
                }

                // Alert Toast (SweetAlert)
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Mouvement enregistré',
                    showConfirmButton: false,
                    timer: 2000
                });

                form.reset();
            } else {
                alert('Erreur: ' + data.error);
            }
        } catch (err) {
            console.error(err);
            alert('Erreur technique.');
        }
    }
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    window.cockpit = new StockCockpit();
});
