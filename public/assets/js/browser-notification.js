/**
 * UNIVERSAL BROWSER NOTIFICATION HANDLER - REFACTORED VERSION
 * Universal, reusable, and future-proof notification system
 * Supports multiple DataTable types with automatic detection
 *
 * @author AI Assistant
 * @date 2024-12-19
 * @updated 2024-12-19 - Complete refactor for universality
 */

// Debug mode check - get from Laravel config or environment
window.NotificationDebugMode = (function () {
    // Check if Laravel config is available
    if (
        typeof window.Laravel !== "undefined" &&
        window.Laravel.config &&
        window.Laravel.config.app_debug !== undefined
    ) {
        return window.Laravel.config.app_debug;
    }

    // Check meta tag
    const debugMeta = document.querySelector('meta[name="app-debug"]');
    if (debugMeta) {
        return debugMeta.getAttribute("content") === "true";
    }

    // Check if we're on localhost or development environment
    const isDevelopment =
        window.location.hostname === "localhost" ||
        window.location.hostname.includes("demo51") ||
        window.location.hostname.includes("127.0.0.1") ||
        window.location.hostname.includes(".local");

    return isDevelopment;
})();

// Custom console.log that respects debug mode
function debugLog(...args) {
    if (window.NotificationDebugMode) {
        console.log(...args);
    }
}

// Custom console.error that respects debug mode
function debugError(...args) {
    if (window.NotificationDebugMode) {
        console.error(...args);
    }
}

// Custom console.warn that respects debug mode
function debugWarn(...args) {
    if (window.NotificationDebugMode) {
        console.warn(...args);
    }
}

debugLog("🚀 Loading UNIVERSAL Browser Notification Handler...");

