# Git Staging Commands per Kategori

**Tanggal**: 11 Juni 2025 11:30 WIB  
**Update**: Berdasarkan analisis git status terbaru

## 🚀 **Kategori 1: SMART ANALYTICS**

### Files yang perlu di-stage:

```bash
# Modified files already in staging/modified state
git add app/Services/AnalyticsService.php
git add app/Livewire/SmartAnalytics.php
git add app/Http/Controllers/ReportsController.php
git add routes/web.php
git add routes/breadcrumbs.php

# New untracked files
git add app/Console/Commands/CalculateDailyAnalytics.php
git add app/Console/Commands/CleanupAnalyticsAlerts.php
git add app/Models/AnalyticsAlert.php
git add app/Models/DailyAnalytics.php
git add app/Models/PerformanceBenchmark.php
git add app/Models/PeriodAnalytics.php
git add database/migrations/2025_01_02_000000_create_analytics_tables.php
git add database/seeders/PerformanceBenchmarkSeeder.php
git add resources/views/livewire/smart-analytics.blade.php
git add resources/views/pages/reports/smart-analytics.blade.php

# One-liner command:
git add app/Services/AnalyticsService.php app/Livewire/SmartAnalytics.php app/Http/Controllers/ReportsController.php routes/web.php routes/breadcrumbs.php app/Console/Commands/CalculateDailyAnalytics.php app/Console/Commands/CleanupAnalyticsAlerts.php app/Models/AnalyticsAlert.php app/Models/DailyAnalytics.php app/Models/PerformanceBenchmark.php app/Models/PeriodAnalytics.php database/migrations/2025_01_02_000000_create_analytics_tables.php database/seeders/PerformanceBenchmarkSeeder.php resources/views/livewire/smart-analytics.blade.php resources/views/pages/reports/smart-analytics.blade.php

# Commit
git commit -m "feat: Implement Smart Analytics system for livestock performance monitoring"
```

---

## 🔐 **Kategori 2: TEMPORARY AUTHORIZATION**

### Files yang perlu di-stage:

```bash
# Modified files
git add app/Models/User.php

# New untracked files
git add app/Http/Middleware/CheckTempAuthorization.php
git add app/Livewire/TempAuthorization.php
git add app/Models/TempAuthAuthorizer.php
git add app/Models/TempAuthLog.php
git add app/Traits/HasTempAuthorization.php
git add app/Console/Commands/ManageTempAuthCommand.php
git add app/Console/Commands/CleanupTempAuth.php
git add config/temp_auth.php
git add database/migrations/2025_06_09_024940_create_temp_auth_authorizers_table.php
git add database/migrations/2025_06_09_025033_create_temp_auth_logs_table.php
git add database/migrations/2025_06_09_032051_add_url_and_namespace_to_temp_auth_logs_table.php
git add database/seeders/TempAuthSeeder.php
git add resources/views/livewire/temp-authorization.blade.php
git add resources/views/livewire/temp-authorization-backup.blade.php

# One-liner command:
git add app/Models/User.php app/Http/Middleware/CheckTempAuthorization.php app/Livewire/TempAuthorization.php app/Models/TempAuthAuthorizer.php app/Models/TempAuthLog.php app/Traits/HasTempAuthorization.php app/Console/Commands/ManageTempAuthCommand.php app/Console/Commands/CleanupTempAuth.php config/temp_auth.php database/migrations/2025_06_09_024940_create_temp_auth_authorizers_table.php database/migrations/2025_06_09_025033_create_temp_auth_logs_table.php database/migrations/2025_06_09_032051_add_url_and_namespace_to_temp_auth_logs_table.php database/seeders/TempAuthSeeder.php resources/views/livewire/temp-authorization.blade.php resources/views/livewire/temp-authorization-backup.blade.php

# Commit
git commit -m "feat: Add temporary authorization system for locked data access"
```

---

