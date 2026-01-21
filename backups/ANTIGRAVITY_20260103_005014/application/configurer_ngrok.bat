@echo off
TITLE ANTIGRAVITY - CONFIGURATION NGROK
COLOR 0E
CLS

ECHO ========================================================
ECHO    CONFIGURATION OBLIGATOIRE NGROK (Compte Gratuit)
ECHO ========================================================
ECHO.
ECHO  Ngrok necessite desormais un compte gratuit pour fonctionner.
ECHO.
ECHO  1. Allez sur : https://dashboard.ngrok.com/signup
ECHO  2. Creez un compte (Google ou Email).
ECHO  3. Allez dans "Your Authtoken" (menu de gauche).
ECHO  4. Copiez le code qui commence par "2..." (ex: 2Mw...)
ECHO.
ECHO ========================================================
set /p TOKEN="Collez votre Authtoken ici et tapez ENTREE : "

"C:\laragon\bin\ngrok\ngrok.exe" config add-authtoken %TOKEN%

ECHO.
ECHO  Configuration terminee !
ECHO  Vous pouvez maintenant relancer "start_bureau_distance.bat".
PAUSE
