/**
 * tasks.js - Logic for To Do List
 * EXTRACTED FROM tasks.php for robustness and caching fixes.
 */

// ERROR HANDLER (Optional, can be removed if specific debugging not needed)
// window.onerror = function(msg, url, line, col, error) { console.error(msg, line); };

// --- LOGIC FUNCTIONS ---

// Open Modal (Add Mode)
function openAddTaskModal() {
    let modalEl = document.getElementById('addTaskModal');
    if (!modalEl) { alert('Erreur: Modal non trouvée.'); return; }

    // Reset Form
    document.querySelector('#addTaskModal .modal-title').textContent = 'Nouvelle Tâche';
    document.querySelector('#addTaskModal input[name="action"]').value = 'add_task';
    document.querySelector('#addTaskModal input[name="title"]').value = '';
    document.querySelector('#addTaskModal textarea[name="description"]').value = '';
    document.querySelector('#addTaskModal select[name="importance"]').value = 'normal';
    document.querySelector('#addTaskModal input[name="task_date"]').value = new Date().toISOString().split('T')[0];

    // Reset Context Fields (If they exist)
    let ctxNone = document.getElementById('ctx_none');
    if (ctxNone) {
        ctxNone.checked = true;
        if (window.$) {
            $('#select_affaire').val(null).trigger('change');
            $('#select_commande').val(null).trigger('change');
        }
    }
    toggleContextFields();

    // Reset ID hidden field
    let idField = document.querySelector('#addTaskModal input[name="task_id"]');
    if (idField) idField.value = '';

    new bootstrap.Modal(modalEl).show();
}

// Open Modal (Edit Mode)
// Open Modal (Edit Mode)
function editTask(event, taskId) {
    event.stopPropagation(); // Stop click from opening details

    if (!window.tasksStore || !window.tasksStore[taskId]) {
        console.error("Task not found in store: " + taskId);
        return;
    }

    let task = window.tasksStore[taskId];

    document.querySelector('#addTaskModal .modal-title').textContent = 'Modifier Tâche';
    document.querySelector('#addTaskModal input[name="action"]').value = 'edit_task';
    document.querySelector('#addTaskModal input[name="title"]').value = task.title;
    document.querySelector('#addTaskModal textarea[name="description"]').value = task.description;
    document.querySelector('#addTaskModal select[name="importance"]').value = task.importance;

    // Date
    let dateVal = task.created_at ? task.created_at.split(' ')[0] : '';
    document.querySelector('#addTaskModal input[name="task_date"]').value = dateVal;

    // Context Logic (Robust)
    let ctxAffaire = document.getElementById('ctx_affaire');
    let ctxCommande = document.getElementById('ctx_commande');
    let ctxNone = document.getElementById('ctx_none');

    if (ctxAffaire && ctxCommande && ctxNone) {
        if (task.affaire_id) {
            ctxAffaire.checked = true;
            if (window.$) $('#select_affaire').val(task.affaire_id).trigger('change');
        } else if (task.commande_id) {
            ctxCommande.checked = true;
            if (window.$) $('#select_commande').val(task.commande_id).trigger('change');
        } else {
            ctxNone.checked = true;
        }
    }
    toggleContextFields();

    // Add Hidden ID
    let form = document.querySelector('#addTaskModal form');
    let idField = form.querySelector('input[name="task_id"]');
    if (!idField) {
        idField = document.createElement('input');
        idField.type = 'hidden';
        idField.name = 'task_id';
        form.appendChild(idField);
    }
    idField.value = task.id;

    new bootstrap.Modal(document.getElementById('addTaskModal')).show();
}