## 📊 **Kategori 3: STATUS HISTORY & TRACKING**

### Files yang perlu di-stage:

```bash
# Modified files
git add app/Models/LivestockPurchase.php
git add app/Models/SupplyPurchase.php

# New untracked files
git add app/Models/FeedStatusHistory.php
git add app/Models/LivestockPurchaseStatusHistory.php
git add app/Models/SupplyStatusHistory.php
git add app/Traits/HasFeedStatusHistory.php
git add app/Traits/HasSupplyStatusHistory.php
git add app/Notifications/FeedPurchaseStatusNotification.php
git add app/Notifications/LivestockPurchaseStatusNotification.php
git add app/Notifications/SupplyPurchaseStatusNotification.php
git add database/migrations/2025_06_08_001510_add_status_to_livestock_purchases_table.php
git add database/migrations/2025_06_08_001511_create_livestock_purchase_status_histories_table.php
git add database/migrations/2025_06_10_211446_create_feed_status_histories_table.php
git add database/migrations/2025_06_11_165904_create_supply_status_histories_table.php

# One-liner command:
git add app/Models/LivestockPurchase.php app/Models/SupplyPurchase.php app/Models/FeedStatusHistory.php app/Models/LivestockPurchaseStatusHistory.php app/Models/SupplyStatusHistory.php app/Traits/HasFeedStatusHistory.php app/Traits/HasSupplyStatusHistory.php app/Notifications/FeedPurchaseStatusNotification.php app/Notifications/LivestockPurchaseStatusNotification.php app/Notifications/SupplyPurchaseStatusNotification.php database/migrations/2025_06_08_001510_add_status_to_livestock_purchases_table.php database/migrations/2025_06_08_001511_create_livestock_purchase_status_histories_table.php database/migrations/2025_06_10_211446_create_feed_status_histories_table.php database/migrations/2025_06_11_165904_create_supply_status_histories_table.php

# Commit
git commit -m "feat: Add status history tracking for purchase transactions"
```

---

## 📈 **Kategori 4: PURCHASE REPORTS SYSTEM**

### Files yang perlu di-stage:

```bash
# New untracked files
git add app/Http/Controllers/PurchaseReportsController.php
git add "app/Services/Report/"
git add resources/views/pages/reports/index_report_pembelian_livestock.blade.php
git add resources/views/pages/reports/index_report_pembelian_pakan.blade.php
git add resources/views/pages/reports/index_report_pembelian_supply.blade.php
git add resources/views/pages/reports/pembelian-livestock.blade.php
git add resources/views/pages/reports/pembelian-pakan.blade.php
git add resources/views/pages/reports/pembelian-supply.blade.php
git add resources/views/pages/reports/harian-pdf.blade.php

# One-liner command:
git add app/Http/Controllers/PurchaseReportsController.php "app/Services/Report/" resources/views/pages/reports/index_report_pembelian_livestock.blade.php resources/views/pages/reports/index_report_pembelian_pakan.blade.php resources/views/pages/reports/index_report_pembelian_supply.blade.php resources/views/pages/reports/pembelian-livestock.blade.php resources/views/pages/reports/pembelian-pakan.blade.php resources/views/pages/reports/pembelian-supply.blade.php resources/views/pages/reports/harian-pdf.blade.php

# Commit
git commit -m "feat: Add comprehensive purchase reports system"
```

---

## 🔧 **Kategori 5: LIVESTOCK DATA IMPROVEMENTS**

### Files yang perlu di-stage:

