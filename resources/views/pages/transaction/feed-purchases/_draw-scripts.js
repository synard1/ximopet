// Initialize KTMenu
KTMenu.init();

const showLoadingSpinner = () => {
    const loadingEl = document.createElement("div");
    document.body.append(loadingEl);
    loadingEl.classList.add("page-loader");
    loadingEl.innerHTML = `
        <span class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </span>
    `;
    KTApp.showPageLoading();
    setTimeout(() => {
        KTApp.hidePageLoading();
        loadingEl.remove();
    }, 3000);
};

// Add click event listener to update status buttons
document
    .querySelectorAll('[data-kt-action="update_status"]')
    .forEach(function (element) {
        element.addEventListener("change", function (e) {
            if (this.disabled) return;
            const purchaseId = this.getAttribute("data-kt-transaction-id");
            console.log("Purchase ID: " + purchaseId);
            const status = this.value;
            const current = this.getAttribute("data-current");

            if (status === "cancelled" || status === "completed") {
                // console.log(purchaseId, status, current);
                lastStatusSelect = this;
                document.getElementById("statusIdInput").value = purchaseId;
                document.getElementById("statusValueInput").value = status;
                document.getElementById("notesInput").value = "";
                $("#notesModal").modal("show");
                this.value = current;
            } else {
                console.log("Updating status to " + status);
                Livewire.dispatch("updateStatusFeedPurchase", {
                    purchaseId: purchaseId,
                    status: status,
                    notes: "",
                });
            }
        });
    });

// Submit modal catatan
document.getElementById("notesForm").addEventListener("submit", function (e) {
    e.preventDefault();
    const id = document.getElementById("statusIdInput").value;
    const status = document.getElementById("statusValueInput").value;
    const notes = document.getElementById("notesInput").value;
    if (!notes) {
        alert("Catatan wajib diisi!");
        return;
    }

    // Show immediate feedback notification if available
    if (
        typeof window.FeedPurchaseDataTableNotifications !== "undefined" &&
        typeof window.FeedPurchaseDataTableNotifications
            .showStatusChangeNotification === "function"
    ) {
        window.FeedPurchaseDataTableNotifications.showStatusChangeNotification({
            transactionId: id,
            oldStatus: lastStatusSelect
                ? lastStatusSelect.getAttribute("data-current")
                : "unknown",
            newStatus: status,
            type: "warning",
            title: "Status Change Processing",
            message: `Updating status to ${status} with notes...`,
        });
    }

    Livewire.dispatch("updateStatusFeedPurchase", {
        purchaseId: id,
        status: status,
        notes: notes,
    });
    $("#notesModal").modal("hide");
    lastStatusSelect = null;
});

// Add click event listener to delete buttons
document
    .querySelectorAll('[data-kt-action="delete_row"]')
    .forEach(function (element) {
        element.addEventListener("click", function () {
            Swal.fire({
                text: "Are you sure you want to remove?",
                icon: "warning",
                buttonsStyling: false,
                showCancelButton: true,
                confirmButtonText: "Yes",
                cancelButtonText: "No",
                customClass: {
                    confirmButton: "btn btn-danger",
                    cancelButton: "btn btn-secondary",
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch("deleteFeedPurchaseBatch", [
                        this.getAttribute("data-transaction-id"),
                    ]);
                }
            });
        });
    });

// Add click event listener to update buttons
document
    .querySelectorAll('[data-kt-action="update_stock_purchasing"]')
    .forEach(function (element) {
        element.addEventListener("click", function (e) {
            e.preventDefault();
            // Select parent row
            const parent = e.target.closest("tr");

            // Get transaksi ID
            const transaksiId = event.currentTarget.getAttribute(
                "data-kt-transaksi-id"
            );

            // Get subject name
            const transaksiName = parent.querySelectorAll("td")[1].innerText;

            // Simulate delete request -- for demo purpose only
            Swal.fire({
                html: `Membuka Data <b>` + transaksiName + `</b>`,
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000,
            }).then(function () {
                Livewire.dispatch("editPembelianStock", [transaksiId]);

                // const cardList = document.getElementById(`stokTableCard`);
                // cardList.style.display = 'block';

                // const cardForm = document.getElementById(`cardForm`);
                // cardForm.style.display = 'block';
            });
        });
    });

