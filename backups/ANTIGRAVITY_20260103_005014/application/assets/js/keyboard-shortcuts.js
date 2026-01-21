/**
 * Keyboard Shortcuts - Raccourcis clavier globaux
 * Améliore la navigation et la productivité
 */

document.addEventListener('keydown', function (e) {
    // Ignorer si l'utilisateur tape dans un input/textarea
    const activeElement = document.activeElement;
    const isTyping = activeElement.tagName === 'INPUT' ||
        activeElement.tagName === 'TEXTAREA' ||
        activeElement.isContentEditable;

    // Navigation rapide (Alt + Lettre)
    if (e.altKey && !isTyping) {
        switch (e.key.toLowerCase()) {
            case 'd':
                e.preventDefault();
                window.location.href = 'dashboard.php';
                break;
            case 'a':
                e.preventDefault();
                window.location.href = 'affaires_liste.php';
                break;
            case 'c':
                e.preventDefault();
                window.location.href = 'commandes_liste.php';
                break;
            case 'p':
                e.preventDefault();
                window.location.href = 'planning.php';
                break;
            case 'e':
                e.preventDefault();
                window.location.href = 'gestion_email.php';
                break;
            case 't':
                e.preventDefault();
                window.location.href = 'tasks.php';
                break;
            case 'b':
                e.preventDefault();
                window.location.href = 'dashboard_bi.php';
                break;
        }
    }

    // Palette de commandes (Ctrl/Cmd + K)
    if ((e.ctrlKey || e.metaKey) && e.key === 'k' && !isTyping) {
        e.preventDefault();
        showCommandPalette();
    }

    // Aide raccourcis (?)
    if (e.key === '?' && !isTyping) {
        e.preventDefault();
        showShortcutsHelp();
    }

    // Fermer modales (Esc)
    if (e.key === 'Escape') {
        closeAllModals();
    }

    // Recherche rapide (Alt + S)
    if (e.altKey && e.key.toLowerCase() === 's') {
        e.preventDefault();
        focusSearch();
    }
});

/**
 * Afficher la palette de commandes
 */
function showCommandPalette() {
    const commands = [
        { icon: '📊', label: 'Dashboard', url: 'dashboard.php', shortcut: 'Alt+D' },
        { icon: '📈', label: 'Analyses BI', url: 'dashboard_bi.php', shortcut: 'Alt+B' },
        { icon: '📁', label: 'Affaires', url: 'affaires_liste.php', shortcut: 'Alt+A' },
        { icon: '🛒', label: 'Commandes', url: 'commandes_liste.php', shortcut: 'Alt+C' },
        { icon: '📅', label: 'Planning', url: 'planning.php', shortcut: 'Alt+P' },
        { icon: '📧', label: 'Email', url: 'gestion_email.php', shortcut: 'Alt+E' },
        { icon: '✅', label: 'Tâches', url: 'tasks.php', shortcut: 'Alt+T' },
        { icon: '👥', label: 'Clients', url: 'clients_liste.php', shortcut: '' },
        { icon: '🏭', label: 'Fournisseurs', url: 'fournisseurs_liste.php', shortcut: '' },
        { icon: '📦', label: 'Stock', url: 'stocks_liste.php', shortcut: '' },
        { icon: '📏', label: 'Métrage', url: 'metrage_cockpit.php', shortcut: '' }
    ];

    let html = '<div class="list-group" style="max-height: 400px; overflow-y: auto;">';
    commands.forEach(cmd => {
        html += `
            <a href="${cmd.url}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span><span style="font-size: 1.2em; margin-right: 10px;">${cmd.icon}</span> ${cmd.label}</span>
                ${cmd.shortcut ? `<kbd class="small">${cmd.shortcut}</kbd>` : ''}
            </a>
        `;
    });
    html += '</div>';

    Swal.fire({
        title: '⚡ Palette de Commandes',
        html: html,
        width: 600,
        showConfirmButton: false,
        showCloseButton: true
    });
}

/**
 * Afficher l'aide des raccourcis
 */
