@echo off
chcp 65001 >nul
echo.
echo ================================================
echo 🚀 SIMULASI DENGAN DATA REAL - PERHITUNGAN BERAT BATCH
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

:: Check if database is accessible
echo 🔍 Checking database connection...
php artisan tinker --execute="echo 'DB connected: ' . (DB::connection()->getPdo() ? 'Yes' : 'No');" >nul 2>&1
if errorlevel 1 (
    echo ❌ Error: Database connection failed!
    echo Please check your database configuration.
    pause
    exit /b 1
)

echo ✅ Database connection successful
echo.

:: Check if required models exist
echo 🔍 Checking required models...
php artisan tinker --execute="echo 'Livestock count: ' . App\Models\Livestock::count();" >nul 2>&1
if errorlevel 1 (
    echo ❌ Error: Livestock model not found or database table missing!
    echo Please run migrations first: php artisan migrate
    pause
    exit /b 1
)

echo ✅ Models check passed
echo.

:: Run the real data simulation
echo 🎯 Starting real data simulation...
php scripts/simulate_with_real_data.php

echo.
echo ================================================
echo 🎯 SIMULASI DENGAN DATA REAL SELESAI
echo ================================================
echo.
echo 💡 Tips:
echo    - Check logs: storage/logs/bgjob.log
echo    - Monitor queue: php artisan queue:work --queue=weight-calculation --verbose
echo    - View results: php artisan tinker
echo    - Run migration if needed: php artisan migrate
echo.
pause 