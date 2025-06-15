/**
 * Sidebar Collapse Functionality
 * Author: System
 * Description: Handle sidebar collapse/expand with smooth animations
 */

class SidebarCollapse {
    constructor() {
        this.body = document.querySelector("body");
        this.sidebar = document.querySelector("#kt_app_sidebar");
        this.toggleBtn = document.querySelector("#kt_sidebar_toggle");
        this.toggleIcon = document.querySelector("#sidebar_toggle_icon");

        this.isCollapsed = false;
        this.savedMenuState = null; // Store menu state before collapse
        this.init();
    }

    init() {
        // Check if elements exist
        if (!this.body || !this.sidebar || !this.toggleBtn) {
            console.warn("Sidebar collapse: Required elements not found");
            return;
        }

        // Load saved state from localStorage
        this.loadState();

        // Add event listeners
        this.addEventListeners();

        // Initialize tooltips for collapsed menu items
        this.initTooltips();

        console.log("✅ Sidebar collapse initialized");
    }

    addEventListeners() {
        // Toggle button click
        this.toggleBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggle();
        });

        // Keyboard shortcut (Ctrl + B)
        document.addEventListener("keydown", (e) => {
            if (e.ctrlKey && e.key === "b") {
                e.preventDefault();
                this.toggle();
            }
        });

        // Window resize handler
        window.addEventListener("resize", () => {
            this.handleResize();
        });

        // Handle mobile menu toggle
        const mobileToggle = document.querySelector(
            "#kt_app_header_menu_toggle"
        );
        if (mobileToggle) {
            mobileToggle.addEventListener("click", () => {
                this.handleMobileToggle();
            });
        }
    }

    toggle() {
        this.isCollapsed = !this.isCollapsed;

        if (this.isCollapsed) {
            this.collapse();
        } else {
            this.expand();
        }

        // Save state
        this.saveState();

        // Emit custom event
        this.emitEvent();
    }

    collapse() {
        this.body.setAttribute("data-kt-app-sidebar-collapsed", "true");
        this.body.setAttribute("data-kt-app-sidebar-fixed", "true");

        // Update sidebar classes
        this.sidebar.classList.remove("drawer-end");
        this.sidebar.classList.add("app-sidebar");

        // Save current menu state before closing
        this.saveMenuState();

        // Close all accordion menus when collapsed
        this.closeAllAccordionMenus();

        // Add tooltip data attributes to menu links
        this.addTooltipAttributes();

        console.log("📌 Sidebar collapsed");
    }

    expand() {
        this.body.setAttribute("data-kt-app-sidebar-collapsed", "false");
        this.body.setAttribute("data-kt-app-sidebar-fixed", "true");

        // Update sidebar classes
        this.sidebar.classList.remove("drawer-end");
        this.sidebar.classList.add("app-sidebar");

        // Restore menu state after a short delay to ensure DOM is ready
        setTimeout(() => {
            this.restoreMenuState();
        }, 100);

        console.log("📖 Sidebar expanded");
    }

    hide() {
        this.body.setAttribute("data-kt-app-sidebar-hidden", "true");
        this.body.setAttribute("data-kt-app-sidebar-fixed", "false");

        // Update sidebar classes for hidden state
        this.sidebar.classList.add("drawer-end");
        this.sidebar.classList.remove("app-sidebar");

        console.log("🫥 Sidebar hidden");
    }

    show() {
        this.body.setAttribute("data-kt-app-sidebar-hidden", "false");
        this.body.setAttribute("data-kt-app-sidebar-fixed", "true");

        // Update sidebar classes for visible state
        this.sidebar.classList.remove("drawer-end");
        this.sidebar.classList.add("app-sidebar");

        console.log("👁️ Sidebar shown");
    }

    saveMenuState() {
        // Save the current state of open accordion menus
        const openMenus = [];
        const openSubmenus = document.querySelectorAll(
            ".menu-sub-accordion.show"
        );

        openSubmenus.forEach((submenu) => {
            const toggle = submenu.previousElementSibling;
            if (toggle && toggle.classList.contains("menu-toggle")) {
                const menuTitle = toggle.querySelector(".menu-title");
                if (menuTitle) {
                    openMenus.push(menuTitle.textContent.trim());
                }
            }
        });

        this.savedMenuState = openMenus;
        console.log("💾 Menu state saved:", this.savedMenuState);
    }

    restoreMenuState() {
        if (!this.savedMenuState || this.savedMenuState.length === 0) {
            return;
        }

        // Restore previously open menus
        this.savedMenuState.forEach((menuTitle) => {
            const menuToggle = Array.from(
                document.querySelectorAll(".menu-toggle")
            ).find((toggle) => {
                const titleElement = toggle.querySelector(".menu-title");
                return (
                    titleElement &&
                    titleElement.textContent.trim() === menuTitle
                );
            });

            if (menuToggle) {
                const submenu = menuToggle.nextElementSibling;
                if (
                    submenu &&
                    submenu.classList.contains("menu-sub-accordion")
                ) {
                    menuToggle.classList.add("active", "show");
                    submenu.classList.add("show");
                }
            }
        });

        console.log("🔄 Menu state restored:", this.savedMenuState);
    }

    closeAllAccordionMenus() {
        // Remove show class from all accordion submenus
        const openSubmenus = document.querySelectorAll(
            ".menu-sub-accordion.show"
        );
        openSubmenus.forEach((submenu) => {
            submenu.classList.remove("show");
        });

        // Remove active and show classes from all menu toggles
        const activeToggles = document.querySelectorAll(
            ".menu-toggle.active, .menu-toggle.show"
        );
        activeToggles.forEach((toggle) => {
            toggle.classList.remove("active", "show");
        });
    }

    addTooltipAttributes() {
        const menuLinks = document.querySelectorAll(".menu-link");
        menuLinks.forEach((link) => {
            const titleElement = link.querySelector(".menu-title");
            if (titleElement) {
                const title = titleElement.textContent.trim();
                link.setAttribute("data-title", title);
            }
        });
    }

    initTooltips() {
        // Initialize tooltips for collapsed state
        this.addTooltipAttributes();
    }

    handleResize() {
        const isMobile = window.innerWidth <= 991.98;

        if (isMobile) {
            // On mobile, always show full sidebar when open
            this.body.setAttribute("data-kt-app-sidebar-collapsed", "false");
        } else {
            // On desktop, restore saved state
            this.loadState();
        }
    }

    handleMobileToggle() {
        const isMobile = window.innerWidth <= 991.98;

        if (isMobile) {
            // Toggle drawer state for mobile
            if (this.sidebar.classList.contains("drawer-end")) {
                this.sidebar.classList.remove("drawer-end");
                this.sidebar.classList.add("drawer-on");
            } else {
                this.sidebar.classList.add("drawer-end");
                this.sidebar.classList.remove("drawer-on");
            }
        }
    }

    saveState() {
        localStorage.setItem("sidebar-collapsed", this.isCollapsed.toString());
    }

    loadState() {
        const savedState = localStorage.getItem("sidebar-collapsed");

        if (savedState !== null) {
            this.isCollapsed = savedState === "true";

            if (this.isCollapsed) {
                this.collapse();
            } else {
                this.expand();
            }
        }
    }

    emitEvent() {
        const event = new CustomEvent("sidebar-toggled", {
            detail: {
                collapsed: this.isCollapsed,
                timestamp: new Date(),
            },
        });

        document.dispatchEvent(event);
    }

    // Public API methods
    getState() {
        return {
            collapsed: this.isCollapsed,
            hidden:
                this.body.getAttribute("data-kt-app-sidebar-hidden") === "true",
        };
    }

    setCollapsed(collapsed = true) {
        if (collapsed !== this.isCollapsed) {
            this.toggle();
        }
    }

    setHidden(hidden = true) {
        if (hidden) {
            this.hide();
        } else {
            this.show();
        }
    }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
    // Wait a bit for other scripts to load
    setTimeout(() => {
        window.sidebarCollapse = new SidebarCollapse();
    }, 100);
});

// Initialize when Livewire is ready (if available)
document.addEventListener("livewire:init", () => {
    if (!window.sidebarCollapse) {
        window.sidebarCollapse = new SidebarCollapse();
    }
});

// Global event listener for sidebar state changes
document.addEventListener("sidebar-toggled", (e) => {
    console.log("🔄 Sidebar state changed:", e.detail);

    // Trigger window resize event to update other components
    setTimeout(() => {
        window.dispatchEvent(new Event("resize"));
    }, 300);
});

// Export for use in other scripts
if (typeof module !== "undefined" && module.exports) {
    module.exports = SidebarCollapse;
}
