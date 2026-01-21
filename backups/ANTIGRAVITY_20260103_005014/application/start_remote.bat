@echo off
echo ===================================================
echo   ACTIVATION DU TUNNEL SECURISE (ANTIGRAVITY)
echo ===================================================
echo.
echo 1. Une fenetre va s'ouvrir.
echo 2. Copiez l'URL qui ressemble a : https://xxxx-xxxx.ngrok-free.app
echo 3. Ouvrez cette URL sur votre mobile.
echo.
echo Lancement en cours...
c:\laragon\bin\ngrok\ngrok.exe http 80
pause
