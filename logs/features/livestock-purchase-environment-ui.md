# Livestock Purchase Environment-Based UI Integration

## Tanggal: 2025-01-27

## Status: ✅ Completed

## Overview

Integrasi UI yang menyesuaikan dengan kondisi environment menggunakan `EnvironmentHelper.php`. UI akan menampilkan informasi yang berbeda berdasarkan environment (production vs development) dan status debug.

## Environment Configuration

### 1. Environment Detection

```php
// Environment configuration
window.EnvironmentConfig = {
    isProduction: {{ \App\Helpers\EnvironmentHelper::isProduction() ? 'true' : 'false' }},
    isLocal: {{ \App\Helpers\EnvironmentHelper::isLocal() ? 'true' : 'false' }},
    debug: {{ config('app.debug') ? 'true' : 'false' }},
    showFlowInfo: {{ (\App\Helpers\EnvironmentHelper::isLocal() || config('app.debug')) ? 'true' : 'false' }},
};
```

### 2. Environment Helper Methods

```php
// Check if current environment is production
EnvironmentHelper::isProduction()

// Check if current environment is local
EnvironmentHelper::isLocal()

// Check if debug mode is enabled
config('app.debug')
```

## UI Behavior by Environment

### 1. Production Mode (Production + Debug False)

**Behavior:** Minimal UI - hanya menampilkan status legend

#### **Visible Elements:**

-   ✅ Status Legend dengan simplified view
-   ✅ Production Mode badge
-   ✅ Production Mode info alert
-   ✅ Environment indicator (Production badge)

#### **Hidden Elements:**

-   ❌ Current Flow Type alert
-   ❌ Business Flow Comparison table
-   ❌ Flow Configuration Actions
-   ❌ Detailed status features

#### **UI Changes:**

```javascript
// Card title changes
document.getElementById("cardTitle").textContent = "Status Legend";

// Show production indicators
document.getElementById("envBadge").style.display = "inline-block";
document.getElementById("productionBadge").style.display = "inline-block";
document.getElementById("productionModeInfo").style.display = "block";

// Hide configuration elements
document.getElementById("currentFlowAlert").style.display = "none";
document.getElementById("businessFlowComparison").style.display = "none";
document.getElementById("flowConfigurationActions").style.display = "none";
```

#### **Status Legend in Production:**

```html
<div class="col">
    <div class="d-flex align-items-start">
        <span class="badge bg-secondary me-3">
            <i class="fas fa-edit me-1"></i>Draft
        </span>
        <div>
            <div class="fw-semibold">Status awal saat membuat pembelian</div>
            <div class="text-muted small">
                Production mode - simplified view
            </div>
        </div>
    </div>
</div>
```

### 2. Development Mode (Local OR Debug True)

**Behavior:** Full UI - menampilkan semua informasi flow configuration

#### **Visible Elements:**

-   ✅ Status Legend dengan detailed view
-   ✅ Current Flow Type alert
-   ✅ Business Flow Comparison table
-   ✅ Flow Configuration Actions
-   ✅ Environment indicator (Local/Debug/Development badge)

#### **Hidden Elements:**

-   ❌ Production Mode badge
-   ❌ Production Mode info alert

#### **UI Changes:**

```javascript
// Card title changes
document.getElementById("cardTitle").textContent =
    "Business Flow Configuration";

// Show environment badge based on type
if (envConfig.isLocal) {
    envText.textContent = "Local";
    envBadge.className = "badge bg-success ms-2";
} else if (envConfig.debug) {
    envText.textContent = "Debug";
    envBadge.className = "badge bg-info ms-2";
} else {
    envText.textContent = "Development";
    envBadge.className = "badge bg-primary ms-2";
}

// Show configuration elements
document.getElementById("currentFlowAlert").style.display = "block";
document.getElementById("businessFlowComparison").style.display = "block";
document.getElementById("flowConfigurationActions").style.display = "block";
```

#### **Status Legend in Development:**

