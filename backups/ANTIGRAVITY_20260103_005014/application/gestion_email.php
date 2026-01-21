<?php
// gestion_email.php - Module Email Intégré
session_start();
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$page_title = 'Messagerie - Antigravity';
require_once 'header.php';

// Vérifier si IMAP est activé
if (!extension_loaded('imap')) {
    ?>
    <div class="container-fluid py-5">
        <div class="alert alert-warning">
            <h4><i class="fas fa-exclamation-triangle me-2"></i>Extension IMAP non activée</h4>
            <p>Le module de messagerie nécessite l'extension PHP IMAP pour fonctionner.</p>
            <p><strong>Pour activer IMAP :</strong></p>
            <ol>
                <li>Ouvrez votre fichier <code>php.ini</code></li>
                <li>Décommentez la ligne : <code>;extension=imap</code> → <code>extension=imap</code></li>
                <li>Redémarrez Apache</li>
            </ol>
        </div>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

require_once 'classes/MailManager.php';
require_once 'classes/TemplateEngine.php';

$mailManager = new MailManager($pdo);
$templateEngine = new TemplateEngine($pdo);

// Récupérer les templates
$templates = $templateEngine->getTemplates();
?>

<!-- SweetAlert2 for popups -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- TinyMCE WYSIWYG Editor -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
    /* LAYOUT 3 COLONNES */
    .email-container {
        display: grid;
        grid-template-columns: 250px 350px 1fr;
        height: calc(100vh - 120px);
        gap: 0;
        background: #f8f9fa;
    }
    
    /* COLONNE 1: SIDEBAR */
    .email-sidebar {
        background: #fff;
        padding: 20px;
        border-right: 1px solid #dee2e6;
        overflow-y: auto;
    }
    
    .btn-compose {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        color: white;
        font-weight: 600;
        padding: 12px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
    }
    
    .btn-compose:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(13, 110, 253, 0.4);
    }
    
    .email-folders {
        list-style: none;
        padding: 0;
        margin: 20px 0;
    }
    
    .email-folders li {
        padding: 12px 15px;
        cursor: pointer;
        border-radius: 6px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .email-folders li:hover {
        background: #f8f9fa;
    }
    
    .email-folders li.active {
        background: #0d6efd;
        color: white;
    }
    
    .email-folders .badge {
        background: #dc3545;
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
    }
    
    /* COLONNE 2: LISTE DES MESSAGES */
    .email-list {
        background: #fff;
        border-right: 1px solid #dee2e6;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }
    
    .email-list-header {
        padding: 15px;
        border-bottom: 1px solid #dee2e6;
        background: #f8f9fa;
    }
    
    .email-items {
        flex: 1;
        overflow-y: auto;
    }
    
    .email-item {
        padding: 15px;
        border-bottom: 1px solid #dee2e6;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .email-item:hover {
        background: #f8f9fa;
    }
    
    .email-item.unread {
        background: var(--bs-primary-bg-subtle);
        border-left: 3px solid var(--bs-primary);
    }
    
    .email-item.selected {
        background: var(--bs-primary);
        color: white;
    }
    
    /* MOBILE RESPONSIVE */
    @media (max-width: 768px) {
        .email-container {
            flex-direction: column;
        }
        
        .email-sidebar {
            width: 100%;
            border-right: none;
            border-bottom: 1px solid var(--bs-border-color);
        }
        
        .email-list {
            width: 100%;
            border-right: none;
        }
        
        .email-viewer {
            width: 100%;
        }
        
        /* Masquer la sidebar sur mobile par défaut */
        .email-sidebar {
            display: none;
        }
        
        .email-sidebar.show-mobile {
            display: block;
        }
        
        /* Bouton hamburger pour mobile */
        .mobile-menu-toggle {
            display: block;
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--bs-primary);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    }
    
    @media (min-width: 769px) {
        .mobile-menu-toggle {
            display: none;
        }
    }
    
    .email-from {
        font-weight: 600;
        color: #212529;
        margin-bottom: 4px;
    }
    
    .email-subject {
        color: #495057;
        margin-bottom: 4px;
        font-size: 14px;
    }
    
    .email-preview {
        color: #6c757d;
        font-size: 13px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .email-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 8px;
    }
    
    .email-date {
        font-size: 12px;
        color: #6c757d;
    }
    
    /* COLONNE 3: VIEWER/COMPOSE */
    .email-viewer {
        background: #fff;
        padding: 30px;
        overflow-y: auto;
    }
    
    .email-header {
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }
    
    .email-header h3 {
        margin-bottom: 15px;
    }
    
    .email-body {
        line-height: 1.6;
    }
    
    /* COMPOSE FORM */
    #email-compose {
        display: none;
    }
    
    .compose-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        color: white;
        padding: 20px;
        border-radius: 8px 8px 0 0;
        margin: -30px -30px 20px -30px;
    }
    
    /* DARK MODE */
    [data-bs-theme="dark"] .email-container {
        background: #1e293b;
    }
    
    [data-bs-theme="dark"] .email-sidebar,
    [data-bs-theme="dark"] .email-list,
    [data-bs-theme="dark"] .email-viewer {
        background: #0f172a;
        color: #e2e8f0;
    }