// Show Details
function showDetail(element, taskId) {
    if (!window.tasksStore || !window.tasksStore[taskId]) return;
    let task = window.tasksStore[taskId];
    let subtasks = task.subtasks || [];

    // Highlight Row
    document.querySelectorAll('.task-row').forEach(el => {
        el.className = 'd-flex justify-content-between align-items-center p-3 border-bottom task-row bg-body';
        el.style.borderLeft = 'none';
        el.style.cursor = 'pointer';
    });
    element.className = 'd-flex justify-content-between align-items-center p-3 border-bottom task-row bg-primary-subtle';
    element.style.borderLeft = '4px solid #0d6efd';

    // Determine Logic Container
    let donePane = document.getElementById('done-pane');
    let targetPane = (donePane && donePane.classList.contains('active')) ? 'detail-panel-done' : 'detail-panel';
    let container = document.getElementById(targetPane) || document.getElementById('detail-panel');

    if (!container) return; // safety

    // Build Detail HTML
    // Escape for HTML display but keep line breaks visual
    let descText = task.description || '';
    let descHtml = descText ? descText.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\n/g, "<br>") : '<i class="text-muted">Aucune description (Double-cliquez pour éditer)</i>';

    let html = `
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h4 class="fw-bold text-dark mb-0">${task.title}</h4>
            <span class="badge bg-${task.importance == 'high' ? 'danger' : 'secondary'}">${task.importance == 'high' ? 'URGENT' : 'Normal'}</span>
        </div>
        
        <!-- Context Link (New) -->
        ${task.affaire_id ? `<div class="mb-3"><a href="affaires_detail.php?id=${task.affaire_id}" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none px-2 py-1"><i class="fas fa-briefcase me-1"></i>${task.nom_affaire}</a></div>` : ''}
        ${task.commande_id ? `<div class="mb-3"><a href="commandes_detail.php?id=${task.commande_id}" class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none px-2 py-1"><i class="fas fa-shopping-cart me-1"></i>${task.ref_interne}</a></div>` : ''}
        
        <!-- Post-it Description HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Description / Notes</small>
            <button class="btn btn-sm btn-light border py-0 px-2" onclick="enableDescEdit(${task.id})" title="Éditer">
                <i class="fas fa-pencil-alt text-primary small"></i> Modifier
            </button>
        </div>

        <div id="desc-container-${task.id}" 
             class="p-3 rounded border mb-4 bg-info-subtle text-dark"
             ondblclick="enableDescEdit(${task.id})"
             title="Double-cliquez pour modifier"
             style="cursor: text; min-height: 80px; white-space: pre-wrap;">${descHtml}</div>
        
        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-tasks me-2"></i>Sous-tâches</h6>
        
        <ul class="list-group mb-3 border-0 bg-transparent" id="subtasks-list-${task.id}">
    `;

    if (subtasks.length === 0) {
        html += '<li class="list-group-item bg-transparent text-muted small border-0">Aucune sous-tâche.</li>';
    } else {
        subtasks.forEach(item => {
            let checked = item.is_completed == 1 ? 'checked' : '';
            let style = item.is_completed == 1 ? 'text-decoration: line-through; opacity: 0.5;' : '';
            let safeContent = item.content.replace(/"/g, "&quot;");

            html += `
                <li class="list-group-item bg-transparent border-0 border-bottom d-flex align-items-center gap-2" id="li-sub-${item.id}">
                    <input type="checkbox" class="form-check-input mt-0" ${checked} 
                           onchange="toggleSubtask(this, ${item.id})" style="cursor: pointer;">
                    
                    <span style="${style}; cursor: text;" class="flex-grow-1 p-1 rounded hover-bg-light" 
                          id="subtext-${item.id}"
                          title="Double-clic pour éditer"
                          ondblclick="enableSubtaskEdit(${item.id})">${safeContent}</span>
                    
                    <a href="#" onclick="deleteSubtask(${item.id}, this)" class="text-danger small ms-2"><i class="fas fa-trash"></i></a>
                </li>
            `;
        });
    }

    html += `</ul>
        
        <div class="input-group">
            <input type="text" id="new-sub-${task.id}" class="form-control" placeholder="Nouvelle étape..." onkeypress="handleEnterSub(${task.id}, event)">
            <button class="btn btn-primary" onclick="addSubtask(${task.id})"><i class="fas fa-plus"></i></button>
        </div>
    `;

    container.innerHTML = html;
}

// Helper for generic enter key
function handleEnterSub(taskId, e) {
    if (e.key === 'Enter') addSubtask(taskId);
}

// INLINE EDIT: DESCRIPTION
function enableDescEdit(taskId) {
    let container = document.getElementById(`desc-container-${taskId}`);
    if (!container) return;

    // Check if already editing (textarea exists)
    if (container.querySelector('textarea')) return;

    let task = window.tasksStore[taskId];
    let currentText = task.description || '';

    // Switch to Textarea
    let h = Math.max(container.clientHeight, 80);
    container.innerHTML = ''; // Clear

    let textarea = document.createElement('textarea');
    textarea.className = 'form-control';
    textarea.style.minHeight = h + 'px';
    textarea.value = currentText;
    textarea.id = `desc-edit-${taskId}`;

    // Bind Events
    textarea.onblur = function () { saveDescEdit(taskId); };

    container.appendChild(textarea);
    textarea.focus();
}

function saveDescEdit(taskId) {
    let textarea = document.getElementById(`desc-edit-${taskId}`);
    if (!textarea) return; // Should not happen

    let newText = textarea.value;
    let container = document.getElementById(`desc-container-${taskId}`);

    // Optimistic Update
    if (window.tasksStore[taskId]) {
        window.tasksStore[taskId].description = newText;
    }

    // HTML Display Logic
    let display = newText ? newText.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\n/g, "<br>") : '<i class="text-muted">Aucune description (Double-cliquez pour éditer)</i>';
    container.innerHTML = display;

    // AJAX
    let formData = new FormData();
    formData.append('ajax_action', 'update_task_desc');
    formData.append('csrf_token', window.CSRF_TOKEN);
    formData.append('task_id', taskId);
    formData.append('description', newText);
    fetch('tasks.php', { method: 'POST', body: formData });
}

// INLINE EDIT: SUBTASKS
function enableSubtaskEdit(subId) {
    let span = document.getElementById(`subtext-${subId}`);
    if (!span) return;

    // Check if already editing
    if (span.tagName === 'INPUT') return;

    let currentText = span.innerText;

    // Create Input
    let input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control form-control-sm';
    input.value = currentText;
    input.id = `subtext-${subId}`; // Keep same ID so label matching works?

    // Bind Events
    input.onblur = function () { saveSubtaskEditInternal(subId, input); };
    input.onkeypress = function (e) { if (e.key === 'Enter') input.blur(); };

    span.replaceWith(input);
    input.focus();
}

function saveSubtaskEditInternal(subId, inputElement) {
    let newText = inputElement.value;

    // Create new Span
    let span = document.createElement('span');
    span.className = 'flex-grow-1 p-1 rounded hover-bg-light';
    span.style.cursor = 'text';
    span.id = `subtext-${subId}`;
    span.innerText = newText;
    span.setAttribute('ondblclick', `enableSubtaskEdit(${subId})`);
    span.title = "Double-clic pour éditer";

    // Restore logic for completed style if needed
    let checkbox = document.querySelector(`#li-sub-${subId} input[type="checkbox"]`);
    if (checkbox && checkbox.checked) {
        span.style.textDecoration = 'line-through';
        span.style.opacity = '0.5';
    }

    inputElement.replaceWith(span);

    // AJAX
    let formData = new FormData();
    formData.append('ajax_action', 'edit_sub');
    formData.append('csrf_token', window.CSRF_TOKEN);
    formData.append('sub_id', subId);
    formData.append('content', newText);
    fetch('tasks.php', { method: 'POST', body: formData });

    // Store Update
    for (const [tid, task] of Object.entries(window.tasksStore)) {
        let sub = task.subtasks.find(s => s.id == subId);
        if (sub) { sub.content = newText; break; }
    }
}

