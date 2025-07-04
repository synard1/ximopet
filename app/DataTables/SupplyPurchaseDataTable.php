<?php

namespace App\DataTables;

// use App\Models\FeedPurchaseBeli as FeedPurchase;
use App\Models\SupplyPurchase;
use App\Models\SupplyPurchaseBatch;
use App\Models\Item;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class SupplyPurchaseDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */

    private function formatRupiah($amount)
    {
        // Convert the number to a string with two decimal places
        $formattedAmount = number_format($amount, 2, ',', '.');

        // Add the currency symbol and return the formatted number
        return "Rp " . $formattedAmount;
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn() // Add this line to include row numbers
            ->editColumn('date', function (SupplyPurchaseBatch $transaction) {
                return $transaction->date->format('d-m-Y');
            })
            ->editColumn('supplier_id', function (SupplyPurchaseBatch $transaction) {
                return $transaction->supplier->name;
            })
            ->editColumn('farm_id', function (SupplyPurchaseBatch $transaction) {
                $firstPurchase = $transaction->supplyPurchases->first();
                return $firstPurchase?->farm?->name ?? '-';
                // return $transaction->supplyPurchases->livestok ?? '';
            })
            ->editColumn('coop_id', function (SupplyPurchaseBatch $transaction) {
                $firstPurchase = $transaction->supplyPurchases->first();
                return $firstPurchase?->livestok?->kandang?->nama ?? '-';
            })
            ->editColumn('total', function (SupplyPurchaseBatch $transaction) {
                $total = $transaction->supplyPurchases->sum(function ($purchase) {
                    return $purchase->quantity * $purchase->price_per_converted_unit;
                    // dd($purchase);
                });

                return $this->formatRupiah($total);
            })
            ->editColumn('status', function (SupplyPurchaseBatch $transaction) {
                $statuses = SupplyPurchaseBatch::STATUS_LABELS;
                $currentStatus = $transaction->status;

                // ✅ SIMPLIFIED PERMISSION CHECK - Fixed permission name
                $canUpdateStatus = auth()->user()->can('update stok management') ||
                    auth()->user()->can('create stok management') ||
                    auth()->user()->hasRole(['Supervisor', 'Admin', 'Super Admin']);

                // If user doesn't have permission, show read-only status
                if (!$canUpdateStatus) {
                    $statusLabel = $statuses[$currentStatus] ?? $currentStatus;
                    return '<span class="badge badge-light-secondary">' . $statusLabel . '</span>';
                }

                // ✅ SIMPLIFIED DROPDOWN LOGIC - Removed complex role-based restrictions
                $isDisabled = in_array($currentStatus, ['cancelled']) ? 'disabled' : '';

                // Get user roles for visibility control
                $userRoles = auth()->user()->roles->pluck('name')->toArray();
                $isSupervisor = in_array('Supervisor', $userRoles) ||
                    in_array('Admin', $userRoles) ||
                    in_array('Super Admin', $userRoles);

                $html = '<div class="d-flex align-items-center">';
                $html .= '<select class="form-select form-select-sm status-select" 
                                  data-kt-transaction-id="' . $transaction->id . '" 
                                  data-kt-action="update_status" 
                                  data-current="' . $currentStatus . '" 
                                  ' . $isDisabled . '>';

                foreach ($statuses as $value => $label) {
                    // ✅ SIMPLIFIED STATUS VISIBILITY - Only restrict 'completed' for non-supervisors
                    if (!$isSupervisor && $value === 'completed' && $currentStatus !== 'completed') {
                        continue;
                    }

                    $selected = $value === $currentStatus ? 'selected' : '';

                    // ✅ SIMPLIFIED OPTION RESTRICTIONS - Only disable reverse status changes
                    $optionDisabled = '';
                    $optionStyle = '';

                    // Prevent going backward from 'arrived' except to 'completed' or 'cancelled'
                    if (
                        $currentStatus === 'arrived' &&
                        !in_array($value, ['arrived', 'completed', 'cancelled'])
                    ) {
                        $optionDisabled = 'disabled';
                        $optionStyle = 'style="background-color: #f5f5f5; color: #999;"';
                    }

                    // Prevent going backward from 'completed'
                    if ($currentStatus === 'completed' && $value !== 'completed') {
                        $optionDisabled = 'disabled';
                        $optionStyle = 'style="background-color: #f5f5f5; color: #999;"';
                    }

                    $html .= "<option value='{$value}' {$selected} {$optionDisabled} {$optionStyle}>{$label}</option>";
                }

                $html .= '</select>';
                $html .= '</div>';

                return $html;
            })
            ->addColumn('action', function (SupplyPurchaseBatch $transaction) {
                return view('pages.transaction.supply-purchases._actions', compact('transaction'));
            })

            ->setRowId('id')
            ->rawColumns(['action', 'status']);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(SupplyPurchaseBatch $model): QueryBuilder
    {
        $query = $model->newQuery();

        if (auth()->user()->hasRole('Operator')) {
            $query->whereHas('supplyPurchases.farm.farmOperators', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        if (auth()->user()->hasRole(['Administrator', 'Manager', 'Supervisor'])) {
            $query->where('company_id', auth()->user()->company_id);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('supplyPurchasing-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            // ->orderBy(0, 'desc')  // This will order by the first visible column (tanggal) in descending order
            ->parameters([
                'scrollX'      =>  true,
                'searching'       =>  true,
                // 'responsive'       =>  true,
                'lengthMenu' => [
                    [10, 25, 50, -1],
                    ['10 rows', '25 rows', '50 rows', 'Show all']
                ],
                'buttons'      => ['export', 'print', 'reload', 'colvis'],
            ])
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/transaction/supply-purchases/_draw-scripts.js')) . "}")
            ->parameters([
                'initComplete' => 'function() {
                    // Set user info for private channel access
                    if (typeof window.Laravel === "undefined") {
                        window.Laravel = {};
                    }
                    if (typeof window.Laravel.user === "undefined") {
                        window.Laravel.user = { id: ' . json_encode(auth()->check() ? auth()->id() : null) . ' };
                    }
                    
                    // ✅ PRODUCTION REAL-TIME NOTIFICATION SYSTEM INTEGRATION
                    window.SupplyPurchaseDataTableNotifications = window.SupplyPurchaseDataTableNotifications || {
                        init: function() {
                            log("[DataTable] Initializing real-time notifications for Supply Purchase DataTable");
                            this.setupRealtimePolling();
                            this.setupUIHandlers();
                            this.setupBroadcastListeners();
                        },
                        
                        // Real-time polling integration with production notification bridge
                        setupRealtimePolling: function() {
                            log("[DataTable] Setting up real-time polling integration");
                            
                            // Connect to production notification system if available
                            if (typeof window.NotificationSystem !== "undefined") {
                                log("[DataTable] Production notification system found - integrating...");
                                
                                // Hook into the notification system polling
                                this.integrateWithProductionBridge();
                            } else {
                                log("[DataTable] Production notification system not found - setting up fallback");
                                this.setupFallbackPolling();
                            }
                        },
                        
                        // Integrate with production notification bridge
                        integrateWithProductionBridge: function() {
                            // Override the production system notification handler to include DataTable updates
                            const originalHandleNotification = window.NotificationSystem.handleNotification;
                            
                            window.NotificationSystem.handleNotification = function(notification) {
                                log("[DataTable] Intercepted notification:", notification);
                                
                                // Call original notification handler
                                originalHandleNotification.call(this, notification);
                                
                                // Check if this is a supply purchase notification that requires refresh
                                const requiresRefresh = notification.data && (
                                    notification.data.requires_refresh === true || 
                                    notification.data.show_refresh_button === true ||
                                    notification.requires_refresh === true ||
                                    notification.show_refresh_button === true
                                );
                                
                                const isSupplyPurchaseRelated = (
                                    (notification.title && notification.title.toLowerCase().includes("supply purchase")) ||
                                    (notification.message && notification.message.toLowerCase().includes("supply purchase")) ||
                                    (notification.message && notification.message.toLowerCase().includes("purchase") && notification.message.toLowerCase().includes("status")) ||
                                    (notification.data && notification.data.batch_id)
                                );
                                
                                log("[DataTable] Notification analysis:", {
                                    requiresRefresh: requiresRefresh,
                                    isSupplyPurchaseRelated: isSupplyPurchaseRelated,
                                    notificationData: notification.data
                                });
                                
                                if (isSupplyPurchaseRelated && requiresRefresh) {
                                    log("[DataTable] Auto-refreshing table due to supply purchase notification");
                                    setTimeout(() => {
                                        window.SupplyPurchaseDataTableNotifications.refreshDataTable();
                                    }, 500); // Small delay to ensure notification is processed first
                                }
                            };
                            
                            log("[DataTable] Successfully integrated with production notification bridge");
                        },
                        
                        // Fallback polling for environments without production bridge
                        setupFallbackPolling: function() {
                            log("[DataTable] Setting up fallback notification polling");
                            
                            this.fallbackInterval = setInterval(() => {
                                this.checkForDataUpdates();
                            }, 5000); // Poll every 5 seconds for fallback
                        },
                        
                        // Check for data updates (fallback method)
                        checkForDataUpdates: function() {
                            // Simple check using bridge endpoint
                            fetch("/testing/notification_bridge.php?action=status")
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success && data.total_notifications > this.lastNotificationCount) {
                                        log("[DataTable] New notifications detected - refreshing table");
                                        this.refreshDataTable();
                                        this.lastNotificationCount = data.total_notifications;
                                    }
                                })
                                .catch(error => {
                                    log("[DataTable] Fallback polling error:", error.message);
                                });
                        },
                        
                        // Setup traditional broadcast listeners (Echo/Pusher)
                        setupBroadcastListeners: function() {
                            if (typeof window.Echo !== "undefined") {
                                log("[DataTable] Setting up Echo broadcast listeners");
                                
                                // Listen to general supply purchase channel
                                window.Echo.channel("supply-purchases")
                                    .listen("status-changed", (e) => {
                                        log("[DataTable] Echo status change received:", e);
                                        this.handleStatusChange(e);
                                    });
                                
                                // Listen to user-specific notifications
                                if (window.Laravel && window.Laravel.user && window.Laravel.user.id) {
                                    window.Echo.private(`App.Models.User.${window.Laravel.user.id}`)
                                        .notification((notification) => {
                                            log("[DataTable] User notification received:", notification);
                                            this.handleUserNotification(notification);
                                        });
                                }
                            } else {
                                log("[DataTable] Laravel Echo not available - relying on bridge notifications");
                            }
                        },
                        
                        setupUIHandlers: function() {
                            // Handle refresh button clicks
                            $(document).on("click", ".refresh-data-btn", function() {
                                log("[DataTable] Manual refresh triggered");
                                window.SupplyPurchaseDataTableNotifications.refreshDataTable();
                            });
                            
                            // Handle notification dismissal
                            $(document).on("click", ".notification-dismiss", function() {
                                $(this).closest(".notification-alert").fadeOut();
                            });
                            
                            // Handle status dropdown changes with real-time feedback
                            $(document).on("change", ".status-select", function() {
                                const $select = $(this);
                                const transactionId = $select.data("kt-transaction-id");
                                const newStatus = $select.val();
                                const currentStatus = $select.data("current");
                                
                                log(`[DataTable] Status change initiated: ${currentStatus} → ${newStatus} for transaction ${transactionId}`);
                                
                                // Show immediate feedback
                                window.SupplyPurchaseDataTableNotifications.showStatusChangeNotification({
                                    transactionId: transactionId,
                                    oldStatus: currentStatus,
                                    newStatus: newStatus,
                                    type: "info",
                                    title: "Status Change Processing",
                                    message: `Updating status from ${currentStatus} to ${newStatus}...`
                                });
                            });
                        },
                        
                        // Handle broadcast status changes (FIXED: No duplicate notifications)
                        handleStatusChange: function(event) {
                            log("[DataTable] Processing broadcast status change:", event);
                            
                            const requiresRefresh = (event.metadata && event.metadata.requires_refresh);
                            
                            // Only auto-refresh data - notification handled by production system
                            if (requiresRefresh) {
                                log("[DataTable] Auto-refreshing table for critical change");
                                this.refreshDataTable();
                            }
                        },
                        
                        // Handle user-specific notifications (FIXED: No duplicate notifications)
                        handleUserNotification: function(notification) {
                            log("[DataTable] Processing user notification:", notification);
                            
                            if (notification.type === "supply_purchase_status_changed") {
                                // Only refresh data - notification handled by production system
                                if (notification.action_required && notification.action_required.includes("refresh_data")) {
                                    log("[DataTable] Auto-refreshing table for user notification");
                                    this.refreshDataTable();
                                }
                            }
                        },
                        
                        // Refresh DataTable
                        refreshDataTable: function() {
                            log("[DataTable] Attempting to refresh DataTable...");
                            
                            try {
                                let refreshed = false;
                                
                                // Method 1: Try specific Supply Purchase table ID
                                if ($.fn.DataTable && $.fn.DataTable.isDataTable("#supplyPurchasing-table")) {
                                    $("#supplyPurchasing-table").DataTable().ajax.reload(null, false);
                                    log("[DataTable] ✅ DataTable refreshed via specific ID: #supplyPurchasing-table");
                                    refreshed = true;
                                }
                                
                                // Method 2: Try any DataTable on the page
                                if (!refreshed) {
                                    $(".table").each(function() {
                                        if ($.fn.DataTable && $.fn.DataTable.isDataTable(this)) {
                                            $(this).DataTable().ajax.reload(null, false);
                                            log("[DataTable] ✅ DataTable refreshed via class selector:", this.id || "unnamed");
                                            refreshed = true;
                                        }
                                    });
                                }
                                
                                // Method 3: Try window.LaravelDataTables if available
                                if (!refreshed && window.LaravelDataTables) {
                                    Object.keys(window.LaravelDataTables).forEach(tableId => {
                                        try {
                                            window.LaravelDataTables[tableId].ajax.reload(null, false);
                                            log("[DataTable] ✅ DataTable refreshed via LaravelDataTables:", tableId);
                                            refreshed = true;
                                        } catch (e) {
                                            log("[DataTable] ⚠️ Failed to refresh table via LaravelDataTables:", tableId, e.message);
                                        }
                                    });
                                }
                                
                                if (!refreshed) {
                                    log("[DataTable] ⚠️ No DataTable found to refresh");
                                    
                                    // Fallback: show manual refresh suggestion
                                    this.showRefreshSuggestion();
                                }
                                
                            } catch (error) {
                                console.error("[DataTable] ❌ Error refreshing DataTable:", error);
                                this.showRefreshSuggestion();
                            }
                        },
                        
                        // Show manual refresh suggestion
                        showRefreshSuggestion: function() {
                            log("[DataTable] 💡 Showing manual refresh suggestion");
                            
                            const suggestionHtml = `
                                <div class="alert alert-warning alert-dismissible fade show position-fixed" 
                                     style="top: 280px; right: 20px; z-index: 9997; min-width: 350px;">
                                    <strong>Table Refresh Needed</strong><br>
                                    Please refresh the page to see the latest data in the table.
                                    <br><br>
                                    <button class="btn btn-warning btn-sm" onclick="window.location.reload()">
                                        <i class="fas fa-sync"></i> Refresh Page Now
                                    </button>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            `;
                            
                            // Remove existing suggestions first
                            const existingSuggestions = document.querySelectorAll(".alert-warning");
                            existingSuggestions.forEach(alert => {
                                if (alert.textContent.includes("Table Refresh Needed")) {
                                    alert.remove();
                                }
                            });
                            
                            document.body.insertAdjacentHTML("beforeend", suggestionHtml);
                            
                            // Auto-remove after 12 seconds
                            setTimeout(() => {
                                const alerts = document.querySelectorAll(".alert-warning");
                                alerts.forEach(alert => {
                                    if (alert.textContent.includes("Table Refresh Needed")) {
                                        alert.remove();
                                    }
                                });
                            }, 12000);
                        },
                        
                        // REMOVED: DataTable-specific notifications (replaced by production notification system)
                        // All notifications now handled by production notification system to avoid duplicates
                        
                        // REMOVED: Status change notifications (handled by production system)
                        // No need for additional notifications as production system handles all notifications
                        
                        getNotificationType: function(priority) {
                            const types = {
                                "high": "warning",
                                "medium": "info", 
                                "low": "success"
                            };
                            return types[priority] || "info";
                        },
                        
                        // Cleanup function
                        destroy: function() {
                            if (this.fallbackInterval) {
                                clearInterval(this.fallbackInterval);
                            }
                        }
                    };
                            
                    // Initialize DataTable notifications
                    window.SupplyPurchaseDataTableNotifications.init();
                    log("[DataTable] ✅ Supply Purchase DataTable real-time notifications initialized");
                }'
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex', 'No.')
                ->title('No.')
                ->addClass('text-center')
                ->width(50),
            Column::make('date')->title('Tanggal Pembelian')->searchable(true),
            // Column::make('no_sj')->title('No. SJ')->searchable(false),
            Column::make('invoice_number')->title('Invoice')->searchable(true),
            Column::make('supplier_id')->title('Supplier')->searchable(true),
            Column::computed('farm_id')->title('Farm')->searchable(true),
            // Column::make('rekanan_id')->title('Nama Supplier')->searchable(true),
            // Column::make('payload.doc.nama')->title('Nama DOC')->searchable(true),
            // Column::make('qty')->searchable(true),
            // Column::make('harga')->searchable(true),
            Column::make('total')->searchable(false),
            Column::make('status')->searchable(false),
            // Column::make('periode')->searchable(true),
            Column::make('created_at')->title('Created Date')->addClass('text-nowrap')->searchable(false)->visible(false),
            Column::computed('action')
                // ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->addClass('text-center')
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Docs_' . date('YmdHis');
    }
}
