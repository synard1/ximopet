<x-default-layout>

    @section('title')
    Data Pembelian Ayam
    @endsection

    @section('breadcrumbs')
    @endsection

    <!-- Include Temporary Authorization Component -->
    <livewire:temp-authorization />

    <div class="card" id="stokTableCard">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
            </div>
            <!--begin::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar" id="cardToolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    {{--
                    <!--begin::Filter-->
                    <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-filter fs-2"></i>Filter</button>
                    <!--begin::Menu 1-->
                    <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                        <!--begin::Header-->
                        <div class="px-7 py-5">
                            <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                        </div>
                        <!--end::Header-->
                        <!--begin::Separator-->
                        <div class="separator border-gray-200"></div>
                        <!--end::Separator-->
                        <!--begin::Content-->
                        <div class="px-7 py-5" data-kt-subscription-table-filter="form">
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Month:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-kt-subscription-table-filter="month" data-hide-search="true">
                                    <option></option>
                                    <option value="jan">January</option>
                                    <option value="feb">February</option>
                                    <option value="mar">March</option>
                                    <option value="apr">April</option>
                                    <option value="may">May</option>
                                    <option value="jun">June</option>
                                    <option value="jul">July</option>
                                    <option value="aug">August</option>
                                    <option value="sep">September</option>
                                    <option value="oct">October</option>
                                    <option value="nov">November</option>
                                    <option value="dec">December</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Status:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-kt-subscription-table-filter="status" data-hide-search="true">
                                    <option></option>
                                    <option value="Active">Active</option>
                                    <option value="Expiring">Expiring</option>
                                    <option value="Suspended">Suspended</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Billing Method:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-kt-subscription-table-filter="billing" data-hide-search="true">
                                    <option></option>
                                    <option value="Auto-debit">Auto-debit</option>
                                    <option value="Manual - Credit Card">Manual - Credit Card</option>
                                    <option value="Manual - Cash">Manual - Cash</option>
                                    <option value="Manual - Paypal">Manual - Paypal</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Product:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-kt-subscription-table-filter="product" data-hide-search="true">
                                    <option></option>
                                    <option value="Basic">Basic</option>
                                    <option value="Basic Bundle">Basic Bundle</option>
                                    <option value="Teams">Teams</option>
                                    <option value="Teams Bundle">Teams Bundle</option>
                                    <option value="Enterprise">Enterprise</option>
                                    <option value=" Enterprise Bundle">Enterprise Bundle</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="reset"
                                    class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-kt-menu-dismiss="true" data-kt-subscription-table-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6"
                                    data-kt-menu-dismiss="true"
                                    data-kt-subscription-table-filter="filter">Apply</button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Menu 1-->
                    <!--end::Filter-->
                    --}}
                    <!--begin::Add user-->
                    <button type="button" class="btn btn-primary" onclick="Livewire.dispatch('showCreateForm')">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data Pembelian Ayam
                    </button>
                    <!--end::Add user-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->

        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <div id="datatable-container">
                <!--begin::Table-->
                <div class="table-responsive">
                    {{ $dataTable->table() }}
                </div>
                <!--end::Table-->
            </div>
            <livewire:livestock-purchase.create />


        </div>
        <!--end::Card body-->
    </div>


    {{--
    <livewire:transaksi.pembelian-list /> --}}
    @include('pages.transaksi.pembelian-stok._modal_pembelian_details')

    <div wire:ignore.self class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="notesForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="notesModalLabel">Catatan Wajib</h5>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Status <span id="statusLabel"></span> tidak dapat dibatalkan atau diubah kembali setelah
                            disimpan.
                        </div>
                        <textarea class="form-control" id="notesInput" name="notes" required
                            placeholder="Masukkan catatan..."></textarea>
                        <input type="hidden" id="statusIdInput" name="id">
                        <input type="hidden" id="statusValueInput" name="status">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Legend Status Pembelian -->
    <div class="card mt-5" id="legendCard">
        <div class="card-header">
            <h3 class="card-title">Legend Status Pembelian</h3>
            <div class="card-toolbar">
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" id="kt_legend_toggle">
                    <i class="ki-duotone ki-down fs-2 rotate-180"></i>
                </button>
            </div>
        </div>
        <div class="card-body collapse" id="legendCardBody">
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                <div class="col">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-secondary me-3">Draft</span>
                        <div>
                            <div class="fw-semibold">Status awal saat membuat pembelian</div>
                            <div class="text-muted small">Belum ada konfirmasi atau validasi<br>Masih bisa
                                diedit/dihapus</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-warning text-dark me-3">Pending</span>
                        <div>
                            <div class="fw-semibold">Sudah dibuat tapi menunggu konfirmasi</div>
                            <div class="text-muted small">Menunggu persetujuan dari pihak terkait<br>Belum bisa diproses
                                lebih lanjut</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-info text-dark me-3">Confirmed</span>
                        <div>
                            <div class="fw-semibold">Sudah dikonfirmasi/disetujui</div>
                            <div class="text-muted small">Siap untuk diproses pengiriman<br>Belum ada pengiriman ternak
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-primary me-3">In Transit</span>
                        <div>
                            <div class="fw-semibold">Ternak sedang dalam perjalanan</div>
                            <div class="text-muted small">Sudah ada nomor DO/Surat Jalan<br>Belum sampai di lokasi
                                tujuan</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-success me-3">Arrived</span>
                        <div>
                            <div class="fw-semibold">Ternak sudah sampai di lokasi tujuan</div>
                            <div class="text-muted small">Sudah dilakukan pemeriksaan awal<br>Siap untuk dipindahkan ke
                                kandang</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-success bg-opacity-75 me-3">In Coop</span>
                        <div>
                            <div class="fw-semibold">Ternak sudah dipindahkan ke kandang</div>
                            <div class="text-muted small">Sudah dilakukan pencatatan di sistem<br>Proses pembelian
                                selesai</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-danger me-3">Cancelled</span>
                        <div>
                            <div class="fw-semibold">Pembelian dibatalkan</div>
                            <div class="text-muted small">Bisa karena berbagai alasan<br>Tidak bisa diproses lebih
                                lanjut</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-dark me-3 text-white">Completed</span>
                        <div>
                            <div class="fw-semibold">Seluruh proses selesai</div>
                            <div class="text-muted small">Semua dokumen lengkap<br>Pembayaran sudah selesai</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Production environment check
            const isProduction = document.querySelector('meta[name="app-env"]')?.content === 'production';
            const log = (message, ...args) => {
                if (!isProduction) {
                    console.log(message, ...args);
                }
            };

            // Hide legend card body on load
            const legendCardBody = document.getElementById('legendCardBody');
            if (legendCardBody) {
                legendCardBody.classList.remove('show');
            }

            // Toggle legend card
            const legendToggle = document.getElementById('kt_legend_toggle');
            if (legendToggle) {
                legendToggle.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    const cardBody = document.getElementById('legendCardBody');
                    
                    if (cardBody.classList.contains('show')) {
                        cardBody.classList.remove('show');
                        icon.classList.add('rotate-180');
                    } else {
                        cardBody.classList.add('show');
                        icon.classList.remove('rotate-180');
                    }
                });
            }
        });
    </script>
    @push('scripts')
    {{ $dataTable->scripts() }}

    {{-- SSE Notification System Integration --}}
    <script src="{{ asset('assets/js/sse-notification-system.js') }}?v=2.0.3"></script>

    <script>
        document.querySelectorAll('[data-kt-button="create_new"]').forEach(function (element) {
			element.addEventListener('click', function () {
				// Simulate delete request -- for demo purpose only
				Swal.fire({
					html: `Preparing Form`,
					icon: "info",
					buttonsStyling: false,
					showConfirmButton: false,
					timer: 2000
				}).then(function () {
                    const cardList = document.getElementById(`stokTableCard`);
                    cardList.style.display = 'none';

                    const cardForm = document.getElementById(`cardForm`);
                    cardForm.style.display = 'block';
				});
			});
		});

        document.addEventListener('livewire:init', function () {
            log('🚀 Livestock Purchase page initialized with PRODUCTION notification integration');

            // ✅ PRODUCTION INTEGRATION: Setup integration with production notification system
            window.LivestockPurchasePageNotifications = {
                init: function() {
                    log('🔧 Initializing Livestock Purchase page notification integration');
                    this.setupProductionIntegration();
                    this.setupLivewireListeners();
                    this.setupKeyboardShortcuts();
                },
                
                setupProductionIntegration: function() {
                    // Wait for production notification system to be ready
                    if (typeof window.NotificationSystem !== 'undefined') {
                        log('✅ Production notification system found - integrating page handlers');
                        this.integrateWithProductionSystem();
                    } else {
                        // Wait and retry
                        setTimeout(() => {
                            if (typeof window.NotificationSystem !== 'undefined') {
                                log('✅ Production notification system loaded - integrating page handlers');
                                this.integrateWithProductionSystem();
                            } else {
                                log('⚠️ Production notification system not available - using fallback mode');
                                this.setupFallbackMode();
                            }
                        }, 2000);
                    }
                },
                
                integrateWithProductionSystem: function() {
                    // Enhance the production notification system to handle livestock purchase page events
                    const originalPollForNotifications = window.NotificationSystem.pollForNotifications;
                    
                    window.NotificationSystem.pollForNotifications = function() {
                        // Call original polling function
                        originalPollForNotifications.call(this);
                        
                        // Additional page-specific handling can be added here
                        window.LivestockPurchasePageNotifications.handlePageSpecificUpdates();
                    };
                    
                    log('🔗 Successfully integrated with production notification system');
                },
                
                setupFallbackMode: function() {
                    log('🔄 Setting up fallback notification mode for Livestock Purchase page');
                    
                    // Direct polling to bridge for this page
                    this.fallbackInterval = setInterval(() => {
                        this.checkForPageUpdates();
                    }, 3000);
                },
                
                checkForPageUpdates: function() {
                    fetch('/testing/notification_bridge.php?since=' + (window.lastPageTimestamp || 0))
                        .then(response => response.json())
                        .then(data => {
                            if (data.notifications && data.notifications.length > 0) {
                                data.notifications.forEach(notification => {
                                    if (this.isLivestockPurchaseNotification(notification)) {
                                        log('📨 [Page] Livestock purchase notification detected:', notification.title);
                                        this.handleLivestockPurchaseNotification(notification);
                                        
                                        if (notification.timestamp > window.lastPageTimestamp) {
                                            window.lastPageTimestamp = notification.timestamp;
                                        }
                                    }
                                });
                            }
                        })
                        .catch(error => {
                            log('⚠️ [Page] Fallback polling error:', error.message);
                        });
                },
                
                isLivestockPurchaseNotification: function(notification) {
                    const title = (notification.title || '').toLowerCase();
                    const message = (notification.message || '').toLowerCase();
                    const source = (notification.source || '').toLowerCase();
                    
                    return title.includes('livestock purchase') || 
                           title.includes('purchase') ||
                           message.includes('livestock purchase') ||
                           source.includes('livestock') ||
                           source.includes('livewire');
                },
                
                handleLivestockPurchaseNotification: function(notification) {
                    log('🎯 [Page] Handling livestock purchase notification:', notification);
                    
                    // Show notification on page
                    if (typeof window.showNotification === 'function') {
                        window.showNotification(
                            notification.title,
                            notification.message,
                            notification.type || 'info'
                        );
                    }
                    
                    // Trigger page-specific updates
                    this.triggerPageUpdates(notification);
                },
                
                triggerPageUpdates: function(notification) {
                    // Refresh DataTable if needed
                    if (notification.data && notification.data.requires_refresh) {
                        log('🔄 [Page] Triggering DataTable refresh from notification');
                        this.refreshDataTable();
                    }
                    
                    // Update form if in edit mode
                    if (notification.data && notification.data.batch_id && window.currentBatchId) {
                        if (notification.data.batch_id === window.currentBatchId) {
                            log('🔄 [Page] Current batch updated - refreshing form');
                            this.refreshCurrentForm();
                        }
                    }
                },
                
                handlePageSpecificUpdates: function() {
                    // This is called from the production system polling
                    // Add any page-specific logic here
                    log('🔍 [Page] Checking for page-specific updates');
                },
                
                refreshDataTable: function() {
                    try {
                        $('.table').each(function() {
                            if ($.fn.DataTable.isDataTable(this)) {
                                $(this).DataTable().ajax.reload(null, false);
                                log('✅ [Page] DataTable refreshed');
                            }
                        });
                    } catch (error) {
                        console.error('❌ [Page] Error refreshing DataTable:', error);
                    }
                },
                
                refreshCurrentForm: function() {
                    // Trigger Livewire refresh if in form mode
                    if (typeof Livewire !== 'undefined') {
                        Livewire.dispatch('refresh');
                        log('✅ [Page] Livewire form refreshed');
                    }
                },
                
                setupLivewireListeners: function() {
                    log('🎧 [Page] Setting up enhanced Livewire listeners');
                    
                    // Enhanced notify-status-change handler
                    Livewire.on('notify-status-change', (data) => {
                        log('📢 [Page] Livewire notification received:', data);
                        
                        const notificationData = Array.isArray(data) ? data[0] : data;
                        
                        // Show notification using production system
                        if (typeof window.NotificationSystem !== 'undefined') {
                            window.NotificationSystem.showNotification(
                                notificationData.title || 'Livestock Purchase Update',
                                notificationData.message || 'A livestock purchase has been updated.',
                                notificationData.type || 'info'
                            );
                        } else {
                            // Fallback notification
                            this.showFallbackNotification(notificationData);
                        }
                        
                        // Handle refresh requirements
                        if (notificationData.requires_refresh || notificationData.show_refresh_button) {
                            this.showRefreshNotification(notificationData);
                        }
                    });
                },
                
                showFallbackNotification: function(data) {
                    if (typeof toastr !== 'undefined') {
                        const toastrType = data.type === 'warning' ? 'warning' : 
                                          data.type === 'error' ? 'error' : 
                                          data.type === 'success' ? 'success' : 'info';
                        toastr[toastrType](data.message, data.title);
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: data.title,
                            text: data.message,
                            icon: data.type === 'error' ? 'error' : data.type === 'warning' ? 'warning' : 'info',
                            timer: 5000,
                            showConfirmButton: false
                        });
                    } else {
                        alert(`${data.title}: ${data.message}`);
                    }
                },
                
                showRefreshNotification: function(data) {
                    log('🔄 [Page] Showing refresh notification');
                    
                    const refreshHtml = `
                        <div class="alert alert-info alert-dismissible fade show position-fixed" 
                             style="top: 140px; right: 20px; z-index: 9998; min-width: 350px; backdrop-filter: blur(10px);">
                            <strong>Data Update Available</strong><br>
                            ${data.message || 'Livestock purchase data has been updated.'}
                            <br><br>
                            <button class="btn btn-primary btn-sm" onclick="window.location.reload()">
                                🔄 Refresh Page
                            </button>
                            <button class="btn btn-secondary btn-sm ms-2" onclick="window.LivestockPurchasePageNotifications.refreshDataTable()">
                                📊 Refresh Table Only
                            </button>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                    
                    // Remove existing refresh notifications
                    $('.alert-info').fadeOut(300, function() { $(this).remove(); });
                    
                    // Add new refresh notification
                    $('body').append(refreshHtml);
                    
                    // Auto-hide after 15 seconds
                    setTimeout(() => {
                        $('.alert-info').fadeOut();
                    }, 15000);
                },
                
                setupKeyboardShortcuts: function() {
                    // Enhanced keyboard shortcuts
                    document.addEventListener('keydown', (e) => {
                        // Ctrl+Shift+P - Test page notification
                        if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                            e.preventDefault();
                            this.testPageNotification();
                        }
                        
                        // Ctrl+Shift+R - Refresh all data
                        if (e.ctrlKey && e.shiftKey && e.key === 'R') {
                            e.preventDefault();
                            this.refreshAllData();
                        }
                        
                        // Ctrl+Shift+S - Show system status
                        if (e.ctrlKey && e.shiftKey && e.key === 'S') {
                            e.preventDefault();
                            this.showSystemStatus();
                        }
                    });
                },
                
                testPageNotification: function() {
                    log('🧪 [Page] Testing page notification system');
                    
                    const testData = {
                        type: 'success',
                        title: 'Page Test Notification',
                        message: 'This is a test notification from the Livestock Purchase page - ' + new Date().toLocaleTimeString(),
                        requires_refresh: false
                    };
                    
                    if (typeof window.NotificationSystem !== 'undefined') {
                        window.NotificationSystem.showNotification(testData.title, testData.message, testData.type);
                    } else {
                        this.showFallbackNotification(testData);
                    }
                },
                
                refreshAllData: function() {
                    log('🔄 [Page] Refreshing all data');
                    this.refreshDataTable();
                    this.refreshCurrentForm();
                },
                
                showSystemStatus: function() {
                    const status = {
                        notificationSystem: typeof window.NotificationSystem !== 'undefined',
                        bridgeActive: window.NotificationSystem ? window.NotificationSystem.bridgeActive : false,
                        connectionStatus: window.NotificationSystem ? window.NotificationSystem.connectionStatus : 'unknown',
                        eventsReceived: window.NotificationSystem ? window.NotificationSystem.eventsReceived : 0
                    };
                    
                    log('📊 [Page] System Status:', status);
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'System Status',
                            html: `
                                <div class="text-start">
                                    <strong>Notification System:</strong> ${status.notificationSystem ? '✅ Active' : '❌ Inactive'}<br>
                                    <strong>Bridge Active:</strong> ${status.bridgeActive ? '✅ Yes' : '❌ No'}<br>
                                    <strong>Connection:</strong> ${status.connectionStatus}<br>
                                    <strong>Events Received:</strong> ${status.eventsReceived}
                                </div>
                            `,
                            icon: 'info'
                        });
                    }
                }
            };
            
            window.addEventListener('hide-datatable', () => {
                $('#datatable-container').hide();
                $('#cardToolbar').hide();
            });

            window.addEventListener('show-datatable', () => {
                $('#datatable-container').show();
                $('#cardToolbar').show();
            });

            window.addEventListener('statusUpdated', () => {
                $('#notesModal').modal('hide');
                window.LaravelDataTables['livestock-purchases-table'].ajax.reload();
            });

            Livewire.on('showForm', function () {
                // Show the form card
                const cardForm = document.getElementById('cardForm');
                if (cardForm) {
                    cardForm.style.display = 'block';
                    log('form ada');
                }
            });

            // ✅ LEGACY LIVEWIRE HANDLERS (Enhanced)
            // SUCCESS AND ERROR HANDLERS
            Livewire.on('success', function (message) {
                log('✅ Livewire success received:', message);
                
                if (typeof window.NotificationSystem !== 'undefined') {
                    window.NotificationSystem.showNotification('Success', Array.isArray(message) ? message[0] : message, 'success');
                } else if (typeof toastr !== 'undefined') {
                    toastr.success(Array.isArray(message) ? message[0] : message);
                } else {
                    alert('Success: ' + (Array.isArray(message) ? message[0] : message));
                }
            });

            Livewire.on('error', function (message) {
                log('❌ Livewire error received:', message);
                
                if (typeof window.NotificationSystem !== 'undefined') {
                    window.NotificationSystem.showNotification('Error', Array.isArray(message) ? message[0] : message, 'error');
                } else if (typeof toastr !== 'undefined') {
                    toastr.error(Array.isArray(message) ? message[0] : message);
                } else {
                    alert('Error: ' + (Array.isArray(message) ? message[0] : message));
                }
            });
        });

        // ✅ SSE INTEGRATION FOR LIVESTOCK PURCHASE NOTIFICATIONS  
        document.addEventListener('DOMContentLoaded', function() {
            log('🐄 Livestock Purchase Index - Initializing SSE notification system');
            
            // Initialize SSE notification system
            if (window.SSENotificationSystem) {
                window.SSENotificationSystem.init();
                
                // Store original handler for livestock purchases
                const originalHandleLivestockPurchaseNotification = window.SSENotificationSystem.handleLivestockPurchaseNotification;
                
                // Override with page-specific logic with enhanced error handling
                window.SSENotificationSystem.handleLivestockPurchaseNotification = function(notification) {
                    log('🔗 SSE-Livewire bridge: Livestock purchase notification received');
                    
                    try {
                        // ✅ SINGLE NOTIFICATION - Only use SSE handler
                        if (originalHandleLivestockPurchaseNotification) {
                            originalHandleLivestockPurchaseNotification.call(this, notification);
                        } else {
                            // Fallback notification handling
                            showGlobalNotification(notification);
                        }
                        
                        // ✅ AUTO RELOAD DATATABLE with timeout protection
                        log('🔄 Auto-reloading Livestock Purchase DataTable...');
                        
                        const reloadTimeout = setTimeout(() => {
                            log('⚠️ DataTable reload timeout - showing reload button');
                            showReloadTableButton();
                        }, 5000); // 5 second timeout
                        
                        // Try multiple DataTable detection methods
                        let reloadSuccess = false;
                        
                        // Method 1: Try correct Livestock Purchase table ID
                        if (window.LaravelDataTables && window.LaravelDataTables['livestock-purchases-table']) {
                            window.LaravelDataTables['livestock-purchases-table'].ajax.reload(function(json) {
                                clearTimeout(reloadTimeout);
                                log('✅ Livestock Purchase DataTable reloaded successfully via LaravelDataTables');
                                reloadSuccess = true;
                            }, false);
                        }
                        // Method 2: Try jQuery DataTable API with correct ID
                        else if ($.fn.DataTable && $.fn.DataTable.isDataTable('#livestock-purchases-table')) {
                            $('#livestock-purchases-table').DataTable().ajax.reload(function() {
                                clearTimeout(reloadTimeout);
                                log('✅ Livestock Purchase DataTable reloaded successfully via jQuery API');
                                reloadSuccess = true;
                            }, false);
                        }
                        // Method 3: Try any DataTable on the page
                        else {
                            $('.table').each(function() {
                                if ($.fn.DataTable && $.fn.DataTable.isDataTable(this)) {
                                    $(this).DataTable().ajax.reload(function() {
                                        clearTimeout(reloadTimeout);
                                        log('✅ Livestock Purchase DataTable reloaded via generic selector:', this.id);
                                        reloadSuccess = true;
                                    }, false);
                                    return false; // Break the loop
                                }
                            });
                        }
                        
                        // Fallback if no DataTable found
                        if (!reloadSuccess) {
                            clearTimeout(reloadTimeout);
                            log('⚠️ No DataTable found, triggering Livewire refresh');
                            if (typeof Livewire !== 'undefined') {
                                Livewire.dispatch('refresh');
                            }
                        }
                        
                    } catch (error) {
                        console.error('❌ Error handling livestock purchase notification:', error);
                        showReloadTableButton();
                    }
                };
                
                log('✅ Livestock Purchase SSE system initialized with auto-reload');
            } else {
                log('⚠️ SSE Notification System not available');
            }
            
            // Helper function to show reload button
            function showReloadTableButton() {
                const notification = {
                    type: 'warning',
                    title: 'Livestock Purchase Data Update',
                    message: 'Click to reload table data',
                    showReloadButton: true
                };
                
                // Create manual reload button
                const reloadButton = document.createElement('button');
                reloadButton.className = 'btn btn-sm btn-warning ms-2';
                reloadButton.innerHTML = '🔄 Reload Table';
                reloadButton.onclick = function() {
                    location.reload();
                };
                
                // Try to add to card toolbar
                const toolbar = document.getElementById('cardToolbar');
                if (toolbar && !toolbar.querySelector('.reload-table-btn')) {
                    reloadButton.classList.add('reload-table-btn');
                    toolbar.appendChild(reloadButton);
                }
            }
        });

        // Log when page is ready
        log('📦 Livestock Purchase page scripts loaded successfully with SSE integration');

        // ✅ Add keyboard shortcut for testing
        document.addEventListener('keydown', function(e) {
            // Ctrl+Shift+P for testing livestock purchase notifications
            if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                e.preventDefault();
                log('🎯 Testing livestock purchase notification via keyboard shortcut');
                testNotificationFromPage();
            }
        });

        // ✅ GLOBAL LIVEWIRE EVENT LISTENER FOR ALL LIVESTOCK PURCHASE NOTIFICATIONS
        document.addEventListener('DOMContentLoaded', function() {
            log('🚀 Livestock Purchase Index - Setting up global notification listeners');
            
            // ✅ Listen to ALL Livewire components for notify-status-change events
            window.addEventListener('livewire:initialized', () => {
                log('📡 Livewire initialized - Setting up global event listeners');
                
                // ✅ Global handler for notify-status-change from ANY Livewire component
                Livewire.on('notify-status-change', (data) => {
                    log('🎯 GLOBAL notify-status-change received:', data);
                    
                    // Extract data from array if needed
                    const notificationData = Array.isArray(data) ? data[0] : data;
                    
                    log('📋 Processing notification data:', notificationData);
                    
                    // Multiple notification methods for maximum reliability
                    showGlobalNotification(notificationData);
                    
                    // Add refresh button if needed
                    if (notificationData.requires_refresh || notificationData.show_refresh_button) {
                        showAdvancedRefreshNotification(notificationData);
                    }
                });
                
                log('✅ Global Livewire event listeners registered');
            });
            
            // ✅ Alternative listener that catches events even before Livewire full initialization
            document.addEventListener('livewire:event', function(event) {
                if (event.detail?.name === 'notify-status-change') {
                    log('🔄 Alternative listener caught notify-status-change:', event.detail.params);
                    
                    const notificationData = Array.isArray(event.detail.params) ? event.detail.params[0] : event.detail.params;
                    showGlobalNotification(notificationData);
                }
            });
            
            log('✅ Alternative event listener registered');
        });

        // ✅ ROBUST GLOBAL NOTIFICATION FUNCTION
        function showGlobalNotification(data) {
            log('🔔 showGlobalNotification called with:', data);
            
            const notification = {
                title: data?.title || 'Livestock Purchase Update',
                message: data?.message || 'A livestock purchase has been updated',
                type: data?.type || 'info'
            };
            
            log('📢 Showing notification:', notification);
            
            // Method 1: Use global showNotification function
            if (typeof window.showNotification === 'function') {
                log('✅ Using window.showNotification');
                window.showNotification(notification.title, notification.message, notification.type);
                return;
            }
            
            // Method 2: Use Toastr if available
            if (typeof toastr !== 'undefined') {
                log('✅ Using toastr notification');
                const toastrType = notification.type === 'warning' ? 'warning' : 
                                  notification.type === 'error' ? 'error' : 
                                  notification.type === 'success' ? 'success' : 'info';
                toastr[toastrType](notification.message, notification.title);
                return;
            }
            
            // Method 3: Browser notification
            if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
                log('✅ Using browser notification');
                new Notification(notification.title, {
                    body: notification.message,
                    icon: '/assets/media/logos/favicon.ico'
                });
                return;
            }
            
            // Method 4: SweetAlert if available
            if (typeof Swal !== 'undefined') {
                log('✅ Using SweetAlert notification');
                Swal.fire({
                    title: notification.title,
                    text: notification.message,
                    icon: notification.type === 'error' ? 'error' : 
                          notification.type === 'warning' ? 'warning' : 
                          notification.type === 'success' ? 'success' : 'info',
                    timer: 5000,
                    showConfirmButton: false
                });
                return;
            }
            
            // Method 5: Custom HTML notification
            createCustomNotification(notification);
        }

        // ✅ CREATE CUSTOM HTML NOTIFICATION
        function createCustomNotification(notification) {
            log('✅ Creating custom HTML notification');
            
            const notificationEl = document.createElement('div');
            notificationEl.className = `alert alert-${notification.type} alert-dismissible fade show position-fixed`;
            notificationEl.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            
            notificationEl.innerHTML = `
                <strong>${notification.title}</strong><br>
                ${notification.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notificationEl);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notificationEl.parentNode) {
                    notificationEl.remove();
                }
            }, 5000);
        }

        // ✅ TABLE RELOAD BUTTON (when auto reload fails)
        function showTableReloadButton() {
            log('⚠️ Showing table reload button');
            
            // Remove existing reload button
            const existingButton = document.getElementById('table-reload-button');
            if (existingButton) existingButton.remove();
            
            const reloadHtml = `
                <div id="table-reload-button" class="alert alert-warning alert-dismissible fade show position-fixed" 
                     style="top: 20px; right: 20px; z-index: 9998; min-width: 300px;">
                    <strong>Table Update Required</strong><br>
                    Livestock purchase data has been updated. Please reload the table to see changes.
                    <br><br>
                    <button class="btn btn-warning btn-sm" onclick="reloadDataTable()">
                        🔄 Reload Table
                    </button>
                    <button type="button" class="btn-close" onclick="document.getElementById('table-reload-button').remove()"></button>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', reloadHtml);
        }
        
        function showAdvancedRefreshNotification(data) {
            log('🔄 Showing advanced refresh notification for:', data);
            
            // Remove existing notifications first
            const existingNotifications = document.querySelectorAll('.refresh-notification');
            existingNotifications.forEach(el => el.remove());
            
            const refreshMessage = `
                <div class="refresh-notification alert alert-info alert-dismissible fade show position-fixed" 
                     style="top: 80px; right: 20px; z-index: 9998; min-width: 350px;">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-info-circle text-info" style="font-size: 24px;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <strong class="d-block">Data Updated</strong>
                            <span class="text-muted">${data.message || 'Livestock purchase data has been updated.'}</span>
                            <br><br>
                            <button class="btn btn-info btn-sm me-2" onclick="reloadDataTable()">
                                <i class="fas fa-table"></i> Reload Table
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="reloadFullPage()">
                                <i class="fas fa-sync"></i> Reload Page
                            </button>
                        </div>
                        <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', refreshMessage);
        }
        
        // ✅ RELOAD DATA TABLE FUNCTION
        function reloadDataTable() {
            log('🔄 Manual DataTable reload requested');
            
            try {
                // Method 1: Use Laravel DataTables with correct table ID
                if (window.LaravelDataTables && window.LaravelDataTables['livestock-purchases-table']) {
                    window.LaravelDataTables['livestock-purchases-table'].ajax.reload(function() {
                        log('✅ DataTable reloaded via LaravelDataTables');
                        removeAllNotifications();
                        showSuccessMessage('Table reloaded successfully!');
                    }, false);
                    return;
                }
                
                // Method 2: Direct DataTable reload
                if ($.fn.DataTable && $('.dataTable').length > 0) {
                    $('.dataTable').DataTable().ajax.reload(function() {
                        log('✅ DataTable reloaded via direct method');
                        removeAllNotifications();
                        showSuccessMessage('Table reloaded successfully!');
                    }, false);
                    return;
                }
                
                // Method 3: Livewire refresh
                if (typeof Livewire !== 'undefined' && Livewire.components && Livewire.components.componentsById) {
                    log('🔄 Trying Livewire component refresh...');
                    const components = Object.values(Livewire.components.componentsById);
                    components.forEach(component => {
                        if (component.name && component.name.includes('livestock-purchase')) {
                            component.call('$refresh');
                        }
                    });
                    removeAllNotifications();
                    showSuccessMessage('Table refreshed via Livewire!');
                    return;
                }
                
                // Method 4: Page reload as last resort
                log('🔄 No DataTable found - triggering page reload');
                showPageReloadButton();
                
            } catch (error) {
                console.error('❌ Error reloading DataTable:', error);
                showPageReloadButton();
            }
        }
        
        function reloadFullPage() {
            showLoadingMessage();
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
        
        function showPageReloadButton() {
            log('🔄 Showing page reload option');
            
            const pageReloadHtml = `
                <div class="alert alert-info alert-dismissible fade show position-fixed" 
                     style="top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; min-width: 350px;">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                        <h5>Manual Refresh Required</h5>
                        <p>Unable to auto-refresh table. Please refresh the page to see latest data.</p>
                        <button class="btn btn-primary" onclick="reloadFullPage()">
                            <i class="fas fa-sync"></i> Refresh Page
                        </button>
                        <button class="btn btn-secondary ms-2" onclick="this.parentElement.parentElement.remove()">
                            Cancel
                        </button>
                    </div>
                </div>
            `;
            
            // Remove existing notifications and show page reload
            removeAllNotifications();
            document.body.insertAdjacentHTML('beforeend', pageReloadHtml);
        }
        
        // ✅ UTILITY FUNCTIONS
        function removeAllNotifications() {
            const notifications = document.querySelectorAll('.refresh-notification, #table-reload-button, .alert.position-fixed');
            notifications.forEach(el => el.remove());
        }
        
        function showSuccessMessage(message) {
            const successHtml = `
                <div class="alert alert-success alert-dismissible fade show position-fixed" 
                     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>${message}</strong>
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', successHtml);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                const successAlert = document.querySelector('.alert-success.position-fixed');
                if (successAlert) successAlert.remove();
            }, 3000);
        }
        
        function showLoadingMessage() {
            const loadingHtml = `
                <div class="alert alert-primary position-fixed" 
                     style="top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; min-width: 300px;">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm me-3" role="status"></div>
                        <strong>Reloading page...</strong>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', loadingHtml);
        }

        // ✅ ENHANCED TESTING FUNCTIONS
        function testNotificationFromPage() {
            log('🧪 Testing notification from Livestock Purchase page');
            
            const testData = {
                type: 'success',
                title: 'Test Notification',
                message: 'This is a test notification from Livestock Purchase page - ' + new Date().toLocaleTimeString(),
                batch_id: 123,
                requires_refresh: false
            };
            
            log('📤 Sending test notification:', testData);
            showGlobalNotification(testData);
            
            // Also trigger Livewire event for testing
            if (typeof Livewire !== 'undefined') {
                log('📡 Triggering Livewire test event');
                // Simulate event dispatch
                const event = new CustomEvent('livewire:event', {
                    detail: {
                        name: 'notify-status-change',
                        params: [testData]
                    }
                });
                document.dispatchEvent(event);
            }
        }

        // ✅ KEYBOARD SHORTCUTS FOR TESTING
        document.addEventListener('keydown', function(e) {
            // Ctrl+Shift+P - Test livestock purchase page notification
            if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                e.preventDefault();
                log('⌨️ Keyboard shortcut: Testing Livestock Purchase notification');
                testNotificationFromPage();
            }
            
            // Ctrl+Shift+L - Test Livewire direct dispatch
            if (e.ctrlKey && e.shiftKey && e.key === 'L') {
                e.preventDefault();
                log('⌨️ Keyboard shortcut: Testing Livewire direct dispatch');
                if (typeof Livewire !== 'undefined') {
                    Livewire.dispatch('notify-status-change', [{
                        type: 'info',
                        title: 'Direct Livewire Test',
                        message: 'Testing direct Livewire event dispatch - ' + new Date().toLocaleTimeString()
                    }]);
                }
            }
        });

        // ✅ MAKE FUNCTIONS GLOBALLY AVAILABLE
        window.testNotificationFromPage = testNotificationFromPage;
        window.showGlobalNotification = showGlobalNotification;
        window.createCustomNotification = createCustomNotification;
        window.showAdvancedRefreshNotification = showAdvancedRefreshNotification;
        window.showTableReloadButton = showTableReloadButton;
        window.reloadDataTable = reloadDataTable;
        window.reloadFullPage = reloadFullPage;
        window.showPageReloadButton = showPageReloadButton;
        window.removeAllNotifications = removeAllNotifications;

        log('🎯 All global notification functions registered');
    </script>
    @endpush


</x-default-layout>