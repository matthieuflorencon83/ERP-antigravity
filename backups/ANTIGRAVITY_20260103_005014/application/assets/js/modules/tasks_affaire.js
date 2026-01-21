/**
 * Module Tâches pour Fiche Affaire
 * Gère l'ajout/suppression/modification de sous-tâches sans rechargement
 */

const TasksAffaire = {
    csrfToken: '',

    init: function (token) {
        this.csrfToken = token;
        console.log("TasksAffaire initialized");
    },

    // --- SUBTASKS ---

    addSubtask: function (taskId) {
        const input = document.getElementById(`new-sub-${taskId}`);
        const content = input.value.trim();

        if (!content) return;

        const formData = new FormData();
        formData.append('ajax_action', 'add_sub');
        formData.append('csrf_token', this.csrfToken);
        formData.append('task_id', taskId);
        formData.append('content', content);

        fetch('tasks.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.appendSubtaskRow(taskId, data.id, content);
                    input.value = '';
                } else {
                    alert('Erreur: ' + (data.error || 'Inconnue'));
                }
            })
            .catch(err => console.error(err));
    },

    appendSubtaskRow: function (taskId, subId, content) {
        const ul = document.getElementById(`subtasks-list-${taskId}`);
        if (!ul) return;

        // Remove "Empty" message if exists
        const emptyMsg = ul.querySelector('.empty-subtask-msg');
        if (emptyMsg) emptyMsg.remove();

        const li = document.createElement('li');
        li.className = 'list-group-item bg-transparent border-0 d-flex align-items-center py-1 ps-0';
        li.id = `sub-row-${subId}`;
        li.innerHTML = `
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" onchange="TasksAffaire.toggleSubtask(${subId})" id="chk-sub-${subId}">
                <label class="form-check-label w-100" for="chk-sub-${subId}" id="lbl-sub-${subId}" style="cursor: pointer;">
                    ${this.escapeHtml(content)}
                </label>
            </div>
            <button class="btn btn-link text-danger p-0 ms-auto opacity-50 hover-opacity-100" onclick="TasksAffaire.deleteSubtask(${subId})">
                <i class="fas fa-times"></i>
            </button>
        `;
        ul.appendChild(li);
    },

    toggleSubtask: function (subId) {
        const chk = document.getElementById(`chk-sub-${subId}`);
        const lbl = document.getElementById(`lbl-sub-${subId}`);

        if (chk.checked) {
            lbl.classList.add('text-decoration-line-through', 'text-muted');
        } else {
            lbl.classList.remove('text-decoration-line-through', 'text-muted');
        }

        const formData = new FormData();
        formData.append('ajax_action', 'toggle_sub');
        formData.append('csrf_token', this.csrfToken);
        formData.append('sub_id', subId);

        fetch('tasks.php', { method: 'POST', body: formData });
    },

    deleteSubtask: function (subId) {
        if (!confirm('Supprimer cette sous-tâche ?')) return;

        const formData = new FormData();
        formData.append('ajax_action', 'del_sub');
        formData.append('csrf_token', this.csrfToken);
        formData.append('sub_id', subId);

        fetch('tasks.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById(`sub-row-${subId}`);
                    if (row) row.remove();
                }
            });
    },

    handleEnter: function (event, taskId) {
        if (event.key === 'Enter') {
            this.addSubtask(taskId);
        }
    },

    // --- UTILS ---
    escapeHtml: function (text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
};
