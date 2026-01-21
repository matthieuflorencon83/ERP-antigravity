<?php
// Recreate modal - insert before closing </style> tag
$file = 'tasks.php';
$content = file_get_contents($file);

// Find the last </style> tag before </body>
$insert_marker = '</style>';
$last_style_pos = strrpos($content, $insert_marker);

if ($last_style_pos === false) {
    die("ERROR: Could not find </style> tag\n");
}

// Calculate insertion point (after the </style> tag)
$insert_pos = $last_style_pos + strlen($insert_marker);

// Modal HTML
$modal_html = <<<'HTML'


<!-- MODAL ADD/EDIT TASK -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Nouvelle Tâche</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="tasks.php">
                <input type="hidden" name="action" value="add_task" id="form-action">
                <input type="hidden" name="task_id" id="form-task-id">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="form-title" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="form-description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Priorité</label>
                            <select name="importance" id="form-importance" class="form-select">
                                <option value="low">Basse</option>
                                <option value="normal" selected>Normale</option>
                                <option value="high">Urgente</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Échéance</label>
                            <input type="date" name="due_date" id="form-due-date" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Affaire</label>
                        <select name="affaire_id" id="select-affaire" class="form-select">
                            <option value="">-- Aucune --</option>
                            <?php foreach ($affaires_list as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nom_affaire']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Commande</label>
                        <select name="commande_id" id="select-commande" class="form-select">
                            <option value="">-- Aucune --</option>
                            <?php foreach ($commandes_list as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['ref_interne']) ?> - <?= htmlspecialchars($c['fournisseur_nom'] ?? 'N/A') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Open modal for adding new task
function openAddTaskModal() {
    document.getElementById('modalTitle').textContent = 'Nouvelle Tâche';
    document.getElementById('form-action').value = 'add_task';
    document.getElementById('form-task-id').value = '';
    document.getElementById('form-title').value = '';
    document.getElementById('form-description').value = '';
    document.getElementById('form-importance').value = 'normal';
    document.getElementById('form-due-date').value = '';
    document.getElementById('select-affaire').value = '';
    document.getElementById('select-commande').value = '';
    
    new bootstrap.Modal(document.getElementById('addTaskModal')).show();
}

// Open modal for editing existing task
function editTask(task) {
    document.getElementById('modalTitle').textContent = 'Modifier la Tâche';
    document.getElementById('form-action').value = 'edit_task';
    document.getElementById('form-task-id').value = task.id;
    document.getElementById('form-title').value = task.title || '';
    document.getElementById('form-description').value = task.description || '';
    document.getElementById('form-importance').value = task.importance || 'normal';
    document.getElementById('form-due-date').value = task.due_date || '';
    document.getElementById('select-affaire').value = task.affaire_id || '';
    document.getElementById('select-commande').value = task.commande_id || '';
    
    new bootstrap.Modal(document.getElementById('addTaskModal')).show();
}
</script>

HTML;

// Insert the modal
$before = substr($content, 0, $insert_pos);
$after = substr($content, $insert_pos);
$new_content = $before . $modal_html . $after;

file_put_contents($file, $new_content);
echo "Modal recreated successfully!\n";
?>