// Global notification system state
window.NotificationSystem = {
    bridgeActive: false,
    lastTimestamp: 0,
    pollingInterval: null,
    connectionStatus: "disconnected",
    eventsReceived: 0,
    currentUserId: null,

    // Universal table configuration - automatically detects all purchase tables
    tableConfig: {
        // Known table patterns - will be auto-detected
        knownTables: [
            "supplyPurchasing-table",
            "feedPurchasing-table",
            "livestock-purchases-table",
            "sales-table",
            "purchases-table",
        ],
        // Auto-detected tables during initialization
        detectedTables: [],
        // Notification keywords that trigger table refresh
        refreshKeywords: [
            "purchase",
            "supply",
            "feed",
            "livestock",
            "sales",
            "status",
            "updated",
            "changed",
            "created",
            "deleted",
        ],
    },

    // Initialize notification system
    init: function () {
        debugLog("🔔 Initializing UNIVERSAL Notification System...");

        // Get current user ID to exclude self-notifications
        this.getCurrentUserId();

        // Initial table detection
        this.autoDetectTables();

        // Setup delayed detection for tables that might load later
        this.setupDelayedDetection();

        // Set proper timestamp to avoid loading old notifications on refresh
        this.initializeTimestamp();

        this.requestNotificationPermission();
        this.setupKeyboardShortcuts();

        // DISABLED: Old polling system (replaced by SSE)
        // this.initializeRealtimeBridge();

        debugLog("✅ UNIVERSAL notification system initialized");
        debugLog(
            "📊 Initially detected tables:",
            this.tableConfig.detectedTables
        );
    },

    // Setup delayed detection for tables that load after initial page load
    setupDelayedDetection: function () {
        debugLog("⏰ Setting up delayed table detection...");

        // Re-detect tables after 1 second (for DataTables that initialize after DOM ready)
        setTimeout(() => {
            debugLog("🔄 Running delayed table detection (1s)...");
            this.autoDetectTables();
        }, 1000);

        // Re-detect tables after 3 seconds (for slow-loading DataTables)
        setTimeout(() => {
            debugLog("🔄 Running delayed table detection (3s)...");
            this.autoDetectTables();
        }, 3000);

        // Re-detect tables after 5 seconds (final attempt)
        setTimeout(() => {
            debugLog("🔄 Running final delayed table detection (5s)...");
            this.autoDetectTables();
            debugLog(
                "📊 Final detected tables:",
                this.tableConfig.detectedTables
            );
        }, 5000);
    },

    // Auto-detect all DataTables on the page
    autoDetectTables: function () {
        debugLog("🔍 Auto-detecting DataTables on page...");

        // Clear previous detections to avoid duplicates
        this.tableConfig.detectedTables = [];

        // Debug: Check environment
        debugLog("🔍 Environment check:", {
            jQueryAvailable: typeof $ !== "undefined",
            dataTableAvailable: typeof $ !== "undefined" && $.fn.DataTable,
            laravelDataTablesAvailable: !!window.LaravelDataTables,
            laravelDataTablesKeys: window.LaravelDataTables
                ? Object.keys(window.LaravelDataTables)
                : [],
        });

        // Method 1: Check known table IDs
        this.tableConfig.knownTables.forEach((tableId) => {
            const element = document.getElementById(tableId);
            debugLog(`🔍 Checking known table #${tableId}:`, {
                elementExists: !!element,
                isDataTable:
                    element &&
                    typeof $ !== "undefined" &&
                    $.fn.DataTable &&
                    $.fn.DataTable.isDataTable(`#${tableId}`),
            });

            if (
                element &&
                typeof $ !== "undefined" &&
                $.fn.DataTable &&
                $.fn.DataTable.isDataTable(`#${tableId}`)
            ) {
                this.tableConfig.detectedTables.push({
                    id: tableId,
                    element: element,
                    type: this.getTableType(tableId),
                    method: "known_id",
                });
                debugLog(`✅ Detected known table: #${tableId}`);
            }
        });

        // Method 2: Check LaravelDataTables registry
        if (window.LaravelDataTables) {
            Object.keys(window.LaravelDataTables).forEach((tableId) => {
                debugLog(`🔍 Checking Laravel registry table #${tableId}`);

                // Avoid duplicates
                if (
                    !this.tableConfig.detectedTables.find(
                        (t) => t.id === tableId
                    )
                ) {
                    const element = document.getElementById(tableId);
                    if (element) {
                        this.tableConfig.detectedTables.push({
                            id: tableId,
                            element: element,
                            type: this.getTableType(tableId),
                            method: "laravel_registry",
                        });
                        debugLog(`✅ Detected Laravel table: #${tableId}`);
                    } else {
                        debugLog(
                            `⚠️ Laravel table #${tableId} element not found in DOM`
                        );
                    }
                }
            });
        } else {
            debugLog("⚠️ LaravelDataTables registry not available");
        }

        // Method 3: Scan all tables with DataTable class
        if (typeof $ !== "undefined") {
            let domTableCount = 0;
            $(".table").each((index, element) => {
                domTableCount++;
                const isDataTable =
                    $.fn.DataTable && $.fn.DataTable.isDataTable(element);
                debugLog(`🔍 DOM table ${index + 1}:`, {
                    id: element.id || `table-${index}`,
                    isDataTable: isDataTable,
                });

                if (isDataTable) {
                    const tableId = element.id || `table-${index}`;
                    // Avoid duplicates
                    if (
                        !this.tableConfig.detectedTables.find(
                            (t) => t.id === tableId
                        )
                    ) {
                        this.tableConfig.detectedTables.push({
                            id: tableId,
                            element: element,
                            type: this.getTableType(tableId),
                            method: "dom_scan",
                        });
                        debugLog(`✅ Detected DOM table: #${tableId}`);
                    }
                }
            });
            debugLog(`🔍 Total DOM tables scanned: ${domTableCount}`);
        } else {
            debugLog("⚠️ jQuery not available for DOM scanning");
        }

        debugLog(
            `🔍 Auto-detection complete: ${this.tableConfig.detectedTables.length} tables found`,
            this.tableConfig.detectedTables
        );
    },

    // Determine table type from ID
    getTableType: function (tableId) {
        const id = tableId.toLowerCase();
        if (id.includes("supply")) return "supply_purchase";
        if (id.includes("feed")) return "feed_purchase";
        if (id.includes("livestock")) return "livestock_purchase";
        if (id.includes("sales")) return "sales";
        if (id.includes("purchase")) return "purchase";
        return "unknown";
    },

    // Get current user ID to exclude self-notifications
    getCurrentUserId: function () {
        try {
            debugLog("👤 DEBUG: Attempting to get current user ID...");

            // Method 1: Try Laravel window object
            if (
                window.Laravel &&
                window.Laravel.user &&
                window.Laravel.user.id
            ) {
                this.currentUserId = parseInt(window.Laravel.user.id);
                debugLog(
                    "👤 Got user ID from window.Laravel:",
                    this.currentUserId
                );
                return;
            }

            // Method 2: Try window.authUserId
            if (window.authUserId) {
                this.currentUserId = parseInt(window.authUserId);
                debugLog(
                    "👤 Got user ID from window.authUserId:",
                    this.currentUserId
                );
                return;
            }

            // Method 3: Try meta tag
            const userMeta = document.querySelector('meta[name="user-id"]');
            if (userMeta) {
                const userId = userMeta.getAttribute("content");
                if (userId && userId !== "null" && userId !== "") {
                    this.currentUserId = parseInt(userId);
                    debugLog(
                        "👤 Got user ID from meta tag:",
                        this.currentUserId
                    );
                    return;
                }
            }

            // Method 4: Try to extract from page context (Laravel Blade)
            if (window.user_id) {
                this.currentUserId = parseInt(window.user_id);
                debugLog(
                    "👤 Got user ID from window.user_id:",
                    this.currentUserId
                );
                return;
            }

            debugLog("⚠️ Could not determine current user ID from any source");
            this.currentUserId = null;
        } catch (error) {
            debugLog("⚠️ Error getting current user ID:", error.message);
            this.currentUserId = null;
        }
    },

    // Initialize timestamp to current time to avoid loading old notifications
    initializeTimestamp: function () {
        // Set timestamp to current time to only get new notifications
        this.lastTimestamp = Math.floor(Date.now() / 1000) - 300; // 5 minutes ago for debugging
        debugLog(
            "⏰ Initialized timestamp for notifications from last 5 minutes:",
            this.lastTimestamp,
            "Current time:",
            Math.floor(Date.now() / 1000)
        );
    },

    // Request notification permission
    requestNotificationPermission: function () {
        if ("Notification" in window && Notification.permission === "default") {
            Notification.requestPermission().then((permission) => {
                debugLog("🔔 Browser notification permission:", permission);
            });
        }
    },

    // Initialize real-time bridge connection
    initializeRealtimeBridge: function () {
        this.testBridgeConnection()
            .then((available) => {
                if (available) {
                    debugLog(
                        "🌉 Bridge available - starting real-time polling"
                    );
                    this.bridgeActive = true;
                    this.startRealtimePolling();
                } else {
                    debugLog(
                        "❌ Bridge not available - notifications disabled"
                    );
                    this.bridgeActive = false;
                }
            })
            .catch((error) => {
                debugLog("❌ Bridge initialization failed:", error.message);
                this.bridgeActive = false;
            });
    },

    // Test bridge connection
    testBridgeConnection: function () {
        const bridgeUrl = "/testing/notification_bridge.php?action=status";
        debugLog("🌉 Testing bridge connection at:", bridgeUrl);

        return fetch(bridgeUrl, {
            method: "GET",
            cache: "no-cache",
        })
            .then((response) => {
                debugLog("🌉 Bridge response status:", response.status);
                return response.json();
            })
            .then((data) => {
                debugLog("🌉 Bridge response data:", data);
                return data && data.bridge_active === true;
            })
            .catch((error) => {
                debugLog("❌ Bridge connection failed:", error.message);
                return false;
            });
    },

    // Start real-time polling (fixed to only get new notifications)
    startRealtimePolling: function () {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }

        this.connectionStatus = "connected";
        debugLog(
            "🔄 Starting UNIVERSAL real-time polling (2s interval, new notifications only)"
        );

        this.pollingInterval = setInterval(() => {
            this.pollForNotifications();
        }, 2000);
    },

    // Poll for notifications (fixed timestamp logic)
    pollForNotifications: function () {
        if (!this.bridgeActive) return;

        // Only get notifications after our last timestamp
        const url = `/testing/notification_bridge.php?since=${this.lastTimestamp}`;
        debugLog("📨 Polling for notifications:", url);

        fetch(url, {
            method: "GET",
            cache: "no-cache",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
        })
            .then((response) => {
                debugLog("📨 Polling response status:", response.status);
                return response.json();
            })
            .then((data) => {
                debugLog("📨 Polling response data:", data);

                if (data.notifications && data.notifications.length > 0) {
                    debugLog(
                        `📨 Received ${data.notifications.length} new notifications`
                    );

                    data.notifications.forEach((notification) => {
                        // Skip notifications from current user (exclude self)
                        if (this.shouldExcludeNotification(notification)) {
                            debugLog(
                                "🚫 Skipping self-notification:",
                                notification.title
                            );
                            return;
                        }

                        this.handleNotification(notification);

                        // Update timestamp to prevent re-showing this notification
                        if (notification.timestamp > this.lastTimestamp) {
                            this.lastTimestamp = notification.timestamp;
                        }
                    });

                    this.eventsReceived += data.notifications.length;
                } else {
                    debugLog("📨 No new notifications");
                }

                this.connectionStatus = "connected";
            })
            .catch((error) => {
                debugLog("⚠️ Polling error:", error.message);
                this.connectionStatus = "error";
            });
    },

    // Check if notification should be excluded (self-notifications)
    shouldExcludeNotification: function (notification) {
        debugLog("🔍 DEBUG Self-exclusion check:", {
            currentUserId: this.currentUserId,
            currentUserIdType: typeof this.currentUserId,
            notificationData: notification.data,
            notification: notification,
        });

        if (!this.currentUserId) {
            debugLog("👤 No current user ID - showing notification");
            return false;
        }

        // Check if notification is from current user
        if (notification.data) {
            const updatedBy = notification.data.updated_by;
            debugLog(
                `🔍 DETAILED CHECK: Notification from user: ${updatedBy} (type: ${typeof updatedBy}), current user: ${
                    this.currentUserId
                } (type: ${typeof this.currentUserId})`
            );

            // Convert both to numbers for comparison
            const notificationUserId = parseInt(updatedBy);
            const currentUserId = parseInt(this.currentUserId);

            debugLog(
                `🔍 CONVERTED IDs: Notification user: ${notificationUserId}, current user: ${currentUserId}`
            );

            if (
                !isNaN(notificationUserId) &&
                !isNaN(currentUserId) &&
                notificationUserId === currentUserId
            ) {
                debugLog("🚫 EXCLUDING self-notification - user IDs match");
                return true;
            }
        }

        debugLog("✅ SHOWING notification (not from current user or no data)");
        return false;
    },

    // Handle incoming notification
    handleNotification: function (notification) {
        debugLog("🎯 Processing notification:", notification.title);

        // Show universal data updated notification
        this.showDataUpdatedNotification(notification);

        // Handle page-specific actions
        this.handlePageSpecificActions(notification);
    },

    // Universal notification display with smart table detection
    showDataUpdatedNotification: function (notification) {
        // Remove any existing data update notifications first
        this.removeExistingDataNotifications();

        const notificationId = "data-update-notification-" + Date.now();

        // Always show refresh buttons for purchase notifications
        const requiresRefresh = this.isRefreshableNotification(notification);

        debugLog("🔍 Notification refresh requirements:", {
            requiresRefresh: requiresRefresh,
            notificationData: notification.data,
            notification: notification,
        });

        // Try universal auto-refresh first
        let autoRefreshSuccess = false;
        debugLog(
            "🔄 Attempting universal auto-refresh before showing notification..."
        );

        try {
            autoRefreshSuccess = this.attemptUniversalAutoRefresh();
        } catch (error) {
            debugLog("❌ Universal auto-refresh failed:", error.message);
            autoRefreshSuccess = false;
        }

        // Determine notification message based on auto-refresh success
        const refreshMessage = autoRefreshSuccess
            ? "Table data refreshed automatically"
            : "Please refresh to see latest data";

        const refreshButtonsClass = autoRefreshSuccess ? "d-none" : "";

        const notificationHtml = `
            <div id="${notificationId}" class="alert alert-info alert-dismissible fade show position-fixed" 
                 style="top: 120px; right: 20px; z-index: 9999; min-width: 350px; max-width: 450px; 
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15); backdrop-filter: blur(10px);">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-info-circle text-primary" style="font-size: 24px;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <strong class="d-block">Data Updated</strong>
                        <span class="text-muted">${
                            notification.message ||
                            "Purchase data has been refreshed."
                        }</span>
                        <br><small class="text-muted" id="refresh-status-${notificationId}">${refreshMessage}</small>
                    </div>
                    <button type="button" class="btn-close ms-2" onclick="window.NotificationSystem.closeNotification('${notificationId}')" 
                            style="filter: brightness(0.8);"></button>
                </div>
                <div class="mt-2 pt-2 border-top ${refreshButtonsClass}" id="refresh-buttons-${notificationId}">
                    <button class="btn btn-primary btn-sm me-2" onclick="window.location.reload()">
                        <i class="fas fa-sync"></i> Refresh Page
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.NotificationSystem.manualRefreshDataTable('${notificationId}')">
                        <i class="fas fa-table"></i> Refresh Table Only
                    </button>
                </div>
            </div>
        `;

        // Add to page
        document.body.insertAdjacentHTML("beforeend", notificationHtml);

        // Set auto-close behavior based on refresh success
        if (autoRefreshSuccess) {
            // Auto-close after 8 seconds if refresh was successful
            setTimeout(() => {
                this.closeNotification(notificationId);
            }, 8000);
            debugLog(
                "✅ Data Updated notification shown with auto-close (refresh successful)"
            );
        } else {
            // Don't auto-close if refresh failed - user needs to manually refresh
            debugLog(
                "⚠️ Data Updated notification shown WITHOUT auto-close (refresh failed - buttons visible)"
            );
        }
    },

    // Check if notification should trigger table refresh
    isRefreshableNotification: function (notification) {
        const title = (notification.title || "").toLowerCase();
        const message = (notification.message || "").toLowerCase();

        return this.tableConfig.refreshKeywords.some(
            (keyword) => title.includes(keyword) || message.includes(keyword)
        );
    },

    // Remove existing data notifications to prevent duplicates
    removeExistingDataNotifications: function () {
        const existingNotifications = document.querySelectorAll(
            '[id^="data-update-notification-"]'
        );
        existingNotifications.forEach((notification) => {
            this.closeNotification(notification.id);
        });
    },

    // Close notification (fixed close functionality)
    closeNotification: function (notificationId) {
        const notification = document.getElementById(notificationId);
        if (notification) {
            // Fade out animation
            notification.style.transition =
                "opacity 0.3s ease, transform 0.3s ease";
            notification.style.opacity = "0";
            notification.style.transform = "translateX(100%)";

            // Remove from DOM after animation
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);

            debugLog("✅ Notification closed:", notificationId);
        }
    },

    // Handle page-specific actions
    handlePageSpecificActions: function (notification) {
        // Auto-refresh DataTable if required
        if (notification.data && notification.data.requires_refresh) {
            this.attemptUniversalAutoRefresh();
        }
    },

    // Universal auto-refresh that works with all detected tables
    attemptUniversalAutoRefresh: function () {
        debugLog(
            "🔄 Attempting universal auto-refresh for all detected tables..."
        );

        let refreshedCount = 0;
        let totalTables = this.tableConfig.detectedTables.length;

        // If no tables detected, try immediate re-detection and fallback methods
        if (totalTables === 0) {
            debugLog(
                "⚠️ No tables detected - attempting immediate re-detection..."
            );
            this.autoDetectTables();
            totalTables = this.tableConfig.detectedTables.length;

            if (totalTables === 0) {
                debugLog(
                    "⚠️ Still no tables detected - trying fallback refresh methods..."
                );
                return this.attemptFallbackRefresh();
            }
        }

        // Try to refresh each detected table
        this.tableConfig.detectedTables.forEach((tableInfo) => {
            try {
                let refreshed = false;

                // Method 1: Try direct DataTable refresh
                if (
                    typeof $ !== "undefined" &&
                    $.fn.DataTable &&
                    $.fn.DataTable.isDataTable(`#${tableInfo.id}`)
                ) {
                    $(`#${tableInfo.id}`).DataTable().ajax.reload(null, false);
                    debugLog(
                        `✅ DataTable refreshed: #${tableInfo.id} (${tableInfo.type})`
                    );
                    refreshed = true;
                }

                // Method 2: Try LaravelDataTables registry
                if (
                    !refreshed &&
                    window.LaravelDataTables &&
                    window.LaravelDataTables[tableInfo.id]
                ) {
                    window.LaravelDataTables[tableInfo.id].ajax.reload(
                        null,
                        false
                    );
                    debugLog(
                        `✅ Laravel DataTable refreshed: #${tableInfo.id} (${tableInfo.type})`
                    );
                    refreshed = true;
                }

                if (refreshed) {
                    refreshedCount++;
                }
            } catch (error) {
                debugLog(
                    `❌ Failed to refresh table #${tableInfo.id}:`,
                    error.message
                );
            }
        });

        // If no tables were refreshed, try fallback methods
        if (refreshedCount === 0) {
            debugLog(
                "⚠️ No detected tables were refreshed - trying fallback methods..."
            );
            return this.attemptFallbackRefresh();
        }

        const success = refreshedCount > 0;
        debugLog(
            `🔄 Universal auto-refresh result: ${refreshedCount}/${totalTables} tables refreshed - ${
                success ? "SUCCESS" : "FAILED"
            }`
        );

        return success;
    },

    // Fallback refresh methods when table detection fails
    attemptFallbackRefresh: function () {
        debugLog("🔄 Attempting fallback refresh methods...");

        let refreshedCount = 0;

        try {
            // Method 1: Try known table IDs directly
            this.tableConfig.knownTables.forEach((tableId) => {
                try {
                    if (
                        typeof $ !== "undefined" &&
                        $.fn.DataTable &&
                        $.fn.DataTable.isDataTable(`#${tableId}`)
                    ) {
                        $(`#${tableId}`).DataTable().ajax.reload(null, false);
                        debugLog(`✅ Fallback refresh successful: #${tableId}`);
                        refreshedCount++;
                    }
                } catch (error) {
                    debugLog(
                        `❌ Fallback refresh failed for #${tableId}:`,
                        error.message
                    );
                }
            });

            // Method 2: Try LaravelDataTables registry
            if (window.LaravelDataTables) {
                Object.keys(window.LaravelDataTables).forEach((tableId) => {
                    try {
                        window.LaravelDataTables[tableId].ajax.reload(
                            null,
                            false
                        );
                        debugLog(
                            `✅ Fallback Laravel refresh successful: #${tableId}`
                        );
                        refreshedCount++;
                    } catch (error) {
                        debugLog(
                            `❌ Fallback Laravel refresh failed for #${tableId}:`,
                            error.message
                        );
                    }
                });
            }

            // Method 3: Try any DataTable on the page
            if (typeof $ !== "undefined") {
                $(".table").each(function () {
                    try {
                        if (
                            $.fn.DataTable &&
                            $.fn.DataTable.isDataTable(this)
                        ) {
                            $(this).DataTable().ajax.reload(null, false);
                            debugLog(
                                `✅ Fallback DOM refresh successful: #${
                                    this.id || "unnamed"
                                }`
                            );
                            refreshedCount++;
                        }
                    } catch (error) {
                        debugLog(
                            `❌ Fallback DOM refresh failed:`,
                            error.message
                        );
                    }
                });
            }

            // Method 4: Try Livewire fallback
            if (refreshedCount === 0 && typeof Livewire !== "undefined") {
                debugLog("🔄 Final fallback: Refreshing Livewire components");
                Livewire.dispatch("$refresh");
                refreshedCount = 1; // Assume success for Livewire
            }
        } catch (error) {
            debugLog("❌ Fallback refresh methods failed:", error.message);
        }

        const success = refreshedCount > 0;
        debugLog(
            `🔄 Fallback refresh result: ${refreshedCount} refreshes - ${
                success ? "SUCCESS" : "FAILED"
            }`
        );

        return success;
    },

    // Manual refresh DataTable (called from button click)
    manualRefreshDataTable: function (notificationId) {
        debugLog("🔄 Manual universal DataTable refresh triggered...");

        const refreshed = this.attemptUniversalAutoRefresh();

        if (refreshed) {
            // Update notification to show success
            const refreshStatus = document.getElementById(
                `refresh-status-${notificationId}`
            );
            const refreshButtons = document.getElementById(
                `refresh-buttons-${notificationId}`
            );

            if (refreshStatus) {
                refreshStatus.textContent = "Tables refreshed successfully!";
                refreshStatus.className = "text-success";
            }

            if (refreshButtons) {
                refreshButtons.classList.add("d-none");
            }

            // Auto-close notification after successful manual refresh
            setTimeout(() => {
                this.closeNotification(notificationId);
            }, 3000);

            debugLog(
                "✅ Manual refresh successful - notification will auto-close"
            );
        } else {
            debugLog("❌ Manual refresh failed - showing fallback options");

            // Update notification to show failure
            const refreshStatus = document.getElementById(
                `refresh-status-${notificationId}`
            );
            if (refreshStatus) {
                refreshStatus.textContent =
                    "Table refresh failed - please refresh page";
                refreshStatus.className = "text-warning";
            }
        }
    },

    // Legacy refresh method (kept for compatibility)
    refreshDataTable: function () {
        debugLog(
            "🔄 Legacy refreshDataTable called - using universal auto-refresh"
        );
        return this.attemptUniversalAutoRefresh();
    },

    // Show refresh suggestion if automatic refresh fails
    showRefreshSuggestion: function () {
        debugLog("💡 Showing manual refresh suggestion");

        const suggestionHtml = `
            <div class="alert alert-warning alert-dismissible fade show position-fixed" 
                 style="top: 200px; right: 20px; z-index: 9998; min-width: 350px;">
                <strong>Refresh Needed</strong><br>
                Please refresh the page manually to see the latest data.
                <br><br>
                <button class="btn btn-warning btn-sm" onclick="window.location.reload()">
                    <i class="fas fa-sync"></i> Refresh Page Now
                </button>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        document.body.insertAdjacentHTML("beforeend", suggestionHtml);

        // Auto-remove after 10 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll(".alert-warning");
            alerts.forEach((alert) => {
                if (alert.textContent.includes("Refresh Needed")) {
                    alert.remove();
                }
            });
        }, 10000);
    },

    // Setup keyboard shortcuts
    setupKeyboardShortcuts: function () {
        document.addEventListener("keydown", (e) => {
            // Ctrl+Shift+N - Test notification
            if (e.ctrlKey && e.shiftKey && e.key === "N") {
                e.preventDefault();
                this.testNotification();
            }

            // Ctrl+Shift+S - Show status
            if (e.ctrlKey && e.shiftKey && e.key === "S") {
                e.preventDefault();
                this.showStatus();
            }

            // Ctrl+Shift+C - Clear all notifications
            if (e.ctrlKey && e.shiftKey && e.key === "C") {
                e.preventDefault();
                this.clearAllNotifications();
            }

            // Ctrl+Shift+R - Force refresh all tables
            if (e.ctrlKey && e.shiftKey && e.key === "R") {
                e.preventDefault();
                this.attemptUniversalAutoRefresh();
            }
        });
    },

    // Test notification
    testNotification: function () {
        debugLog("🧪 Testing UNIVERSAL notification system");

        const testNotification = {
            type: "info",
            title: "Test Notification",
            message:
                "This is a test of the UNIVERSAL notification system - " +
                new Date().toLocaleTimeString(),
            data: {
                requires_refresh: false,
                test: true,
            },
        };

        this.showDataUpdatedNotification(testNotification);
    },

    // Clear all notifications
    clearAllNotifications: function () {
        const notifications = document.querySelectorAll(
            '[id^="data-update-notification-"]'
        );
        notifications.forEach((notification) => {
            this.closeNotification(notification.id);
        });
        debugLog("🧹 All notifications cleared");
    },

    // Show system status
    showStatus: function () {
        const status = {
            bridgeActive: this.bridgeActive,
            connectionStatus: this.connectionStatus,
            eventsReceived: this.eventsReceived,
            lastTimestamp: this.lastTimestamp,
            currentUserId: this.currentUserId,
            detectedTables: this.tableConfig.detectedTables.length,
            tableDetails: this.tableConfig.detectedTables.map((t) => ({
                id: t.id,
                type: t.type,
                method: t.method,
            })),
        };

        debugLog("📊 UNIVERSAL Notification System Status:", status);

        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "Universal Notification System Status",
                html: `
                    <div class="text-start">
                        <strong>Bridge Active:</strong> ${
                            status.bridgeActive ? "✅ Yes" : "❌ No"
                        }<br>
                        <strong>Connection:</strong> ${
                            status.connectionStatus
                        }<br>
                        <strong>Events Received:</strong> ${
                            status.eventsReceived
                        }<br>
                        <strong>Last Timestamp:</strong> ${
                            status.lastTimestamp
                        }<br>
                        <strong>Current User ID:</strong> ${
                            status.currentUserId || "Unknown"
                        }<br>
                        <strong>Detected Tables:</strong> ${
                            status.detectedTables
                        }<br>
                        <strong>Table Details:</strong><br>
                        ${status.tableDetails
                            .map(
                                (t) =>
                                    `&nbsp;&nbsp;• #${t.id} (${t.type}) via ${t.method}`
                            )
                            .join("<br>")}
                    </div>
                `,
                icon: "info",
            });
        }
    },

    // Get system status
    getStatus: function () {
        return {
            bridgeActive: this.bridgeActive,
            connectionStatus: this.connectionStatus,
            eventsReceived: this.eventsReceived,
            lastTimestamp: this.lastTimestamp,
            currentUserId: this.currentUserId,
            detectedTables: this.tableConfig.detectedTables,
        };
    },
};

// Global helper functions
window.getNotificationStatus = function () {
    return window.NotificationSystem.getStatus();
};

window.testUniversalNotification = function () {
    window.NotificationSystem.testNotification();
};

window.clearAllNotifications = function () {
    window.NotificationSystem.clearAllNotifications();
};

window.refreshAllTables = function () {
    return window.NotificationSystem.attemptUniversalAutoRefresh();
};

// Auto-initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    debugLog("🔔 DOM ready - initializing UNIVERSAL notification system");
    window.NotificationSystem.init();
});

// Also initialize if DOM is already loaded
if (document.readyState === "loading") {
    debugLog("📄 DOM still loading - waiting for DOMContentLoaded");
} else {
    debugLog(
        "📄 DOM already loaded - initializing UNIVERSAL notification system immediately"
    );
    setTimeout(() => {
        window.NotificationSystem.init();
    }, 500);
}
