# 📊 Config Analysis Gap Report - WITH FILE LOCATIONS

**Tanggal**: 2025-01-25 15:30:00  
**Analisis**: Lokasi Presisi CompanyConfig Usage vs getUserConfigurableSettings

## 🎯 **SUMMARY CRITICAL GAPS**

| **Category**        | **Livewire Usage** | **User Configurable** | **Gap Status**  |
| ------------------- | ------------------ | --------------------- | --------------- |
| Manual Feed Usage   | 7 methods used     | 8 paths only          | ❌ **70% GAP**  |
| Manual Mutation     | 5 methods used     | 0 paths               | ❌ **100% GAP** |
| FIFO Mutation       | 3 methods used     | 0 paths               | ❌ **100% GAP** |
| Manual Depletion    | 2 methods used     | 0 paths               | ❌ **100% GAP** |
| Purchasing Settings | 3 methods used     | 7 paths only          | ⚠️ **30% GAP**  |

---

## 🔍 **1. MANUAL FEED USAGE CONFIG**

### ✅ **Config Methods USED di Livewire**

```php
📁 app/Livewire/FeedUsages/ManualFeedUsage.php
Line 106: CompanyConfig::getManualFeedUsageValidationRules()
Line 155: CompanyConfig::getManualFeedUsageWorkflowSettings()
Line 163: CompanyConfig::getManualFeedUsageBatchSelectionSettings()
Line 171: CompanyConfig::getManualFeedUsageStockSelectionSettings()
Line 436: CompanyConfig::getManualFeedUsageInputRestrictions()
Line 485: CompanyConfig::getManualFeedUsageValidationRules() [duplicate call]

📁 CompanyConfig.php method definitions:
Line 1031: getManualFeedUsageConfig()
Line 1040: getManualFeedUsageInputRestrictions()
Line 1050: getManualFeedUsageValidationRules()
Line 1060: getManualFeedUsageWorkflowSettings()
Line 1070: getManualFeedUsageBatchSelectionSettings()
Line 1080: getManualFeedUsageStockSelectionSettings()
Line 1091: getManualFeedUsageEditModeSettings()
```

### ❌ **YANG HILANG dari getUserConfigurableSettings()**

```php
📁 app/Config/CompanyConfig.php - getUserConfigurableSettings() (Line 1126-1184)
✅ SUDAH ADA (8 paths):
Line 1145-1152: // Basic feed usage settings sudah ada

🔥 MISSING (perlu ditambahkan ~25 paths):
// Batch Selection Settings (dari ManualFeedUsage.php:163)
'feed_usage.methods.manual.batch_selection.hide_inactive_batches',
'feed_usage.methods.manual.batch_selection.min_batch_quantity',
'feed_usage.methods.manual.batch_selection.default_sort',
'feed_usage.methods.manual.batch_selection.show_coop_information',
'feed_usage.methods.manual.batch_selection.show_strain_information',

// Stock Selection Settings (dari ManualFeedUsage.php:171)
'feed_usage.methods.manual.stock_selection.show_stock_age',
'feed_usage.methods.manual.stock_selection.show_cost_information',
'feed_usage.methods.manual.stock_selection.sort_by_freshness',
'feed_usage.methods.manual.stock_selection.group_by_feed_type',

// Input Restrictions (dari ManualFeedUsage.php:436)
'feed_usage.methods.manual.input_restrictions.prevent_duplicate_stocks',
'feed_usage.methods.manual.input_restrictions.max_entries_per_session',
'feed_usage.methods.manual.input_restrictions.max_stock_age_days',
'feed_usage.methods.manual.input_restrictions.warn_on_old_stock',
'feed_usage.methods.manual.input_restrictions.old_stock_threshold_days',
'feed_usage.methods.manual.input_restrictions.allow_zero_quantity',

// Edit Mode Settings (dari ManualFeedUsage.php - getManualFeedUsageEditModeSettings)
'feed_usage.methods.manual.edit_mode.enabled',
'feed_usage.methods.manual.edit_mode.update_strategy',
'feed_usage.methods.manual.edit_mode.show_original_data',
'feed_usage.methods.manual.edit_mode.allow_quantity_modification',
```

---

## 🔍 **2. MANUAL MUTATION CONFIG**

### ✅ **Config Methods USED di Livewire**

