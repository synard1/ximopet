# Analytics Dashboard Debugging Guide

## Debugging Tools Added

### 1. Extensive Console Logging

Semua aktivitas analytics sekarang ter-log dengan prefix `[Analytics Debug]`:

**Frontend (JavaScript):**

-   Livewire event tracking (request, finished, load, update)
-   Chart initialization process
-   Timeout mechanism monitoring
-   Element visibility detection
-   Error handling

**Backend (PHP):**

-   Component mounting process
-   Analytics service calls
-   Data processing steps
-   Execution time tracking
-   Error details with stack traces

### 2. Manual Debug Functions

#### `debugAnalytics()`

Fungsi untuk check status loading secara manual:

```javascript
// Panggil di browser console atau klik tombol Debug
debugAnalytics();
```

Output akan menampilkan:

-   Status loading overlay (visible/hidden)
-   Element properties (display, classes)
-   Elapsed loading time
-   Timeout status

#### `forceHideLoading()`

Fungsi untuk force hide loading overlay:

```javascript
// Panggil di browser console atau klik tombol Force Hide
forceHideLoading();
```

### 3. UI Debug Tools

Ditambahkan tombol debug di dashboard:

-   **Debug Button**: Menampilkan status loading saat ini
-   **Force Hide Button**: Paksa sembunyikan loading overlay

## Debugging Steps

### Step 1: Monitor Console Logs

1. Buka Developer Tools (F12)
2. Buka tab Console
3. Filter dengan keyword: `Analytics Debug`
4. Refresh halaman analytics dashboard

### Step 2: Check Loading Timeline

Log akan menampilkan timeline lengkap:

```
[Analytics Debug] Livewire initialized
[Analytics Debug] Starting component mount
[Analytics Debug] Loading farms and coops data
[Analytics Debug] Default date range set
[Analytics Debug] Calling refreshAnalytics to load real data
[Analytics Debug] Starting refreshAnalytics
[Analytics Debug] Building filters for analytics service
[Analytics Debug] Calling AnalyticsService->getSmartInsights
[Analytics Debug] Received insights from service
[Analytics Debug] Dispatching analytics-updated event
[Analytics Debug] Analytics refreshed successfully
[Analytics Debug] Setting isLoading to false
```

### Step 3: Monitor Backend Logs

```bash
# Monitor real-time logs
tail -f storage/logs/laravel.log | findstr "Analytics Debug"

# Or check recent logs
tail -100 storage/logs/laravel.log | findstr "Analytics Debug"
```

### Step 4: Use Manual Debug Tools

Jika loading stuck > 15 detik:

1. Klik tombol **Debug** untuk check status
2. Check console untuk detailed info
3. Jika perlu, klik **Force Hide** untuk bypass loading

## Common Issues & Solutions

### Issue 1: Loading Stuck > 15 Detik

**Diagnosis:**

```javascript
debugAnalytics();
// Check output di console
```

**Solutions:**

1. Check backend logs untuk error
2. Verifikasi database connection
3. Use `forceHideLoading()` untuk bypass
4. Refresh page jika perlu

### Issue 2: Livewire Events Tidak Fire

**Diagnosis:**
Check console untuk event logs:

```
[Analytics Debug] Livewire request started
[Analytics Debug] Livewire request finished
```

**Solutions:**

1. Verifikasi Livewire version compatibility
2. Check untuk JavaScript errors
3. Ensure proper event listener registration

### Issue 3: Charts Tidak Render

**Diagnosis:**
Check console untuk chart logs:

```
[Analytics Debug] Creating mortality chart
[Analytics Debug] Chart data loaded: {...}
```

**Solutions:**

1. Verifikasi Chart.js loading
2. Check data format dan struktur
3. Ensure canvas elements exist

### Issue 4: Backend Service Timeout

**Diagnosis:**
Check backend logs untuk execution time:

```
[Analytics Debug] Analytics refreshed successfully
execution_time_ms: 5000
```

**Solutions:**

1. Optimize database queries
2. Add database indexes
3. Consider caching strategies
4. Break down large operations

## Debugging Commands

### Laravel Logs

```bash
# Clear logs
echo "" > storage/logs/laravel.log

# Monitor real-time
tail -f storage/logs/laravel.log

# Filter analytics only
tail -f storage/logs/laravel.log | grep "Analytics Debug"

# Check recent errors
tail -100 storage/logs/laravel.log | grep "ERROR"
```

### Database Checks

```bash
# Check analytics data
php artisan tinker
>> App\Models\DailyAnalytics::count()
>> App\Models\DailyAnalytics::latest()->take(5)->get()

# Test analytics service
>> app(App\Services\AnalyticsService::class)->getSmartInsights([])
```

### Performance Monitoring

```bash
# Check analytics calculation
php artisan analytics:daily-calculate --days=1 --force

# Monitor execution time
time php artisan analytics:daily-calculate --days=1 --force
```

## Timeout Mechanism

### Multiple Timeout Layers:

1. **Primary Timeout**: 15 seconds dengan Livewire events
2. **Fallback Timeout**: 20 seconds independent
3. **Manual Override**: Debug tools untuk force hide

### Timeout Configuration:

```javascript
// Primary timeout (adjustable)
setTimeout(() => {
    /* hide overlay */
}, 15000);

// Fallback timeout (adjustable)
setTimeout(() => {
    /* force hide */
}, 20000);
```

## Log Analysis

### Successful Loading Pattern:

```
[Analytics Debug] Starting refreshAnalytics
[Analytics Debug] Calling AnalyticsService->getSmartInsights
[Analytics Debug] Received insights from service
[Analytics Debug] Analytics refreshed successfully
execution_time_ms: 1234
```

### Error Pattern:

```
[Analytics Debug] Starting refreshAnalytics
[Analytics Debug] Calling AnalyticsService->getSmartInsights
[Analytics Debug] Failed to refresh analytics
error_message: "Database connection timeout"
```

### Stuck Loading Pattern:

```
[Analytics Debug] Starting refreshAnalytics
[Analytics Debug] Calling AnalyticsService->getSmartInsights
// No further logs = service hanging
```

## Performance Monitoring

Track execution times dari logs:

-   **Normal**: < 2000ms
-   **Slow**: 2000-5000ms
-   **Problem**: > 5000ms

## Emergency Procedures

### If Dashboard Completely Stuck:

1. Open browser console
2. Run: `forceHideLoading()`
3. Check backend logs
4. Restart application if needed

### If Backend Service Hanging:

1. Check database connections
2. Review slow query log
3. Consider service restart
4. Check server resources

---

## Quick Reference

**Debug Console Commands:**

```javascript
debugAnalytics(); // Check current status
forceHideLoading(); // Force hide loading
```

**Log Monitoring:**

```bash
tail -f storage/logs/laravel.log | grep "Analytics Debug"
```

**Performance Check:**

```bash
php artisan analytics:daily-calculate --days=1 --force
```

---

**Created**: June 9, 2025  
**Purpose**: Comprehensive debugging untuk analytics loading issues  
**Status**: Ready for use ✅
