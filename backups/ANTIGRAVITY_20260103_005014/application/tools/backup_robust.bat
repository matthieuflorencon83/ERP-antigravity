@echo off
set BACKUP_DIR=c:\laragon\www\antigravity\backups\backup_%date:~-4,4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%
set BACKUP_DIR=%BACKUP_DIR: =0%
mkdir "%BACKUP_DIR%"

echo [1/2] Dumping Database...
"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe" --user=root --password=root --host=localhost antigravity > "%BACKUP_DIR%\antigravity_db.sql"
if %ERRORLEVEL% EQU 0 (
    echo    - Database Dump OK
) else (
    echo    - DATABASE DUMP FAILED
)

echo [2/2] Copying Files (Robocopy)...
REM Copy everything except node_modules, .git, backups, and vendor (optional)
robocopy "c:\laragon\www\antigravity" "%BACKUP_DIR%\files" /E /XD node_modules .git backups vendor /XF *.log /R:1 /W:1 /NFL /NDL > "%BACKUP_DIR%\robocopy.log"

echo.
echo ==============================================
echo  BACKUP COMPLETED
echo  Location: %BACKUP_DIR%
echo ==============================================
