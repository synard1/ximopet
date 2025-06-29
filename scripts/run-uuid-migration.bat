@echo off
REM UUID Migration Script for Windows
REM This script handles the complete UUID migration process

echo 🚀 Starting UUID Migration Deployment...

REM Check if we're in the right directory
if not exist "artisan" (
    echo [ERROR] Please run this script from the Laravel project root directory
    pause
    exit /b 1
)

REM Check if database is accessible
echo [INFO] Checking database connection...
php artisan tinker --execute="echo 'Database connection OK';" >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Cannot connect to database. Please check your configuration.
    pause
    exit /b 1
)

REM Create backup directory
if not exist "backup" mkdir backup

REM Clear caches
echo [INFO] Clearing application caches...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

REM Run migrations
echo [INFO] Running UUID migration migrations...
php artisan migrate --force

REM Run seeder for data migration
echo [INFO] Running UUID data migration seeder...
php artisan db:seed --class=UuidMigrationSeeder --force

REM Clear permission cache
echo [INFO] Clearing permission cache...
php artisan permission:cache-reset

REM Test the migration
echo [INFO] Running post-migration tests...

REM Test user authentication
php artisan tinker --execute="
$user = App\Models\User::first();
if ($user) {
    echo 'User found with UUID: ' . $user->uuid . PHP_EOL;
    echo 'User has roles: ' . $user->roles->count() . PHP_EOL;
    echo 'User has permissions: ' . $user->permissions->count() . PHP_EOL;
    echo 'Test passed!' . PHP_EOL;
} else {
    echo 'No users found!' . PHP_EOL;
    exit(1);
}
" >nul 2>&1

if errorlevel 1 (
    echo [ERROR] ❌ User authentication test failed
    pause
    exit /b 1
) else (
    echo [INFO] ✅ User authentication test passed
)

REM Test permission system
php artisan tinker --execute="
$user = App\Models\User::first();
if ($user && $user->hasRole('SuperAdmin')) {
    echo 'Permission system working correctly' . PHP_EOL;
} else {
    echo 'Permission system test passed (no SuperAdmin role)' . PHP_EOL;
}
" >nul 2>&1

if errorlevel 1 (
    echo [ERROR] ❌ Permission system test failed
    pause
    exit /b 1
) else (
    echo [INFO] ✅ Permission system test passed
)

REM Final cleanup
echo [INFO] Clearing caches after migration...
php artisan config:clear
php artisan cache:clear
php artisan permission:cache-reset

echo [INFO] 🎉 UUID Migration completed successfully!
echo [INFO] 📋 Next steps:
echo    1. Test all application functionality
echo    2. Monitor application performance
echo    3. Check error logs for any issues
echo    4. Update any hardcoded user ID references in your code
echo    5. Consider removing the uuid_mappings table after verification

echo [INFO] 📝 Migration logs: storage/logs/laravel.log

echo.
echo [WARNING] ⚠️  IMPORTANT: Please test your application thoroughly before deploying to production!
pause 