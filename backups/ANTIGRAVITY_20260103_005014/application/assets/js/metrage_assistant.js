/**
 * METRAGE ASSISTANT V3 (SIDEBAR LOGIC)
 * Manages the "Virtual Brain" side of the interface.
 * Pushes alerts, tips, and checklists based on user context.
 */

const MetrageAssistant = {

    // Push a message to the sidebar
    say: (message, type = 'info', icon = 'fas fa-info-circle') => {
        let cssClass = 'bg-white text-dark';
        if (type === 'warning') cssClass = 'alert-warning';
        if (type === 'danger') cssClass = 'alert-danger';
        if (type === 'success') cssClass = 'bg-success-subtle text-success-emphasis border-success';

        const html = `
            <div class="ag-assistant-message ${cssClass} shadow-sm">
                <div class="d-flex">
                    <div class="icon"><i class="${icon}"></i></div>
                    <div>
                        <strong>Assistant :</strong><br>
                        <span class="small">${message}</span>
                    </div>
                </div>
            </div>
        `;

        $('#assistant-stream').prepend(html); // Newest on top
    },

    // Clear stream
    clear: () => {
        $('#assistant-stream').html('');
    },

    // Set "Anti-Oubli" Checklist
    setChecklist: (items) => {
        let listHtml = '<ul class="list-group list-group-flush small bg-transparent">';
        items.forEach(item => {
            listHtml += `<li class="list-group-item bg-transparent px-0"><i class="far fa-square me-2"></i>${item}</li>`;
        });
        listHtml += '</ul>';

        $('#assistant-checklist-body').html(listHtml);
    },

    // Init with welcome
    init: () => {
        MetrageAssistant.clear();
        MetrageAssistant.say("Bonjour ! Je suis votre assistant métrage. Sélectionnez un ouvrage pour commencer.", "info", "fas fa-robot");
    },
    // --- VISUAL ENCYCLOPEDIA ---
    updateVisualGuide: (inputId) => {
        if (typeof METRAGE_GUIDES === 'undefined') return;

        // Find guide using partial match on input ID or name
        // Our seeds utilize "keyword" logic often
        const guide = METRAGE_GUIDES.find(g => inputId.includes(g.trigger_input_id));

        if (guide) {
            // Build Sidebar Card
            let html = `
                <div class="card mb-3 border-info shadow-sm animate__animated animate__fadeIn">
                    <img src="assets/img/guides/${guide.image_filename}" class="card-img-top p-2" alt="${guide.titre}" onerror="this.src='assets/img/guide_placeholder.png'">
                    <div class="card-body bg-info-subtle text-info-emphasis p-3">
                        <h6 class="card-title fw-bold"><i class="fas fa-info-circle me-2"></i>${guide.titre}</h6>
                        <p class="card-text small mb-0">${guide.texte_conseil}</p>
                    </div>
                </div>
            `;
            // Prepend to Assistant Stream (or dedicated zone)
            $('#assistant-stream').prepend(html);
        }
    }
};

// Global Listeners for Visual Guide
$(document).on('focus click', 'input, select, textarea', function () {
    let id = $(this).attr('id');
    let name = $(this).attr('name');
    // Try ID first, then part of name
    if (id) MetrageAssistant.updateVisualGuide(id);
    else if (name) MetrageAssistant.updateVisualGuide(name);
});

$(document).ready(() => {
    MetrageAssistant.init();
});
