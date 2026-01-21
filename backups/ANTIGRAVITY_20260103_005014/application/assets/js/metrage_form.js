/**
 * METRAGE FORM HANDLER V2.0 - SAFE MODE (DEBUG)
 * Calls API DISABLED to isolate freeze issue.
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        initModalEvents();
        initFormSubmission();
    });

    function initModalEvents() {
        $('#modal-new-inter').on('show.bs.modal', function () {
            console.log('[MetrageForm SAFE] Opening modal...');
            $('#form-new-inter')[0].reset();
            loadAffaires();     // MOCKED
            loadTechniciens();  // MOCKED
        });
    }

    function loadAffaires() {
        const $select = $('#select-affaire');
        $select.html('<option value="">-- Mode Debug: API désactivée --</option>');
        console.log('[MetrageForm SAFE] Adding fake options...');

        // Simulation délai réseau
        setTimeout(() => {
            $select.append('<option value="999">AFFAIRE TEST LOCAL (SAFE)</option>');
            console.log('[MetrageForm SAFE] Fake options added.');
        }, 500);
    }

    function loadTechniciens() {
        const $select = $('#select-technicien');
        $select.html('<option value="">-- Mode Debug: API désactivée --</option>');
        setTimeout(() => {
            $select.append('<option value="888">TECHNICIEN TEST LOCAL</option>');
        }, 500);
    }

    function initFormSubmission() {
        $('#form-new-inter').on('submit', function (e) {
            e.preventDefault();
            Swal.fire('Mode Debug', 'Soumission désactivée en mode safe.', 'info');
        });
    }

})(jQuery);
