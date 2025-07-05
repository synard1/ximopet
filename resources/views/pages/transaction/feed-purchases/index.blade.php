<x-default-layout>

    @section('title')
    Data Pembelian Pakan
    @endsection

    @section('breadcrumbs')
    @endsection

    @if(auth()->user()->can('read feed purchasing'))
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

                    @if(auth()->user()->can('create feed purchasing'))
                    <!--begin::Add feed purchasing-->
                    <button type="button" class="btn btn-primary" onclick="Livewire.dispatch('showCreateForm')">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data Pembelian
                    </button>
                    <!--end::Add feed purchasing-->
                    @endif
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
            <livewire:feed-purchases.create />


        </div>
        <!--end::Card body-->
    </div>
    @else
    <div class="card">
        <div class="card-body">
            <div class="text-center">
                <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                <h3 class="text-danger">Unauthorized Access</h3>
                <p class="text-muted">You do not have permission to view feed purchasing data.</p>
            </div>
        </div>
    </div>
    @endif

    {{--
    <livewire:transaksi.pembelian-list /> --}}
    @include('pages.transaction.feed-purchases._modal_pembelian_details')
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

    @push('scripts')
    {{ $dataTable->scripts() }}

    {{-- SSE Notification System Integration --}}
    {{-- <script src="{{ asset('assets/js/sse-notification-system.js') }}?v=2.0.3"></script> --}}

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

                    // $('#supplierDropdown').select2();

                    // Livewire.on('reinitialize-select2', function () {
                    //     $('.select2').select2();
                    // });

                    // console.log('form loaded');
                    // Livewire.dispatch('createPembelian');

                    const cardList = document.getElementById(`stokTableCard`);
                    cardList.style.display = 'none';
                    // cardList.classList.toggle('d-none');

                    const cardForm = document.getElementById(`cardForm`);
                    cardForm.style.display = 'block';
                    // cardList.classList.toggle('d-none');
					// fetchFarm();

				});
				
			});

		});

        document.addEventListener('livewire:init', function () {
            log('🚀 Feed Purchase page initialized with PRODUCTION notification integration');

            // ✅ PRODUCTION INTEGRATION: Setup integration with production notification system
            window.FeedPurchasePageNotifications = {
                init: function() {
                    log('🔧 Initializing Feed Purchase page notification integration');
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
                    // Enhance the production notification system to handle feed purchase page events
                    const originalPollForNotifications = window.NotificationSystem.pollForNotifications;
                    
                    window.NotificationSystem.pollForNotifications = function() {
                        // Call original polling function
                        originalPollForNotifications.call(this);
                        
                        // Additional page-specific handling can be added here
                        window.FeedPurchasePageNotifications.handlePageSpecificUpdates();
                    };
                    
                    log('🔗 Successfully integrated with production notification system');
                },
                
                setupFallbackMode: function() {
                    log('🔄 Setting up fallback notification mode for Feed Purchase page');
                    
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
                                    if (this.isFeedPurchaseNotification(notification)) {
                                        log('📨 [Page] Feed purchase notification detected:', notification.title);
                                        this.handleFeedPurchaseNotification(notification);
                                        
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
                
                isFeedPurchaseNotification: function(notification) {
                    const title = (notification.title || '').toLowerCase();
                    const message = (notification.message || '').toLowerCase();
                    const source = (notification.source || '').toLowerCase();
                    
                    return title.includes('feed purchase') || 
                           title.includes('purchase') ||
                           message.includes('feed purchase') ||
                           source.includes('feed') ||
                           source.includes('livewire');
                },
                
                handleFeedPurchaseNotification: function(notification) {
                    log('🎯 [Page] Handling feed purchase notification:', notification);
                    
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
                                notificationData.title || 'Feed Purchase Update',
                                notificationData.message || 'A feed purchase has been updated.',
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
                            ${data.message || 'Feed purchase data has been updated.'}
                            <br><br>
                            <button class="btn btn-primary btn-sm" onclick="window.location.reload()">
                                🔄 Refresh Page
                            </button>
                            <button class="btn btn-secondary btn-sm ms-2" onclick="window.FeedPurchasePageNotifications.refreshDataTable()">
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
                        message: 'This is a test notification from the Feed Purchase page - ' + new Date().toLocaleTimeString(),
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
            
            // DISABLED: Old polling notification system (replaced by SSE)
            // setTimeout(() => {
            //     window.FeedPurchasePageNotifications.init();
            // }, 1000);

            window.addEventListener('hide-datatable', () => {
                $('#datatable-container').hide();
                $('#cardToolbar').hide();
            });

            window.addEventListener('show-datatable', () => {
                $('#datatable-container').show();
                $('#cardToolbar').show();
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

        // ✅ Add keyboard shortcut for testing
        document.addEventListener('keydown', function(e) {
            // Ctrl+Shift+P for testing feed purchase notifications
            if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                e.preventDefault();
                log('🎯 Testing feed purchase notification via keyboard shortcut');
                testNotificationFromPage();
            }
        });

        // Log when page is ready
        log('📦 Feed Purchase page scripts loaded successfully');

        // ✅ GLOBAL LIVEWIRE EVENT LISTENER FOR ALL FEED PURCHASE NOTIFICATIONS
        document.addEventListener('DOMContentLoaded', function() {
            log('🚀 Feed Purchase Index - Setting up global notification listeners');
            
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
                title: data?.title || 'Feed Purchase Update',
                message: data?.message || 'A feed purchase has been updated',
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
                    Feed purchase data has been updated. Please reload the table to see changes.
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
                            <span class="text-muted">${data.message || 'Feed purchase data has been updated.'}</span>
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
                // Method 1: Use page notification system
                if (window.FeedPurchasePageNotifications && typeof window.FeedPurchasePageNotifications.refreshDataTable === 'function') {
                    window.FeedPurchasePageNotifications.refreshDataTable();
                    log('✅ DataTable reloaded via page notification system');
                    removeAllNotifications();
                    showSuccessMessage('Table reloaded successfully!');
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
                        if (component.name && component.name.includes('feed-purchase')) {
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
            log('🧪 Testing notification from Feed Purchase page');
            
            const testData = {
                type: 'success',
                title: 'Test Notification',
                message: 'This is a test notification from Feed Purchase page - ' + new Date().toLocaleTimeString(),
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
            // Ctrl+Shift+P - Test feed purchase page notification
            if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                e.preventDefault();
                log('⌨️ Keyboard shortcut: Testing Feed Purchase notification');
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

    <!-- SSE Notification System (NEW: No more polling!) -->
    {{-- <script src="{{ asset('assets/js/sse-notification-system.js') }}"></script> --}}

    <script>
        // Integration script to bridge SSE and existing Livewire system
        document.addEventListener('DOMContentLoaded', function() {
            log('🔗 Bridging SSE notification system with existing Livewire handlers');
            
            // Override SSE notification handler to integrate with existing page functions
            if (window.SSENotificationSystem) {
                // Store original handler
                const originalHandleFeedPurchaseNotification = window.SSENotificationSystem.handleFeedPurchaseNotification;
                
                // Override with page-specific logic with error handling
                window.SSENotificationSystem.handleFeedPurchaseNotification = function(notification) {
                    log('🔗 SSE-Livewire bridge: Feed purchase notification received');
                    
                    try {
                        // ✅ DEBOUNCE CHECK - Prevent rapid multiple notifications
                        const notificationKey = `notification_${notification.data?.batch_id}_${notification.data?.new_status}_${Date.now()}`;
                        if (window.lastNotificationKey === notificationKey.substring(0, notificationKey.lastIndexOf('_'))) {
                            log('🔄 Notification debounced (too frequent)', notificationKey);
                            return;
                        }
                        window.lastNotificationKey = notificationKey.substring(0, notificationKey.lastIndexOf('_'));
                        
                        // ✅ SINGLE NOTIFICATION - Only use SSE handler, remove duplicate
                        // Call original SSE handler (already shows notification)
                        if (originalHandleFeedPurchaseNotification) {
                            originalHandleFeedPurchaseNotification.call(this, notification);
                        }
                        
                        // ✅ AUTO RELOAD DATATABLE with timeout protection
                        log('🔄 Auto-reloading DataTable...');
                        
                        // Set timeout to prevent hanging
                        const reloadTimeout = setTimeout(() => {
                            log('⚠️ DataTable reload timeout - showing manual buttons');
                            showTableReloadButton();
                        }, 5000); // 5 second timeout
                        
                        if (window.FeedPurchasePageNotifications && typeof window.FeedPurchasePageNotifications.refreshDataTable === 'function') {
                            try {
                                window.FeedPurchasePageNotifications.refreshDataTable();
                                clearTimeout(reloadTimeout);
                                log('✅ DataTable reloaded via page notification system');
                            } catch (error) {
                                clearTimeout(reloadTimeout);
                                console.error('❌ Page notification system failed:', error);
                                fallbackDataTableReload();
                            }
                        } else {
                            fallbackDataTableReload();
                            clearTimeout(reloadTimeout);
                        }
                        
                        // ✅ Show refresh notification with reload options if needed
                        if (notification.requires_refresh) {
                            setTimeout(() => {
                                showAdvancedRefreshNotification(notification);
                            }, 1000); // Reduced from 2000ms to 1000ms for faster UX
                        }
                        
                    } catch (error) {
                        console.error('❌ Error in SSE notification handler:', error);
                        // Fallback: show manual reload button
                        showTableReloadButton();
                    }
                };
                
                // ✅ FALLBACK DATATABLE RELOAD FUNCTION
                function fallbackDataTableReload() {
                    try {
                        if (typeof $ !== 'undefined' && $.fn.DataTable && $('.dataTable').length > 0) {
                            $('.dataTable').DataTable().ajax.reload(function(json) {
                                log('✅ DataTable reloaded via direct method');
                                if (json && json.recordsTotal !== undefined) {
                                    log(`📊 DataTable now shows ${json.recordsTotal} records`);
                                }
                            }, false);
                        } else {
                            log('⚠️ DataTable not found - showing reload button');
                            showTableReloadButton();
                        }
                    } catch (error) {
                        console.error('❌ DataTable fallback reload failed:', error);
                        showTableReloadButton();
                    }
                }
                
                log('✅ SSE-Livewire bridge configured successfully');
            } else {
                log('⚠️ SSE Notification System not loaded - using fallback');
            }
        });
    </script>

    @endpush

    @livewire('qa-checklist-monitor', ['url' => request()->path()])
    @livewire('admin-monitoring.permission-info')

</x-default-layout>