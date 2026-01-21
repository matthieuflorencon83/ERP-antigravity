<?php
// Apply header row to completed tasks tab
$file = 'tasks.php';
$content = file_get_contents($file);

// Add header row to the "Terminées" tab
$search_done = <<<'SEARCH'
                            <!-- LEFT PANEL: Task List -->
                            <div class="col-md-7 border-end">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($tasks_done as $t): ?>
SEARCH;

$replace_done = <<<'REPLACE'
                            <!-- LEFT PANEL: Task List -->
                            <div class="col-md-7 border-end">
                                <!-- HEADER ROW WITH FILTERS -->
                                <div class="bg-light border-bottom p-2 sticky-top" style="top: 0; z-index: 10;">
                                    <div class="d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                                        <!-- Checkbox column -->
                                        <div class="flex-shrink-0" style="width: 24px;"></div>
                                        
                                        <!-- Priority column with filter -->
                                        <div class="flex-shrink-0" style="width: 60px;">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-link text-dark p-0 dropdown-toggle text-decoration-none fw-semibold" type="button" data-bs-toggle="dropdown" style="font-size: 0.75rem;">
                                                    Priorité
                                                </button>
                                                <div class="dropdown-menu p-2" style="min-width: 150px;">
                                                    <div class="form-check">
                                                        <input class="form-check-input filter-importance-done" type="checkbox" value="high" id="filter-high-done">
                                                        <label class="form-check-label small" for="filter-high-done">
                                                            <span class="badge bg-danger text-white" style="font-size: 0.65rem;">URGENT</span>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input filter-importance-done" type="checkbox" value="normal" id="filter-normal-done">
                                                        <label class="form-check-label small" for="filter-normal-done">Normale</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input filter-importance-done" type="checkbox" value="low" id="filter-low-done">
                                                        <label class="form-check-label small" for="filter-low-done">Basse</label>
                                                    </div>
                                                    <hr class="my-1">
                                                    <button class="btn btn-sm btn-link text-secondary p-0" onclick="resetFiltersDone()">Réinitialiser</button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Chantier column with filter -->
                                        <div class="flex-shrink-0" style="width: 140px;">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-link text-dark p-0 dropdown-toggle text-decoration-none fw-semibold" type="button" data-bs-toggle="dropdown" style="font-size: 0.75rem;">
                                                    Chantier
                                                </button>
                                                <div class="dropdown-menu p-2" style="min-width: 250px; max-height: 300px; overflow-y: auto;">
                                                    <input type="text" class="form-control form-control-sm mb-2" id="search-chantier-done" placeholder="Rechercher...">
                                                    <div id="chantier-list-done">
                                                        <?php 
                                                        $chantiers_done = [];
                                                        foreach ($tasks_done as $t) {
                                                            if (!empty($t['nom_affaire'])) {
                                                                $chantiers_done[$t['nom_affaire']] = $t['nom_affaire'];
                                                            }
                                                            if (!empty($t['ref_interne'])) {
                                                                $chantiers_done[$t['ref_interne']] = $t['ref_interne'];
                                                            }
                                                        }
                                                        foreach ($chantiers_done as $chantier): ?>
                                                            <div class="form-check chantier-option-done">
                                                                <input class="form-check-input filter-chantier-done" type="checkbox" value="<?= htmlspecialchars($chantier) ?>" id="filter-done-<?= md5($chantier) ?>">
                                                                <label class="form-check-label small" for="filter-done-<?= md5($chantier) ?>">
                                                                    <?= htmlspecialchars($chantier) ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <hr class="my-1">
                                                    <button class="btn btn-sm btn-link text-secondary p-0" onclick="resetFiltersDone()">Réinitialiser</button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Title column -->
                                        <div class="flex-shrink-0 fw-semibold" style="width: 150px; font-size: 0.75rem;">
                                            Titre
                                        </div>
                                        
                                        <!-- Description column -->
                                        <div class="flex-grow-1 fw-semibold" style="font-size: 0.75rem;">
                                            Description
                                        </div>
                                        
                                        <!-- Subtasks column -->
                                        <div class="flex-shrink-0 fw-semibold text-center" style="width: 60px; font-size: 0.75rem;">
                                            Points
                                        </div>
                                        
                                        <!-- Actions column -->
                                        <div class="flex-shrink-0 fw-semibold text-center" style="width: 60px; font-size: 0.75rem;">
                                            Actions
                                        </div>
                                    </div>
                                </div>
                                
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($tasks_done as $t): ?>
REPLACE;

$content = str_replace($search_done, $replace_done, $content);

// Add JavaScript functions for done tab filters
$search_js = 'function resetFilters() {
    document.querySelectorAll(\'.filter-importance, .filter-chantier\').forEach(cb => cb.checked = false);
    applyFilters();
}';

$replace_js = <<<'REPLACE'
function resetFilters() {
    document.querySelectorAll('.filter-importance, .filter-chantier').forEach(cb => cb.checked = false);
    applyFilters();
}

function resetFiltersDone() {
    document.querySelectorAll('.filter-importance-done, .filter-chantier-done').forEach(cb => cb.checked = false);
    applyFiltersDone();
}

function applyFiltersDone() {
    const selectedImportance = Array.from(document.querySelectorAll('.filter-importance-done:checked')).map(cb => cb.value);
    const selectedChantiers = Array.from(document.querySelectorAll('.filter-chantier-done:checked')).map(cb => cb.value);
    const taskItems = document.querySelectorAll('.task-item-done');
    
    taskItems.forEach(item => {
        let show = true;
        const taskData = JSON.parse(item.dataset.taskData || '{}');
        
        if (selectedImportance.length > 0) {
            if (!selectedImportance.includes(taskData.importance)) {
                show = false;
            }
        }
        
        if (selectedChantiers.length > 0) {
            const affaireName = taskData.nom_affaire || '';
            const refInterne = taskData.ref_interne || '';
            if (!selectedChantiers.includes(affaireName) && !selectedChantiers.includes(refInterne)) {
                show = false;
            }
        }
        
        item.style.display = show ? '' : 'none';
    });
}
REPLACE;

$content = str_replace($search_js, $replace_js, $content);

// Add event listeners for done tab filters
$search_listeners = '    // Search in chantier dropdown
    const searchInput = document.getElementById(\'search-chantier\');
    if (searchInput) {
        searchInput.addEventListener(\'input\', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll(\'.chantier-option\').forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? \'\' : \'none\';
            });
        });
    }
});';

$replace_listeners = <<<'REPLACE'
    // Search in chantier dropdown
    const searchInput = document.getElementById('search-chantier');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.chantier-option').forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
    
    // Filters for done tab
    document.querySelectorAll('.filter-importance-done, .filter-chantier-done').forEach(cb => {
        cb.addEventListener('change', applyFiltersDone);
    });
    
    const searchInputDone = document.getElementById('search-chantier-done');
    if (searchInputDone) {
        searchInputDone.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.chantier-option-done').forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
REPLACE;

$content = str_replace($search_listeners, $replace_listeners, $content);

file_put_contents($file, $content);
echo "Header row applied to both tabs with filters!\n";
?>
