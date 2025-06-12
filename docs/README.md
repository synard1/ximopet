# 📚 Dokumentasi Sistem Demo51

Direktori ini berisi semua dokumentasi terkait sistem manajemen peternakan Demo51.

## 📋 Daftar Modul & Dokumentasi

### 🔐 **1. Temporary Authorization System**

Sistem autorisasi temporer untuk mengubah data yang di-lock.

-   **[TEMP_AUTHORIZATION.md](./TEMP_AUTHORIZATION.md)** - Dokumentasi dasar (v1.0)
-   **[TEMP_AUTH_ENHANCED.md](./TEMP_AUTH_ENHANCED.md)** ⭐ - Enhanced version (v2.0)
-   **[TEMP_AUTH_REFACTOR.md](./TEMP_AUTH_REFACTOR.md)** - Refactor & Enhancement log
-   **[IMPLEMENTATION_LOG.md](./IMPLEMENTATION_LOG.md)** - Log implementasi detail
-   **[DEBUG_STEPS.md](./DEBUG_STEPS.md)** - Panduan debugging & troubleshooting
-   **[FIX_LOG.md](./FIX_LOG.md)** - Log perbaikan bug & improvements

### 📊 **2. Smart Analytics System**

Sistem analisis cerdas untuk mengidentifikasi kandang dengan mortalitas tinggi, performa penjualan, dan metrik produksi.

-   **[SMART_ANALYTICS.md](./SMART_ANALYTICS.md)** - Dokumentasi lengkap sistem
-   **[SMART_ANALYTICS_QUICK_START.md](./SMART_ANALYTICS_QUICK_START.md)** - Panduan setup 5 menit
-   **[SMART_ANALYTICS_IMPLEMENTATION.md](./SMART_ANALYTICS_IMPLEMENTATION.md)** - Log implementasi
-   **[SMART_ANALYTICS_API.md](./SMART_ANALYTICS_API.md)** - API Reference & Examples
-   **[SMART_ANALYTICS_TROUBLESHOOTING.md](./SMART_ANALYTICS_TROUBLESHOOTING.md)** - Troubleshooting guide

### 🎯 **3. Comprehensive Data Management**

Sistem lengkap untuk generate dummy data realistis mulai dari livestock purchase hingga daily recordings.

-   **[COMPREHENSIVE_DATA_SEEDER.md](./COMPREHENSIVE_DATA_SEEDER.md)** ⭐ - Complete farm data seeder
-   **[SEEDER_EXECUTION_REPORT.md](./SEEDER_EXECUTION_REPORT.md)** ⭐ - Seeder execution report & results
-   **[COMPLETION_LOG.md](./COMPLETION_LOG.md)** ✅ - Final completion summary
-   **[DEBUG_MENU_FIX.md](./DEBUG_MENU_FIX.md)** - Menu navigation syntax fixes

---

## 🚀 Quick Start by Module

### Temporary Authorization

```bash
# Setup enhanced version
php artisan migrate
php artisan db:seed --class=TempAuthSeeder
# Test: Navigate to any locked form and request authorization
```

### Smart Analytics

```bash
# Setup analytics system
php artisan migrate
php artisan db:seed --class=PerformanceBenchmarkSeeder
php artisan analytics:daily-calculate --days=7
# Access: /report/smart-analytics
```

### Comprehensive Data Generation

```bash
# Generate complete farm data for analytics
php artisan farm:generate-data --fresh --force

# Custom data generation
php artisan farm:generate-data --farms=5 --days=60

# Direct seeder execution
php artisan db:seed --class=ComprehensiveFarmDataSeeder
```

---

## 📖 Documentation Standards

### File Naming Convention

-   `MODULE_NAME.md` - Main documentation
-   `MODULE_NAME_QUICK_START.md` - 5-minute setup guide
-   `MODULE_NAME_IMPLEMENTATION.md` - Implementation logs
-   `MODULE_NAME_API.md` - API reference
-   `MODULE_NAME_TROUBLESHOOTING.md` - Debug & troubleshooting

### Content Structure

1. **Overview** - Purpose and features
2. **Installation** - Setup instructions
3. **Configuration** - Settings and options
4. **Usage** - Basic and advanced usage
5. **API Reference** - Methods and examples
6. **Troubleshooting** - Common issues and solutions
7. **Changelog** - Version history

---

## 🔧 Development

-   **Current Version**: Laravel 10.x + Livewire 3.x
-   **Database**: MySQL 8.x
-   **Frontend**: Bootstrap 5 + Chart.js
-   **Last Updated**: January 2025

## 📞 Support

1. **Check Documentation**: Start with module-specific documentation
2. **Review Logs**: Check `storage/logs/laravel.log`
3. **Debug Commands**: Use `php artisan tinker` for debugging
4. **Implementation Logs**: Check `*_IMPLEMENTATION.md` for context

---

**Project**: Demo51 Livestock Management System  
**Documentation Version**: 2.0  
**Maintained by**: Development Team
