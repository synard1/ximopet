/**
 * SECURITY BLACKLIST NOTIFICATION SYSTEM
 * Shows blacklist notifications to users while allowing access to login page
 *
 * @author AI Assistant
 * @date 2025-06-13
 * @version 1.0.0
 */

window.SecurityBlacklistNotification = {
    /**
     * Initialize blacklist notification system
     */
    init: function () {
        this.checkForBlacklistNotification();
        this.setupBlacklistHandlers();
    },

    /**
     * Check if there's a blacklist notification to show
     */
    checkForBlacklistNotification: function () {
        // Check for Laravel session flash data
        const blacklistData = this.getBlacklistDataFromSession();

        if (blacklistData) {
            this.showBlacklistNotification(blacklistData);
        }
    },

    /**
     * Get blacklist data from Laravel session (passed via meta tags or inline script)
     */
    getBlacklistDataFromSession: function () {
        // Check for meta tag with blacklist data
        const blacklistMeta = document.querySelector(
            'meta[name="security-blacklisted"]'
        );
        if (blacklistMeta) {
            try {
                return JSON.parse(blacklistMeta.getAttribute("content"));
            } catch (e) {
                console.warn("Failed to parse blacklist data from meta tag");
            }
        }

        // Check for global variable set by Laravel
        if (typeof window.securityBlacklisted !== "undefined") {
            return window.securityBlacklisted;
        }

        return null;
    },

    /**
     * Show blacklist notification popup
     */
    showBlacklistNotification: function (data) {
        const message =
            data.message ||
            "Your IP address has been temporarily blocked due to security violations.";
        const expiresAt = data.expires_at;
        const reason = data.reason || "Security violation";

        let detailedMessage = message;

        if (expiresAt) {
            const expiryDate = new Date(expiresAt);
            const now = new Date();
            const hoursRemaining = Math.ceil(
                (expiryDate - now) / (1000 * 60 * 60)
            );

            detailedMessage += `\n\nAccess will be restored in approximately ${hoursRemaining} hours.`;
            detailedMessage += `\nExpires: ${expiryDate.toLocaleString()}`;
        }

        if (reason && reason !== "Security violation") {
            detailedMessage += `\n\nReason: ${reason}`;
        }

        detailedMessage +=
            "\n\nYou can still access the login page, but other areas of the site are restricted.";

        // Try SweetAlert2 first
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "warning",
                title: "IP Address Blacklisted",
                text: detailedMessage,
                confirmButtonText: "I Understand",
                confirmButtonColor: "#d33",
                allowOutsideClick: true,
                allowEscapeKey: true,
                customClass: {
                    popup: "security-blacklist-popup",
                },
            });
        }
        // Fallback to Toastr if available
        else if (typeof toastr !== "undefined") {
            toastr.error(detailedMessage, "IP Blacklisted", {
                timeOut: 0,
                extendedTimeOut: 0,
                closeButton: true,
                tapToDismiss: false,
            });
        }
        // Final fallback to alert
        else {
            alert("IP BLACKLISTED\n\n" + detailedMessage);
        }

        // Add visual indicator to the page
        this.addBlacklistIndicator(data);
    },

    /**
     * Add visual indicator to the page
     */
    addBlacklistIndicator: function (data) {
        // Create a warning banner at the top of the page
        const banner = document.createElement("div");
        banner.id = "security-blacklist-banner";
        banner.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 10px 20px;
            text-align: center;
            font-weight: bold;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            border-bottom: 3px solid #c0392b;
        `;

        const expiresAt = data.expires_at;
        let bannerText = "⚠️ IP BLACKLISTED - Limited Access Mode";

        if (expiresAt) {
            const expiryDate = new Date(expiresAt);
            const now = new Date();
            const hoursRemaining = Math.ceil(
                (expiryDate - now) / (1000 * 60 * 60)
            );
            bannerText += ` - Expires in ~${hoursRemaining}h`;
        }

        banner.innerHTML = `
            <span>${bannerText}</span>
            <button onclick="this.parentElement.style.display='none'" style="
                background: rgba(255,255,255,0.2);
                border: none;
                color: white;
                padding: 5px 10px;
                margin-left: 15px;
                border-radius: 3px;
                cursor: pointer;
                font-size: 12px;
            ">×</button>
        `;

        // Insert banner at the top of the page
        document.body.insertBefore(banner, document.body.firstChild);

        // Adjust body padding to account for banner
        document.body.style.paddingTop = "60px";

        // Auto-hide banner after 30 seconds
        setTimeout(() => {
            if (banner.parentNode) {
                banner.style.transition = "opacity 0.5s";
                banner.style.opacity = "0";
                setTimeout(() => {
                    if (banner.parentNode) {
                        banner.remove();
                        document.body.style.paddingTop = "";
                    }
                }, 500);
            }
        }, 30000);
    },

    /**
     * Setup handlers for blacklist-related events
     */
    setupBlacklistHandlers: function () {
        // Listen for AJAX responses that might contain blacklist info
        if (typeof $ !== "undefined") {
            $(document).ajaxComplete((event, xhr, settings) => {
                if (xhr.status === 403) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.code === "IP_BLACKLISTED") {
                            this.showBlacklistNotification({
                                message: response.message,
                                reason: "AJAX request blocked",
                            });
                        }
                    } catch (e) {
                        // Ignore parsing errors
                    }
                }
            });
        }

        // Listen for fetch responses
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            const response = await originalFetch(...args);

            if (response.status === 403) {
                try {
                    const clonedResponse = response.clone();
                    const data = await clonedResponse.json();
                    if (data.code === "IP_BLACKLISTED") {
                        this.showBlacklistNotification({
                            message: data.message,
                            reason: "Fetch request blocked",
                        });
                    }
                } catch (e) {
                    // Ignore parsing errors
                }
            }

            return response;
        };
    },

    /**
     * Show a simple blacklist warning (for use by other scripts)
     */
    showSimpleWarning: function (
        message = "Access restricted due to IP blacklist"
    ) {
        if (typeof toastr !== "undefined") {
            toastr.warning(message, "Access Restricted", {
                timeOut: 5000,
                closeButton: true,
            });
        } else {
            console.warn("[Security] " + message);
        }
    },
};

// Auto-initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    window.SecurityBlacklistNotification.init();
});

// Also initialize if DOM is already loaded
if (document.readyState === "loading") {
    // DOM still loading
} else {
    window.SecurityBlacklistNotification.init();
}

// Export for global access
window.SecurityBlacklistNotification = window.SecurityBlacklistNotification;
