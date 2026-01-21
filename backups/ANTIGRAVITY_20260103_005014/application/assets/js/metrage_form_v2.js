/**
 * METRAGE FORM HANDLER V2.1 (RESTORED)
 * Gestion robuste du formulaire de création d'intervention.
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        initModalEvents();
        initFormSubmission();
    });

    function initModalEvents() {
        $('#modal-new-inter').on('show.bs.modal', function () {
            console.log('[MetrageForm] Loading data...');
            $('#form-new-inter')[0].reset();
            loadAffaires();
            loadTechniciens();
        });
    }

    function loadAffaires() {
        const $select = $('#select-affaire');
        $select.html('<option value="">Chargement...</option>');

        $.ajax({
            url: 'api_metrage_cockpit.php?action=get_affaires_sans_metrage',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                $select.empty();

                if (response.success && Array.isArray(response.affaires)) {
                    if (response.affaires.length === 0) {
                        $select.append('<option value="">Aucune affaire disponible</option>');
                    } else {
                        $select.append('<option value="">-- Sélectionnez une affaire --</option>');
                        response.affaires.forEach(function (aff) {
                            $select.append(
                                $('<option>', {
                                    value: aff.id,
                                    text: `${aff.nom_affaire} (${aff.client})`
                                })
                            );
                        });
                    }
                } else {
                    handleLoadError($select, "Format invalide");
                }
            },
            error: function () {
                handleLoadError($select, "Erreur API");
            }
        });
    }

    function loadTechniciens() {
        const $select = $('#select-technicien');
        $.ajax({
            url: 'api_metrage_cockpit.php?action=get_techniciens',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success && Array.isArray(response.techniciens)) {
                    $select.empty();
                    $select.append('<option value="">Non assigné</option>');
                    response.techniciens.forEach(function (tech) {
                        $select.append(
                            $('<option>', {
                                value: tech.id,
                                text: tech.nom
                            })
                        );
                    });
                }
            }
        });
    }

    function initFormSubmission() {
        $('#form-new-inter').on('submit', function (e) {
            e.preventDefault();

            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');

            const affaireId = $('#select-affaire').val();
            if (!affaireId) {
                Swal.fire('Erreur', 'Veuillez sélectionner une affaire.', 'warning');
                return;
            }

            const originalBtnText = $btn.html();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Traitement...');

            const formData = $form.serialize() + '&action=create_intervention';

            $.ajax({
                url: 'api_metrage_cockpit.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès',
                            text: 'Intervention planifiée avec succès',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            $('#modal-new-inter').modal('hide');
                            if (typeof Cockpit !== 'undefined') Cockpit.refreshData();
                        });
                    } else {
                        Swal.fire('Erreur', response.error || 'Erreur inconnue', 'error');
                    }
                },
                error: function (xhr) {
                    console.error("Erreur Submit:", xhr.responseText);
                    Swal.fire('Erreur Système', 'Impossible de créer l\'intervention.', 'error');
                },
                complete: function () {
                    $btn.prop('disabled', false).html(originalBtnText);
                }
            });
        });
    }

    function handleLoadError($el, msg) {
        $el.html(`<option value="">Erreur: ${msg}</option>`);
    }

})(jQuery);
