/**
 * Main Application JavaScript File
 *
 * @author AI Assistant
 * @date 2024-12-11
 */

// Import Laravel Echo configuration
import "./bootstrap";

// Make sure Laravel user info is available for Echo private channels
if (typeof window.Laravel === "undefined") {
    window.Laravel = {};
}

// Additional global configurations can be added here
console.log("🚀 Application JavaScript initialized");

// Supply Purchase Notification System - Global Setup
window.SupplyPurchaseGlobal = {
    /**
     * Test real-time notification system
     */
    testNotification: function () {
        if (!window.Echo) {
            console.error("❌ Laravel Echo not available for testing");
            return;
        }

        console.log("🧪 Testing Supply Purchase notification system...");

        // Test general channel
        window.Echo.channel("supply-purchases").listen(
            "status-changed",
            (e) => {
                console.log("✅ General channel test received:", e);
            }
        );

        // Test private user channel (if authenticated)
        if (window.Laravel.user && window.Laravel.user.id) {
            window.Echo.private(
                `App.Models.User.${window.Laravel.user.id}`
            ).notification((notification) => {
                console.log(
                    "✅ Private notification test received:",
                    notification
                );
            });
        }

        console.log("🔄 Notification listeners setup complete");
    },

    /**
     * Check system readiness
     */
    checkReadiness: function () {
        const checks = {
            echo: !!window.Echo,
            pusher: !!window.Pusher,
            livewire: !!window.Livewire,
            csrf: !!document.querySelector('meta[name="csrf-token"]'),
            user: !!(window.Laravel && window.Laravel.user),
        };

        console.log("🔍 System Readiness Check:", checks);

        const allReady = Object.values(checks).every((check) => check);
        console.log(
            allReady ? "✅ System fully ready" : "⚠️ Some components missing"
        );

        return checks;
    },
};

// Auto-check readiness when loaded
document.addEventListener("DOMContentLoaded", function () {
    setTimeout(() => {
        window.SupplyPurchaseGlobal.checkReadiness();
    }, 1000);
});