```bash
# Modified files
git add app/Models/Livestock.php
git add app/Models/LivestockDepletion.php
git add app/Models/LivestockPurchaseItem.php
git add app/Models/LivestockSalesItem.php
git add app/Livewire/DataIntegrity/LivestockDataIntegrity.php
git add app/Livewire/LivestockPurchase/Create.php
git add resources/views/livewire/data-integrity/livestock-data-integrity.blade.php
git add resources/views/pages/admin/data-integrity/livestock-integrity-check.blade.php

# New untracked files
git add app/Observers/LivestockDepletionObserver.php
git add database/migrations/2025_06_09_170724_add_metadata_to_livestock_depletions_table.php

# Deleted files (include in commit to record deletion)
git add app/Models/KematianTernak.php
git add app/Models/KonsumsiPakan.php

# One-liner command:
git add app/Models/Livestock.php app/Models/LivestockDepletion.php app/Models/LivestockPurchaseItem.php app/Models/LivestockSalesItem.php app/Livewire/DataIntegrity/LivestockDataIntegrity.php app/Livewire/LivestockPurchase/Create.php resources/views/livewire/data-integrity/livestock-data-integrity.blade.php resources/views/pages/admin/data-integrity/livestock-integrity-check.blade.php app/Observers/LivestockDepletionObserver.php database/migrations/2025_06_09_170724_add_metadata_to_livestock_depletions_table.php app/Models/KematianTernak.php app/Models/KonsumsiPakan.php

# Commit
git commit -m "refactor: Improve livestock data management and integrity checks"
```

---

## 🏗️ **Kategori 6: DATABASE SCHEMA UPDATES**

### Files yang perlu di-stage:

```bash
# Modified files
git add database/migrations/2024_10_16_130340_create_ternaks_table.php
git add database/migrations/2025_04_15_203049_create_feed_management_table.php
git add database/migrations/2025_04_19_105412_create_livestock_management_table.php
git add database/migrations/2025_04_23_100440_create_supply_management_table.php

# Deleted files (include in commit to record deletion)
git add database/migrations/2024_07_19_133528_create_kandangs_table.php
git add database/migrations/2024_12_14_125931_add_end_date_to_kelompok_ternak_table.php

# Also include KelompokTernak model deletion
git add app/Models/KelompokTernak.php

# One-liner command:
git add database/migrations/2024_10_16_130340_create_ternaks_table.php database/migrations/2025_04_15_203049_create_feed_management_table.php database/migrations/2025_04_19_105412_create_livestock_management_table.php database/migrations/2025_04_23_100440_create_supply_management_table.php database/migrations/2024_07_19_133528_create_kandangs_table.php database/migrations/2024_12_14_125931_add_end_date_to_kelompok_ternak_table.php app/Models/KelompokTernak.php

# Commit
git commit -m "refactor: Update database schema and remove obsolete migrations"
```

---

## 🎨 **Kategori 7: UI/UX IMPROVEMENTS**

### Files yang perlu di-stage:

```bash
# Modified files
git add resources/views/layout/master.blade.php
git add resources/views/layouts/style60/_auth.blade.php
git add resources/views/layouts/style60/master.blade.php
git add resources/views/layouts/style60/partials/sidebar-layout/_header.blade.php
git add resources/views/layouts/style60/partials/sidebar-layout/header/_menu/_menu.blade.php
git add resources/views/pages/dashboards/index.blade.php
git add resources/views/pages/masterdata/stok/_table.blade.php

# One-liner command:
git add resources/views/layout/master.blade.php resources/views/layouts/style60/_auth.blade.php resources/views/layouts/style60/master.blade.php resources/views/layouts/style60/partials/sidebar-layout/_header.blade.php resources/views/layouts/style60/partials/sidebar-layout/header/_menu/_menu.blade.php resources/views/pages/dashboards/index.blade.php resources/views/pages/masterdata/stok/_table.blade.php

# Commit
git commit -m "ui: Update layout and improve user interface components"
```

---

## 📝 **Kategori 8: FORMS & TRANSACTIONS**

### Files yang perlu di-stage:

