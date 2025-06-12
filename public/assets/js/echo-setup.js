/**
 * Enhanced Laravel Echo Setup for Real-time Notifications
 *
 * @author AI Assistant
 * @date 2024-12-11
 */

console.log("🔧 Loading Echo Setup...");

// Wait for DOM to be ready
document.addEventListener("DOMContentLoaded", function () {
    console.log("📄 DOM loaded, initializing Supply Purchase Global...");

    if (typeof window.SupplyPurchaseGlobal !== "undefined") {
        // Initialize the notification system
        console.log("🔧 Initializing Supply Purchase Global...");

        // Check if Echo is available
        if (window.Echo) {
            console.log("✅ Laravel Echo is available");

            // Setup test listeners
            console.log("🧪 Setting up test listeners...");

            // Listen to supply purchase status changes
            window.Echo.channel("supply-purchases").listen(
                "status-changed",
                function (event) {
                    console.log(
                        "📢 Supply purchase status change received:",
                        event
                    );

                    // Show notification
                    showNotification(
                        "Supply Purchase Updated",
                        `Status changed: ${event.old_status} → ${event.new_status}`,
                        "info"
                    );

                    // Trigger UI update if needed
                    if (event.metadata && event.metadata.requires_refresh) {
                        console.log(
                            "🔄 Refreshing data due to high priority change..."
                        );
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    }
                }
            );

            // Listen for user notifications if authenticated
            if (
                window.Laravel &&
                window.Laravel.user &&
                window.Laravel.user.id
            ) {
                const userId = window.Laravel.user.id;
                console.log(`🔐 Private channel setup for user: ${userId}`);

                window.Echo.private(`App.Models.User.${userId}`).notification(
                    function (notification) {
                        console.log(
                            "📬 User notification received:",
                            notification
                        );

                        // Show notification
                        showNotification(
                            notification.data.title || "Notification",
                            notification.data.message ||
                                "You have a new notification",
                            notification.data.priority === "high"
                                ? "warning"
                                : "info"
                        );
                    }
                );
            }

            console.log("🔄 Test notification listeners setup complete");
        } else {
            // Fallback if Echo is not available
            console.log(
                "⚠️ Laravel Echo not available, creating enhanced mock..."
            );

            // Create enhanced mock Echo
            window.Echo = createEnhancedMockEcho();
        }

        // Add keyboard shortcuts for testing
        addKeyboardShortcuts();

        console.log(
            "✅ Supply Purchase Notification System loaded successfully!"
        );
    } else {
        console.log(
            "⚠️ SupplyPurchaseGlobal not found, setting up basic Echo..."
        );

        // Basic Echo setup if SupplyPurchaseGlobal is not available
        if (!window.Echo) {
            window.Echo = createEnhancedMockEcho();
        }
    }
});

// Function to create enhanced mock Echo
function createEnhancedMockEcho() {
    console.log("🧪 Creating enhanced Mock Echo...");

    return {
        channel: function (channel) {
            console.log(`📡 Mock Echo: Listening to channel '${channel}'`);
            return {
                listen: function (event, callback) {
                    console.log(
                        `👂 Mock Echo: Listening for event '${event}' on channel '${channel}'`
                    );

                    // Store the callback for manual testing
                    window.MockEchoCallbacks = window.MockEchoCallbacks || {};
                    window.MockEchoCallbacks[`${channel}.${event}`] = callback;

                    return this;
                },
            };
        },

        private: function (channel) {
            console.log(
                `🔐 Mock Echo: Connecting to private channel '${channel}'`
            );
            return {
                notification: function (callback) {
                    console.log(
                        `📬 Mock Echo: Listening for notifications on private channel '${channel}'`
                    );

                    // Store the callback for manual testing
                    window.MockEchoCallbacks = window.MockEchoCallbacks || {};
                    window.MockEchoCallbacks[`${channel}.notification`] =
                        callback;

                    return this;
                },
            };
        },

        // Mock method to trigger events for testing
        triggerTest: function (channel, event, data) {
            const callbackKey = `${channel}.${event}`;
            if (
                window.MockEchoCallbacks &&
                window.MockEchoCallbacks[callbackKey]
            ) {
                console.log(
                    `🎭 Mock Echo: Triggering test event '${event}' on channel '${channel}'`
                );
                window.MockEchoCallbacks[callbackKey](data);
            } else {
                console.log(
                    `❌ Mock Echo: No callback found for '${callbackKey}'`
                );
            }
        },
    };
}