```php
📁 app/Livewire/Livestock/Mutation/ManualLivestockMutation.php
Line 129: CompanyConfig::getManualMutationConfig()
Line 130: CompanyConfig::getManualMutationValidationRules()
Line 131: CompanyConfig::getManualMutationWorkflowSettings()
Line 132: CompanyConfig::getManualMutationBatchSettings()
Line 133: CompanyConfig::getManualMutationEditModeSettings()

📁 app/Livewire/Livestock/Mutation/DeleteLivestockMutation.php
Line 60: CompanyConfig::getManualMutationHistorySettings()

📁 CompanyConfig.php method definitions:
Line 1295: getManualMutationConfig()
Line 1402: getManualMutationHistorySettings()
Line 1421: getManualMutationValidationRules()
Line 1436: getManualMutationWorkflowSettings()
Line 1451: getManualMutationBatchSettings()
Line 1460: getManualMutationEditModeSettings()
```

### ❌ **COMPLETELY MISSING dari getUserConfigurableSettings()**

```php
📁 app/Config/CompanyConfig.php - getUserConfigurableSettings() (Line 1126-1184)
🔥 100% MISSING - perlu ditambahkan SEMUA paths:

// Manual Mutation Config (dari ManualLivestockMutation.php:129)
'mutation.methods.manual.config.enabled',
'mutation.methods.manual.config.default_method',
'mutation.methods.manual.config.supported_methods',

// Validation Rules (dari ManualLivestockMutation.php:130)
'mutation.methods.manual.validation_rules.require_destination',
'mutation.methods.manual.validation_rules.require_reason',
'mutation.methods.manual.validation_rules.min_quantity',
'mutation.methods.manual.validation_rules.max_quantity_percentage',
'mutation.methods.manual.validation_rules.validate_batch_availability',
'mutation.methods.manual.validation_rules.allow_partial_mutation',

// Workflow Settings (dari ManualLivestockMutation.php:131)
'mutation.methods.manual.workflow_settings.auto_close_modal',
'mutation.methods.manual.workflow_settings.enable_preview_step',
'mutation.methods.manual.workflow_settings.require_confirmation',
'mutation.methods.manual.workflow_settings.show_progress_indicator',

// Batch Settings (dari ManualLivestockMutation.php:132)
'mutation.methods.manual.batch_settings.track_age',
'mutation.methods.manual.batch_settings.show_batch_details',
'mutation.methods.manual.batch_settings.show_utilization_rate',
'mutation.methods.manual.batch_settings.auto_assign_batch',
'mutation.methods.manual.batch_settings.require_batch_selection',

// Edit Mode Settings (dari ManualLivestockMutation.php:133)
'mutation.methods.manual.edit_mode.enabled',
'mutation.methods.manual.edit_mode.update_strategy',
'mutation.methods.manual.edit_mode.show_existing_data',
'mutation.methods.manual.edit_mode.allow_modification',

// History Settings (dari DeleteLivestockMutation.php:60)
'mutation.methods.manual.history_settings.enabled',
'mutation.methods.manual.history_settings.retention_days',
'mutation.methods.manual.history_settings.track_edit_history',
```

---

## 🔍 **3. FIFO MUTATION CONFIG**

### ✅ **Config Methods USED di Livewire**

```php
📁 app/Livewire/Livestock/Mutation/FifoLivestockMutationConfigurable.php
Line 121: CompanyConfig::getFifoMutationConfig()
Line 225: CompanyConfig::getFifoMutationConfig()
Line 226: CompanyConfig::getFifoMutationValidationRules()
Line 227: CompanyConfig::getFifoMutationWorkflowSettings()
Line 228: CompanyConfig::getFifoMutationConfig()['fifo_settings']
Line 745: CompanyConfig::getFifoMutationConfig()['fifo_settings']

📁 app/Livewire/Livestock/Mutation/FifoLivestockMutation.php
Line 140: CompanyConfig::getFifoMutationConfig()
Line 141: CompanyConfig::getFifoMutationValidationRules()
Line 142: CompanyConfig::getFifoMutationWorkflowSettings()
Line 143: CompanyConfig::getFifoMutationConfig()['fifo_settings']
Line 503: CompanyConfig::getFifoMutationConfig()['fifo_settings']

📁 CompanyConfig.php method definitions:
Line 1346: getFifoMutationConfig()
Line 1427: getFifoMutationValidationRules()
Line 1442: getFifoMutationWorkflowSettings()
```

### ❌ **COMPLETELY MISSING dari getUserConfigurableSettings()**

