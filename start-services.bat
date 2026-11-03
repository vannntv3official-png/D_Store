@echo off
title D STORE - Local Service Launcher
echo ========================================================
echo   D STORE - Local Development Services (MySQL + Laravel)
echo ========================================================
echo.

:: 1. Check if MySQL (port 3306) is listening
netstat -ano | findstr /R /C:":3306 .*LISTENING" >nul
if %errorlevel% equ 0 (
    echo [OK] MySQL is already running on port 3306.
) else (
    echo [STARTING] Starting MySQL 8.4 Community Server...
    start "MySQL Server" /min "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqld.exe" --datadir="C:\mysql_data" --port=3306 --console
    timeout /t 3 /nobreak >nul
    echo [OK] MySQL started successfully.
)

:: 2. Check if Laravel Web Server (port 8000) is listening
netstat -ano | findstr /R /C:":8000 .*LISTENING" >nul
if %errorlevel% equ 0 (
    echo [OK] Laravel Development Server is already running on port 8000.
) else (
    echo [STARTING] Starting Laravel HTTP Server...
    start "Laravel Serve" cmd /k "php artisan serve --port=8000"
    timeout /t 2 /nobreak >nul
    echo [OK] Laravel server started at http://127.0.0.1:8000
)

echo.
echo ========================================================
echo   D STORE is ready!
echo   Customer Storefront : http://127.0.0.1:8000
echo   Staff Admin Portal  : http://127.0.0.1:8000/admin/login
echo ========================================================
echo.
pause
