<?php
// config/env_loader.php
// Chargeur de variables d'environnement (Alternative à vlucas/phpdotenv)

/**
 * Charge les variables depuis le fichier .env
 * @param string $path Chemin vers le fichier .env
 */
function loadEnv($path = __DIR__ . '/../.env') {
    if (!file_exists($path)) {
        // Si .env n'existe pas, utiliser .env.example comme template
        if (file_exists(__DIR__ . '/../.env.example')) {
            error_log("[ENV] Fichier .env manquant. Copiez .env.example vers .env et configurez vos identifiants.");
        }
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parser la ligne KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Supprimer les guillemets si présents
            $value = trim($value, '"\'');
            
            // Définir la variable d'environnement
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

/**
 * Récupère une variable d'environnement
 * @param string $key Nom de la variable
 * @param mixed $default Valeur par défaut
 * @return mixed
 */
function env($key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    // Conversion des booléens
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
    }
    
    return $value;
}

// Charger automatiquement au require
loadEnv();