</style>

<div class="email-container">
    <!-- COLONNE 1: SIDEBAR -->
    <div class="email-sidebar">
        <button class="btn btn-compose w-100 mb-3" onclick="openCompose()">
            <i class="fas fa-plus me-2"></i>Nouveau Message
        </button>
        
        <ul class="email-folders">
            <li class="active" onclick="loadFolder('inbox')">
                <span><i class="fas fa-inbox me-2"></i>Boîte de réception</span>
                <span class="badge" id="inbox-count">0</span>
            </li>
            <li onclick="loadFolder('sent')">
                <span><i class="fas fa-paper-plane me-2"></i>Envoyés</span>
            </li>
            <li onclick="loadFolder('drafts')">
                <span><i class="fas fa-file-alt me-2"></i>Brouillons</span>
            </li>
        </ul>
        
        <hr>
        
        <div class="email-filters">
            <h6 class="text-muted mb-3">Filtres Intelligents</h6>
            <ul class="email-folders">
                <li onclick="filterByContext('affaire')">
                    <span><i class="fas fa-briefcase me-2"></i>Affaire en cours</span>
                </li>
                <li onclick="filterByContext('client')">
                    <span><i class="fas fa-user me-2"></i>Client actuel</span>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- COLONNE 2: LISTE DES MESSAGES -->
    <div class="email-list">
        <div class="email-list-header">
            <input type="search" id="email-search" class="form-control" placeholder="Rechercher...">
        </div>
        
        <div class="email-items" id="email-items">
            <div class="text-center p-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Chargement des emails...</p>
            </div>
        </div>
    </div>
    
    <!-- COLONNE 3: VIEWER/COMPOSE -->
    <div class="email-viewer">
        <!-- EMAIL CONTENT -->
        <div id="email-content">
            <div class="text-center p-5 text-muted">
                <i class="fas fa-envelope-open fa-3x mb-3"></i>
                <p>Sélectionnez un email pour le lire</p>
            </div>
        </div>
        
        <!-- COMPOSE FORM -->
        <div id="email-compose">
            <div class="compose-header">
                <h4><i class="fas fa-envelope me-2"></i>Nouveau Message</h4>
            </div>
            
            <form id="form-compose">
                <!-- CSRF Protection -->
                <?= csrf_field() ?>
                
                <div class="mb-3">
                    <label class="form-label">Template (optionnel)</label>
                    <select id="compose-template" class="form-select" onchange="loadTemplate()">
                        <option value="">-- Aucun template --</option>
                        <?php foreach ($templates as $tpl): ?>
                            <option value="<?= $tpl['id'] ?>"><?= htmlspecialchars($tpl['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Destinataire *</label>
                    <input type="email" id="compose-to" class="form-control" required>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Client (pour variables)</label>
                        <select id="compose-client" class="form-select">
                            <option value="">-- Aucun --</option>
                        </select>
                        <small class="text-muted">Permet d'utiliser {NOM_CLIENT}, {EMAIL_CLIENT}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Affaire (pour variables)</label>
                        <select id="compose-affaire" class="form-select">
                            <option value="">-- Aucune --</option>
                        </select>
                        <small class="text-muted">Permet d'utiliser {AFFAIRE}, {NUMERO_AFFAIRE}</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Sujet *</label>
                    <input type="text" id="compose-subject" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Message *</label>
                    <textarea id="compose-body" class="form-control" rows="12" required></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Pièces jointes</label>
                    <button type="button" class="btn btn-outline-primary" onclick="openGEDPicker()">
                        <i class="fas fa-paperclip me-2"></i>Ajouter depuis la GED
                    </button>
                    <div id="attachments-list" class="mt-2"></div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Pièces jointes</label>
                    <div class="d-flex gap-2 mb-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="openGEDPicker()">
                            <i class="fas fa-folder-open me-2"></i>Parcourir la GED
                        </button>
                    </div>
                    <div id="attachments-list" class="border rounded p-2 bg-light">
                        <small class="text-muted">Aucune pièce jointe</small>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeCompose()">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal GED Picker -->
<div class="modal fade" id="gedPickerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-folder-open me-2"></i>Sélectionner des fichiers</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="search" id="ged-search" class="form-control" placeholder="Rechercher un fichier...">
                </div>
                <div id="ged-file-tree" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="confirmGEDSelection()">
                    <i class="fas fa-check me-2"></i>Ajouter (<span id="selected-count">0</span>)
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
let currentFolder = 'inbox';
let currentEmail = null;
let selectedAttachments = []; // Pièces jointes sélectionnées
let gedFiles = []; // Tous les fichiers de la GED
let gedSelectedFiles = new Set(); // Fichiers sélectionnés dans la GED (pour le modal)

// Navigation
// Charger la boîte de réception au démarrage
document.addEventListener('DOMContentLoaded', function() {
    loadFolder('inbox');
});

function loadFolder(folder) {
    currentFolder = folder;
    
    // Update active state
    document.querySelectorAll('.email-folders li').forEach(li => li.classList.remove('active'));
    event.target.closest('li').classList.add('active');
    
    // Load emails
    fetch(`api/email_api.php?action=list&folder=${folder}`)
        .then(res => res.json())
        .then(data => {
            displayEmailList(data);
        })
        .catch(err => {
            console.error('Error loading emails:', err);
            Swal.fire('Erreur', 'Impossible de charger les emails', 'error');
        });
}

function displayEmailList(emails) {
    const container = document.getElementById('email-items');
    
    if (!emails || emails.length === 0) {
        container.innerHTML = '<div class="text-center p-5 text-muted"><i class="fas fa-inbox fa-3x mb-3"></i><p>Aucun email</p></div>';
        return;
    }
    
    container.innerHTML = emails.map(email => `
        <div class="email-item ${email.seen ? '' : 'unread'}" onclick="loadEmail(${email.id})">
            <div class="email-from">${email.from_name || email.from}</div>
            <div class="email-subject">${email.subject}</div>
            <div class="email-meta">
                <span class="email-date">${formatDate(email.date)}</span>
                ${email.has_attachment ? '<i class="fas fa-paperclip text-muted"></i>' : ''}
            </div>
        </div>
    `).join('');
    
    // Update count
    const unreadCount = emails.filter(e => !e.seen).length;
    document.getElementById('inbox-count').textContent = unreadCount;
}

function loadEmail(emailId) {
    fetch(`api/email_api.php?action=get&id=${emailId}`)
        .then(res => res.json())
        .then(email => {
            displayEmail(email);
            currentEmail = email;
        });
}

function displayEmail(email) {
    const content = document.getElementById('email-content');
    content.innerHTML = `
        <div class="email-header">
            <h3>${email.subject}</h3>
            <div class="text-muted">
                <strong>De:</strong> ${email.from_name || email.from}<br>
                <strong>Date:</strong> ${formatDate(email.date)}
            </div>
        </div>
        <div class="email-body">
            ${email.body}
        </div>
        <div class="mt-4">
            <button class="btn btn-primary" onclick="replyTo()">
                <i class="fas fa-reply me-2"></i>Répondre
            </button>
        </div>
    `;
}

function openCompose() {
    document.getElementById('email-content').style.display = 'none';
    document.getElementById('email-compose').style.display = 'block';
    
    // Charger les clients et affaires pour les variables
    loadClientsAndAffaires();
}

function closeCompose() {
    // Vider l'éditeur TinyMCE
    if (tinymce.get('compose-body')) {
        tinymce.get('compose-body').setContent('');
    }
    
    // Réinitialiser les pièces jointes
    selectedAttachments = [];
    displayAttachments();
    
    document.getElementById('email-content').style.display = 'block';
    document.getElementById('email-compose').style.display = 'none';
    document.getElementById('form-compose').reset();
}

function loadTemplate() {
    const templateId = document.getElementById('compose-template').value;
    if (!templateId) {
        tinymce.get('compose-body').setContent('');
        document.getElementById('compose-subject').value = '';
        return;
    }
    
    // Charger le template depuis l'API
    fetch(`api/email_api.php?action=get_template&id=${templateId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('compose-subject').value = data.sujet || '';
                tinymce.get('compose-body').setContent(data.contenu || '');
                
                // Afficher un message pour les variables
                if (data.contenu && data.contenu.includes('{')) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Variables détectées',
                        html: 'Les variables seront remplacées automatiquement :<br><br>' +
                              '<code>{NOM_CLIENT}</code>, <code>{PRENOM_CLIENT}</code>, <code>{EMAIL_CLIENT}</code><br>' +
                              '<code>{AFFAIRE}</code>, <code>{NUMERO_AFFAIRE}</code><br>' +
                              '<code>{DATE}</code>, <code>{HEURE}</code>, <code>{ENTREPRISE}</code>, <code>{SIGNATURE}</code>',
                        timer: 5000
                    });
                }
            } else {
                Swal.fire('Erreur', data.error || 'Impossible de charger le template', 'error');
            }
        })
        .catch(err => {
            console.error('Erreur chargement template:', err);
            Swal.fire('Erreur', 'Impossible de charger le template', 'error');
        });
}

function loadClientsAndAffaires() {
    // Charger les clients
    fetch('api/clients_api.php?action=list_simple')
        .then(res => res.json())
        .then(clients => {
            const select = document.getElementById('compose-client');
            select.innerHTML = '<option value="">-- Aucun --</option>' +
                (clients || []).map(c => `<option value="${c.id}">${c.nom} ${c.prenom || ''}</option>`).join('');
        })
        .catch(err => console.error('Erreur chargement clients:', err));
    
    // Charger les affaires
    fetch('api/affaires_api.php?action=list_simple')
        .then(res => res.json())
        .then(affaires => {
            const select = document.getElementById('compose-affaire');
            select.innerHTML = '<option value="">-- Aucune --</option>' +
                (affaires || []).map(a => `<option value="${a.id}">${a.nom_affaire}</option>`).join('');
        })
        .catch(err => console.error('Erreur chargement affaires:', err));
}

function openGEDPicker() {
    // Charger l'arborescence GED
    fetch('api/ged_api.php?action=list')
        .then(res => res.json())
        .then(files => {
            displayGEDTree(files);
            const modal = new bootstrap.Modal(document.getElementById('gedPickerModal'));
            modal.show();
        })
        .catch(err => {
            console.error('Erreur chargement GED:', err);
            Swal.fire('Erreur', 'Impossible de charger les fichiers GED', 'error');
        });
}

function displayGEDTree(files) {
    const container = document.getElementById('ged-file-tree');
    
    if (!files || files.length === 0) {
        container.innerHTML = '<div class="text-center p-4 text-muted">Aucun fichier disponible</div>';
        return;
    }
    
    container.innerHTML = files.map(file => `
        <div class="form-check mb-2 p-2 border-bottom">
            <input class="form-check-input ged-file-checkbox" type="checkbox" value="${file.path}" 
                   id="file-${file.id}" data-name="${file.name}" onchange="updateSelectedCount()">
            <label class="form-check-label w-100" for="file-${file.id}">
                <div class="d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-file me-2 text-primary"></i>
                        <strong>${file.name}</strong>
                    </span>
                    <small class="text-muted">${formatFileSize(file.size)}</small>
                </div>
                <small class="text-muted ms-4">${file.path.replace('C:/ARTSALU/AFFAIRES/', '')}</small>
            </label>
        </div>
    `).join('');
    
    // Recherche
    document.getElementById('ged-search').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        document.querySelectorAll('.form-check').forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(query) ? 'block' : 'none';
        });
    });
}

function updateSelectedCount() {
    const count = document.querySelectorAll('.ged-file-checkbox:checked').length;
    document.getElementById('selected-count').textContent = count;
}

function confirmGEDSelection() {
    const checkboxes = document.querySelectorAll('.ged-file-checkbox:checked');
    selectedAttachments = Array.from(checkboxes).map(cb => ({
        path: cb.value,
        name: cb.dataset.name
    }));
    
    displayAttachments();
    bootstrap.Modal.getInstance(document.getElementById('gedPickerModal')).hide();
    
    Swal.fire({
        icon: 'success',
        title: 'Fichiers ajoutés',
        text: `${selectedAttachments.length} fichier(s) ajouté(s)`,
        timer: 2000,
        showConfirmButton: false
    });
}

function displayAttachments() {
    const container = document.getElementById('attachments-list');
    
    if (selectedAttachments.length === 0) {
        container.innerHTML = '<small class="text-muted">Aucune pièce jointe</small>';
        return;
    }
    
    container.innerHTML = selectedAttachments.map((file, index) => `
        <div class="d-flex align-items-center justify-content-between mb-2 p-2 bg-white rounded">
            <span><i class="fas fa-paperclip me-2 text-primary"></i>${file.name}</span>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeAttachment(${index})">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
}

function removeAttachment(index) {
    selectedAttachments.splice(index, 1);
    displayAttachments();
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 86400000) { // Moins de 24h
        return date.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'});
    } else {
        return date.toLocaleDateString('fr-FR');
    }
}

// Submit form
document.getElementById('form-compose').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Récupérer le contenu HTML de TinyMCE
    const htmlContent = tinymce.get('compose-body').getContent();
    
    const data = {
        csrf_token: document.querySelector('[name="csrf_token"]').value,
        to: document.getElementById('compose-to').value,
        subject: document.getElementById('compose-subject').value,
        body: htmlContent,
        attachments: selectedAttachments,
        affaire_id: document.getElementById('compose-affaire').value || null,
        client_id: document.getElementById('compose-client').value || null
    };
    
    fetch('api/email_api.php?action=send', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            Swal.fire('Succès', 'Email envoyé !', 'success');
            closeCompose();
        } else {
            Swal.fire('Erreur', result.error || 'Impossible d\'envoyer l\'email', 'error');
        }
    });
});

// Initialiser TinyMCE au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
        selector: '#compose-body',
        height: 400,
        menubar: false,
        plugins: 'lists link image code table paste help wordcount',
        toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image | code',
        content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; }',
        language: 'fr_FR',
        branding: false,
        promotion: false
    });
    
    // Charger les emails au démarrage
    loadFolder('inbox');
});
</script>

<?php require_once 'footer.php'; ?>