// HELPER: Update Badge (Unchecked / Total)
function updateTaskBadge(taskId) {
    if (!window.tasksStore || !window.tasksStore[taskId]) return;

    let task = window.tasksStore[taskId];
    let total = task.subtasks.length;
    let completed = task.subtasks.filter(s => s.is_completed == 1).length;
    let unchecked = total - completed;

    let badge = document.getElementById('badge-count-' + taskId);
    if (badge) {
        if (total === 0) {
            badge.style.display = 'none';
        } else {
            badge.style.display = 'inline-block';
            badge.innerHTML = `<i class="fas fa-list-ul me-1"></i> ${unchecked}/${total}`;
        }
    }
}

// AJAX SUBTASK TOGGLE
function toggleSubtask(checkbox, subtaskId) {
    let span = checkbox.nextElementSibling;
    let isCompleted = checkbox.checked;

    if (isCompleted) {
        span.style.textDecoration = 'line-through';
        span.style.opacity = '0.5';
    } else {
        span.style.textDecoration = 'none';
        span.style.opacity = '1';
    }

    let formData = new FormData();
    formData.append('ajax_action', 'toggle_sub');
    formData.append('csrf_token', window.CSRF_TOKEN);
    formData.append('sub_id', subtaskId);
    fetch('tasks.php', { method: 'POST', body: formData });

    // Update LOCAL STORE
    for (const [tid, task] of Object.entries(window.tasksStore)) {
        let sub = task.subtasks.find(s => s.id == subtaskId);
        if (sub) {
            sub.is_completed = isCompleted ? 1 : 0;
            break;
        }
    }
    updateTaskBadge(taskId);
}

