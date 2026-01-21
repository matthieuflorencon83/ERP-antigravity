<?php
$file = 'c:/laragon/www/antigravity/tasks.php';
$content = file_get_contents($file);

$startMarker = '<div class="row">';
$endMarker = '<!-- MODAL ADD TASK -->';

$startPos = strpos($content, $startMarker);
$endPos = strpos($content, $endMarker);

if ($startPos === false || $endPos === false) {
    die("Markers not found");
}

$before = substr($content, 0, $startPos);
$after = substr($content, $endPos);

$newHtml = <<<'HTML'
    <!-- TABS NAVIGATION -->
    <ul class="nav nav-tabs mb-0 border-bottom-0" id="taskTabs" role="tablist">
        <li class="nav-item me-1" role="presentation">
            <button class="nav-link active fw-bold px-4 pt-3 pb-2 border-bottom-0" id="todo-tab" data-bs-toggle="tab" data-bs-target="#todo" type="button" role="tab" aria-controls="todo" aria-selected="true">
                <i class="fas fa-list-ul me-2"></i>En cours <span class="badge bg-primary-subtle text-primary ms-2 rounded-pill"><?= count($tasks_todo) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4 pt-3 pb-2 text-muted border-bottom-0" id="done-tab" data-bs-toggle="tab" data-bs-target="#done" type="button" role="tab" aria-controls="done" aria-selected="false">
                <i class="fas fa-check-circle me-2"></i>Terminées <span class="badge bg-secondary-subtle text-secondary ms-2 rounded-pill"><?= count($tasks_done) ?></span>
            </button>
        </li>
    </ul>

    <!-- TABS CONTENT -->
    <div class="card shadow-sm border-0 border-top-0 rounded-top-0">
        <div class="card-body p-0">
            <div class="tab-content" id="taskTabsContent">
                
                <!-- TAB 1: EN COURS -->
                <div class="tab-pane fade show active" id="todo" role="tabpanel" aria-labelledby="todo-tab">
                    <?php if (empty($tasks_todo)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-clipboard-check fa-4x text-light mb-3"></i>
                            <p class="text-muted fw-medium">Rien à faire ! Profitez-en pour prendre un café. ☕</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($tasks_todo as $t): ?>
                                <li class="list-group-item p-3 d-flex align-items-center justify-content-between hover-bg-light transition-base">
                                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                                        <!-- Checkbox (LEFT) -->
                                        <a href="tasks.php?toggle_id=<?= $t['id'] ?>" class="text-decoration-none">
                                            <div class="rounded-circle border border-2 border-secondary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 24px; height: 24px;">
                                                <i class="fas fa-check text-white small" style="opacity: 0;"></i>
                                            </div>
                                        </a>
                                        
                                        <div class="flex-grow-1">
                                            <div class="fw-bold text-dark mb-1 d-flex align-items-center gap-2 flex-wrap">
                                                <?= htmlspecialchars($t['title']) ?>
                                                <?php if($t['importance'] == 'high'): ?>
                                                    <span class="badge bg-danger-subtle text-danger small px-2">Urgent</span>
                                                <?php endif; ?>
                                                
                                                <!-- Context Badges -->
                                                <?php if($t['nom_affaire']): ?>
                                                    <a href="affaires_detail.php?id=<?= $t['affaire_id'] ?>" class="badge bg-info-subtle text-info border border-info-subtle text-decoration-none" title="Aller à l'affaire">
                                                        <i class="fas fa-folder-open me-1"></i><?= htmlspecialchars($t['nom_affaire']) ?>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if($t['ref_interne']): ?>
                                                     <a href="commandes_detail.php?id=<?= $t['commande_id'] ?>" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none" title="Aller à la commande">
                                                        <i class="fas fa-shopping-cart me-1"></i><?= htmlspecialchars($t['ref_interne']) ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <small class="text-muted">Créé le <?= date('d/m H:i', strtotime($t['created_at'])) ?></small>
                                                <?php if(!empty($t['due_date'])): ?>
                                                    <?php 
                                                        $d_due = strtotime($t['due_date']);
                                                        $is_late = $d_due < time() && date('Y-m-d') > $t['due_date'];
                                                        $color = $is_late ? 'text-danger fw-bold' : 'text-primary';
                                                    ?>
                                                    <small class="<?= $color ?>"><i class="far fa-clock me-1"></i>Pour le <?= date('d/m/Y', $d_due) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="actions flex-shrink-0 d-flex align-items-center">
                                        <button class="btn btn-link text-muted p-0 ms-2" onclick='editTask(<?= json_encode($t) ?>)' title="Modifier">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <a href="tasks.php?delete_id=<?= $t['id'] ?>" class="btn btn-link text-danger p-0 ms-3" onclick="return confirm('Confirmer la suppression ?');" title="Supprimer">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- TAB 2: TERMINÉES -->
                <div class="tab-pane fade" id="done" role="tabpanel" aria-labelledby="done-tab">
                    <?php if (!empty($tasks_done)): ?>
                        <ul class="list-group list-group-flush bg-transparent">
                            <?php foreach ($tasks_done as $t): ?>
                                <li class="list-group-item bg-transparent p-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3 text-muted flex-grow-1">
                                         <!-- Checkbox (LEFT) - STATIC -->
                                         <a href="tasks.php?toggle_id=<?= $t['id'] ?>" class="text-decoration-none">
                                            <div class="rounded-circle bg-success border border-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 24px; height: 24px;">
                                                <i class="fas fa-check text-white small"></i>
                                            </div>
                                        </a>
                                        
                                        <div class="text-decoration-line-through flex-grow-1">
                                            <?= htmlspecialchars($t['title']) ?>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <?php if($t['nom_affaire']): ?><small class="text-xs"><i class="fas fa-folder me-1"></i><?= $t['nom_affaire'] ?></small><?php endif; ?>
                                                <?php if($t['due_date']): ?><small class="text-xs text-info"><i class="far fa-calendar-alt me-1"></i><?= date('d/m/Y', strtotime($t['due_date'])) ?></small><?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                     
                                     <a href="tasks.php?delete_id=<?= $t['id'] ?>" class="text-muted small hover-danger ms-3 flex-shrink-0" onclick="return confirm('Supprimer ?');">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-center text-muted small p-4 mb-0">L'historique est vide.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
HTML;

$finalContent = $before . $newHtml . "\n</div>\n\n" . $after;
file_put_contents($file, $finalContent);
echo "Modification Applied Successfully.";
?>
