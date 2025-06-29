#!/bin/bash

# UUID Migration Deployment Script
# This script handles the complete UUID migration process

set -e  # Exit on any error

echo "🚀 Starting UUID Migration Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "Please run this script from the Laravel project root directory"
    exit 1
fi

# Check if database is accessible
print_status "Checking database connection..."
if ! php artisan tinker --execute="echo 'Database connection OK';" > /dev/null 2>&1; then
    print_error "Cannot connect to database. Please check your configuration."
    exit 1
fi

# Backup database
print_status "Creating database backup..."
BACKUP_FILE="backup/pre-uuid-migration-$(date +%Y%m%d-%H%M%S).sql"
mkdir -p backup

if command -v mysqldump > /dev/null 2>&1; then
    DB_DATABASE=$(php artisan tinker --execute="echo config('database.connections.mysql.database');")
    DB_USERNAME=$(php artisan tinker --execute="echo config('database.connections.mysql.username');")
    DB_PASSWORD=$(php artisan tinker --execute="echo config('database.connections.mysql.password');")
    DB_HOST=$(php artisan tinker --execute="echo config('database.connections.mysql.host');")
    
    mysqldump -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE"
    print_status "Database backed up to: $BACKUP_FILE"
else
    print_warning "mysqldump not found. Please backup your database manually before proceeding."
    read -p "Press Enter to continue or Ctrl+C to abort..."
fi

# Clear caches
print_status "Clearing application caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
print_status "Running UUID migration migrations..."
php artisan migrate --force

# Run seeder for data migration
print_status "Running UUID data migration seeder..."
php artisan db:seed --class=UuidMigrationSeeder --force

# Clear permission cache
print_status "Clearing permission cache..."
php artisan permission:cache-reset

# Test the migration
print_status "Running post-migration tests..."

# Test user authentication
if php artisan tinker --execute="
    \$user = App\Models\User::first();
    if (\$user) {
        echo 'User found with UUID: ' . \$user->uuid . PHP_EOL;
        echo 'User has roles: ' . \$user->roles->count() . PHP_EOL;
        echo 'User has permissions: ' . \$user->permissions->count() . PHP_EOL;
        echo 'Test passed!' . PHP_EOL;
    } else {
        echo 'No users found!' . PHP_EOL;
        exit(1);
    }
" > /dev/null 2>&1; then
    print_status "✅ User authentication test passed"
else
    print_error "❌ User authentication test failed"
    exit 1
fi

# Test permission system
if php artisan tinker --execute="
    \$user = App\Models\User::first();
    if (\$user && \$user->hasRole('SuperAdmin')) {
        echo 'Permission system working correctly' . PHP_EOL;
    } else {
        echo 'Permission system test passed (no SuperAdmin role)' . PHP_EOL;
    }
" > /dev/null 2>&1; then
    print_status "✅ Permission system test passed"
else
    print_error "❌ Permission system test failed"
    exit 1
fi

# Final cleanup
print_status "Clearing caches after migration..."
php artisan config:clear
php artisan cache:clear
php artisan permission:cache-reset

print_status "🎉 UUID Migration completed successfully!"
print_status "📋 Next steps:"
echo "   1. Test all application functionality"
echo "   2. Monitor application performance"
echo "   3. Check error logs for any issues"
echo "   4. Update any hardcoded user ID references in your code"
echo "   5. Consider removing the uuid_mappings table after verification"

print_status "📁 Backup location: $BACKUP_FILE"
print_status "📝 Migration logs: storage/logs/laravel.log"

echo ""
print_warning "⚠️  IMPORTANT: Please test your application thoroughly before deploying to production!" 