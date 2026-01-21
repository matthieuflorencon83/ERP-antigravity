@echo off
color 1F
title ANTIGRAVITY - DEMARRAGE SYSTEME
echo ========================================================
echo      MISE A JOUR ET DEMARRAGE EN COURS...
echo ========================================================
echo.

:: 1. NETTOYAGE (On tue les anciens serveurs qui traînent)
echo [1/4] Arret des anciens processus...
taskkill /F /IM python.exe /T >nul 2>&1
timeout /t 1 /nobreak >nul

:: 2. NAVIGATION
cd /d "%~dp0"

:: 3. ACTIVATION & MISE A JOUR
echo [2/4] Activation du moteur...
call venv\Scripts\activate

echo [3/4] Verification de la base de donnees (Mise a jour)...
python manage.py migrate

:: 4. LANCEMENT
echo [4/4] Lancement de l'interface...
:: On attend 3 secondes que le serveur chauffe
start /B cmd /c "timeout /t 4 /nobreak >nul & start http://127.0.0.1:8000/affaires/715d853c-4917-4d76-ba87-aaab7bdd6c55/besoins/"

echo.
echo  XXX  SERVEUR EN LIGNE - NE PAS FERMER CETTE FENETRE  XXX
echo.
python manage.py runserver

pause
