<?php

namespace App\DataTables;

use App\Models\LivestockPurchase as Transaksi;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class LivestockPurchaseDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('created_at', function (Transaksi $transaksi) {
                return $transaksi->created_at->format('d M Y, h:i a');
            })
            ->editColumn('farm_id', function (Transaksi $transaksi) {
                $detail = $transaksi->details->first();
                return $detail?->livestock?->farm?->name ?? '-';
            })
            ->editColumn('coop_id', function (Transaksi $transaksi) {
                $detail = $transaksi->details->first();
                return $detail?->livestock?->coop?->name ?? 'N/A';
            })
            ->filterColumn('farm_id', function ($query, $keyword) {
                $query->whereHas('details.livestock.farm', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('coop_id', function ($query, $keyword) {
                $query->whereHas('details.livestock.coop', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->editColumn('tanggal', function (Transaksi $transaksi) {
                return $transaksi->tanggal->format('d-m-Y');
            })
            ->editColumn('supplier_id', function (Transaksi $transaksi) {
                return $transaksi->supplier->name ?? '';
            })
            ->editColumn('expedition_id', function (Transaksi $transaksi) {
                return $transaksi->expedition->name ?? '';
            })
            ->editColumn('details.jumlah', function (Transaksi $transaksi) {
                return formatNumber($transaksi->details->sum('quantity'), 0) ?? 0;
            })
            ->editColumn('details.harga_per_ekor', function (Transaksi $transaksi) {
                return formatRupiah($transaksi->details->sum('price_per_unit'), 0) ?? 0;
            })
            ->editColumn('status', function (Transaksi $transaksi) {
                $statuses = Transaksi::STATUS_LABELS;
                $currentStatus = $transaksi->status;
                $isDisabled = in_array($currentStatus, ['cancelled', 'completed']) ? 'disabled' : '';

                $html = '<div class="d-flex align-items-center">';
                $html .= '<select class="form-select form-select-sm status-select" data-kt-transaction-id="' . $transaksi->id . '" data-kt-action="update_status" data-current="' . $currentStatus . '" ' . $isDisabled . '>';

                foreach ($statuses as $value => $label) {
                    $selected = $value === $currentStatus ? 'selected' : '';
                    $optionDisabled = ($currentStatus === 'in_coop' && $value !== 'completed' && $value !== 'in_coop') ? 'disabled' : '';
                    $optionStyle = ($currentStatus === 'in_coop' && $value !== 'completed' && $value !== 'in_coop') ? 'style="background-color: #f5f5f5; color: #999;"' : '';
                    $html .= "<option value='{$value}' {$selected} {$optionDisabled} {$optionStyle}>{$label}</option>";
                }

                $html .= '</select>';
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['status'])
            ->addColumn('action', function (Transaksi $transaksi) {
                return view('pages.transaction.livestock-purchases._actions', compact('transaksi'));
            })
            // ->editColumn('payload.doc.nama', function (Transaksi $transaksi) {
            //     if ($transaksi->payload) {
            //         if (isset($transaksi->payload['doc']) && !empty($transaksi->payload['doc'])) {
            //             // The array exists and is not empty
            //             return $transaksi->payload['doc']['kode'] . ' - ' . $transaksi->payload['doc']['nama'] ?? '';
            //         }
            //     } else {
            //         return '';
            //     }
            // })
            // ->editColumn('farm_id', function (Transaksi $transaksi) {
            //     return $transaksi->farms->nama ?? '';
            // })
            // ->editColumn('coop_id', function (Transaksi $transaksi) {
            //     return $transaksi->coops->nama ?? '';
            // })
            // ->editColumn('kelompok_ternak_id', function (Transaksi $transaksi) {
            //     return $transaksi->kelompokTernak->name ?? '';
            // })
            // ->editColumn('harga', function (Transaksi $transaksi) {
            //     return formatRupiah($transaksi->harga, 0);
            // })
            // ->editColumn('sub_total', function (Transaksi $transaksi) {
            //     return formatRupiah($transaksi->sub_total, 0);
            // })
            ->setRowId('id');
        // ->filterColumn('rekanan_id', function ($query, $keyword) {
        //     $query->whereHas('rekanans', function ($q) use ($keyword) {
        //         $q->where('nama', 'like', "%{$keyword}%");
        //     });
        // })
        // ->filterColumn('farm_id', function ($query, $keyword) {
        //     $query->whereHas('farms', function ($q) use ($keyword) {
        //         $q->where('nama', 'like', "%{$keyword}%");
        //     });
        // })

        // })
        // ->filterColumn('kelompok_ternak_id', function ($query, $keyword) {
        //     $query->whereHas('kelompokTernak', function ($q) use ($keyword) {
        //         $q->where('name', 'like', "%{$keyword}%");
        //     });
        // });
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Transaksi $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['details.livestock.farm', 'details.livestock.coop', 'supplier', 'expedition'])
            ->orderBy('tanggal', 'ASC');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('livestock-purchases-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
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
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/transaction/livestock-purchases/_draw-scripts.js')) . "}")
            ->parameters([
                'initComplete' => 'function() {
                    // Set user info for private channel access
                    if (typeof window.Laravel === "undefined") {
                        window.Laravel = {};
                    }
                    if (typeof window.Laravel.user === "undefined") {
                        window.Laravel.user = { id: ' . (auth()->check() ? auth()->id() : 'null') . ' };
                    }
                    
                    // ✅ PRODUCTION REAL-TIME NOTIFICATION SYSTEM INTEGRATION
                    window.LivestockPurchaseDataTableNotifications = window.LivestockPurchaseDataTableNotifications || {
                        init: function() {
                            console.log("[DataTable] Initializing real-time notifications for Livestock Purchase DataTable");
                            this.setupRealtimePolling();
                            this.setupUIHandlers();
                            this.setupBroadcastListeners();
                        },
                        
                        // Real-time polling integration with production notification bridge
                        setupRealtimePolling: function() {
                            console.log("[DataTable] Setting up real-time polling integration");
                            
                            // Connect to production notification system if available
                            if (typeof window.NotificationSystem !== "undefined") {
                                console.log("[DataTable] Production notification system found - integrating...");
                                
                                // Hook into the notification system polling
                                this.integrateWithProductionBridge();
                            } else {
                                console.log("[DataTable] Production notification system not found - setting up fallback");
                                this.setupFallbackPolling();
                            }
                        },
                        
                        // Integrate with production notification bridge
                        integrateWithProductionBridge: function() {
                            // Override the production system notification handler to include DataTable updates
                            const originalHandleNotification = window.NotificationSystem.handleNotification;
                            
                            window.NotificationSystem.handleNotification = function(notification) {
                                console.log("[DataTable] Intercepted notification:", notification);
                                
                                // Call original notification handler
                                originalHandleNotification.call(this, notification);
                                
                                // Check if this is a supply purchase notification that requires refresh
                                const requiresRefresh = notification.data && (
                                    notification.data.requires_refresh === true || 
                                    notification.data.show_refresh_button === true ||
                                    notification.requires_refresh === true ||
                                    notification.show_refresh_button === true
                                );
                                
                                const isLivestockPurchaseRelated = (
                                    (notification.title && notification.title.toLowerCase().includes("livestock purchase")) ||
                                    (notification.message && notification.message.toLowerCase().includes("livestock purchase")) ||
                                    (notification.message && notification.message.toLowerCase().includes("purchase") && notification.message.toLowerCase().includes("status")) ||
                                    (notification.data && notification.data.batch_id)
                                );
                                
                                console.log("[DataTable] Notification analysis:", {
                                    requiresRefresh: requiresRefresh,
                                    isLivestockPurchaseRelated: isLivestockPurchaseRelated,
                                    notificationData: notification.data
                                });
                                
                                if (isLivestockPurchaseRelated && requiresRefresh) {
                                    console.log("[DataTable] Auto-refreshing table due to livestock purchase notification");
                                    setTimeout(() => {
                                        window.LivestockPurchaseDataTableNotifications.refreshDataTable();
                                    }, 500); // Small delay to ensure notification is processed first
                                }
                            };
                            
                            console.log("[DataTable] Successfully integrated with production notification bridge");
                        },
                        
                        // Fallback polling for environments without production bridge
                        setupFallbackPolling: function() {
                            console.log("[DataTable] Setting up fallback notification polling");
                            
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
                                        console.log("[DataTable] New notifications detected - refreshing table");
                                        this.refreshDataTable();
                                        this.lastNotificationCount = data.total_notifications;
                                    }
                                })
                                .catch(error => {
                                    console.log("[DataTable] Fallback polling error:", error.message);
                                });
                        },
                        
                        // Setup traditional broadcast listeners (Echo/Pusher)
                        setupBroadcastListeners: function() {
                            if (typeof window.Echo !== "undefined") {
                                console.log("[DataTable] Setting up Echo broadcast listeners");
                                
                                // Listen to general livestock purchase channel
                                window.Echo.channel("livestock-purchases")
                                    .listen("status-changed", (e) => {
                                        console.log("[DataTable] Echo status change received:", e);
                                        this.handleStatusChange(e);
                                    });
                                
                                                                 // Listen to user-specific notifications
                                 if (window.Laravel && window.Laravel.user && window.Laravel.user.id) {
                                     window.Echo.private(`App.Models.User.${window.Laravel.user.id}`)
                                         .notification((notification) => {
                                            console.log("[DataTable] User notification received:", notification);
                                             this.handleUserNotification(notification);
                                         });
                                 }
                            } else {
                                console.log("[DataTable] Laravel Echo not available - relying on bridge notifications");
                            }
                        },
                        
                        setupUIHandlers: function() {
                            // Handle refresh button clicks
                            $(document).on("click", ".refresh-data-btn", function() {
                                console.log("[DataTable] Manual refresh triggered");
                                window.LivestockPurchaseDataTableNotifications.refreshDataTable();
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
                                
                                console.log(`[DataTable] Status change initiated: ${currentStatus} → ${newStatus} for transaction ${transactionId}`);
                                
                                // Show immediate feedback
                                window.LivestockPurchaseDataTableNotifications.showStatusChangeNotification({
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
                            console.log("[DataTable] Processing broadcast status change:", event);
                            
                            const requiresRefresh = (event.metadata && event.metadata.requires_refresh);
                            
                            // Only auto-refresh data - notification handled by production system
                            if (requiresRefresh) {
                                console.log("[DataTable] Auto-refreshing table for critical change");
                                this.refreshDataTable();
                            }
                        },
                        
                        // Handle user-specific notifications (FIXED: No duplicate notifications)
                        handleUserNotification: function(notification) {
                            console.log("[DataTable] Processing user notification:", notification);
                            
                            if (notification.type === "livestock_purchase_status_changed") {
                                // Only refresh data - notification handled by production system
                                if (notification.action_required && notification.action_required.includes("refresh_data")) {
                                    console.log("[DataTable] Auto-refreshing table for user notification");
                                    this.refreshDataTable();
                                }
                            }
                        },
                        
                        // Refresh DataTable
                        refreshDataTable: function() {
                            console.log("[DataTable] Attempting to refresh DataTable...");
                            
                            // Enhanced debugging
                            console.log("[DataTable] Table ID check:", {
                                tableExists: $.fn.DataTable.isDataTable("#livestock-purchases-table"),
                                tableElement: document.getElementById("livestock-purchases-table"),
                                allTables: $(".table").length,
                                dataTableInstances: Object.keys(window.LaravelDataTables || {})
                            });
                            
                            try {
                                let refreshed = false;
                                
                                // Method 1: Try specific Livestock Purchase table ID
                                if ($.fn.DataTable && $.fn.DataTable.isDataTable("#livestock-purchases-table")) {
                                    $("#livestock-purchases-table").DataTable().ajax.reload(null, false);
                                    console.log("[DataTable] ✅ DataTable refreshed via specific ID: #livestock-purchases-table");
                                    refreshed = true;
                                }
                                
                                // Method 2: Try any DataTable on the page
                                if (!refreshed) {
                                    $(".table").each(function() {
                                        if ($.fn.DataTable && $.fn.DataTable.isDataTable(this)) {
                                            $(this).DataTable().ajax.reload(null, false);
                                            console.log("[DataTable] ✅ DataTable refreshed via class selector:", this.id || "unnamed");
                                            refreshed = true;
                                        }
                                    });
                                }
                                
                                // Method 3: Try window.LaravelDataTables if available
                                if (!refreshed && window.LaravelDataTables) {
                                    Object.keys(window.LaravelDataTables).forEach(tableId => {
                                        try {
                                            window.LaravelDataTables[tableId].ajax.reload(null, false);
                                            console.log("[DataTable] ✅ DataTable refreshed via LaravelDataTables:", tableId);
                                            refreshed = true;
                                        } catch (e) {
                                            console.log("[DataTable] ⚠️ Failed to refresh table via LaravelDataTables:", tableId, e.message);
                                        }
                                    });
                                }
                                
                                if (!refreshed) {
                                    console.log("[DataTable] ⚠️ No DataTable found to refresh");
                                    
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
                            console.log("[DataTable] 💡 Showing manual refresh suggestion");
                            
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
                        
                        // Show status change notification for immediate feedback
                        showStatusChangeNotification: function(data) {
                            // Prevent duplicate notifications for the same transaction
                            const notificationId = "status-change-" + (data.transactionId || "unknown");
                            
                            // Remove existing notification for this transaction
                            $(".alert[data-notification-id=\\"" + notificationId + "\\"]").remove();
                            
                            // Show immediate feedback for status changes
                            const alertClass = "alert-" + (data.type || "info");
                            let alertHtml = "<div class=\\"alert " + alertClass + " alert-dismissible fade show\\" role=\\"alert\\" data-notification-id=\\"" + notificationId + "\\">";
                            alertHtml += "<strong>" + (data.title || "Status Update") + "</strong> ";
                            alertHtml += data.message || "Status is being updated...";
                            alertHtml += "<button type=\\"button\\" class=\\"btn-close notification-dismiss\\" aria-label=\\"Close\\"></button>";
                            alertHtml += "</div>";
                            
                            // Add alert
                            $("#livestock-purchases-table").closest(".card-body").prepend(alertHtml);
                            
                            // Auto-remove after 3 seconds
                            setTimeout(() => {
                                $(".alert[data-notification-id=\\"" + notificationId + "\\"]").fadeOut(function() {
                                    $(this).remove();
                                });
                            }, 3000);
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
                    window.LivestockPurchaseDataTableNotifications.init();
                    console.log("[DataTable] ✅ Livestock Purchase DataTable real-time notifications initialized");
                }'
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('DT_RowIndex')->title('No')->searchable(false),
            Column::make('id')->title('ID')->searchable(true)->visible(env('APP_ENV') === 'local'),
            Column::make('invoice_number')->searchable(true),
            Column::make('tanggal')->title('Tanggal Pembelian')->searchable(true),
            Column::computed('farm_id')->title('Farm')->searchable(true)->visible(true),
            Column::computed('coop_id')->title('Kandang')->searchable(true)->visible(true),
            Column::make('supplier_id')->title('Supplier')->searchable(true)->visible(true),
            Column::make('expedition_id')->title('Ekspedisi')->searchable(true)->visible(false),
            Column::make('details.jumlah')->title('Jumlah')->searchable(false),
            Column::make('details.harga_per_ekor')->title('Harga Per Ekor')->searchable(false),
            Column::make('status')->title('Status')->searchable(true)->visible(true),
            Column::make('created_at')->title('Created Date')
                ->visible(false)
                ->searchable(false)
                ->addClass('text-nowrap details-control'),
            Column::computed('action')
                // ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
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
