/**
 * SECURITY PROTECTION SYSTEM
 * Proteksi keamanan untuk mencegah developer tools di production environment
 *
 * @author AI Assistant
 * @date 2024-12-19
 * @version 1.0.0
 */

// Security configuration
window.SecurityProtection = {
    isProduction: false,
    isEnabled: false,
    checkInterval: null,
    warningCount: 0,
    maxWarnings: 3,
    redirectUrl: "/login",
    logoutUrl: "/logout",
    violationHistory: [],
    lastViolationTime: 0,
    violationCooldown: 5000, // 5 seconds between violations

    // Detection methods
    detectionMethods: {
        devtools: true,
        rightClick: true,
        keyboardShortcuts: true,
        consoleAccess: true,
        debugger: true,
    },

    // Define a whitelist of IP addresses
    ipWhitelist: [
        "192.168.1.1", // Example IPs
        "203.0.113.0",
        "127.0.0.1",
        "localhost",
        "demo51",
        "192.168.1.100",
        "192.168.1.101",
        "192.168.1.102",
        "192.168.1.103",
        // Add more IPs as needed
    ],

    // Initialize security protection
    init: function () {
        this.detectEnvironment();
        this.checkIpWhitelist(); // Check IP whitelist

        if (this.isProduction && this.isEnabled) {
            this.debugLog("🔒 Security Protection: ENABLED for production");
            this.setupProtection();
        } else {
            this.debugLog("🔓 Security Protection: DISABLED for development");
        }
    },

    // Detect environment from meta tags or Laravel config
    detectEnvironment: function () {
        // Check meta tag first
        const envMeta = document.querySelector('meta[name="app-env"]');
        if (envMeta) {
            this.isProduction =
                envMeta.getAttribute("content") === "production";
        }

        // Check Laravel config if available
        if (typeof window.Laravel !== "undefined" && window.Laravel.config) {
            if (window.Laravel.config.app_env !== undefined) {
                this.isProduction =
                    window.Laravel.config.app_env === "production";
            }
        }

        // Fallback: check hostname patterns
        if (!this.isProduction) {
            const hostname = window.location.hostname;
            // this.isProduction = !hostname.includes("localhost");
            !hostname.includes("localhost") &&
                !hostname.includes("127.0.0.1") &&
                !hostname.includes(".local") &&
                !hostname.includes("demo51");
        }

        // Enable protection in production
        this.isEnabled = this.isProduction;

        this.debugLog("🔍 Environment Detection:", {
            hostname: window.location.hostname,
            isProduction: this.isProduction,
            isEnabled: this.isEnabled,
        });
    },

    // Setup all protection mechanisms
    setupProtection: function () {
        this.debugLog("🛡️ Setting up security protection mechanisms...");

        if (this.detectionMethods.devtools) {
            this.setupDevToolsDetection();
        }

        if (this.detectionMethods.rightClick) {
            if (this.isProduction) {
                this.disableRightClick();
                console.log("Right-click disabled");
            }
        }

        if (this.detectionMethods.keyboardShortcuts) {
            this.disableKeyboardShortcuts();
        }

        if (this.detectionMethods.consoleAccess) {
            this.protectConsole();
        }

        if (this.detectionMethods.debugger) {
            this.setupDebuggerDetection();
        }

        // Setup periodic checks
        this.startPeriodicChecks();

        this.debugLog("✅ Security protection mechanisms activated");
    },

    // DevTools detection using multiple methods
    setupDevToolsDetection: function () {
        this.debugLog("🔍 Setting up DevTools detection...");

        // Method 1: Window size detection
        this.setupWindowSizeDetection();

        // Method 2: Console detection
        this.setupConsoleDetection();

        // Method 3: Debugger statement detection
        this.setupDebuggerStatementDetection();

        // Method 4: Performance timing detection
        this.setupPerformanceDetection();
    },

    // Window size detection method
    setupWindowSizeDetection: function () {
        let threshold = 160;

        setInterval(() => {
            if (this.isEnabled) {
                const widthThreshold =
                    window.outerWidth - window.innerWidth > threshold;
                const heightThreshold =
                    window.outerHeight - window.innerHeight > threshold;

                if (widthThreshold || heightThreshold) {
                    this.handleSecurityViolation(
                        "DevTools detected via window size analysis"
                    );
                }
            }
        }, 1000);
    },

    // Console detection method
    setupConsoleDetection: function () {
        let devtools = {
            open: false,
            orientation: null,
        };

        const threshold = 160;

        setInterval(() => {
            if (this.isEnabled) {
                if (
                    window.outerHeight - window.innerHeight > threshold ||
                    window.outerWidth - window.innerWidth > threshold
                ) {
                    if (!devtools.open) {
                        devtools.open = true;
                        this.handleSecurityViolation(
                            "DevTools detected via console analysis"
                        );
                    }
                } else {
                    devtools.open = false;
                }
            }
        }, 500);
    },

    // Debugger statement detection
    setupDebuggerStatementDetection: function () {
        setInterval(() => {
            if (this.isEnabled) {
                const start = performance.now();
                debugger;
                const end = performance.now();

                if (end - start > 100) {
                    this.handleSecurityViolation(
                        "Debugger statement execution detected"
                    );
                }
            }
        }, 2000);
    },

    // Performance timing detection
    setupPerformanceDetection: function () {
        setInterval(() => {
            if (this.isEnabled) {
                const start = performance.now();
                console.clear();
                const end = performance.now();

                if (end - start > 1) {
                    this.handleSecurityViolation(
                        "Console manipulation detected"
                    );
                }
            }
        }, 3000);
    },

    // Disable right-click context menu
    disableRightClick: function () {
        document.addEventListener("contextmenu", (e) => {
            if (this.isEnabled) {
                e.preventDefault();
                this.showWarning("Right-click is disabled in production mode");
                return false;
            }
        });

        this.debugLog("🚫 Right-click disabled");
    },

    // Disable keyboard shortcuts
    disableKeyboardShortcuts: function () {
        const blockedKeys = [
            { key: "F12" },
            { key: "I", ctrl: true, shift: true },
            { key: "J", ctrl: true, shift: true },
            { key: "C", ctrl: true, shift: true },
            { key: "U", ctrl: true },
            { key: "S", ctrl: true },
            { key: "A", ctrl: true },
            { key: "P", ctrl: true },
            { key: "F", ctrl: true },
        ];

        document.addEventListener("keydown", (e) => {
            if (this.isEnabled) {
                for (let blocked of blockedKeys) {
                    if (e.key === blocked.key) {
                        if (blocked.ctrl && !e.ctrlKey) continue;
                        if (blocked.shift && !e.shiftKey) continue;
                        if (blocked.alt && !e.altKey) continue;

                        e.preventDefault();
                        e.stopPropagation();
                        this.showWarning(
                            "Keyboard shortcut disabled in production mode"
                        );
                        return false;
                    }
                }
            }
        });

        this.debugLog("⌨️ Keyboard shortcuts disabled");
    },

    // Protect console access
    protectConsole: function () {
        if (this.isEnabled) {
            // Override console methods
            const originalLog = console.log;
            const originalError = console.error;
            const originalWarn = console.warn;
            const originalInfo = console.info;

            console.log = () => {};
            console.error = () => {};
            console.warn = () => {};
            console.info = () => {};
            console.debug = () => {};
            console.trace = () => {};
            console.dir = () => {};
            console.dirxml = () => {};
            console.table = () => {};
            console.clear = () => {};

            // Detect console access attempts
            Object.defineProperty(console, "_commandLineAPI", {
                get: () => {
                    this.handleSecurityViolation(
                        "Console access attempt detected"
                    );
                    return {};
                },
            });

            this.debugLog("🔒 Console access protected");
        }
    },

    // Setup debugger detection
    setupDebuggerDetection: function () {
        // Anti-debugging techniques
        setInterval(() => {
            if (this.isEnabled) {
                // Check for debugging
                const before = Date.now();
                (function () {
                    return false;
                })();
                const after = Date.now();

                if (after - before > 100) {
                    this.handleSecurityViolation("Debugging activity detected");
                }
            }
        }, 1000);
    },

    // Start periodic security checks
    startPeriodicChecks: function () {
        this.checkInterval = setInterval(() => {
            if (this.isEnabled) {
                this.performSecurityCheck();
            }
        }, 5000);

        this.debugLog("⏰ Periodic security checks started");
    },

    // Perform comprehensive security check
    performSecurityCheck: function () {
        // Check for common developer tools indicators
        const checks = [
            this.checkForFirebug(),
            this.checkForDevToolsConsole(),
            this.checkForInspectorElements(),
            this.checkForDebuggerBreakpoints(),
        ];

        if (checks.some((check) => check)) {
            this.handleSecurityViolation("Comprehensive security check failed");
        }
    },

    // Check for Firebug
    checkForFirebug: function () {
        return window.console && window.console.firebug;
    },

    // Check for DevTools console
    checkForDevToolsConsole: function () {
        return (
            window.chrome &&
            window.chrome.runtime &&
            window.chrome.runtime.onConnect
        );
    },

    // Check for inspector elements
    checkForInspectorElements: function () {
        const devToolsElements = document.querySelectorAll(
            '[class*="devtools"], [id*="devtools"], [class*="inspector"], [id*="inspector"]'
        );
        return devToolsElements.length > 0;
    },

    // Check for debugger breakpoints
    checkForDebuggerBreakpoints: function () {
        try {
            const start = performance.now();
            eval("debugger");
            const end = performance.now();
            return end - start > 100;
        } catch (e) {
            return false;
        }
    },

    // Handle security violation
    handleSecurityViolation: function (reason) {
        // Check cooldown to prevent spam
        const now = Date.now();
        if (now - this.lastViolationTime < this.violationCooldown) {
            return;
        }
        this.lastViolationTime = now;

        this.warningCount++;

        // Record violation history
        this.violationHistory.push({
            reason: reason,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            url: window.location.href,
        });

        this.debugLog("🚨 Security Violation:", {
            reason: reason,
            warningCount: this.warningCount,
            maxWarnings: this.maxWarnings,
            timestamp: new Date().toISOString(),
        });

        // Send violation to server
        this.recordViolationOnServer(reason);

        // Show warning
        this.showSecurityAlert(reason);

        // If max warnings reached, force logout
        if (this.warningCount >= this.maxWarnings) {
            this.forceLogout(reason);
        }
    },

    // Record violation on server
    recordViolationOnServer: function (reason) {
        try {
            fetch("/api/security/violation", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    reason: reason,
                    metadata: {
                        user_agent: navigator.userAgent,
                        url: window.location.href,
                        timestamp: new Date().toISOString(),
                        violation_count: this.warningCount,
                        violation_history: this.violationHistory.slice(-5), // Last 5 violations
                    },
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    this.debugLog("📡 Violation recorded on server:", data);

                    // Check if IP is now blacklisted
                    if (data.violation_count >= 3) {
                        this.debugLog("🚫 IP has been blacklisted");
                        this.showBlacklistWarning();
                    }
                })
                .catch((error) => {
                    this.debugLog("❌ Error recording violation:", error);
                });
        } catch (e) {
            this.debugLog("❌ Error sending violation to server:", e);
        }
    },

    // Show blacklist warning
    showBlacklistWarning: function () {
        const message =
            "Your IP address has been temporarily blacklisted due to multiple security violations.\n\nAccess will be restricted for 72 hours.";

        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "error",
                title: "IP Blacklisted",
                text: message,
                confirmButtonText: "OK",
                allowOutsideClick: false,
                allowEscapeKey: false,
            }).then(() => {
                this.redirectToLogin();
            });
        } else {
            alert(message);
            this.redirectToLogin();
        }
    },

    // Show security alert
    showSecurityAlert: function (reason) {
        const alertMessage = `Security Alert: ${reason}\n\nWarning ${this.warningCount}/${this.maxWarnings}\n\nDeveloper tools are not allowed in production mode.`;

        // Try SweetAlert first
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "warning",
                title: "Security Alert",
                text: alertMessage,
                confirmButtonText: "I Understand",
                allowOutsideClick: false,
                allowEscapeKey: false,
            });
        } else {
            alert(alertMessage);
        }
    },

    // Show simple warning
    showWarning: function (message) {
        if (typeof toastr !== "undefined") {
            toastr.warning(message, "Security Warning");
        } else {
            console.warn(message);
        }
    },

    // Force logout and redirect
    forceLogout: function (reason) {
        this.debugLog("🔒 Forcing logout due to security violations");

        // Clear all storage
        this.clearAllStorage();

        // Send logout request
        this.sendLogoutRequest();

        // Show final warning
        const message = `Security violation limit exceeded.\n\nReason: ${reason}\n\nYou will be redirected to login page.`;

        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "error",
                title: "Security Violation",
                text: message,
                confirmButtonText: "OK",
                allowOutsideClick: false,
                allowEscapeKey: false,
            }).then(() => {
                this.redirectToLogin();
            });
        } else {
            alert(message);
            this.redirectToLogin();
        }
    },

    // Clear all browser storage
    clearAllStorage: function () {
        try {
            // Clear localStorage
            localStorage.clear();

            // Clear sessionStorage
            sessionStorage.clear();

            // Clear cookies
            document.cookie.split(";").forEach(function (c) {
                document.cookie = c
                    .replace(/^ +/, "")
                    .replace(
                        /=.*/,
                        "=;expires=" + new Date().toUTCString() + ";path=/"
                    );
            });

            // Clear IndexedDB if available
            if (window.indexedDB) {
                indexedDB.databases().then((databases) => {
                    databases.forEach((db) => {
                        indexedDB.deleteDatabase(db.name);
                    });
                });
            }

            this.debugLog("🧹 All browser storage cleared");
        } catch (e) {
            this.debugLog("❌ Error clearing storage:", e);
        }
    },

    // Send logout request to server
    sendLogoutRequest: function () {
        try {
            // Try to send security logout request
            fetch("/api/security/logout", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    reason: "security_violation_limit_exceeded",
                    timestamp: new Date().toISOString(),
                    violation_count: this.warningCount,
                    violation_history: this.violationHistory,
                }),
            }).catch(() => {
                // Ignore errors, we'll redirect anyway
            });
        } catch (e) {
            this.debugLog("❌ Error sending logout request:", e);
        }
    },

    // Redirect to login page
    redirectToLogin: function () {
        setTimeout(() => {
            window.location.href = this.redirectUrl;
        }, 1000);
    },

    // Debug logging (only in development)
    debugLog: function (...args) {
        if (!this.isProduction && typeof console !== "undefined") {
            console.log("[Security Protection]", ...args);
        }
    },

    // Disable protection (for testing)
    disable: function () {
        this.isEnabled = false;
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
        this.debugLog("🔓 Security protection disabled");
    },

    getUserIp: function () {
        return "127.0.0.1";
    },

    // Enable protection
    enable: function () {
        this.isEnabled = true;
        this.setupProtection();
        this.debugLog("🔒 Security protection enabled");
    },

    // Check if the user's IP is in the whitelist
    checkIpWhitelist: function () {
        // Assuming you have a way to get the user's IP address
        const userIp = this.getUserIp(); // Implement this function to retrieve the user's IP

        if (this.ipWhitelist.includes(userIp)) {
            this.isEnabled = false; // Disable all security checks
            this.debugLog("🔓 IP is whitelisted. Security checks disabled.");
        }
    },
};

// Auto-initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    window.SecurityProtection.init();
});

// Also initialize if DOM is already loaded
if (document.readyState === "loading") {
    // DOM still loading
} else {
    window.SecurityProtection.init();
}

// Prevent page unload in production to avoid bypassing
window.addEventListener("beforeunload", function (e) {
    if (
        window.SecurityProtection.isEnabled &&
        window.SecurityProtection.warningCount > 0
    ) {
        const message =
            "Security monitoring is active. Are you sure you want to leave?";
        e.returnValue = message;
        return message;
    }
});

// Export for global access
window.SecurityProtection = window.SecurityProtection;