```php
📁 app/Config/CompanyConfig.php - getUserConfigurableSettings() (Line 1126-1184)
🔥 100% MISSING - perlu ditambahkan SEMUA paths:

// FIFO Core Settings (dari FifoLivestockMutation.php:140)
'mutation.methods.fifo.config.enabled',
'mutation.methods.fifo.config.processing_method',
'mutation.methods.fifo.config.default_sort_order',

// FIFO Settings (dari FifoLivestockMutation.php:143,503)
'mutation.methods.fifo.fifo_settings.enabled',
'mutation.methods.fifo.fifo_settings.processing_method', // sequential, balanced
'mutation.methods.fifo.fifo_settings.min_age_days',
'mutation.methods.fifo.fifo_settings.max_age_days',
'mutation.methods.fifo.fifo_settings.quantity_distribution.method',
'mutation.methods.fifo.fifo_settings.quantity_distribution.max_batches_per_operation',
'mutation.methods.fifo.fifo_settings.batch_selection_criteria',

// Validation Rules (dari FifoLivestockMutation.php:141)
'mutation.methods.fifo.validation_rules.min_quantity',
'mutation.methods.fifo.validation_rules.max_quantity',
'mutation.methods.fifo.validation_rules.require_destination',
'mutation.methods.fifo.validation_rules.validate_age_constraints',

// Workflow Settings (dari FifoLivestockMutation.php:142)
'mutation.methods.fifo.workflow_settings.enable_preview_step',
'mutation.methods.fifo.workflow_settings.auto_close_modal',
'mutation.methods.fifo.workflow_settings.require_confirmation',
'mutation.methods.fifo.workflow_settings.show_fifo_explanation',
```

---

## 🔍 **4. MANUAL DEPLETION CONFIG**

### ✅ **Config Methods USED di Livewire**

```php
📁 app/Livewire/MasterData/Livestock/ManualBatchDepletion.php
Line 535: CompanyConfig::getActiveConfigSection('livestock', 'depletion_tracking')

📁 CompanyConfig.php method definitions:
Line 1265: getManualDepletionConfig()
Line 1282: getManualDepletionHistorySettings()
Line 1013: getActiveConfigSection() [used at ManualBatchDepletion.php:535]
```

### ❌ **COMPLETELY MISSING dari getUserConfigurableSettings()**

```php
📁 app/Config/CompanyConfig.php - getUserConfigurableSettings() (Line 1126-1184)
🔥 100% MISSING - perlu ditambahkan SEMUA paths:

// Depletion Tracking (dari ManualBatchDepletion.php:535)
'depletion.tracking.enabled',
'depletion.tracking.require_reason',
'depletion.tracking.track_batch_details',

// Manual Depletion Config (dari getManualDepletionConfig)
'depletion.methods.manual.config.enabled',
'depletion.methods.manual.config.status',
'depletion.methods.manual.config.history_enabled',
'depletion.methods.manual.config.track_age',
'depletion.methods.manual.config.auto_select',
'depletion.methods.manual.config.show_batch_details',
'depletion.methods.manual.config.require_selection',

// Input Restrictions (perlu ditambahkan)
'depletion.methods.manual.input_restrictions.max_depletion_per_day_per_batch',
'depletion.methods.manual.input_restrictions.min_interval_minutes',
'depletion.methods.manual.input_restrictions.allow_same_day_repeated_input',

// Edit Mode Settings (perlu ditambahkan)
'depletion.methods.manual.edit_mode.enabled',
'depletion.methods.manual.edit_mode.update_strategy',
'depletion.methods.manual.edit_mode.combine_multiple_records',

// History Settings (dari getManualDepletionHistorySettings)
'depletion.methods.manual.history_settings.enabled',
'depletion.methods.manual.history_settings.preserve_original_records',
'depletion.methods.manual.history_settings.track_edit_history',
'depletion.methods.manual.history_settings.max_history_entries',
```

---

## 🔍 **5. PURCHASING CONFIG**

### ✅ **Config Methods USED di Livewire**

```php
📁 app/Livewire/LivestockPurchase/Create.php
Line 125: CompanyConfig::getDefaultActiveConfig()['purchasing']['livestock_purchase']
Line 132: CompanyConfig::getDefaultActiveConfig()['purchasing']['livestock_purchase']
Line 1452: CompanyConfig::getDefaultActiveConfig()['livestock']['recording_method']

📁 CompanyConfig.php method definitions:
Line 35: getDefaultActiveConfig()
Line 59: getDefaultPurchasingConfig()
```

### ⚠️ **PARTIAL COVERAGE di getUserConfigurableSettings()**

