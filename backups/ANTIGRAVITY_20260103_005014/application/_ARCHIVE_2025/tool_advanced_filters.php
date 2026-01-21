<?php
// Replace simple filters with advanced dropdown filters like in the image
$file = 'tasks.php';
$content = file_get_contents($file);

// Remove the simple filters we just added
$search_old = <<<'SEARCH'
    <!-- FILTERS -->
    <div class="row g-2 mb-3 mt-2">
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-muted">Priorité</label>
            <select id="filter-importance" class="form-select form-select-sm">
                <option value="">Toutes</option>
                <option value="high">Urgente</option>
                <option value="normal">Normale</option>
                <option value="low">Basse</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold text-muted">Client / Chantier</label>
            <input type="text" id="filter-chantier" class="form-control form-control-sm" placeholder="Rechercher...">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-sm btn-outline-secondary w-100" onclick="resetFilters()">
                <i class="fas fa-redo me-1"></i>Réinitialiser
            </button>
        </div>
    </div>
SEARCH;

$replace_new = <<<'REPLACE'
    <!-- FILTERS -->
    <div class="row g-2 mb-3 mt-2">
        <div class="col-md-3">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center" type="button" id="filterImportanceBtn" data-bs-toggle="dropdown">
                    <span>Priorité</span>
                </button>
                <div class="dropdown-menu p-2" style="min-width: 200px;">
                    <div class="form-check">
                        <input class="form-check-input filter-importance" type="checkbox" value="high" id="filter-high">
                        <label class="form-check-label" for="filter-high">
                            <span class="badge bg-danger text-white small">URGENTE</span>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input filter-importance" type="checkbox" value="normal" id="filter-normal">
                        <label class="form-check-label" for="filter-normal">Normale</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input filter-importance" type="checkbox" value="low" id="filter-low">
                        <label class="form-check-label" for="filter-low">Basse</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center" type="button" id="filterChantierBtn" data-bs-toggle="dropdown">
                    <span>Client / Chantier</span>
                </button>
                <div class="dropdown-menu p-2" style="min-width: 250px; max-height: 300px; overflow-y: auto;">
                    <input type="text" class="form-control form-control-sm mb-2" id="search-chantier" placeholder="Rechercher...">
                    <div id="chantier-list">
                        <?php 
                        $chantiers = [];
                        foreach ($tasks_todo as $t) {
                            if (!empty($t['nom_affaire'])) {
                                $chantiers[$t['nom_affaire']] = $t['nom_affaire'];
                            }
                            if (!empty($t['ref_interne'])) {
                                $chantiers[$t['ref_interne']] = $t['ref_interne'];
                            }
                        }
                        foreach ($chantiers as $chantier): ?>
                            <div class="form-check chantier-option">
                                <input class="form-check-input filter-chantier" type="checkbox" value="<?= htmlspecialchars($chantier) ?>" id="filter-<?= md5($chantier) ?>">
                                <label class="form-check-label" for="filter-<?= md5($chantier) ?>">
                                    <?= htmlspecialchars($chantier) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <button class="btn btn-sm btn-outline-secondary w-100" onclick="resetFilters()">
                <i class="fas fa-redo me-1"></i>Réinitialiser
            </button>
        </div>
    </div>
REPLACE;

$content = str_replace($search_old, $replace_new, $content);

// Update JavaScript filter functions
$search_js = <<<'SEARCH'
// Filter functions
function resetFilters() {
    document.getElementById('filter-importance').value = '';
    document.getElementById('filter-chantier').value = '';
    applyFilters();
}

function applyFilters() {
    const importanceFilter = document.getElementById('filter-importance').value.toLowerCase();
    const chantierFilter = document.getElementById('filter-chantier').value.toLowerCase();
    const taskItems = document.querySelectorAll('.task-item');
    
    taskItems.forEach(item => {
        let show = true;
        
        // Filter by importance
        if (importanceFilter) {
            const taskData = JSON.parse(item.dataset.taskData || '{}');
            if (taskData.importance !== importanceFilter) {
                show = false;
            }
        }
        
        // Filter by chantier (search in affaire name or ref_interne)
        if (chantierFilter) {
            const taskData = JSON.parse(item.dataset.taskData || '{}');
            const affaireName = (taskData.nom_affaire || '').toLowerCase();
            const refInterne = (taskData.ref_interne || '').toLowerCase();
            if (!affaireName.includes(chantierFilter) && !refInterne.includes(chantierFilter)) {
                show = false;
            }
        }
        
        item.style.display = show ? '' : 'none';
    });
}

// Attach filter listeners
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filter-importance').addEventListener('change', applyFilters);
    document.getElementById('filter-chantier').addEventListener('input', applyFilters);
});
SEARCH;

$replace_js = <<<'REPLACE'
// Filter functions
function resetFilters() {
    document.querySelectorAll('.filter-importance, .filter-chantier').forEach(cb => cb.checked = false);
    applyFilters();
}

function applyFilters() {
    const selectedImportance = Array.from(document.querySelectorAll('.filter-importance:checked')).map(cb => cb.value);
    const selectedChantiers = Array.from(document.querySelectorAll('.filter-chantier:checked')).map(cb => cb.value);
    const taskItems = document.querySelectorAll('.task-item');
    
    taskItems.forEach(item => {
        let show = true;
        const taskData = JSON.parse(item.dataset.taskData || '{}');
        
        // Filter by importance (if any selected)
        if (selectedImportance.length > 0) {
            if (!selectedImportance.includes(taskData.importance)) {
                show = false;
            }
        }
        
        // Filter by chantier (if any selected)
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

// Attach filter listeners
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.filter-importance, .filter-chantier').forEach(cb => {
        cb.addEventListener('change', applyFilters);
    });
    
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
});
REPLACE;

$content = str_replace($search_js, $replace_js, $content);

file_put_contents($file, $content);
echo "Advanced dropdown filters created successfully!\n";
?>
