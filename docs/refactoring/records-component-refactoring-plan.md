# Records Component Refactoring Plan

**File:** `app/Livewire/Records.php`  
**Current Size:** 3596 lines  
**Target:** Reduce to ~1000-1500 lines by extracting business logic

## 📊 Current Analysis

### Component Size Breakdown

-   **Total Lines:** 3596
-   **Public Methods:** 15+
-   **Private Methods:** 40+
-   **Properties:** 30+
-   **Complexity:** Very High

### Main Issues

1. **Single Responsibility Violation:** Component handles UI, business logic, data processing, and background calculations
2. **High Complexity:** Save method alone is 400+ lines
3. **Poor Testability:** Business logic mixed with Livewire component
4. **Maintenance Difficulty:** Hard to debug and modify
5. **Performance Impact:** Heavy calculations in main thread

---

## 🎯 Refactoring Strategy

### Phase 1: Extract Data Processing Services

**Priority:** High | **Impact:** Major reduction in component size

#### 1.1 Create `RecordingDataService`

**Extract Functions:**

-   `loadYesterdayData()` (150+ lines)
-   `loadRecordingData()` (80+ lines)
-   `checkCurrentLivestockStock()` (60+ lines)
-   `generateYesterdaySummary()` (20+ lines)
-   `resetYesterdayData()` (20+ lines)

**Benefits:**

-   Reduces component by ~330 lines
-   Centralizes data loading logic
-   Improves caching opportunities
-   Better testability

```php
// app/Services/Recording/RecordingDataService.php
class RecordingDataService
{
    public function loadYesterdayData(int $livestockId, string $date): array
    public function loadRecordingData(int $livestockId): Collection
    public function getCurrentLivestockStock(int $livestockId): ?array
    public function generateDataSummary(array $data): string
}
```

#### 1.2 Create `StockManagementService`

**Extract Functions:**

-   `checkStockByTernakId()` (20+ lines)
-   `loadStockData()` (20+ lines)
-   `initializeItemQuantities()` (20+ lines)
-   `loadAvailableSupplies()` (40+ lines)
-   `checkSupplyStock()` (20+ lines)

**Benefits:**

-   Reduces component by ~120 lines
-   Reusable across components
-   Better stock validation logic

```php
// app/Services/Stock/StockManagementService.php
class StockManagementService
{
    public function getAvailableStockByLivestock(int $livestockId): Collection
    public function getAvailableSupplies(int $livestockId): Collection
    public function validateStockAvailability(int $livestockId, array $quantities): bool
}
```

### Phase 2: Extract Calculation Services

**Priority:** High | **Impact:** Performance improvement

#### 2.1 Create `PerformanceMetricsService`

**Extract Functions:**

-   `calculatePerformanceMetrics()` (50+ lines)
-   `getPopulationHistory()` (80+ lines)
-   `getDetailedOutflowHistory()` (30+ lines)
-   `getWeightHistory()` (60+ lines)
-   `getFeedConsumptionHistory()` (80+ lines)

**Benefits:**

-   Reduces component by ~300 lines
-   Can be moved to background jobs
-   Better caching for metrics
-   Reusable for reports

```php
// app/Services/Analytics/PerformanceMetricsService.php
class PerformanceMetricsService
{
    public function calculateMetrics(int $livestockId, Carbon $date): array
    public function getPopulationHistory(int $livestockId, Carbon $endDate): array
    public function getWeightHistory(int $livestockId, Carbon $endDate): array
    public function getFeedHistory(int $livestockId, Carbon $endDate): array
}
```

#### 2.2 Create `PayloadBuilderService`

**Extract Functions:**

-   `buildStructuredPayload()` (150+ lines)
-   `getDetailedUnitInfo()` (80+ lines)
-   `getDetailedSupplyUnitInfo()` (80+ lines)
-   `getStockDetails()` (100+ lines)
-   `getSupplyStockDetails()` (100+ lines)

**Benefits:**

-   Reduces component by ~510 lines
-   Standardizes payload structure
-   Better versioning support

```php
// app/Services/Recording/PayloadBuilderService.php
class PayloadBuilderService
{
    public function buildPayload(array $data): array
    public function getUnitInformation(Feed $feed, float $quantity): array
    public function getStockDetails(int $feedId, int $livestockId): array
}
```

### Phase 3: Extract Processing Services

**Priority:** Medium | **Impact:** Better separation of concerns

#### 3.1 Create `FeedUsageProcessor`

**Extract Functions:**

-   `saveFeedUsageWithTracking()` (120+ lines)
-   `hasUsageChanged()` (20+ lines)

**Benefits:**

-   Reduces component by ~140 lines
-   Dedicated feed processing logic
-   Better error handling

```php
// app/Services/Feed/FeedUsageProcessor.php
class FeedUsageProcessor
{
    public function processFeedUsage(array $usages, int $livestockId, string $date): FeedUsage
    public function updateExistingUsage(FeedUsage $usage, array $newUsages): bool
    public function revertUsageDetails(FeedUsage $usage): void
}
```

#### 3.2 Create `SupplyUsageProcessor`

**Extract Functions:**

-   `saveSupplyUsageWithTracking()` (120+ lines)
-   `processSupplyUsageDetail()` (70+ lines)
-   `hasSupplyUsageChanged()` (20+ lines)

**Benefits:**

-   Reduces component by ~210 lines
-   Dedicated supply processing logic