function addSubtask(taskId) {
    const input = document.getElementById(`new-sub-${taskId}`);
    const content = input.value;

    if (content) {
        let formData = new FormData();
        formData.append('ajax_action', 'add_sub');
        formData.append('csrf_token', window.CSRF_TOKEN);
        formData.append('task_id', taskId);
        formData.append('content', content);

        fetch('tasks.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Append Element Dynamically
                    let ul = document.getElementById(`subtasks-list-${taskId}`);

                    if (ul.innerHTML.includes('Aucune sous-tâche')) ul.innerHTML = '';

                    let li = document.createElement('li');
                    li.className = 'list-group-item bg-transparent border-0 border-bottom d-flex align-items-center gap-2';
                    li.id = `li-sub-${data.id}`;
                    li.innerHTML = `
                    <input type="checkbox" class="form-check-input mt-0" onchange="toggleSubtask(this, ${data.id})" style="cursor: pointer;">
                    <span class="flex-grow-1" id="subtext-${data.id}" style="cursor: pointer;" ondblclick="enableSubtaskEdit(${data.id}, this)">${content}</span>
                    <a href="#" onclick="deleteSubtask(${data.id}, this)" class="text-danger small"><i class="fas fa-trash"></i></a>
                `;

                    ul.appendChild(li);

                    input.value = '';

                    // Update Badge Counter
                    // Update Local Store FIRST so badge calc is correct
                    if (window.tasksStore[taskId]) {
                        window.tasksStore[taskId].subtasks.push({
                            id: data.id,
                            task_id: taskId,
                            content: content,
                            is_completed: 0
                        });
                    }
                    updateTaskBadge(taskId);



                } else {
                    alert('Erreur: ' + (data.error || 'Inconnue'));
                }
            })
            .catch(err => {
                console.error(err);
                alert("Erreur de communication serveur.");
            });
    }
}

function deleteSubtask(subId, element) {
    if (!confirm('Supprimer cette étape ?')) return;

    // Find Task ID from parent context
    let ul = element.closest('ul');
    let taskId = ul.id.replace('subtasks-list-', '');

    let formData = new FormData();
    formData.append('ajax_action', 'del_sub');
    formData.append('csrf_token', window.CSRF_TOKEN);
    formData.append('sub_id', subId);

    fetch('tasks.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                element.closest('li').remove();

                // Update Local Store FIRST
                if (window.tasksStore[taskId]) {
                    window.tasksStore[taskId].subtasks = window.tasksStore[taskId].subtasks.filter(s => s.id != subId);
                }
                updateTaskBadge(taskId);
            }
        });
}


// --- EVENT DELEGATION & READY ---

// EXPOSE GLOBALS (Required for inline HTML onclick)
window.openAddTaskModal = openAddTaskModal;
window.editTask = editTask;
window.showDetail = showDetail;
window.enableDescEdit = enableDescEdit;
window.saveDescEdit = saveDescEdit;
window.enableSubtaskEdit = enableSubtaskEdit;
window.saveSubtaskEditInternal = saveSubtaskEditInternal;
window.toggleSubtask = toggleSubtask;
window.addSubtask = addSubtask;
window.deleteSubtask = deleteSubtask;
window.handleEnterSub = handleEnterSub;

// Double Click Delegation
document.addEventListener('dblclick', function (e) {
    if (!window.tasksStore) return;

    // 1. Description Edit
    let descContainer = e.target.closest('[id^="desc-container-"]');
    if (descContainer && !descContainer.querySelector('textarea')) {
        let taskId = descContainer.id.replace('desc-container-', '');
        if (typeof enableDescEdit === 'function') enableDescEdit(taskId);
        return;
    }

    // 2. Subtask Edit
    let subSpan = e.target.closest('[id^="subtext-"]');
    if (subSpan && subSpan.tagName === 'SPAN') {
        let subId = subSpan.id.replace('subtext-', '');
        if (typeof enableSubtaskEdit === 'function') enableSubtaskEdit(subId);
        return;
    }
});

