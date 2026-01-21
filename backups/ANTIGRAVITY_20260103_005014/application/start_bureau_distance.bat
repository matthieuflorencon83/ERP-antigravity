@echo off
TITLE ANTIGRAVITY - ACCES DISTANT (5G)
COLOR 0A
CLS

ECHO ========================================================
ECHO    ANTIGRAVITY - LANCEMENT DE L'ACCES DISTANT
ECHO ========================================================
ECHO.
ECHO  1. Une fenetre va s'ouvrir.
ECHO  2. Cherchez la ligne "Forwarding".
ECHO  3. Copiez le lien qui commence par "https://..."
ECHO  4. Ouvrez ce lien sur votre telephone.
ECHO.
ECHO  ATTENTION : Ne fermez pas cette fenetre tant que vous
ECHO              utilisez le logiciel a distance.
ECHO.
ECHO ========================================================
ECHO  Lancement du tunnel...
ECHO.

"C:\laragon\bin\ngrok\ngrok.exe" http 80

PAUSE