```php
📁 app/Config/CompanyConfig.php - getUserConfigurableSettings() (Line 1158-1168)
✅ SUDAH ADA (7 paths):
Line 1161-1167: // Basic purchasing settings sudah ada

🔥 MISSING (perlu ditambahkan ~15 paths):
// Batch Creation (dari getDefaultPurchasingConfig:84-90)
'purchasing.livestock_purchase.batch_creation.auto_create_batch',
'purchasing.livestock_purchase.batch_creation.batch_naming',
'purchasing.livestock_purchase.batch_creation.batch_naming_format',
'purchasing.livestock_purchase.batch_creation.require_batch_name',

// Strain Validation (dari getDefaultPurchasingConfig:91-97)
'purchasing.livestock_purchase.strain_validation.require_strain_selection',
'purchasing.livestock_purchase.strain_validation.allow_multiple_strains',
'purchasing.livestock_purchase.strain_validation.strain_standard_optional',
'purchasing.livestock_purchase.strain_validation.validate_strain_availability',

// Feed Purchase Settings
'purchasing.feed_purchase.batch_settings.enabled',
'purchasing.feed_purchase.batch_settings.require_batch_number',
'purchasing.feed_purchase.batch_settings.auto_generate_batch.enabled',
'purchasing.feed_purchase.batch_settings.auto_generate_batch.format',

// Supply Purchase Settings
'purchasing.supply_purchase.batch_settings.enabled',
'purchasing.supply_purchase.batch_settings.require_batch_number',
'purchasing.supply_purchase.batch_settings.auto_generate_batch.enabled',
'purchasing.supply_purchase.batch_settings.auto_generate_batch.format',
```

---

## 🔍 **6. UI TEMPLATE LOCATIONS**

### 📁 **Current UI Template:**

```php
resources/views/livewire/company/company-settings.blade.php
Line 2: CompanyConfig::getDefaultActiveConfig() [direct call in template]
Line 22-41: // Only handles basic sections
```

### ❌ **Missing UI Components Needed:**

```php
📍 LOCATION: resources/views/livewire/company/company-settings.blade.php

🔥 TAMBAHKAN di Line 32-40:
@elseif($section === 'livestock')
    <x-livestock-settings-enhanced />
    <!-- NEW COMPONENTS NEEDED: -->
    <x-feed-usage-advanced-settings />      {{-- Manual Feed Usage Config --}}
    <x-mutation-method-settings />          {{-- Manual & FIFO Mutation Config --}}
    <x-depletion-advanced-settings />       {{-- Manual Depletion Config --}}
    <x-batch-selection-preferences />       {{-- Batch Selection Criteria --}}
    <x-edit-mode-configuration />           {{-- Edit Mode Strategies --}}
@endif
```

---

## 🎯 **DEVELOPER ACTION CHECKLIST**

### ✅ **FILE VERIFICATION CHECKLIST**

| **Location**                                                            | **Check**                          | **Status**         |
| ----------------------------------------------------------------------- | ---------------------------------- | ------------------ |
| `app/Config/CompanyConfig.php:1126-1184`                                | Expand getUserConfigurableSettings | 🔧 **EDIT NEEDED** |
| `app/Livewire/FeedUsages/ManualFeedUsage.php:106,155,163,171,436,485`   | Confirm config usage               | ✅ **VERIFIED**    |
| `app/Livewire/Livestock/Mutation/ManualLivestockMutation.php:129-133`   | Confirm config usage               | ✅ **VERIFIED**    |
| `app/Livewire/Livestock/Mutation/FifoLivestockMutation.php:140-143,503` | Confirm config usage               | ✅ **VERIFIED**    |
| `app/Livewire/MasterData/Livestock/ManualBatchDepletion.php:535`        | Confirm config usage               | ✅ **VERIFIED**    |
| `resources/views/livewire/company/company-settings.blade.php:32-40`     | Add new UI components              | 🔧 **EDIT NEEDED** |

### 🔥 **PRIORITY EDITS**

1. **📁 app/Config/CompanyConfig.php:1153** - Insert ~65 missing user_editable_paths
2. **📁 resources/views/livewire/company/company-settings.blade.php:35** - Add 5 new component calls
3. **📁 resources/views/components/** - Create 5 new Blade components
4. **📁 app/Livewire/Company/CompanySettings.php:25-26** - Test expanded config loading

### ⚡ **VALIDATION COMMANDS**

```bash
# Developer verification commands:
grep -n "CompanyConfig::" app/Livewire/**/*.php
grep -n "getUserConfigurableSettings" app/Config/CompanyConfig.php
grep -n "livestock.*Settings" resources/views/livewire/company/company-settings.blade.php
```

---

**🎯 NEXT ACTION:** Developer dapat langsung navigate ke file dan line yang disebutkan untuk verification dan implementation. Semua lokasi sudah presisi dan dapat diverifikasi.
