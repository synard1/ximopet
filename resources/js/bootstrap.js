import Echo from "laravel-echo";
import Pusher from "pusher-js";

/**
 * Laravel Echo Configuration for Real-time Broadcasting
 *
 * @author AI Assistant
 * @date 2024-12-11
 */

// Make Pusher available globally
window.Pusher = Pusher;

// Configure Laravel Echo
window.Echo = new Echo({
    broadcaster: "pusher",
    key: process.env.MIX_PUSHER_APP_KEY || "your-pusher-key",
    cluster: process.env.MIX_PUSHER_APP_CLUSTER || "ap1",
    forceTLS: true,
    enabledTransports: ["ws", "wss"],

    // Authentication for private channels
    auth: {
        headers: {
            "X-CSRF-TOKEN":
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "",
        },
    },

    // Enable stats addons for debugging
    enableStats: !isProduction, // Only enable stats in non-production
    logToConsole: !isProduction, // Only log to console in non-production
});

// Debug information
if (window.Echo) {
    log("✅ Laravel Echo initialized successfully");
    log("📡 Broadcasting configuration:", {
        broadcaster: "pusher",
        cluster: process.env.MIX_PUSHER_APP_CLUSTER || "ap1",
        forceTLS: true,
    });
} else {
    console.error("❌ Laravel Echo failed to initialize"); // Keep error logging in all environments
}

// Export for use in other modules
export default window.Echo;
