/**
 * SERVER-SENT EVENTS NOTIFICATION SYSTEM
 * Efficient real-time notification system without polling overhead
 *
 * @author AI Assistant
 * @date 2024-12-19
 * @version 2.0.0
 */

log("🚀 Loading SSE Notification System...");

window.SSENotificationSystem = {
    eventSource: null,
    connectionStatus: "disconnected",
    eventsReceived: 0,
    reconnectAttempts: 0,
    maxReconnectAttempts: 5,
    reconnectDelay: 2000,
    currentUserId: null,
    bridgeUrl: "/testing/sse-notification-bridge.php",

    // Initialize SSE connection
    init: function () {
        log("🔔 Initializing SSE Notification System...");

        this.getCurrentUserId();
        this.requestNotificationPermission();
        this.setupKeyboardShortcuts();
        this.connectSSE();

        // Auto-reconnect on page visibility change
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden && this.connectionStatus === "disconnected") {
                log("📄 Page visible - reconnecting SSE...");
                this.connectSSE();
            }
        });

        log("✅ SSE notification system initialized");
    },

    // Connect to SSE endpoint
    connectSSE: function () {
        if (
            this.eventSource &&
            this.eventSource.readyState !== EventSource.CLOSED
        ) {
            log("🔗 SSE already connected or connecting");
            return;
        }

        log("🔌 Connecting to SSE bridge...");

        try {
            this.eventSource = new EventSource(this.bridgeUrl);

            this.eventSource.onopen = (event) => {
                log("✅ SSE connection established");
                this.connectionStatus = "connected";
                this.reconnectAttempts = 0;
                this.showConnectionStatus("connected");
            };

            this.eventSource.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    log("📨 SSE message received:", data);
                    this.handleNotification(data);
                } catch (error) {
                    console.error("❌ Error parsing SSE message:", error);
                }
            };

            // Handle specific event types
            this.eventSource.addEventListener(
                "supply_purchase_notification",
                (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        log("📦 Supply purchase notification:", data);
                        this.handleSupplyPurchaseNotification(data);
                    } catch (error) {
                        console.error(
                            "❌ Error handling supply purchase notification:",
                            error
                        );
                    }
                }
            );

            this.eventSource.addEventListener(
                "feed_purchase_notification",
                (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        log("🌾 Feed purchase notification:", data);
                        this.handleFeedPurchaseNotification(data);
                    } catch (error) {
                        console.error(
                            "❌ Error handling feed purchase notification:",
                            error
                        );
                    }
                }
            );

            this.eventSource.addEventListener(
                "livestock_purchase_notification",
                (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        log("🐄 Livestock purchase notification:", data);
                        this.handleLivestockPurchaseNotification(data);
                    } catch (error) {
                        console.error(
                            "❌ Error handling livestock purchase notification:",
                            error
                        );
                    }
                }
            );

            this.eventSource.addEventListener("heartbeat", (event) => {
                try {
                    const data = JSON.parse(event.data);
                    log("💓 Heartbeat received:", data.uptime + "s");
                } catch (error) {
                    log("💓 Heartbeat (raw)");
                }
            });

            this.eventSource.onerror = (event) => {
                console.error("❌ SSE connection error:", event);
                this.connectionStatus = "error";
                this.showConnectionStatus("error");
                this.handleReconnection();
            };
        } catch (error) {
            console.error("❌ Failed to establish SSE connection:", error);
            this.connectionStatus = "error";
            this.handleReconnection();
        }
    },

    // Handle reconnection logic
    handleReconnection: function () {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            log("❌ Max reconnection attempts reached - switching to fallback");
            this.connectionStatus = "failed";
            this.showConnectionStatus("failed");
            this.enablePollingFallback();
            return;
        }

        this.reconnectAttempts++;
        log(
            `🔄 Reconnecting SSE (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})...`
        );

        setTimeout(() => {
            this.connectSSE();
        }, this.reconnectDelay * this.reconnectAttempts); // Exponential backoff
    },

    // Enable polling fallback when SSE fails
    enablePollingFallback: function () {
        log(
            "🔄 SSE failed - showing manual refresh message instead of polling"
        );

        // Show manual refresh notification instead of polling
        this.showManualRefreshNotification();
    },

    // Show manual refresh notification when SSE fails
    showManualRefreshNotification: function () {
        const notificationHtml = `
            <div class="alert alert-warning alert-dismissible fade show position-fixed"
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 350px;">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 24px;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <strong class="d-block">Real-time Connection Failed</strong>
                        <span class="text-muted">Please refresh the page manually to see updates</span>
                        <br><br>
                        <button class="btn btn-warning btn-sm" onclick="window.location.reload()">
                            <i class="fas fa-sync"></i> Refresh Page Now
                        </button>
                    </div>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML("beforeend", notificationHtml);
    },

    // Handle incoming notifications
    handleNotification: function (notification) {
        this.eventsReceived++;

        // Skip self-notifications
        if (
            notification.data &&
            notification.data.updated_by &&
            notification.data.updated_by == this.currentUserId
        ) {
            log("🚫 Skipping self-notification");
            return;
        }

        this.showNotification(notification);

        // Refresh DataTable if needed
        if (
            notification.requires_refresh ||
            notification.data?.requires_refresh
        ) {
            this.refreshDataTables(notification);
        }
    },

    // Handle supply purchase specific notifications
    handleSupplyPurchaseNotification: function (notification) {
        log("📦 Handling supply purchase notification");

        // ✅ PREVENT DUPLICATE NOTIFICATIONS
        const existingNotifications = document.querySelectorAll(
            ".position-fixed .alert"
        );
        let duplicateFound = false;

        existingNotifications.forEach((el) => {
            const content = el.textContent || el.innerText;
            if (
                content.includes(notification.title) ||
                (notification.data?.invoice_number &&
                    content.includes(notification.data.invoice_number))
            ) {
                duplicateFound = true;
            }
        });

        if (!duplicateFound) {
            this.handleNotification(notification);
        } else {
            log("🔄 Duplicate notification prevented for:", notification.title);
        }
    },

    // Handle feed purchase specific notifications
    handleFeedPurchaseNotification: function (notification) {
        log("🌾 Handling feed purchase notification");
        this.handleNotification(notification);
    },

    // Handle livestock purchase specific notifications
    handleLivestockPurchaseNotification: function (notification) {
        log("🐄 Handling livestock purchase notification");
        this.handleNotification(notification);
    },

    // Show visual notification
    showNotification: function (notification) {
        const notificationId = "sse-notification-" + Date.now();

        const notificationHtml = `
            <div id="${notificationId}" class="alert alert-info alert-dismissible fade show position-fixed"
                 style="top: 120px; right: 20px; z-index: 9999; min-width: 350px; max-width: 450px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15); backdrop-filter: blur(10px);">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-bell text-primary" style="font-size: 24px;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <strong class="d-block">${
                            notification.title || "Notification"
                        }</strong>
                        <span class="text-muted">${
                            notification.message ||
                            "You have a new notification"
                        }</span>
                        <br><small class="text-muted">Real-time update via SSE</small>
                    </div>
                    <button type="button" class="btn-close ms-2" onclick="window.SSENotificationSystem.closeNotification('${notificationId}')"
                            style="filter: brightness(0.8);"></button>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML("beforeend", notificationHtml);

        // Auto-close after 8 seconds
        setTimeout(() => {
            this.closeNotification(notificationId);
        }, 8000);

        // Show browser notification if permission granted
        this.showBrowserNotification(notification);
    },

    // Close notification
    closeNotification: function (notificationId) {
        const element = document.getElementById(notificationId);
        if (element) {
            element.classList.add("hiding");
            setTimeout(() => element.remove(), 300);
        }
    },

    // Refresh DataTables
    refreshDataTables: function (notification) {
        log("🔄 Refreshing DataTables for SSE notification");

        let refreshCount = 0;

        // Method 1: Laravel DataTables registry
        if (window.LaravelDataTables) {
            Object.keys(window.LaravelDataTables).forEach((tableId) => {
                try {
                    const table = window.LaravelDataTables[tableId];
                    if (table && typeof table.draw === "function") {
                        table.draw(false);
                        refreshCount++;
                        log(`✅ Refreshed table: ${tableId}`);
                    }
                } catch (error) {
                    log(
                        `❌ Failed to refresh table ${tableId}:`,
                        error.message
                    );
                }
            });
        }

        // Method 2: jQuery DataTables
        if (typeof $ !== "undefined" && $.fn.DataTable) {
            $(".table").each(function () {
                if ($.fn.DataTable.isDataTable(this)) {
                    try {
                        $(this).DataTable().draw(false);
                        refreshCount++;
                        log(`✅ Refreshed jQuery table: ${this.id}`);
                    } catch (error) {
                        log(
                            `❌ Failed to refresh jQuery table ${this.id}:`,
                            error.message
                        );
                    }
                }
            });
        }

        log(`🔄 Refreshed ${refreshCount} DataTables`);
        return refreshCount > 0;
    },

    // Request notification permission
    requestNotificationPermission: function () {
        if ("Notification" in window && Notification.permission === "default") {
            Notification.requestPermission().then((permission) => {
                log("🔔 Notification permission:", permission);
            });
        }
    },

    // Show browser notification
    showBrowserNotification: function (notification) {
        if ("Notification" in window && Notification.permission === "granted") {
            new Notification(notification.title || "System Notification", {
                body: notification.message || "You have a new update",
                icon: "/assets/media/logos/favicon.ico",
                badge: "/assets/media/logos/favicon.ico",
            });
        }
    },

    // Get current user ID
    getCurrentUserId: function () {
        try {
            // Try to get from Laravel meta tag
            const userMeta = document.querySelector('meta[name="user-id"]');
            if (userMeta) {
                this.currentUserId = userMeta.getAttribute("content");
                return;
            }

            // Try to get from global variable
            if (window.userId) {
                this.currentUserId = window.userId;
                return;
            }

            // Try to get from auth object
            if (window.auth && window.auth.user && window.auth.user.id) {
                this.currentUserId = window.auth.user.id;
                return;
            }

            log("⚠️ Could not determine current user ID");
        } catch (error) {
            log("⚠️ Error getting user ID:", error.message);
        }
    },

    // Show connection status
    showConnectionStatus: function (status) {
        const statusColors = {
            connected: "success",
            error: "warning",
            failed: "danger",
        };

        const statusMessages = {
            connected: "Real-time notifications active",
            error: "Connection issue - retrying...",
            failed: "Real-time failed - using fallback",
        };

        const statusHtml = `
            <div class="alert alert-${statusColors[status]} position-fixed" 
                 style="bottom: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <i class="fas fa-wifi"></i> ${statusMessages[status]}
            </div>
        `;

        // Remove existing status notifications
        document
            .querySelectorAll('.alert[style*="bottom: 20px"]')
            .forEach((el) => el.remove());

        document.body.insertAdjacentHTML("beforeend", statusHtml);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            document
                .querySelectorAll('.alert[style*="bottom: 20px"]')
                .forEach((el) => el.remove());
        }, 3000);
    },

    // Setup keyboard shortcuts
    setupKeyboardShortcuts: function () {
        document.addEventListener("keydown", (e) => {
            // Ctrl+Shift+S - Show SSE status
            if (e.ctrlKey && e.shiftKey && e.key === "S") {
                e.preventDefault();
                this.showStatus();
            }

            // Ctrl+Shift+R - Reconnect SSE
            if (e.ctrlKey && e.shiftKey && e.key === "R") {
                e.preventDefault();
                this.reconnect();
            }
        });
    },

    // Show system status
    showStatus: function () {
        const status = {
            connectionStatus: this.connectionStatus,
            eventsReceived: this.eventsReceived,
            reconnectAttempts: this.reconnectAttempts,
            currentUserId: this.currentUserId,
            readyState: this.eventSource ? this.eventSource.readyState : "null",
        };

        log("📊 SSE Notification System Status:", status);

        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "SSE Notification System Status",
                html: `
                    <div class="text-start">
                        <strong>Connection:</strong> ${
                            status.connectionStatus
                        }<br>
                        <strong>Events Received:</strong> ${
                            status.eventsReceived
                        }<br>
                        <strong>Reconnect Attempts:</strong> ${
                            status.reconnectAttempts
                        }<br>
                        <strong>User ID:</strong> ${
                            status.currentUserId || "Unknown"
                        }<br>
                        <strong>Ready State:</strong> ${status.readyState}
                    </div>
                `,
                icon: "info",
            });
        }
    },

    // Manual reconnect
    reconnect: function () {
        log("🔄 Manual SSE reconnection requested");

        if (this.eventSource) {
            this.eventSource.close();
        }

        this.reconnectAttempts = 0;
        this.connectSSE();
    },

    // Cleanup on page unload
    cleanup: function () {
        if (this.eventSource) {
            log("🧹 Cleaning up SSE connection");
            this.eventSource.close();
        }
    },
};

// Initialize on DOM ready
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
        window.SSENotificationSystem.init();
    });
} else {
    window.SSENotificationSystem.init();
}

// Cleanup on page unload
window.addEventListener("beforeunload", () => {
    window.SSENotificationSystem.cleanup();
});

log("✅ SSE Notification System loaded");
