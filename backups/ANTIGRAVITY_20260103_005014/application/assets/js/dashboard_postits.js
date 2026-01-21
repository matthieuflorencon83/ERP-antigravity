document.addEventListener('DOMContentLoaded', function () {
    // Focus handling for modal
    const modalEl = document.getElementById('modalAddPostit');
    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', function () {
            const textarea = document.getElementById('new-postit-content');
            if (textarea) {
                textarea.focus();
            }
        });
    }

    loadPostits();

    // DRAG & RESIZE STATE
    let activePostit = null;
    let isDragging = false;
    let isResizing = false;
    let startX, startY, startWidth, startHeight, startTop, startLeft;
    let maxZIndex = 10; // Base z-index

    function loadPostits() {
        fetch('api/postits.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('postits-wrapper');
                    container.innerHTML = '';
                    if (window.innerWidth > 768) {
                        container.style.position = 'absolute';
                        container.style.top = '0';
                        container.style.left = '0';
                        container.style.right = '0';
                        container.style.bottom = '0';
                    }

                    data.postits.forEach(postit => {
                        const div = document.createElement('div');
                        div.className = `postit-card postit-${postit.color}`;

                        const x = postit.x_pos || 20;
                        const y = postit.y_pos || 20;
                        const w = postit.width || 220;
                        const h = postit.height || 220;
                        const z = postit.z_index || 1;

                        // Update global maxZ
                        if (z > maxZIndex) maxZIndex = z;

                        if (window.innerWidth > 768) {
                            div.style.left = x + 'px';
                            div.style.top = y + 'px';
                            div.style.width = w + 'px';
                            div.style.height = h + 'px';
                            div.style.zIndex = z;
                        }

                        div.setAttribute('data-id', postit.id);

                        div.innerHTML = `
                            <div class="postit-header-drag" onmousedown="startDrag(event, this.parentElement)"></div>
                            <div class="postit-delete" onclick="deletePostit(${postit.id})">&times;</div>
                            <div class="postit-content" ondblclick="enablePostitEdit(${postit.id}, this)" title="Double-clic pour modifier">${postit.content.replace(/\n/g, '<br>')}</div>
                            <div class="postit-resize-handle" onmousedown="startResize(event, this.parentElement)"></div>
                        `;
                        container.appendChild(div);
                    });
                }
            });
    }

    // --- INLINE EDIT LOGIC ---
    window.enablePostitEdit = function (id, element) {
        if (element.querySelector('textarea')) return; // Already editing

        const currentText = element.innerText; // Get raw text
        const h = element.clientHeight;

        element.innerHTML = '';

        const textarea = document.createElement('textarea');
        textarea.className = 'form-control border-0 bg-transparent p-0';
        textarea.style.height = h + 'px';
        textarea.style.resize = 'none';
        textarea.style.width = '100%';
        textarea.style.outline = 'none';
        textarea.style.boxShadow = 'none';
        textarea.value = currentText;
        textarea.id = `edit-postit-${id}`;

        textarea.onblur = function () { savePostitContent(id, textarea, element); };
        // textarea.onkeypress = function(e) { if(e.key === 'Enter' && !e.shiftKey) textarea.blur(); }; // Allow multiline

        element.appendChild(textarea);
        textarea.focus();
    };

    window.savePostitContent = function (id, textarea, container) {
        const newContent = textarea.value;
        const color = container.parentElement.className.match(/postit-(\w+)/)[1] || 'jaune'; // Preserve color logic if needed

        // Optimistic Update
        container.innerHTML = newContent.replace(/\n/g, '<br>');

        fetch(`api/postits.php?action=update_content&id=${id}`, {
            method: 'POST', // POST required for body
            body: JSON.stringify({ id: id, content: newContent }),
            headers: { 'Content-Type': 'application/json' }
        }).then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert('Erreur: ' + (data.message || 'Sauvegarde échouée'));
                }
            });
    };

    // --- DRAG LOGIC ---
    window.startDrag = function (e, card) {
        if (window.innerWidth <= 768) return;
        e.preventDefault();

        activePostit = card;
        isDragging = true;

        // Z-Index Management
        maxZIndex++;
        card.style.zIndex = maxZIndex;

        startX = e.clientX;
        startY = e.clientY;
        startLeft = parseInt(card.style.left || 20);
        startTop = parseInt(card.style.top || 20);

        card.classList.add('is-dragging');

        document.addEventListener('mousemove', onDrag);
        document.addEventListener('mouseup', stopDrag);
    };

    function onDrag(e) {
        if (!isDragging || !activePostit) return;

        const dx = e.clientX - startX;
        const dy = e.clientY - startY;

        let newLeft = startLeft + dx;
        let newTop = startTop + dy;

        const container = document.getElementById('postits-wrapper');
        if (container) {
            const w = activePostit.offsetWidth;
            const h = activePostit.offsetHeight;
            const maxLeft = container.offsetWidth - w;
            const maxTop = container.offsetHeight - h;
            newLeft = Math.max(0, Math.min(newLeft, maxLeft));
            newTop = Math.max(0, Math.min(newTop, maxTop));
        }

        activePostit.style.left = newLeft + 'px';
        activePostit.style.top = newTop + 'px';
    }

    function stopDrag() {
        if (isDragging && activePostit) {
            activePostit.classList.remove('is-dragging');
            saveCoords(activePostit);
        }
        isDragging = false;
        activePostit = null;
        document.removeEventListener('mousemove', onDrag);
        document.removeEventListener('mouseup', stopDrag);
    }

    // --- RESIZE LOGIC ---
    window.startResize = function (e, card) {
        if (window.innerWidth <= 768) return;
        e.preventDefault();
        e.stopPropagation();

        activePostit = card;
        isResizing = true;

        // Z-Index Bump on resize too
        maxZIndex++;
        card.style.zIndex = maxZIndex;

        startX = e.clientX;
        startY = e.clientY;
        startWidth = parseInt(card.style.width || 220);
        startHeight = parseInt(card.style.height || 220);

        document.addEventListener('mousemove', onResize);
        document.addEventListener('mouseup', stopResize);
    };

    function onResize(e) {
        if (!isResizing || !activePostit) return;

        const dx = e.clientX - startX;
        const dy = e.clientY - startY;

        const newW = Math.max(100, startWidth + dx);
        const newH = Math.max(100, startHeight + dy);

        activePostit.style.width = newW + 'px';
        activePostit.style.height = newH + 'px';
    }

    function stopResize() {
        if (isResizing && activePostit) {
            saveCoords(activePostit);
        }
        isResizing = false;
        activePostit = null;
        document.removeEventListener('mousemove', onResize);
        document.removeEventListener('mouseup', stopResize);
    }

    // --- UTILS ---
    function saveCoords(card) {
        const id = card.getAttribute('data-id');
        const x = parseInt(card.style.left, 10);
        const y = parseInt(card.style.top, 10);
        const w = parseInt(card.style.width, 10);
        const h = parseInt(card.style.height, 10);
        const z = parseInt(card.style.zIndex, 10) || 1;

        fetch(`api/postits.php?action=update_coords`, {
            method: 'POST',
            body: JSON.stringify({ id, x, y, width: w, height: h, z_index: z }),
            headers: { 'Content-Type': 'application/json' }
        });
    }

    // Keep existing functionalities
    window.savePostit = function () {
        const content = document.getElementById('new-postit-content').value;
        const color = document.querySelector('input[name="postit-color"]:checked').value;

        if (!content.trim()) return;

        fetch('api/postits.php', {
            method: 'POST',
            body: JSON.stringify({ content: content, color: color }),
            headers: { 'Content-Type': 'application/json' }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modalEl = document.getElementById('modalAddPostit');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();
                    document.getElementById('new-postit-content').value = '';
                    loadPostits();
                }
            });
    };

    window.deletePostit = function (id) {
        if (!confirm('Supprimer ce mémo ?')) return;

        fetch(`api/postits.php?action=delete&id=${id}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                if (data.success) loadPostits();
            });
    };
});
