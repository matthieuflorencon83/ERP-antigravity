@echo off
REM ========================================
REM ANTIGRAVITY - BACKUP COMPLET SYSTEME
REM ========================================

echo ========================================
echo ANTIGRAVITY - SAUVEGARDE COMPLETE
echo ========================================
echo.

REM Définir les chemins
set TIMESTAMP=%date:~-4%%date:~3,2%%date:~0,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set BACKUP_DIR=C:\BACKUPS\ANTIGRAVITY_%TIMESTAMP%
set APP_DIR=C:\laragon\www\antigravity
set MYSQL_BIN=C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin
set DB_NAME=antigravity_v3

echo [1/4] Creation du repertoire de sauvegarde...
mkdir "%BACKUP_DIR%"
mkdir "%BACKUP_DIR%\application"
mkdir "%BACKUP_DIR%\database"

echo [2/4] Sauvegarde des fichiers de l'application...
xcopy "%APP_DIR%" "%BACKUP_DIR%\application\" /E /I /H /Y /EXCLUDE:backup_exclude.txt
echo    - Fichiers application sauvegardes

echo [3/4] Export de la base de donnees MySQL...
"%MYSQL_BIN%\mysqldump.exe" -u root --databases %DB_NAME% --result-file="%BACKUP_DIR%\database\%DB_NAME%_%TIMESTAMP%.sql"
if %ERRORLEVEL% EQU 0 (
    echo    - Base de donnees exportee avec succes
) else (
    echo    - ERREUR lors de l'export de la base de donnees
)

echo [4/4] Creation du fichier d'informations...
(
    echo ANTIGRAVITY - SAUVEGARDE COMPLETE
    echo ================================
    echo.
    echo Date: %date% %time%
    echo Application: %APP_DIR%
    echo Base de donnees: %DB_NAME%
    echo.
    echo Contenu de la sauvegarde:
    echo - /application/ : Tous les fichiers du logiciel
    echo - /database/ : Export SQL complet de la base
    echo.
    echo Pour restaurer:
    echo 1. Copier le contenu de /application/ vers C:\laragon\www\antigravity\
    echo 2. Importer le fichier SQL: mysql -u root antigravity_v3 ^< database\*.sql
) > "%BACKUP_DIR%\README.txt"

echo.
echo ========================================
echo SAUVEGARDE TERMINEE !
echo ========================================
echo.
echo Emplacement: %BACKUP_DIR%
echo.
echo Appuyez sur une touche pour ouvrir le dossier...
pause >nul
explorer "%BACKUP_DIR%"