```bash
# Modified files
git add app/Livewire/FeedPurchases/Create.php
git add app/Livewire/SupplyPurchases/Create.php
git add app/Livewire/MasterData/Feed/Create.php
git add app/Livewire/MasterData/Supply/Create.php
git add resources/views/livewire/feed-purchases/create.blade.php
git add resources/views/livewire/livestock-purchase/create.blade.php
git add resources/views/pages/transaction/feed-purchases/_draw-scripts.js
git add resources/views/pages/transaction/feed-purchases/index.blade.php
git add resources/views/pages/transaction/livestock-purchases/_actions.blade.php
git add resources/views/pages/transaction/livestock-purchases/_draw-scripts.js
git add resources/views/pages/transaction/livestock-purchases/index.blade.php
git add resources/views/pages/transaction/supply-purchases/_actions.blade.php
git add resources/views/pages/transaction/supply-purchases/_draw-scripts.js
git add resources/views/pages/transaction/supply-purchases/index.blade.php

# One-liner command:
git add app/Livewire/FeedPurchases/Create.php app/Livewire/SupplyPurchases/Create.php app/Livewire/MasterData/Feed/Create.php app/Livewire/MasterData/Supply/Create.php resources/views/livewire/feed-purchases/create.blade.php resources/views/livewire/livestock-purchase/create.blade.php resources/views/pages/transaction/feed-purchases/_draw-scripts.js resources/views/pages/transaction/feed-purchases/index.blade.php resources/views/pages/transaction/livestock-purchases/_actions.blade.php resources/views/pages/transaction/livestock-purchases/_draw-scripts.js resources/views/pages/transaction/livestock-purchases/index.blade.php resources/views/pages/transaction/supply-purchases/_actions.blade.php resources/views/pages/transaction/supply-purchases/_draw-scripts.js resources/views/pages/transaction/supply-purchases/index.blade.php

# Commit
git commit -m "improve: Enhance transaction forms and data processing"
```

---

## 🔄 **Kategori 9: MODELS & BUSINESS LOGIC**

### Files yang perlu di-stage:

```bash
# Modified files
git add app/Models/Supply.php
git add app/Models/SupplyPurchaseBatch.php
git add app/Models/TransaksiHarian.php
git add app/Models/Feed.php
git add app/Models/FeedPurchase.php
git add app/Models/FeedPurchaseBatch.php
git add app/DataTables/FeedPurchaseDataTable.php
git add app/DataTables/FeedStockDataTable.php
git add app/DataTables/LivestockDataTable.php
git add app/DataTables/LivestockPurchaseDataTable.php
git add app/DataTables/SupplyPurchaseDataTable.php
git add app/DataTables/SupplyStockDataTable.php
git add app/Models/AuditTrail.php
git add app/Models/CurrentLivestock.php
git add app/Models/Farm.php
git add app/Models/Kandang.php

# One-liner command:
git add app/Models/Supply.php app/Models/SupplyPurchaseBatch.php app/Models/TransaksiHarian.php app/Models/Feed.php app/Models/FeedPurchase.php app/Models/FeedPurchaseBatch.php app/DataTables/FeedPurchaseDataTable.php app/DataTables/FeedStockDataTable.php app/DataTables/LivestockDataTable.php app/DataTables/LivestockPurchaseDataTable.php app/DataTables/SupplyPurchaseDataTable.php app/DataTables/SupplyStockDataTable.php app/Models/AuditTrail.php app/Models/CurrentLivestock.php app/Models/Farm.php app/Models/Kandang.php

# Commit
git commit -m "refactor: Update models and improve business logic"
```

---

## ⚙️ **Kategori 10: CONFIGURATION & SETUP**

### Files yang perlu di-stage:

```bash
# Modified files
git add composer.json
git add composer.lock
git add package.json
git add package-lock.json
git add webpack.mix.js
git add app/Providers/AppServiceProvider.php
git add app/Providers/EventServiceProvider.php

# Deleted files (include in commit to record deletion)
git add fetch_menu.php
git add qa_checklist_master_data.php

# One-liner command:
git add composer.json composer.lock package.json package-lock.json webpack.mix.js app/Providers/AppServiceProvider.php app/Providers/EventServiceProvider.php fetch_menu.php qa_checklist_master_data.php

# Commit
git commit -m "config: Update dependencies and system configuration"
```

