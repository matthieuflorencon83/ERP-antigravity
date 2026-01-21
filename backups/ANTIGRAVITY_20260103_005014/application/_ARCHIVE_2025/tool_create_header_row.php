<?php
// Create header row with integrated filters for task table
$file = 'tasks.php';
$content = file_get_contents($file);

// Remove the old separate filters section
$search_old_filters = <<<'SEARCH'
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
SEARCH;

$replace_old_filters = '';

$content = str_replace($search_old_filters, $replace_old_filters, $content);

// Now add the header row with integrated filters inside the left panel, before the task list
$search_panel = <<<'SEARCH'
                            <!-- LEFT PANEL: Task List -->
                            <div class="col-md-7 border-end">
                                <ul class="list-group list-group-flush">
SEARCH;

$replace_panel = <<<'REPLACE'
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
                                                        <input class="form-check-input filter-importance" type="checkbox" value="high" id="filter-high">
                                                        <label class="form-check-label small" for="filter-high">
                                                            <span class="badge bg-danger text-white" style="font-size: 0.65rem;">URGENT</span>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input filter-importance" type="checkbox" value="normal" id="filter-normal">
                                                        <label class="form-check-label small" for="filter-normal">Normale</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input filter-importance" type="checkbox" value="low" id="filter-low">
                                                        <label class="form-check-label small" for="filter-low">Basse</label>
                                                    </div>
                                                    <hr class="my-1">
                                                    <button class="btn btn-sm btn-link text-secondary p-0" onclick="resetFilters()">Réinitialiser</button>
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
                                                                <label class="form-check-label small" for="filter-<?= md5($chantier) ?>">
                                                                    <?= htmlspecialchars($chantier) ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <hr class="my-1">
                                                    <button class="btn btn-sm btn-link text-secondary p-0" onclick="resetFilters()">Réinitialiser</button>
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
REPLACE;

$content = str_replace($search_panel, $replace_panel, $content);

file_put_contents($file, $content);
echo "Header row with integrated filters created!\n";
?>