// Add click event listener to update buttons
document
    .querySelectorAll('[data-kt-action="update_row_stok"]')
    .forEach(function (element) {
        element.addEventListener("click", function (e) {
            e.preventDefault();
            // Select parent row
            const parent = e.target.closest("tr");

            // Get transaksi ID
            const transaksiId = event.currentTarget.getAttribute(
                "data-kt-transaksi-id"
            );

            // Get subject name
            const transaksiName = parent.querySelectorAll("td")[1].innerText;

            // Simulate delete request -- for demo purpose only
            Swal.fire({
                html: `Membuka Data <b>` + transaksiName + `</b>`,
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000,
            }).then(function () {
                Livewire.dispatch("editPembelian", [transaksiId]);

                // flatpickr("#tanggalPembelian", {
                //     enableTime: true,
                //     dateFormat: "Y-m-d H:i",
                // });

                // flatpickr("#tanggal", {
                //     enableTime: true,
                //     dateFormat: "Y-m-d H:i",
                //     defaultDate: '{{ $tanggal }}',
                // });

                getDetailsPurchasing(transaksiId);

                const cardList = document.getElementById(`stokTableCard`);
                cardList.style.display = "none";
                // cardList.classList.toggle('d-none');

                const cardForm = document.getElementById(`stokFormCard`);
                cardForm.style.display = "block";
            });
        });
    });

// Add click event listener to update buttons
document
    .querySelectorAll('[data-kt-action="view_details"]')
    .forEach(function (element) {
        element.addEventListener("click", function (e) {
            e.preventDefault();
            var modal = document.getElementById("kt_modal_pembelian_details");

            // Select parent row
            const parent = e.target.closest("tr");

            // Get transaksi ID
            const transaksiId = event.currentTarget.getAttribute(
                "data-kt-transaction-id"
            );
            const statusData =
                event.currentTarget.getAttribute("data-kt-status");

            // Get suppliers name
            const transaksiSupplier =
                parent.querySelectorAll("td")[2].innerText;
            const transaksiFaktur = parent.querySelectorAll("td")[0].innerText;

            // Simulate delete request -- for demo purpose only
            Swal.fire({
                html: `Membuka Data <b>${transaksiFaktur} - ${transaksiSupplier}</b>`,
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000,
            }).then(function () {
                modal.addEventListener("show.bs.modal", function (event) {
                    // Button that triggered the modal
                    var button = event.relatedTarget;
                    // Extract info from data-* attributes
                    var title = `${transaksiFaktur} - ${transaksiSupplier} Detail Data`;
                    var status = statusData;
                    var statusLabel = document.getElementById("statusLabel");
                    statusLabel.textContent = status;
                    // Update the modal's title
                    var modalTitle = modal.querySelector(".modal-title");
                    modalTitle.textContent = title;
                    // console.log("status", status);
                });
                getDetails(transaksiId, statusData);
                // console.log(transaksiId);

                $("#kt_modal_pembelian_details").modal("show");
                // Livewire.dispatch('editKandang', [transaksiId]);
            });
        });
    });