```html
<div class="col">
    <div class="d-flex align-items-start">
        <span class="badge bg-secondary me-3">
            <i class="fas fa-edit me-1"></i>Draft
        </span>
        <div>
            <div class="fw-semibold">Status awal saat membuat pembelian</div>
            <div class="text-muted small">
                Can Edit, Can Delete, No special features
            </div>
        </div>
    </div>
</div>
```

## Environment Indicators

### 1. Card Header Badge

```html
<span class="badge bg-info ms-2" id="envBadge" style="display: none;">
    <i class="fas fa-info-circle me-1"></i>
    <span id="envText">Production</span>
</span>
```

#### **Badge Colors by Environment:**

-   **Production:** `bg-warning` (Yellow)
-   **Local:** `bg-success` (Green)
-   **Debug:** `bg-info` (Blue)
-   **Development:** `bg-primary` (Blue)

### 2. Status Legend Badge

```html
<span
    class="badge bg-secondary ms-2"
    id="productionBadge"
    style="display: none;"
>
    <i class="fas fa-shield-alt me-1"></i>Production Mode
</span>
```

### 3. Production Mode Info Alert

```html
<div
    class="alert alert-warning mb-3"
    id="productionModeInfo"
    style="display: none;"
>
    <div class="d-flex align-items-center">
        <i class="fas fa-shield-alt me-3 fs-2"></i>
        <div>
            <strong>Production Mode Active</strong><br />
            <small class="text-muted">
                Flow configuration details are hidden for security. Only status
                legend is displayed. Use development environment for full
                configuration access.
            </small>
        </div>
    </div>
</div>
```

## Keyboard Shortcuts Behavior

### 1. Production Mode

-   **Ctrl+Shift+C:** Disabled (shows console message)
-   **Ctrl+Shift+F:** Disabled (shows console message)
-   **Ctrl+Shift+B:** Enabled (toggle card)
-   **Ctrl+Shift+P:** Enabled (test notification)

### 2. Development Mode

-   **Ctrl+Shift+C:** Enabled (show config details)
-   **Ctrl+Shift+F:** Enabled (test flow)
-   **Ctrl+Shift+B:** Enabled (toggle card)
-   **Ctrl+Shift+P:** Enabled (test notification)

### 3. Shortcuts Note

```html
<span class="text-warning ms-2" id="shortcutsNote" style="display: none;">
    <i class="fas fa-exclamation-triangle me-1"></i>
    Some shortcuts disabled in production
</span>
```

## JavaScript Functions

### 1. Environment Configuration

```javascript
function configureUIForEnvironment() {
    const envConfig = window.EnvironmentConfig;

    if (envConfig.isProduction && !envConfig.debug) {
        // Production mode configuration
        log("🏭 Production mode detected - hiding flow configuration info");

        // Update UI for production
        document.getElementById("cardTitle").textContent = "Status Legend";
        document.getElementById("envBadge").style.display = "inline-block";
        document.getElementById("envText").textContent = "Production";
        document.getElementById("envBadge").className = "badge bg-warning ms-2";

        // Show production indicators
        document.getElementById("productionBadge").style.display =
            "inline-block";
        document.getElementById("productionModeInfo").style.display = "block";

        // Hide configuration elements
        document.getElementById("currentFlowAlert").style.display = "none";
        document.getElementById("businessFlowComparison").style.display =
            "none";
        document.getElementById("flowConfigurationActions").style.display =
            "none";

        // Show shortcuts note
        document.getElementById("shortcutsNote").style.display = "inline";
    } else {
        // Development mode configuration
        log(
            "🛠️ Development mode detected - showing all flow configuration info"
        );

        // Update UI for development
        document.getElementById("cardTitle").textContent =
            "Business Flow Configuration";

        // Set environment badge
        const envBadge = document.getElementById("envBadge");
        const envText = document.getElementById("envText");
        envBadge.style.display = "inline-block";

        if (envConfig.isLocal) {
            envText.textContent = "Local";
            envBadge.className = "badge bg-success ms-2";
        } else if (envConfig.debug) {
            envText.textContent = "Debug";
            envBadge.className = "badge bg-info ms-2";
        } else {
            envText.textContent = "Development";
            envBadge.className = "badge bg-primary ms-2";
        }

        // Hide production indicators
        document.getElementById("productionBadge").style.display = "none";
        document.getElementById("productionModeInfo").style.display = "none";

        // Show configuration elements
        document.getElementById("currentFlowAlert").style.display = "block";
        document.getElementById("businessFlowComparison").style.display =
            "block";
        document.getElementById("flowConfigurationActions").style.display =
            "block";

        // Hide shortcuts note
        document.getElementById("shortcutsNote").style.display = "none";
    }
}
```

