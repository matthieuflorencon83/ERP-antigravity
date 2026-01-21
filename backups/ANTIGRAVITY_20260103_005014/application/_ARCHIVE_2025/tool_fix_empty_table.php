<?php
// Fix: Keep table visible even when empty, and ensure click handlers work
$file = 'tasks.php';
$content = file_get_contents($file);

// 1. Fix: Keep the table structure visible even when empty
$search_empty = <<<'SEARCH'
                    <?php if (empty($tasks_todo)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-clipboard-check fa-4x text-light mb-3"></i>
                            <p class="text-muted fw-medium">Rien à faire ! Profitez-en pour prendre un café. ☕</p>
                        </div>
                    <?php else: ?>
SEARCH;

$replace_empty = <<<'REPLACE'
                    <?php if (empty($tasks_todo)): ?>
                        <div class="row g-0">
                            <div class="col-md-7 border-end">
                                <!-- HEADER ROW WITH FILTERS -->
                                <div class="bg-light border-bottom p-2 sticky-top" style="top: 0; z-index: 10;">
                                    <div class="d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                                        <div class="flex-shrink-0" style="width: 24px;"></div>
                                        <div class="flex-shrink-0" style="width: 60px;">
                                            <span class="fw-semibold" style="font-size: 0.75rem;">Priorité</span>
                                        </div>
                                        <div class="flex-shrink-0" style="width: 140px;">
                                            <span class="fw-semibold" style="font-size: 0.75rem;">Chantier</span>
                                        </div>
                                        <div class="flex-shrink-0 fw-semibold" style="width: 150px; font-size: 0.75rem;">Titre</div>
                                        <div class="flex-grow-1 fw-semibold" style="font-size: 0.75rem;">Description</div>
                                        <div class="flex-shrink-0 fw-semibold text-center" style="width: 60px; font-size: 0.75rem;">Points</div>
                                        <div class="flex-shrink-0 fw-semibold text-center" style="width: 60px; font-size: 0.75rem;">Actions</div>
                                    </div>
                                </div>
                                <div class="text-center p-5">
                                    <i class="fas fa-clipboard-check fa-4x text-light mb-3"></i>
                                    <p class="text-muted fw-medium">Rien à faire ! Profitez-en pour prendre un café. ☕</p>
                                </div>
                            </div>
                            <div class="col-md-5 bg-light">
                                <div id="subtasks-panel" class="p-3">
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-hand-pointer fa-3x mb-3"></i>
                                        <p class="fw-medium">Cliquez sur une tâche pour voir ses sous-tâches</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
REPLACE;

$content = str_replace($search_empty, $replace_empty, $content);

// 2. Same for done tab
$search_empty_done = <<<'SEARCH'
                    <?php if (empty($tasks_done)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-inbox fa-4x text-light mb-3"></i>
                            <p class="text-muted fw-medium">Aucune tâche terminée pour le moment.</p>
                        </div>
                    <?php else: ?>
SEARCH;

$replace_empty_done = <<<'REPLACE'
                    <?php if (empty($tasks_done)): ?>
                        <div class="row g-0">
                            <div class="col-md-7 border-end">
                                <div class="bg-light border-bottom p-2 sticky-top" style="top: 0; z-index: 10;">
                                    <div class="d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                                        <div class="flex-shrink-0" style="width: 24px;"></div>
                                        <div class="flex-shrink-0" style="width: 60px;">
                                            <span class="fw-semibold" style="font-size: 0.75rem;">Priorité</span>
                                        </div>
                                        <div class="flex-shrink-0" style="width: 140px;">
                                            <span class="fw-semibold" style="font-size: 0.75rem;">Chantier</span>
                                        </div>
                                        <div class="flex-shrink-0 fw-semibold" style="width: 150px; font-size: 0.75rem;">Titre</div>
                                        <div class="flex-grow-1 fw-semibold" style="font-size: 0.75rem;">Description</div>
                                        <div class="flex-shrink-0 fw-semibold text-center" style="width: 60px; font-size: 0.75rem;">Points</div>
                                        <div class="flex-shrink-0 fw-semibold text-center" style="width: 60px; font-size: 0.75rem;">Actions</div>
                                    </div>
                                </div>
                                <div class="text-center p-5">
                                    <i class="fas fa-inbox fa-4x text-light mb-3"></i>
                                    <p class="text-muted fw-medium">Aucune tâche terminée pour le moment.</p>
                                </div>
                            </div>
                            <div class="col-md-5 bg-light">
                                <div id="subtasks-panel-done" class="p-3">
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-hand-pointer fa-3x mb-3"></i>
                                        <p class="fw-medium">Cliquez sur une tâche pour voir ses sous-tâches</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
REPLACE;

$content = str_replace($search_empty_done, $replace_empty_done, $content);

file_put_contents($file, $content);
echo "Fixed: Table structure now persists even when empty!\n";
?>