// Add click event listener to edit No. SJ buttons
document
    .querySelectorAll('[data-kt-action="edit_sj"]')
    .forEach(function (element) {
        element.addEventListener("click", function (e) {
            e.preventDefault();

            // Select parent row
            const parent = e.target.closest("tr");

            // Get transaksi ID
            const transaksiId = this.getAttribute("data-kt-transaksi-id");
            const doNumber = this.getAttribute("data-do-number");

            // Get current No. SJ
            // const currentNoSj = parent.querySelectorAll('td')[1].innerText;

            // Create modal HTML
            const modalHtml = `
            <div class="modal fade" id="editNoSjModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit No. SJ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="text" class="form-control" id="noSjInputModal" value="${doNumber}">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="saveNoSj">Save changes</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

            // Append modal to body
            document.body.insertAdjacentHTML("beforeend", modalHtml);

            // Initialize modal
            const modal = new bootstrap.Modal(
                document.getElementById("editNoSjModal")
            );
            modal.show();

            // Add event listener to save button
            document
                .getElementById("saveNoSj")
                .addEventListener("click", function () {
                    const newNoSj =
                        document.getElementById("noSjInputModal").value;

                    console.log(newNoSj);
                    console.log(transaksiId);

                    // Here you would typically send an AJAX request or use Livewire to update the No. SJ
                    // For this example, we'll just use a Livewire dispatch
                    Livewire.dispatch("updateDoNumber", {
                        transaksiId: transaksiId,
                        newNoSj: newNoSj,
                    });

                    // // Close the modal
                    // modal.hide();

                    // // Show success message
                    // Swal.fire({
                    //     text: 'No. SJ has been updated successfully.',
                    //     icon: 'success',
                    //     buttonsStyling: false,
                    //     confirmButtonText: 'Ok, got it!',
                    //     customClass: {
                    //         confirmButton: 'btn btn-primary'
                    //     }
                    // }).then(function() {
                    //     // Optionally, you can refresh the table or update the specific row here
                    // });
                });

            // Remove modal from DOM when it's hidden
            document
                .getElementById("editNoSjModal")
                .addEventListener("hidden.bs.modal", function () {
                    this.remove();
                });
        });
    });

// // Listen for 'success' event emitted by Livewire
// Livewire.on('success', (message) => {
//     // Reload the transaksis-table datatable
//     LaravelDataTables['transaksis-table'].ajax.reload();
// });

// Feed Purchase DataTable Draw Scripts with Real-time Notifications
console.log("[FeedPurchase DataTable] Initializing draw scripts...");

// Feed Purchase Real-time Notification System
console.log(
    "[FeedPurchase DataTable] Initializing real-time notification system..."
);

window.FeedPurchaseDataTableNotifications = {
    init: function () {
        console.log(
            "[FeedPurchase DataTable] Setting up notification handlers..."
        );
        this.setupRealtimePolling();
        this.setupBroadcastListeners();
        this.setupUIHandlers();
    },

    setupRealtimePolling: function () {
        console.log(
            "[FeedPurchase DataTable] Setting up real-time polling integration"
        );

        // Connect to production notification system if available
        if (typeof window.NotificationSystem !== "undefined") {
            console.log(
                "[FeedPurchase DataTable] Production notification system found - integrating..."
            );
            this.integrateWithProductionBridge();
        } else {
            console.log(
                "[FeedPurchase DataTable] Production notification system not found - setting up fallback"
            );
            this.setupFallbackPolling();
        }
    },

    // Integrate with production notification bridge
    integrateWithProductionBridge: function () {
        // Override the production system notification handler to include DataTable updates
        const originalHandleNotification =
            window.NotificationSystem.handleNotification;

        window.NotificationSystem.handleNotification = function (notification) {
            console.log(
                "[FeedPurchase DataTable] Intercepted notification:",
                notification
            );

            // Call original notification handler
            originalHandleNotification.call(this, notification);

            // Check if this is a feed purchase notification that requires refresh
            const requiresRefresh =
                notification.data &&
                (notification.data.requires_refresh === true ||
                    notification.data.show_refresh_button === true ||
                    notification.requires_refresh === true ||
                    notification.show_refresh_button === true);

            const isFeedPurchaseRelated =
                (notification.title &&
                    notification.title
                        .toLowerCase()
                        .includes("feed purchase")) ||
                (notification.message &&
                    notification.message
                        .toLowerCase()
                        .includes("feed purchase")) ||
                (notification.message &&
                    notification.message.toLowerCase().includes("feed") &&
                    notification.message.toLowerCase().includes("status")) ||
                (notification.data && notification.data.batch_id);

            console.log("[FeedPurchase DataTable] Notification analysis:", {
                requiresRefresh: requiresRefresh,
                isFeedPurchaseRelated: isFeedPurchaseRelated,
                notificationData: notification.data,
            });

            if (isFeedPurchaseRelated && requiresRefresh) {
                console.log(
                    "[FeedPurchase DataTable] Auto-refreshing table due to feed purchase notification"
                );
                setTimeout(() => {
                    window.FeedPurchaseDataTableNotifications.refreshDataTable();
                }, 500); // Small delay to ensure notification is processed first
            }
        };

        console.log(
            "[FeedPurchase DataTable] Successfully integrated with production notification bridge"
        );
    },

    setupFallbackPolling: function () {
        console.log(
            "[FeedPurchase DataTable] Setting up fallback polling every 30 seconds"
        );

        this.fallbackInterval = setInterval(() => {
            // Check for any critical status changes
            const table = $("#feedPurchasing-table").DataTable();
            if (table && table.ajax) {
                console.log("[FeedPurchase DataTable] Fallback refresh check");
                // Could implement change detection here
            }
        }, 30000);
    },

    setupBroadcastListeners: function () {
        if (typeof window.Echo !== "undefined") {
            console.log(
                "[FeedPurchase DataTable] Setting up Echo broadcast listeners"
            );

            // Listen to general feed purchase channel
            window.Echo.channel("feed-purchases").listen(
                "status-changed",
                (e) => {
                    console.log(
                        "[FeedPurchase DataTable] Echo status change received:",
                        e
                    );
                    this.handleStatusChange(e);
                }
            );

            // Listen to user-specific notifications
            if (
                window.Laravel &&
                window.Laravel.user &&
                window.Laravel.user.id
            ) {
                window.Echo.private(
                    "App.Models.User." + window.Laravel.user.id
                ).notification((notification) => {
                    console.log(
                        "[FeedPurchase DataTable] User notification received:",
                        notification
                    );
                    this.handleUserNotification(notification);
                });
            }
        } else {
            console.log(
                "[FeedPurchase DataTable] Laravel Echo not available - relying on bridge notifications"
            );
        }
    },

    setupUIHandlers: function () {
        // Handle refresh button clicks
        $(document).on("click", ".refresh-data-btn", function () {
            console.log("[FeedPurchase DataTable] Manual refresh triggered");
            window.FeedPurchaseDataTableNotifications.refreshDataTable();
        });

        // Handle notification dismissal
        $(document).on("click", ".notification-dismiss", function () {
            $(this).closest(".notification-alert").fadeOut();
        });

        // Handle status dropdown changes with real-time feedback
        $(document).on("change", ".status-select", function () {
            const $select = $(this);
            const transactionId = $select.data("kt-transaction-id");
            const newStatus = $select.val();
            const currentStatus = $select.data("current");

            console.log(
                "FeedPurchase status change initiated: " +
                    currentStatus +
                    " → " +
                    newStatus +
                    " for transaction " +
                    transactionId
            );

            // Show immediate feedback
            window.FeedPurchaseDataTableNotifications.showStatusChangeNotification(
                {
                    transactionId: transactionId,
                    oldStatus: currentStatus,
                    newStatus: newStatus,
                    type: "info",
                    title: "Status Change Processing",
                    message:
                        "Updating status from " +
                        currentStatus +
                        " to " +
                        newStatus +
                        "...",
                }
            );
        });
    },

    // Handle broadcast status changes
    handleStatusChange: function (event) {
        console.log(
            "[FeedPurchase DataTable] Processing broadcast status change:",
            event
        );

        const requiresRefresh =
            event.metadata && event.metadata.requires_refresh;

        // Only auto-refresh data - notification handled by production system
        if (requiresRefresh) {
            console.log(
                "[FeedPurchase DataTable] Auto-refreshing table for critical change"
            );
            this.refreshDataTable();
        }
    },

    // Handle user-specific notifications
    handleUserNotification: function (notification) {
        console.log(
            "[FeedPurchase DataTable] Processing user notification:",
            notification
        );

        if (notification.type === "feed_purchase_status_changed") {
            // Only refresh data - notification handled by production system
            if (
                notification.action_required &&
                notification.action_required.includes("refresh_data")
            ) {
                console.log(
                    "[FeedPurchase DataTable] Auto-refreshing table for user notification"
                );
                this.refreshDataTable();
            }
        }
    },

    // Refresh DataTable
    refreshDataTable: function () {
        try {
            const table = $("#feedPurchasing-table").DataTable();
            if (table && table.ajax) {
                console.log("[FeedPurchase DataTable] Refreshing data...");
                table.ajax.reload(null, false);

                // Show refresh notification
                this.showRefreshNotification();
            }
        } catch (e) {
            console.error(
                "[FeedPurchase DataTable] Error refreshing table:",
                e
            );
        }
    },

    showRefreshNotification: function () {
        // Create or update refresh notification
        let alertHtml =
            '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
        alertHtml += '<i class="fas fa-sync-alt me-2"></i>';
        alertHtml +=
            "<strong>Table Refresh Needed</strong> Data has been updated. ";
        alertHtml +=
            '<button type="button" class="btn btn-sm btn-outline-secondary refresh-data-btn me-2">Refresh Now</button>';
        alertHtml +=
            '<button type="button" class="btn-close notification-dismiss" aria-label="Close"></button>';
        alertHtml += "</div>";

        // Remove existing refresh alerts
        $(".alert-warning").each(function () {
            if ($(this).text().includes("Table Refresh Needed")) {
                $(this).remove();
            }
        });

        // Add new alert
        $("#feedPurchasing-table").closest(".card-body").prepend(alertHtml);

        // Auto-remove after 12 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll(".alert-warning");
            alerts.forEach((alert) => {
                if (alert.textContent.includes("Table Refresh Needed")) {
                    alert.remove();
                }
            });
        }, 12000);
    },

    showStatusChangeNotification: function (data) {
        // Prevent duplicate notifications for the same transaction
        const notificationId =
            "status-change-" + (data.transactionId || "unknown");

        // Remove existing notification for this transaction
        $('.alert[data-notification-id="' + notificationId + '"]').remove();

        // Show immediate feedback for status changes
        const alertClass = "alert-" + (data.type || "info");
        let alertHtml =
            '<div class="alert ' +
            alertClass +
            ' alert-dismissible fade show" role="alert" data-notification-id="' +
            notificationId +
            '">';
        alertHtml +=
            "<strong>" + (data.title || "Status Update") + "</strong> ";
        alertHtml += data.message || "Status is being updated...";
        alertHtml +=
            '<button type="button" class="btn-close notification-dismiss" aria-label="Close"></button>';
        alertHtml += "</div>";

        // Add alert
        $("#feedPurchasing-table").closest(".card-body").prepend(alertHtml);

        // Auto-remove after 3 seconds (reduced from 5)
        setTimeout(() => {
            $('.alert[data-notification-id="' + notificationId + '"]').fadeOut(
                function () {
                    $(this).remove();
                }
            );
        }, 3000);
    },

    getNotificationType: function (priority) {
        const types = {
            high: "warning",
            medium: "info",
            low: "success",
        };
        return types[priority] || "info";
    },

    // Cleanup function
    destroy: function () {
        if (this.fallbackInterval) {
            clearInterval(this.fallbackInterval);
        }
    },
};

// Initialize FeedPurchase DataTable notifications
window.FeedPurchaseDataTableNotifications.init();
console.log(
    "[FeedPurchase DataTable] ✅ Feed Purchase DataTable real-time notifications initialized"
);
