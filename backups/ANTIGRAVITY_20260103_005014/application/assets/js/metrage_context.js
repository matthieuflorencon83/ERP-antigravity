/**
 * METRAGE CONTEXT (THE GLUE)
 * Centralizes cross-module state to avoid conflicts.
 * Allows 'Organisation' module to talk to 'Technique', etc.
 */

const MetrageContext = {
    // Current Global State
    state: {
        type_ouvrage: '', // 'MENUISERIE', 'VOLET', etc.
        pose_type: '',    // 'RENOVATION', 'NEUF', ...
        couleur: '',      // To help Silicone choice
        support: ''       // To help Fixation choice
    },

    // Listeners
    init: () => {
        // Listen to Type Change (from main page usually, or input hidden)
        // Here we attach to specific inputs once they exist in DOM

        // Pose Change
        $(document).on('change', 'select[name="fields[pose_type]"], input[name="fields[pose_type]"]', function () {
            MetrageContext.state.pose_type = $(this).val();
            // Broadcast to other modules
            if (window.MetrageRules) MetrageRules.updatePoseContext(MetrageContext.state.pose_type);
        });

        // Couleur Change
        $(document).on('change', '[name*="couleur"]', function () {
            MetrageContext.state.couleur = $(this).val();
            // Example: Update Silicone Suggestion if block exists
            MetrageContext.updateSiliconeSuggestion();
        });
    },

    updateSiliconeSuggestion: () => {
        const col = MetrageContext.state.couleur;
        let silicone = 'Standard (Blanc/Translucide)';

        if (col && (col.includes('GRIS') || col.includes('ANTE') || col === 'NOIR')) {
            silicone = 'Gris Anthracite (RAL 7016) ou Noir';
        } else if (col && col.includes('CHENE') || col.includes('BOIS')) {
            silicone = 'Ton Pierre ou Brun';
        }

        // Check if Organisation module has silicone field
        const el = $('#silicone_preconise');
        if (el.length) {
            el.val(silicone); // Auto-fill
            // Trigger visual update if needed
        }
    }
};

$(document).ready(() => {
    MetrageContext.init();
});
