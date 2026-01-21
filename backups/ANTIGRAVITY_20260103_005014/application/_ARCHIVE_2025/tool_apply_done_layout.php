<?php
// Replace the "Terminées" tab with the same two-panel layout as "En cours"
$file = 'tasks.php';
$content = file_get_contents($file);

// Find and replace the entire "Terminées" tab section
$search = <<<'SEARCH'
                <!-- TAB 2: TERMINÉES -->
                <div class="tab-pane fade" id="done" role="tabpanel" aria-labelledby="done-tab">
                    <?php if (empty($tasks_done)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-inbox fa-4x text-light mb-3"></i>
                            <p class="text-muted fw-medium">Aucune tâche terminée pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($tasks_done as $t): ?>
                                <li class="list-group-item p-3 d-flex align-items-center justify-content-between hover-bg-light transition-base">
                                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                                        <a href="tasks.php?toggle_id=<?= $t['id'] ?>" class="text-decoration-none">
                                            <div class="rounded-circle bg-success border-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                <i class="fas fa-check text-white small"></i>
                                            </div>
                                        </a>
                                        <div class="flex-grow-1">
                                            <div class="text-decoration-line-through text-muted fw-medium"><?= htmlspecialchars($t['title']) ?></div>
                                            <small class="text-muted">Terminé le <?= date('d/m H:i', strtotime($t['created_at'])) ?></small>
                                        </div>
                                    </div>
                                    <a href="tasks.php?delete_id=<?= $t['id'] ?>" class="btn btn-link text-danger p-0" onclick="return confirm('Confirmer la suppression ?');" title="Supprimer">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
SEARCH;

$replace = <<<'REPLACE'
                <!-- TAB 2: TERMINÉES -->
                <div class="tab-pane fade" id="done" role="tabpanel" aria-labelledby="done-tab">
                    <?php if (empty($tasks_done)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-inbox fa-4x text-light mb-3"></i>
                            <p class="text-muted fw-medium">Aucune tâche terminée pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-0">
                            <!-- LEFT PANEL: Task List -->
                            <div class="col-md-7 border-end">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($tasks_done as $t): ?>
                                        <li class="list-group-item p-2 hover-bg-light transition-base task-item-done" 
                                            data-task-id="<?= $t['id'] ?>" 
                                            data-subtasks='<?= json_encode($t['subtasks']) ?>'
                                            data-task-data='<?= json_encode(['importance' => $t['importance'], 'nom_affaire' => $t['nom_affaire'], 'ref_interne' => $t['ref_interne']]) ?>'
                                            style="cursor: pointer;">
                                            <div class="d-flex align-items-center gap-2">
                                                <!-- Checkbox -->
                                                <a href="tasks.php?toggle_id=<?= $t['id'] ?>" class="text-decoration-none flex-shrink-0" onclick="event.stopPropagation();">
                                                    <div class="rounded-circle bg-success border-3 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <i class="fas fa-check text-white" style="font-size: 0.7rem;"></i>
                                                    </div>
                                                </a>
                                                
                                                <!-- Priority -->
                                                <div class="flex-shrink-0" style="width: 60px;">
                                                    <?php if($t['importance'] == 'high'): ?>
                                                        <span class="badge bg-danger text-white" style="font-size: 0.7rem;">URGENT</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Client/Affaire -->
                                                <div class="flex-shrink-0" style="width: 140px;">
                                                    <?php if($t['nom_affaire']): ?>
                                                        <a href="affaires_detail.php?id=<?= $t['affaire_id'] ?>" class="text-decoration-none text-info fw-medium" style="font-size: 0.85rem;" onclick="event.stopPropagation();">
                                                            <i class="fas fa-folder-open me-1"></i><?= htmlspecialchars($t['nom_affaire']) ?>
                                                        </a>
                                                    <?php elseif($t['ref_interne']): ?>
                                                        <a href="commandes_detail.php?id=<?= $t['commande_id'] ?>" class="text-decoration-none text-primary fw-medium" style="font-size: 0.85rem;" onclick="event.stopPropagation();">
                                                            <i class="fas fa-shopping-cart me-1"></i><?= htmlspecialchars($t['ref_interne']) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Title -->
                                                <div class="flex-shrink-0 text-decoration-line-through text-muted fw-medium" style="width: 150px; font-size: 0.9rem;">
                                                    <?= htmlspecialchars($t['title']) ?>
                                                </div>
                                                
                                                <!-- Description -->
                                                <div class="flex-grow-1 text-secondary text-decoration-line-through" style="font-size: 0.85rem;">
                                                    <?= htmlspecialchars($t['description'] ?? '') ?>
                                                </div>
                                                
                                                <!-- Subtask Count -->
                                                <?php if (!empty($t['subtasks'])): ?>
                                                <div class="flex-shrink-0">
                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.75rem;">
                                                        <i class="fas fa-list-ul me-1"></i><?= count($t['subtasks']) ?>
                                                    </span>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <!-- Actions -->
                                                <div class="actions flex-shrink-0 d-flex align-items-center gap-2">
                                                    <a href="tasks.php?delete_id=<?= $t['id'] ?>" class="btn btn-link text-danger p-0" onclick="event.stopPropagation(); return confirm('Confirmer la suppression ?');" title="Supprimer" style="font-size: 0.9rem;">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <!-- RIGHT PANEL: Subtasks Detail -->
                            <div class="col-md-5 bg-light">
                                <div id="subtasks-panel-done" class="p-3">
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-hand-pointer fa-3x mb-3"></i>
                                        <p class="fw-medium">Cliquez sur une tâche pour voir ses sous-tâches</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
REPLACE;

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Two-panel layout applied to 'Terminées' tab!\n";
?>
