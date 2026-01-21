# enable_imap.ps1
# Script pour activer l'extension IMAP dans PHP

Write-Host "=== Activation de l'extension IMAP pour PHP ===" -ForegroundColor Cyan

# Trouver le fichier php.ini
$phpIniPath = "C:\laragon\bin\php\php.ini"

# Chercher dans tous les dossiers PHP de Laragon
$phpDirs = Get-ChildItem "C:\laragon\bin\php" -Directory -ErrorAction SilentlyContinue
foreach ($dir in $phpDirs) {
    $iniFile = Join-Path $dir.FullName "php.ini"
    if (Test-Path $iniFile) {
        $phpIniPath = $iniFile
        Write-Host "Fichier php.ini trouvé: $phpIniPath" -ForegroundColor Green
        break
    }
}

if (-not (Test-Path $phpIniPath)) {
    Write-Host "ERREUR: Fichier php.ini introuvable!" -ForegroundColor Red
    Write-Host "Veuillez vérifier que Laragon est installé dans C:\laragon" -ForegroundColor Yellow
    exit 1
}

# Lire le contenu
$content = Get-Content $phpIniPath -Raw

# Vérifier si IMAP est déjà activé
if ($content -match "^extension=imap" -and $content -notmatch "^;extension=imap") {
    Write-Host "L'extension IMAP est déjà activée!" -ForegroundColor Green
    exit 0
}

# Activer IMAP (décommenter la ligne)
$content = $content -replace "(?m)^;extension=imap", "extension=imap"

# Si la ligne n'existe pas du tout, l'ajouter
if ($content -notmatch "extension=imap") {
    Write-Host "Ajout de la ligne extension=imap..." -ForegroundColor Yellow
    $content += "`nextension=imap`n"
}

# Sauvegarder
Set-Content -Path $phpIniPath -Value $content -NoNewline

Write-Host "Extension IMAP activée avec succès!" -ForegroundColor Green

# Redémarrer Apache
Write-Host "`nRedémarrage d'Apache..." -ForegroundColor Cyan

# Arrêter Apache
$apacheStop = Start-Process -FilePath "C:\laragon\laragon.exe" -ArgumentList "stop" -PassThru -WindowStyle Hidden -Wait

Start-Sleep -Seconds 2

# Démarrer Apache
$apacheStart = Start-Process -FilePath "C:\laragon\laragon.exe" -ArgumentList "start" -PassThru -WindowStyle Hidden -Wait

Write-Host "Apache redémarré!" -ForegroundColor Green
Write-Host "`n=== Configuration terminée ===" -ForegroundColor Cyan
Write-Host "Vous pouvez maintenant accéder à http://localhost/antigravity/gestion_email.php" -ForegroundColor White