function showShortcutsHelp() {
    Swal.fire({
        title: '⌨️ Raccourcis Clavier',
        html: `
            <div class="text-start">
                <h6 class="fw-bold mb-3">Navigation</h6>
                <table class="table table-sm table-borderless">
                    <tr><td><kbd>Alt + D</kbd></td><td>Dashboard</td></tr>
                    <tr><td><kbd>Alt + B</kbd></td><td>Analyses BI</td></tr>
                    <tr><td><kbd>Alt + A</kbd></td><td>Affaires</td></tr>
                    <tr><td><kbd>Alt + C</kbd></td><td>Commandes</td></tr>
                    <tr><td><kbd>Alt + P</kbd></td><td>Planning</td></tr>
                    <tr><td><kbd>Alt + E</kbd></td><td>Email</td></tr>
                    <tr><td><kbd>Alt + T</kbd></td><td>Tâches</td></tr>
                </table>
                
                <h6 class="fw-bold mb-3 mt-4">Actions</h6>
                <table class="table table-sm table-borderless">
                    <tr><td><kbd>Ctrl + K</kbd></td><td>Palette de commandes</td></tr>
                    <tr><td><kbd>Alt + S</kbd></td><td>Recherche</td></tr>
                    <tr><td><kbd>?</kbd></td><td>Afficher cette aide</td></tr>
                    <tr><td><kbd>Esc</kbd></td><td>Fermer modales</td></tr>
                </table>
            </div>
        `,
        width: 600,
        confirmButtonText: 'Compris !'
    });
}

/**
 * Fermer toutes les modales ouvertes
 */
function closeAllModals() {
    // Fermer les modales Bootstrap
    const modals = document.querySelectorAll('.modal.show');
    modals.forEach(modal => {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    });

    // Fermer SweetAlert
    if (typeof Swal !== 'undefined') {
        Swal.close();
    }

    // Fermer les dropdowns
    const dropdowns = document.querySelectorAll('.dropdown-menu.show');
    dropdowns.forEach(dropdown => {
        dropdown.classList.remove('show');
    });
}

/**
 * Focus sur le champ de recherche
 */
function focusSearch() {
    const searchInput = document.querySelector('input[type="search"], input[placeholder*="Recherch"]');
    if (searchInput) {
        searchInput.focus();
        searchInput.select();
    }
}

// Ajouter un indicateur visuel des raccourcis au survol
document.addEventListener('DOMContentLoaded', function () {
    // Ajouter des tooltips sur les liens de navigation
    const navLinks = {
        'dashboard.php': 'Alt+D',
        'dashboard_bi.php': 'Alt+B',
        'affaires_liste.php': 'Alt+A',
        'commandes_liste.php': 'Alt+C',
        'planning.php': 'Alt+P',
        'gestion_email.php': 'Alt+E',
        'tasks.php': 'Alt+T'
    };

    Object.entries(navLinks).forEach(([url, shortcut]) => {
        const links = document.querySelectorAll(`a[href="${url}"]`);
        links.forEach(link => {
            const currentTitle = link.getAttribute('title') || '';
            link.setAttribute('title', currentTitle ? `${currentTitle} (${shortcut})` : shortcut);
        });
    });

    // Afficher un message d'accueil avec les raccourcis (une seule fois)
    if (!localStorage.getItem('shortcuts_shown')) {
        setTimeout(() => {
            Swal.fire({
                icon: 'info',
                title: '💡 Astuce',
                html: 'Appuyez sur <kbd>?</kbd> pour voir tous les raccourcis clavier disponibles !',
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true
            });
            localStorage.setItem('shortcuts_shown', 'true');
        }, 2000);
    }
});

// CSS pour les kbd tags
const style = document.createElement('style');
style.textContent = `
    kbd {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 3px;
        box-shadow: 0 1px 0 rgba(0,0,0,0.2);
        color: #495057;
        display: inline-block;
        font-family: 'Courier New', monospace;
        font-size: 0.85em;
        padding: 2px 6px;
        white-space: nowrap;
    }
`;
document.head.appendChild(style);
