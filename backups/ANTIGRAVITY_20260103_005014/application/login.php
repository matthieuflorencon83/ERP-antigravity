<?php
session_start();
require_once 'db.php';
require_once 'core/functions.php'; // Chargement de la boîte à outils

// 1. Sécurité HTTP
secure_headers();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. Protection CSRF
    csrf_require();
    
    $identifiant = trim($_POST['identifiant'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifiant && $password) {
        // ... (Suite logique)
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE identifiant = ?");
        $stmt->execute([$identifiant]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe_hash'])) {
            // Login Success
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom_complet'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['theme'] = 'light'; // Defaut

            // AUDIT LOG
            try {
                $stmtLog = $pdo->prepare("INSERT INTO access_logs (user_id, user_nom, event_type, ip_address, user_agent) VALUES (?, ?, 'LOGIN', ?, ?)");
                $stmtLog->execute([$user['id'], $user['nom_complet'], $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN', $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN']);
            } catch (Exception $e) {}

            header("Location: index.php");
            exit;
        } else {
            $error = "Identifiant ou mot de passe incorrect.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Antigravity</title>
    
    <!-- Design System Foundation -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/antigravity.css?v=<?= time() ?>" rel="stylesheet">
    
    <style>
        /* Specific Login Styles - Keeping it minimal and centered */
        body {
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%); /* Deep Professional Gradient */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(30, 41, 59, 0.7); /* Glassmorphism Base */
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }

        .login-header {
            background: rgba(15, 76, 117, 0.8); /* Antigravity Blue with transparency */
            padding: 2.5rem 2rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .login-logo {
            font-size: 2.5rem;
            color: #fff;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .login-subtitle {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .login-body {
            padding: 2.5rem;
        }

        .input-group-text {
            background-color: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
            color: #94a3b8;
        }

        .form-control {
            background-color: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .form-control:focus {
            background-color: rgba(255,255,255,0.1);
            border-color: var(--ag-accent);
            color: white;
            box-shadow: 0 0 0 0.25rem rgba(50, 130, 184, 0.25);
        }

        /* Override autofill Webkit background */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #243442 inset !important;
            -webkit-text-fill-color: white !important;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-rocket"></i>
            </div>
            <h2 class="h4 fw-bold text-white mb-1">ANTIGRAVITY</h2>
            <div class="login-subtitle">Système de Gestion Intégré V4.0</div>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_field() ?>
                <div class="mb-4">
                    <label class="form-label text-white-50 small text-uppercase fw-bold">Identifiant</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="identifiant" class="form-control" placeholder="Entrez votre identifiant" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="mb-5">
                    <label class="form-label text-white-50 small text-uppercase fw-bold">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2 py-3 fw-bold" style="font-size: 1.1rem; border-radius: 8px;">
                        <span>Connexion Sécurisée</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="py-3 text-center border-top border-secondary border-opacity-25">
            <small class="text-white-50">Arts & Alu &copy; <?= date('Y') ?></small>
        </div>
    </div>

</body>
</html>
