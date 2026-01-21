/**
 * Module Matériel Affaire
 * Gère l'ajout, édition, suppression et changement de statut du matériel nécessaire
 */

const MaterielAffaire = {
    modal: null,
    affaireId: null,

    init: function (affaireId) {
        this.affaireId = affaireId;
        this.modal = new bootstrap.Modal(document.getElementById('modalMateriel'));

        // Event listeners
        document.getElementById('formMateriel').addEventListener('submit', (e) => this.handleSubmit(e));
    },

    openAdd: function () {
        document.getElementById('materiel_id').value = '';
        document.getElementById('formMateriel').reset();
        document.getElementById('modalMaterielLabel').textContent = 'Ajouter Matériel';
        document.getElementById('materiel_affaire_id').value = this.affaireId;
        this.modal.show();
    },

    openEdit: function (id) {
        // Fetch data
        const formData = new FormData();
        formData.append('action', 'get');
        formData.append('id', id);

        fetch('ajax/materiel_actions.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = data.data;
                    document.getElementById('materiel_id').value = item.id;
                    document.getElementById('materiel_designation').value = item.designation;
                    document.getElementById('materiel_quantite').value = item.quantite;
                    document.getElementById('materiel_unite').value = item.unite;
                    document.getElementById('materiel_priorite').value = item.priorite;
                    document.getElementById('materiel_statut').value = item.statut;
                    document.getElementById('materiel_commentaire').value = item.commentaire || '';

                    document.getElementById('modalMaterielLabel').textContent = 'Modifier Matériel';
                    this.modal.show();
                } else {
                    alert('Erreur: ' + data.message);
                }
            });
    },

    delete: function (id) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce matériel ?')) return;

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        fetch('ajax/materiel_actions.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            });
    },

    changeStatus: function (id, newStatus) {
        const formData = new FormData();
        formData.append('action', 'change_status');
        formData.append('id', id);
        formData.append('statut', newStatus);

        fetch('ajax/materiel_actions.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            });
    },

    handleSubmit: function (e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        const id = document.getElementById('materiel_id').value;
        const action = id ? 'update' : 'add';

        formData.append('action', action);
        if (!id) formData.append('affaire_id', this.affaireId);

        fetch('ajax/materiel_actions.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.modal.hide();
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            });
    }
};