---

## 🧠 **Kategori 11: SERVICES & CONTROLLERS**

### Files yang perlu di-stage:

```bash
# Modified files
git add app/Http/Controllers/DashboardController.php
git add app/Http/Controllers/FeedController.php
git add app/Http/Controllers/SupplyController.php
git add app/Services/Livestock/LivestockCostService.php
git add app/Services/LivestockDataIntegrityService.php
git add app/Services/SupplyDataIntegrityService.php
git add app/Livewire/Records.php
git add app/Livewire/SupplyDataIntegrity.php

# One-liner command:
git add app/Http/Controllers/DashboardController.php app/Http/Controllers/FeedController.php app/Http/Controllers/SupplyController.php app/Services/Livestock/LivestockCostService.php app/Services/LivestockDataIntegrityService.php app/Services/SupplyDataIntegrityService.php app/Livewire/Records.php app/Livewire/SupplyDataIntegrity.php

# Commit
git commit -m "improve: Enhance services and controllers functionality"
```

---

## 📊 **Kategori 12: REPORTS & VIEWS**

### Files yang perlu di-stage:

```bash
# Modified files
git add resources/views/livewire/qa-checklist-monitor.blade.php
git add resources/views/pages/reports/harian.blade.php
git add resources/views/pages/reports/index_report_harian.blade.php
git add resources/views/pages/reports/index_report_livestock_cost.blade.php
git add resources/views/pages/reports/index_report_performa.blade.php
git add resources/views/pages/reports/livestock-cost.blade.php

# One-liner command:
git add resources/views/livewire/qa-checklist-monitor.blade.php resources/views/pages/reports/harian.blade.php resources/views/pages/reports/index_report_harian.blade.php resources/views/pages/reports/index_report_livestock_cost.blade.php resources/views/pages/reports/index_report_performa.blade.php resources/views/pages/reports/livestock-cost.blade.php

# Commit
git commit -m "improve: Update reports and enhance views"
```

---

## 🧪 **Kategori 13: SEEDERS & DATA**

### Files yang perlu di-stage:

```bash
# Modified files
git add database/seeders/DemoSeeder.php
git add database/seeders/MutationTestSeeder.php
git add database/seeders/OVKSeeder.php
git add database/seeders/SupplyCategorySeeder.php

# New untracked files
git add database/seeders/BasicRecordingSeeder.php
git add database/seeders/ComprehensiveFarmDataSeeder.php
git add database/seeders/ExtendedRecordingSeeder.php
git add database/seeders/ModifyReadOnlyDataPermissionSeeder.php
git add database/seeders/SimpleFarmDataSeeder.php

# One-liner command:
git add database/seeders/DemoSeeder.php database/seeders/MutationTestSeeder.php database/seeders/OVKSeeder.php database/seeders/SupplyCategorySeeder.php database/seeders/BasicRecordingSeeder.php database/seeders/ComprehensiveFarmDataSeeder.php database/seeders/ExtendedRecordingSeeder.php database/seeders/ModifyReadOnlyDataPermissionSeeder.php database/seeders/SimpleFarmDataSeeder.php

# Commit
git commit -m "data: Update seeders and add comprehensive data generators"
```

---

## 📖 **Kategori 14: DOCUMENTATION & TESTING**

### Files yang perlu di-stage:

