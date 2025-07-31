@echo off
chcp 65001 >nul
echo.
echo ================================================
echo 🚀 SIMULASI PERHITUNGAN BERAT BATCH LIVESTOCK
echo ================================================
echo.

:: Set environment
set LARAVEL_PATH=%~dp0..
cd /d "%LARAVEL_PATH%"

:: Check if Laravel is available
if not exist "artisan" (
    echo ❌ Error: Laravel artisan file not found!
    echo Please run this script from the Laravel project root directory.
    pause
    exit /b 1
)

:: Check if PHP is available
php --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Error: PHP is not installed or not in PATH!
    pause
    exit /b 1
)

echo ✅ Environment check passed
echo 📁 Working directory: %CD%
echo.

:: Run the simulation
echo 🎯 Starting simulation...
php scripts/simulate_batch_weight_calculation.php

echo.
echo ================================================
echo 🎯 SIMULASI SELESAI
echo ================================================
echo.
echo 💡 Tips:
echo    - Check logs: storage/logs/bgjob.log
echo    - Monitor queue: php artisan queue:work --queue=weight-calculation --verbose
echo    - View results: php artisan tinker
echo.
pause 