### 2. Status Legend Update

```javascript
function updateStatusLegend() {
    const statusFlow = window.LivestockPurchaseConfig?.statusFlow || {};
    const currentFlowType =
        window.LivestockPurchaseConfig?.currentFlowType || "simple";
    const flowConfig =
        window.LivestockPurchaseConfig?.availableFlows?.[currentFlowType];
    const envConfig = window.EnvironmentConfig;

    if (Object.keys(statusFlow).length > 0 && flowConfig) {
        const legendContainer = document.getElementById(
            "statusLegendContainer"
        );
        if (legendContainer) {
            const statuses = flowConfig.statuses || [];

            legendContainer.innerHTML = statuses
                .map((status) => {
                    const statusConfig = statusFlow[status];
                    if (!statusConfig) return "";

                    const colorClass = getStatusColorClass(statusConfig.color);
                    const icon = statusConfig.icon || "fas fa-circle";

                    // In production mode, show simplified status info
                    const statusFeatures =
                        envConfig.isProduction && !envConfig.debug
                            ? "Production mode - simplified view"
                            : getStatusFeatures(statusConfig);

                    return `
                    <div class="col">
                        <div class="d-flex align-items-start">
                            <span class="badge ${colorClass} me-3">
                                <i class="${icon} me-1"></i>${
                        statusConfig.description
                            ? status.charAt(0).toUpperCase() + status.slice(1)
                            : status
                    }
                            </span>
                            <div>
                                <div class="fw-semibold">${
                                    statusConfig.description || status
                                }</div>
                                <div class="text-muted small">
                                    ${statusFeatures}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                })
                .join("");
        }

        log(
            "🎨 Status legend updated with config data for environment:",
            envConfig
        );
    }
}
```

### 3. Keyboard Shortcuts with Environment Check

```javascript
document.addEventListener("keydown", function (e) {
    // Ctrl+Shift+F - Test business flow configuration (development only)
    if (e.ctrlKey && e.shiftKey && e.key === "F") {
        e.preventDefault();
        if (window.EnvironmentConfig.showFlowInfo) {
            log("🎯 Testing business flow configuration via keyboard shortcut");
            testCurrentFlow();
        } else {
            log("🚫 Business flow testing disabled in production mode");
        }
    }

    // Ctrl+Shift+C - Show configuration details (development only)
    if (e.ctrlKey && e.shiftKey && e.key === "C") {
        e.preventDefault();
        if (window.EnvironmentConfig.showFlowInfo) {
            log("🎯 Showing configuration details via keyboard shortcut");
            showConfigDetails();
        } else {
            log("🚫 Configuration details disabled in production mode");
        }
    }

    // Ctrl+Shift+B - Toggle business flow card (always available)
    if (e.ctrlKey && e.shiftKey && e.key === "B") {
        e.preventDefault();
        log("🎯 Toggling business flow card via keyboard shortcut");
        const toggle = document.getElementById("kt_business_flow_toggle");
        if (toggle) toggle.click();
    }
});
```

## Security Considerations

### 1. Production Mode Security

-   **Hidden Configuration:** Flow configuration details tidak ditampilkan
-   **Simplified Status:** Status legend hanya menampilkan informasi dasar
-   **Disabled Actions:** Action buttons untuk config management di-disable
-   **Limited Shortcuts:** Keyboard shortcuts untuk config di-disable

### 2. Information Disclosure Prevention

-   **No Debug Info:** Debug information tidak ditampilkan di production
-   **No Config Details:** Detailed configuration tidak accessible
-   **No Flow Testing:** Flow testing functionality di-disable
-   **No Config Changes:** Configuration change functionality di-disable

## Performance Benefits

### 1. Production Mode

-   **Reduced DOM Elements:** Lebih sedikit elemen DOM yang di-render
-   **Simplified JavaScript:** JavaScript yang lebih sederhana
-   **Faster Loading:** Loading yang lebih cepat
-   **Less Memory Usage:** Penggunaan memory yang lebih sedikit

### 2. Development Mode

-   **Full Functionality:** Semua fitur tersedia untuk development
-   **Debug Information:** Informasi debug lengkap
-   **Configuration Access:** Akses penuh ke configuration
-   **Testing Capabilities:** Kemampuan testing lengkap

## Console Logging

### 1. Environment Information

```javascript
log("🌍 Environment Configuration:", window.EnvironmentConfig);
log("🏭 Production Mode:", window.EnvironmentConfig.isProduction);
log("🛠️ Debug Mode:", window.EnvironmentConfig.debug);
log("📊 Show Flow Info:", window.EnvironmentConfig.showFlowInfo);
```

### 2. Mode Detection Logs

```javascript
// Production mode
log("🏭 Production mode detected - hiding flow configuration info");