```bash
# New untracked files
git add PRODUCTION_READY_SUMMARY.md
git add SOLUTION_SUMMARY.md
git add docs/
git add fix_mortality_chart.js
git add testing/
git add tests/Feature/MortalityChartsTest.php
git add app/Console/Commands/GenerateFarmDataCommand.php
git add app/Console/Commands/CleanupDuplicateRevokeLogsCommand.php
git add public/testing/
git add resources/js/
git add resources/views/sample/

# One-liner command:
git add PRODUCTION_READY_SUMMARY.md SOLUTION_SUMMARY.md docs/ fix_mortality_chart.js testing/ tests/Feature/MortalityChartsTest.php app/Console/Commands/GenerateFarmDataCommand.php app/Console/Commands/CleanupDuplicateRevokeLogsCommand.php public/testing/ resources/js/ resources/views/sample/

# Commit
git commit -m "docs: Add comprehensive documentation and testing utilities"
```

---

## 🎯 **QUICK STAGING SCRIPT**

### Create a staging script:

```bash
# Create script file
cat > stage_commits.sh << 'EOF'
#!/bin/bash

echo "🚀 Git Staging Script - Choose category:"
echo "1. Smart Analytics"
echo "2. Temporary Authorization"
echo "3. Status History & Tracking"
echo "4. Purchase Reports System"
echo "5. Livestock Data Improvements"
echo "6. Database Schema Updates"
echo "7. UI/UX Improvements"
echo "8. Forms & Transactions"
echo "9. Models & Business Logic"
echo "10. Configuration & Setup"
echo "11. Services & Controllers"
echo "12. Reports & Views"
echo "13. Seeders & Data"
echo "14. Documentation & Testing"

read -p "Enter category number: " category

case $category in
    1)
        git add app/Services/AnalyticsService.php app/Livewire/SmartAnalytics.php app/Http/Controllers/ReportsController.php routes/web.php routes/breadcrumbs.php app/Console/Commands/CalculateDailyAnalytics.php app/Console/Commands/CleanupAnalyticsAlerts.php app/Models/AnalyticsAlert.php app/Models/DailyAnalytics.php app/Models/PerformanceBenchmark.php app/Models/PeriodAnalytics.php database/migrations/2025_01_02_000000_create_analytics_tables.php database/seeders/PerformanceBenchmarkSeeder.php resources/views/livewire/smart-analytics.blade.php resources/views/pages/reports/smart-analytics.blade.php
        echo "Smart Analytics files staged. Ready to commit with: git commit -m 'feat: Implement Smart Analytics system for livestock performance monitoring'"
        ;;
    2)
        git add app/Models/User.php app/Http/Middleware/CheckTempAuthorization.php app/Livewire/TempAuthorization.php app/Models/TempAuthAuthorizer.php app/Models/TempAuthLog.php app/Traits/HasTempAuthorization.php app/Console/Commands/ManageTempAuthCommand.php app/Console/Commands/CleanupTempAuth.php config/temp_auth.php database/migrations/2025_06_09_024940_create_temp_auth_authorizers_table.php database/migrations/2025_06_09_025033_create_temp_auth_logs_table.php database/migrations/2025_06_09_032051_add_url_and_namespace_to_temp_auth_logs_table.php database/seeders/TempAuthSeeder.php resources/views/livewire/temp-authorization.blade.php resources/views/livewire/temp-authorization-backup.blade.php
        echo "Temporary Authorization files staged. Ready to commit with: git commit -m 'feat: Add temporary authorization system for locked data access'"
        ;;
    *)
        echo "Invalid selection. Use categories 1-14"
        ;;
esac
EOF

chmod +x stage_commits.sh
```

---

## ⚠️ **PENTING - SEBELUM COMMIT:**

1. **Double check files** dengan `git status` setelah staging
2. **Review changes** dengan `git diff --cached`
3. **Test functionality** untuk memastikan tidak ada breaking changes
4. **Follow conventional commits** format sesuai yang disediakan

## 🎯 **QUICK VERIFICATION:**

```bash
# Cek status setelah staging
git status

# Review changes yang akan di-commit
git diff --cached

# Reset staging jika perlu
git reset HEAD
```
 