// Add test functions to window for easy access
window.testEcho = {
    triggerSupplyPurchaseEvent: function (data = null) {
        const testData = data || {
            batch_id: 123,
            invoice_number: "INV-TEST-001",
            old_status: "draft",
            new_status: "confirmed",
            updated_by: "Test User",
            metadata: {
                priority: "normal",
                requires_refresh: false,
            },
        };

        console.log("🧪 Triggering supply purchase event:", testData);

        if (window.Echo && window.Echo.triggerTest) {
            window.Echo.triggerTest(
                "supply-purchases",
                "status-changed",
                testData
            );
        } else {
            console.log("❌ Echo.triggerTest not available");
        }
    },

    triggerUserNotification: function (userId = null, data = null) {
        const targetUserId =
            userId ||
            (window.Laravel && window.Laravel.user
                ? window.Laravel.user.id
                : 1);
        const testData = data || {
            type: "App\\Notifications\\SupplyPurchaseStatusNotification",
            data: {
                title: "Test Notification",
                message:
                    "This is a test notification from the notification system!",
                batch_id: 123,
                priority: "normal",
            },
        };

        console.log("🧪 Triggering user notification:", testData);

        if (window.Echo && window.Echo.triggerTest) {
            window.Echo.triggerTest(
                `App.Models.User.${targetUserId}`,
                "notification",
                testData
            );
        } else {
            console.log("❌ Echo.triggerTest not available");
        }
    },
};

// Enhanced notification display function
function showNotification(title, message, type = "info") {
    console.log(`📢 Showing notification: ${title} - ${message}`);

    // Try different notification methods
    if (typeof toastr !== "undefined") {
        // Use Toastr if available
        toastr.options = {
            closeButton: true,
            debug: false,
            newestOnTop: false,
            progressBar: true,
            positionClass: "toast-top-right",
            preventDuplicates: false,
            onclick: null,
            showDuration: "300",
            hideDuration: "1000",
            timeOut: "5000",
            extendedTimeOut: "1000",
            showEasing: "swing",
            hideEasing: "linear",
            showMethod: "fadeIn",
            hideMethod: "fadeOut",
        };

        toastr[type](message, title);
        console.log("✅ Notification shown using Toastr");
    } else if (typeof Swal !== "undefined") {
        // Use SweetAlert if available
        Swal.fire({
            title: title,
            text: message,
            icon: type,
            timer: 5000,
            timerProgressBar: true,
            showConfirmButton: false,
            toast: true,
            position: "top-end",
        });
        console.log("✅ Notification shown using SweetAlert");
    } else {
        // Fallback to browser notification or alert
        if ("Notification" in window && Notification.permission === "granted") {
            new Notification(title, {
                body: message,
                icon: "/favicon.ico",
            });
            console.log("✅ Notification shown using Browser Notification");
        } else {
            alert(`${title}: ${message}`);
            console.log("✅ Notification shown using Alert");
        }
    }
}

// Global test notification function
window.testNotification = function () {
    console.log("🧪 Testing notification system...");

    showNotification(
        "Test Notification",
        "This is a test notification to verify the system is working!",
        "success"
    );
};

// Add keyboard shortcuts for testing
function addKeyboardShortcuts() {
    document.addEventListener("keydown", function (e) {
        // Ctrl+Shift+T for test notification
        if (e.ctrlKey && e.shiftKey && e.key === "T") {
            e.preventDefault();
            console.log("🎹 Keyboard shortcut: Testing notification...");
            window.testNotification();
        }

        // Ctrl+Shift+S for system check
        if (e.ctrlKey && e.shiftKey && e.key === "S") {
            e.preventDefault();
            console.log("🎹 Keyboard shortcut: System check...");
            if (
                window.SupplyPurchaseGlobal &&
                window.SupplyPurchaseGlobal.checkReadiness
            ) {
                window.SupplyPurchaseGlobal.checkReadiness();
            } else {
                console.log("🔍 Basic system check:");
                console.log("- Echo:", !!window.Echo);
                console.log(
                    "- User:",
                    !!(window.Laravel && window.Laravel.user)
                );
                console.log("- Toastr:", typeof toastr !== "undefined");
                console.log("- SweetAlert:", typeof Swal !== "undefined");
            }
        }

        // Ctrl+Shift+N for simulate notification
        if (e.ctrlKey && e.shiftKey && e.key === "N") {
            e.preventDefault();
            console.log("🎹 Keyboard shortcut: Simulating notification...");
            if (window.testEcho) {
                window.testEcho.triggerUserNotification();
            }
        }
    });

    console.log("⌨️ Keyboard shortcuts added:");
    console.log("   Ctrl+Shift+T: Test notification");
    console.log("   Ctrl+Shift+S: System check");
    console.log("   Ctrl+Shift+N: Simulate notification");
}

console.log("🎯 Echo setup complete!");
