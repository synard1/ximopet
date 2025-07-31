#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}================================================"
echo -e "🚀 SIMULASI DENGAN DATA REAL - PERHITUNGAN BERAT BATCH"
echo -e "================================================${NC}"
echo

# Set environment
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LARAVEL_PATH="$(dirname "$SCRIPT_DIR")"
cd "$LARAVEL_PATH"

# Check if Laravel is available
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: Laravel artisan file not found!${NC}"
    echo "Please run this script from the Laravel project root directory."
    exit 1
fi

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ Error: PHP is not installed or not in PATH!${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Environment check passed${NC}"
echo -e "${BLUE}📁 Working directory: $(pwd)${NC}"
echo

# Check if log directory exists
if [ ! -d "storage/logs" ]; then
    echo -e "${YELLOW}⚠️  Creating logs directory...${NC}"
    mkdir -p storage/logs
fi

# Check if bgjob.log exists, if not create it
if [ ! -f "storage/logs/bgjob.log" ]; then
    echo -e "${YELLOW}⚠️  Creating bgjob.log file...${NC}"
    touch storage/logs/bgjob.log
    chmod 644 storage/logs/bgjob.log
fi

# Check if database is accessible
echo -e "${BLUE}🔍 Checking database connection...${NC}"
if ! php artisan tinker --execute="echo 'DB connected: ' . (DB::connection()->getPdo() ? 'Yes' : 'No');" >/dev/null 2>&1; then
    echo -e "${RED}❌ Error: Database connection failed!${NC}"
    echo "Please check your database configuration."
    exit 1
fi

echo -e "${GREEN}✅ Database connection successful${NC}"
echo

# Check if required models exist
echo -e "${BLUE}🔍 Checking required models...${NC}"
if ! php artisan tinker --execute="echo 'Livestock count: ' . App\Models\Livestock::count();" >/dev/null 2>&1; then
    echo -e "${RED}❌ Error: Livestock model not found or database table missing!${NC}"
    echo "Please run migrations first: php artisan migrate"
    exit 1
fi

echo -e "${GREEN}✅ Models check passed${NC}"
echo

# Run the real data simulation
echo -e "${BLUE}🎯 Starting real data simulation...${NC}"
php scripts/simulate_with_real_data.php

echo
echo -e "${BLUE}================================================"
echo -e "🎯 SIMULASI DENGAN DATA REAL SELESAI"
echo -e "================================================${NC}"
echo
echo -e "${GREEN}💡 Tips:${NC}"
echo -e "   - Check logs: ${YELLOW}tail -f storage/logs/bgjob.log${NC}"
echo -e "   - Monitor queue: ${YELLOW}php artisan queue:work --queue=weight-calculation --verbose${NC}"
echo -e "   - View results: ${YELLOW}php artisan tinker${NC}"
echo -e "   - Run migration if needed: ${YELLOW}php artisan migrate${NC}"
echo 