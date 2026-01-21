<?php
// parametres_email.php - Configuration Email
session_start();
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

// Note: Tous les utilisateurs authentifiés peuvent configurer l'email
// Si vous voulez restreindre aux admins, décommentez ci-dessous :
// if ($_SESSION['role'] !== 'ADMIN') {
//     header('Location: index.php');
//     exit;
// }

$page_title = 'Paramètres Email - Antigravity';
require_once 'header.php';

// Récupérer les paramètres actuels
$stmt = $pdo->query("SELECT * FROM parametres_generaux WHERE cle_config LIKE 'email_%'");
$params = [];
while ($row = $stmt->fetch()) {
    $params[$row['cle_config']] = $row['valeur_config'];
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'email_smtp_host' => $_POST['smtp_host'] ?? 'smtp.office365.com',
        'email_smtp_port' => $_POST['smtp_port'] ?? '587',
        'email_smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
        'email_smtp_username' => $_POST['smtp_username'] ?? '',
        'email_smtp_password' => $_POST['smtp_password'] ?? '',
        'email_from_name' => $_POST['from_name'] ?? 'Antigravity',
        'email_from_email' => $_POST['from_email'] ?? '',
        'email_imap_host' => $_POST['imap_host'] ?? 'outlook.office365.com',
        'email_imap_port' => $_POST['imap_port'] ?? '993'
    ];
    
    foreach ($settings as $key => $value) {
        // Vérifier si le paramètre existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM parametres_generaux WHERE cle_config = ?");
        $stmt->execute([$key]);
        
        if ($stmt->fetchColumn() > 0) {
            // Update
            $stmt = $pdo->prepare("UPDATE parametres_generaux SET valeur_config = ? WHERE cle_config = ?");
            $stmt->execute([$value, $key]);
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO parametres_generaux (cle_config, valeur_config) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }
    }
    
    // Mettre à jour le fichier de config
    updateMailConfig($settings);
    
    $success = "Paramètres email enregistrés avec succès !";
}

function updateMailConfig($settings) {
    $configContent = "<?php
// config/mail_config.php
// Configuration du serveur email (Généré automatiquement)

return [
    'smtp' => [
        'host' => '{$settings['email_smtp_host']}',
        'port' => {$settings['email_smtp_port']},
        'encryption' => '{$settings['email_smtp_encryption']}',
        'username' => '{$settings['email_smtp_username']}',
        'password' => '{$settings['email_smtp_password']}',
        'from_name' => '{$settings['email_from_name']}',
        'from_email' => '{$settings['email_from_email']}'
    ],
    
    'imap' => [
        'host' => '{{$settings['email_imap_host']}:{$settings['email_imap_port']}/imap/ssl}INBOX',
        'username' => '{$settings['email_smtp_username']}',
        'password' => '{$settings['email_smtp_password']}'
    ],
    
    'cache' => [
        'enabled' => true,
        'ttl' => 300
    ],
    
    'limits' => [
        'max_emails_per_hour' => 50,
        'max_attachment_size' => 10485760
    ]
];
";
    
    file_put_contents(__DIR__ . '/config/mail_config.php', $configContent);
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-envelope me-2"></i>Configuration Email (Office 365)
                    </h4>
                </div>
                
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?= $success ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Instructions -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Comment obtenir un mot de passe d'application Office 365 :</h6>
                        <ol class="mb-0">
                            <li>Allez sur <a href="https://account.microsoft.com/security" target="_blank">https://account.microsoft.com/security</a></li>
                            <li>Cliquez sur "Sécurité avancée"</li>
                            <li>Créer un "Mot de passe d'application"</li>
                            <li>Copiez le mot de passe généré ci-dessous</li>
                        </ol>
                    </div>
                    
                    <form method="POST">
                        <h5 class="mb-3">Serveur SMTP (Envoi)</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Serveur SMTP</label>
                                <input type="text" name="smtp_host" class="form-control" 
                                       value="<?= $params['email_smtp_host'] ?? 'smtp.office365.com' ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Port</label>
                                <input type="number" name="smtp_port" class="form-control" 
                                       value="<?= $params['email_smtp_port'] ?? '587' ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Chiffrement</label>
                            <select name="smtp_encryption" class="form-select">
                                <option value="tls" <?= ($params['email_smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (Recommandé)</option>
                                <option value="ssl" <?= ($params['email_smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email / Nom d'utilisateur *</label>
                            <input type="email" name="smtp_username" class="form-control" 
                                   value="<?= $params['email_smtp_username'] ?? '' ?>" 
                                   placeholder="contact@antigravity.fr" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mot de passe d'application *</label>
                            <input type="password" name="smtp_password" class="form-control" 
                                   value="<?= $params['email_smtp_password'] ?? '' ?>" 
                                   placeholder="Généré depuis Office 365" required>
                            <small class="text-muted">Ne PAS utiliser votre mot de passe principal Office 365</small>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Expéditeur par Défaut</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Nom de l'expéditeur</label>
                            <input type="text" name="from_name" class="form-control" 
                                   value="<?= $params['email_from_name'] ?? 'Antigravity - Menuiserie' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email de l'expéditeur</label>
                            <input type="email" name="from_email" class="form-control" 
                                   value="<?= $params['email_from_email'] ?? '' ?>" 
                                   placeholder="contact@antigravity.fr" required>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Serveur IMAP (Réception)</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Serveur IMAP</label>
                                <input type="text" name="imap_host" class="form-control" 
                                       value="<?= $params['email_imap_host'] ?? 'outlook.office365.com' ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Port</label>
                                <input type="number" name="imap_port" class="form-control" 
                                       value="<?= $params['email_imap_port'] ?? '993' ?>" required>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer
                            </button>
                            <a href="gestion_email.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Retour à la messagerie
                            </a>
                            <button type="button" class="btn btn-outline-primary ms-auto" onclick="testConnection()">
                                <i class="fas fa-vial me-2"></i>Tester la connexion
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testConnection() {
    Swal.fire({
        title: 'Test de connexion',
        text: 'Envoi d\'un email de test...',
        icon: 'info',
        showConfirmButton: false,
        allowOutsideClick: false
    });
    
    fetch('api/email_api.php?action=test_connection')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Succès', 'Connexion réussie !', 'success');
            } else {
                Swal.fire('Erreur', data.error, 'error');
            }
        })
        .catch(err => {
            Swal.fire('Erreur', 'Impossible de tester la connexion', 'error');
        });
}
</script>

<?php require_once 'footer.php'; ?>