// Document Ready (Filter Init & Modal Fix)
$(document).ready(function () {
    // Fix Modal Stacking
    if ($('#addTaskModal').length > 0) {
        $('#addTaskModal').appendTo("body");
    }

    // Init Select2
    if ($.fn.select2) {
        $('#filter-chantier, #filter-chantier-done').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: function () {
                return $(this).data('placeholder');
            },
            allowClear: true,
            dropdownAutoWidth: true
        });

        // Hook Select2 change
        $('#filter-chantier, #filter-chantier-done').on('select2:select select2:clear', function (e) {
            if (this.id.includes('done')) applyDoneFilters();
            else applyFilters();
        });
    }

    // console.log("Tasks Manager Ready (External)."); 

    // Init Select2 for Commande
    if ($.fn.select2) {
        $('#select_affaire').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Sélectionner Chantier',
            allowClear: true
        });
        $('#select_commande').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Sélectionner Commande',
            allowClear: true
        });
    }
});


// CONTEXT SWITCHING LOGIC
// CONTEXT SWITCHING LOGIC
function toggleContextFields() {
    // Check for Radio Checked
    let checkedRadio = document.querySelector('input[name="context_type"]:checked');
    // Check for Hidden Input (fallback for Affairs Detail)
    let hiddenInput = document.querySelector('input[type="hidden"][name="context_type"]');

    let type = 'none';
    if (checkedRadio) type = checkedRadio.value;
    else if (hiddenInput) type = hiddenInput.value;

    // Hide All First
    let wrapAff = document.getElementById('wrapper_affaire');
    let wrapCmd = document.getElementById('wrapper_commande');

    if (wrapAff) wrapAff.style.display = 'none';
    if (wrapCmd) wrapCmd.style.display = 'none';

    // Show Relevant
    if (type === 'affaire' && wrapAff) {
        wrapAff.style.display = 'block';
    } else if (type === 'commande' && wrapCmd) {
        wrapCmd.style.display = 'block';
    }
}
// Expose
window.toggleContextFields = toggleContextFields;

/* RESET MODAL LOGIC (ADD)
let originalOpenAddTaskModal = window.openAddTaskModal;
window.openAddTaskModal = function () {
    originalOpenAddTaskModal();
    // Default to None
    document.getElementById('ctx_none').checked = true;
    $('#select_affaire').val(null).trigger('change');
    $('#select_commande').val(null).trigger('change');
    toggleContextFields();
};

// PRE-FILL MODAL LOGIC (EDIT)
// REMOVED BROKEN OVERRIDE
// let originalEditTask = window.editTask;
// window.editTask = function (event, taskId) {
event.stopPropagation();

let task = window.tasksStore[taskId];
if (!task) return;

    // Call standard fill (title, desc...)
    // Instead of calling original which might conflict or be incomplete, let's reimplement or wrap carefully.
    // Actually, original uses textContent which is fine. We just need to set the specific context fields AFTER.

    // We'll duplicate the fill logic for safety/clarity or just let original run and then override?
    // Original is defined inside this file earlier. Let's just USE the original function logic by overwriting it completely at line 34?
    // NO, replace_file_content replaces lines. I can just REWRITE the `editTask` function in place.
    // BUT I am using appended code here. 

    // Better strategy: I will REPLACE the `openAddTaskModal` and `editTask` functions in their original location in the next tool call.
    // For this tool call, I will ONLY add the helper `toggleContextFields` and the Select2 init at the bottom.
}; */


// Filters Logic
function applyFilters() {
    applyFiltersGeneric('todo-pane', 'filter-priorite', 'filter-chantier');
}
function applyDoneFilters() {
    applyFiltersGeneric('done-pane', 'filter-priorite-done', 'filter-chantier-done');
}
function applyFiltersGeneric(containerId, prioId, chantierId) {
    let chantier = $('#' + chantierId).val() || '';
    chantier = chantier.toLowerCase();

    let prioriteInput = document.getElementById(prioId);
    let priorite = prioriteInput ? prioriteInput.value.toLowerCase() : '';

    let rows = document.querySelectorAll('#' + containerId + ' .task-row');

    rows.forEach(row => {
        let show = true;
        if (chantier) {
            let chantierDiv = row.children[2];
            let txt = chantierDiv.innerText.toLowerCase();
            if (chantier === 'none') {
                if (chantierDiv.innerText.trim() !== '-' && chantierDiv.innerText.trim() !== '') show = false;
            } else {
                if (!txt.includes(chantier)) show = false;
            }
        }
        if (priorite && show) {
            let prioDiv = row.children[1];
            if (!prioDiv.innerText.toLowerCase().includes(priorite)) show = false;
        }
        row.style.display = show ? 'flex' : 'none';
        if (show) row.classList.add('d-flex');
        else row.classList.remove('d-flex');
    });
}
window.applyFilters = applyFilters;
window.applyDoneFilters = applyDoneFilters;