// Development mode
log("🛠️ Development mode detected - showing all flow configuration info");
```

### 3. Disabled Actions Logs

```javascript
// Disabled shortcuts
log("🚫 Business flow testing disabled in production mode");
log("🚫 Configuration details disabled in production mode");
```

## Testing Scenarios

### 1. Production Environment Test

```bash
# Set environment to production
APP_ENV=production
APP_DEBUG=false
```

**Expected Behavior:**

-   Only status legend visible
-   Production mode indicators shown
-   Configuration elements hidden
-   Keyboard shortcuts disabled

### 2. Development Environment Test

```bash
# Set environment to local
APP_ENV=local
APP_DEBUG=true
```

**Expected Behavior:**

-   All configuration elements visible
-   Development mode indicators shown
-   Full functionality available
-   All keyboard shortcuts enabled

### 3. Debug Mode Test

```bash
# Set environment to production with debug
APP_ENV=production
APP_DEBUG=true
```

**Expected Behavior:**

-   All configuration elements visible (due to debug=true)
-   Debug mode indicators shown
-   Full functionality available
-   All keyboard shortcuts enabled

## Future Enhancements

### 1. Environment-Specific Features

-   **Custom Themes:** Different UI themes per environment
-   **Feature Flags:** Environment-based feature toggles
-   **Performance Monitoring:** Environment-specific performance tracking

### 2. Security Enhancements

-   **Role-Based Access:** User role-based configuration access
-   **Audit Logging:** Configuration change audit trails
-   **Encryption:** Sensitive configuration encryption

### 3. User Experience

-   **Environment Switcher:** UI to switch between environments
-   **Configuration Export:** Export configuration for backup
-   **Import Validation:** Validate imported configurations

## Conclusion

Environment-based UI integration telah berhasil diimplementasikan dengan fitur-fitur berikut:

### ✅ **Completed Features:**

1. **Environment Detection** - Deteksi environment menggunakan EnvironmentHelper
2. **Production Mode UI** - Simplified UI untuk production
3. **Development Mode UI** - Full UI untuk development
4. **Security Features** - Hidden configuration di production
5. **Performance Optimization** - Reduced DOM elements di production
6. **Keyboard Shortcuts** - Environment-aware shortcuts
7. **Visual Indicators** - Environment badges dan alerts
8. **Console Logging** - Detailed logging untuk debugging

### 🎯 **Business Benefits:**

1. **Security** - Configuration details hidden di production
2. **Performance** - Optimized UI untuk production
3. **User Experience** - Appropriate UI per environment
4. **Development Efficiency** - Full tools available di development

### 📊 **Technical Benefits:**

1. **Maintainability** - Environment-aware code structure
2. **Scalability** - Easy to add new environment types
3. **Reliability** - Robust environment detection
4. **Debugging** - Comprehensive logging system

Integrasi ini memastikan bahwa UI menyesuaikan dengan environment yang sedang berjalan, memberikan keamanan di production sambil tetap memberikan kemudahan development di environment development.
