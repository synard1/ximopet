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
    enableStats: true,
    logToConsole: true,
});

// Debug information
if (window.Echo) {
    console.log("✅ Laravel Echo initialized successfully");
    console.log("📡 Broadcasting configuration:", {
        broadcaster: "pusher",
        cluster: process.env.MIX_PUSHER_APP_CLUSTER || "ap1",
        forceTLS: true,
    });
} else {
    console.error("❌ Laravel Echo failed to initialize");
}

// Export for use in other modules
export default window.Echo;