```php
// app/Services/Supply/SupplyUsageProcessor.php
class SupplyUsageProcessor
{
    public function processSupplyUsage(array $usages, int $livestockId, string $date): SupplyUsage
    public function processUsageDetail(SupplyUsage $usage, array $usageData): void
}
```

#### 3.3 Create `DepletionProcessor`

**Extract Functions:**

-   `storeDeplesiWithDetails()` (100+ lines)
-   `shouldUseFifoDepletion()` (90+ lines)
-   `storeDeplesiWithFifo()` (80+ lines)
-   `previewFifoDepletion()` (50+ lines)
-   `getFifoDepletionStats()` (30+ lines)

**Benefits:**

-   Reduces component by ~350 lines
-   Centralized depletion logic
-   Better FIFO handling

```php
// app/Services/Livestock/DepletionProcessor.php
class DepletionProcessor
{
    public function processDepletion(string $type, int $quantity, int $recordingId, int $livestockId): LivestockDepletion
    public function shouldUseFifo(Livestock $livestock, string $type): bool
    public function previewFifoDistribution(string $type, int $quantity, int $livestockId): ?array
}
```

### Phase 4: Extract Background Jobs

**Priority:** Medium | **Impact:** Performance improvement

#### 4.1 Create Background Jobs for Heavy Operations

```php
// app/Jobs/CalculatePerformanceMetricsJob.php
class CalculatePerformanceMetricsJob implements ShouldQueue
{
    public function handle(PerformanceMetricsService $service): void
    {
        // Calculate and cache performance metrics
    }
}

// app/Jobs/UpdateLivestockQuantityJob.php
class UpdateLivestockQuantityJob implements ShouldQueue
{
    public function handle(): void
    {
        // Update livestock quantities with history tracking
    }
}

// app/Jobs/RecalculateCostDataJob.php
class RecalculateCostDataJob implements ShouldQueue
{
    public function handle(LivestockCostService $service): void
    {
        // Recalculate cost data in background
    }
}
```

### Phase 5: Create Specialized Classes

**Priority:** Low | **Impact:** Better organization

#### 5.1 Create Data Transfer Objects (DTOs)

```php
// app/DTOs/RecordingData.php
class RecordingData
{
    public function __construct(
        public int $livestockId,
        public string $date,
        public int $age,
        public array $feedUsages,
        public array $supplyUsages,
        public array $depletionData
    ) {}
}

// app/DTOs/PerformanceMetrics.php
class PerformanceMetrics
{
    public function __construct(
        public float $liveability,
        public float $fcr,
        public float $adg,
        public float $ip
    ) {}
}
```

#### 5.2 Create Form Handlers

```php
// app/Http/Livewire/Forms/RecordingForm.php
class RecordingForm extends Form
{
    public string $date = '';
    public int $mortality = 0;
    public int $culling = 0;
    public array $itemQuantities = [];

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'mortality' => 'nullable|integer|min:0',
            'culling' => 'nullable|integer|min:0',
        ];
    }
}
```

---

## 📈 Expected Results After Refactoring

### Component Size Reduction

-   **Current:** 3596 lines
-   **Target:** 1200-1500 lines
-   **Reduction:** ~60% smaller

### Performance Improvements

-   Heavy calculations moved to background jobs
-   Better caching opportunities
-   Reduced memory usage in main thread

### Maintainability Improvements

-   Single responsibility per service
-   Better testability
-   Easier debugging
-   Cleaner code organization

### Architecture Benefits

-   Service-oriented architecture
-   Better separation of concerns
-   Reusable business logic
-   Scalable design

---

## 🚀 Implementation Priority

### Phase 1 (Week 1-2): Critical Extractions

1. `RecordingDataService` - **High Impact**
2. `PayloadBuilderService` - **High Impact**
3. `PerformanceMetricsService` - **Medium Impact**

### Phase 2 (Week 3-4): Processing Services

1. `FeedUsageProcessor` - **Medium Impact**
2. `SupplyUsageProcessor` - **Medium Impact**
3. `DepletionProcessor` - **Medium Impact**

### Phase 3 (Week 5-6): Background Jobs & Optimization

1. Background jobs implementation
2. Caching strategies
3. Performance testing

### Phase 4 (Week 7-8): Final Polish

1. DTOs and Form handlers
2. Documentation updates
3. Testing and validation

---

## 🔧 Implementation Notes

### Backward Compatibility

-   Maintain existing public API
-   Gradual migration approach
-   Comprehensive testing

### Testing Strategy

-   Unit tests for each service
-   Integration tests for component
-   Performance benchmarks

### Monitoring

-   Log service performance
-   Monitor background job execution
-   Track component load times

---

## 📋 Action Items

### Immediate (This Week)

-   [ ] Create service directory structure
-   [ ] Extract `RecordingDataService`
-   [ ] Extract `StockManagementService`
-   [ ] Update component to use new services

### Short Term (Next 2 Weeks)

-   [ ] Extract calculation services
-   [ ] Implement background jobs
-   [ ] Add comprehensive testing

### Long Term (Next Month)

-   [ ] Complete all extractions
-   [ ] Performance optimization
-   [ ] Documentation updates
-   [ ] Team training on new architecture

---

**Total Estimated Reduction: ~2000 lines (55% reduction)**  
**Estimated Development Time: 6-8 weeks**  
**Risk Level: Medium (with proper testing)**
