<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 REAL-TIME NOTIFICATION TEST CLIENT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-family: 'Arial', sans-serif;
        }

        .container-fluid {
            padding: 20px;
        }

        .status-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 20px;
            margin: 10px 0;
            backdrop-filter: blur(10px);
        }

        .log-area {
            background: #1a1a1a;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            padding: 15px;
            border-radius: 10px;
            height: 400px;
            overflow-y: auto;
            margin: 10px 0;
        }

        .status-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }

        .status-connected {
            background: #28a745;
        }

        .status-disconnected {
            background: #dc3545;
        }

        .status-waiting {
            background: #ffc107;
        }

        .notification-demo {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }

        .test-instruction {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .heartbeat {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="mb-4">🚀 REAL-TIME NOTIFICATION TEST CLIENT</h1>
                <p class="lead">Test notifications from PHP scripts and see them appear here in real-time!</p>
            </div>
        </div>

        <div class="row">
            <!-- Connection Status -->
            <div class="col-md-6">
                <div class="status-card">
                    <h3>📡 Connection Status</h3>
                    <div class="mb-3">
                        <span id="connectionIndicator" class="status-indicator status-waiting"></span>
                        <span id="connectionStatus">Connecting to real-time bridge...</span>
                    </div>
                    <div class="small">
                        <strong>Connected Since:</strong> <span id="connectionTime">Not connected</span><br>
                        <strong>Events Received:</strong> <span id="eventsReceived">0</span><br>
                        <strong>Last Event:</strong> <span id="lastEvent">None</span>
                    </div>
                </div>

                <div class="status-card">
                    <h3>🧪 Test Instructions</h3>
                    <div class="test-instruction">
                        <h5>How to Test Real-Time Notifications:</h5>
                        <ol>
                            <li><strong>Keep this page open</strong> - Connection is active</li>
                            <li><strong>Open Command Prompt/Terminal</strong></li>
                            <li><strong>Navigate to:</strong> <code>C:\laragon\www\demo51</code></li>
                            <li><strong>Run test:</strong> <code>php testing\test_realtime_notification.php</code></li>
                            <li><strong>Watch for notifications</strong> - They should appear immediately!</li>
                        </ol>
                        <div class="alert alert-info mt-3">
                            <strong>💡 Tip:</strong> You should see notifications appear on this page within 1-2 seconds of running the PHP script!
                        </div>
                    </div>
                </div>
            </div>

            <!-- Real-time Logs -->
            <div class="col-md-6">
                <div class="status-card">
                    <h3>📋 Real-Time Event Log</h3>
                    <div id="eventLog" class="log-area">
                        🚀 Initializing real-time notification client...<br>
                        📡 Connecting to Server-Sent Events bridge...<br>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-outline-light btn-sm" onclick="clearLog()">🗑️ Clear Log</button>
                        <button class="btn btn-outline-light btn-sm" onclick="testConnection()">🔄 Test Connection</button>
                        <button class="btn btn-outline-light btn-sm" onclick="reconnect()">🔌 Reconnect</button>
                        <button class="btn btn-outline-light btn-sm" onclick="forceCheckNotifications()">🔔 Check Now</button>
                        <button class="btn btn-outline-light btn-sm" onclick="showLastTimestamp()">🕒 Show Timestamp</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="status-card">
                    <h3>🎯 Manual Testing Buttons</h3>
                    <p>Use these buttons to test different notification methods:</p>

                    <button class="btn btn-primary m-2" onclick="testToastrNotification()">
                        📢 Test Toastr Notification
                    </button>

                    <button class="btn btn-success m-2" onclick="testBrowserNotification()">
                        🔔 Test Browser Notification
                    </button>

                    <button class="btn btn-info m-2" onclick="testSweetAlertNotification()">
                        🍭 Test SweetAlert Notification
                    </button>

                    <button class="btn btn-warning m-2" onclick="testCustomNotification()">
                        🏷️ Test Custom HTML Notification
                    </button>

                    <button class="btn btn-danger m-2" onclick="triggerBackendTest()">
                        🚀 Trigger Backend Event
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Demo Area -->
    <div id="notificationDemo" class="notification-demo"></div>

    <script>
        // 🎯 REAL-TIME NOTIFICATION CLIENT
        let eventSource;
        let eventsReceived = 0;
        let connectionStartTime;

        // 📝 LOGGING FUNCTION
        function logEvent(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const logArea = document.getElementById('eventLog');

            const colors = {
                'success': '#28a745',
                'error': '#dc3545',
                'warning': '#ffc107',
                'info': '#17a2b8'
            };

            const color = colors[type] || '#00ff00';

            logArea.innerHTML += `<span style="color: ${color}">[${timestamp}] ${message}</span><br>`;
            logArea.scrollTop = logArea.scrollHeight;

            console.log(`[${timestamp}] ${message}`);
        }

        // 🔌 CONNECT TO REAL-TIME BRIDGE
        function connectToRealtimeBridge() {
            logEvent('🔌 Connecting to real-time notification bridge...', 'info');

            // Use AJAX polling instead of SSE for better compatibility
            if (!window.lastTimestamp) window.lastTimestamp = 0;
            let pollingInterval;

            function pollForNotifications() {
                const pollUrl = `notification_bridge.php?since=${window.lastTimestamp}`;
                logEvent(`🔍 Polling: ${pollUrl}`, 'info');

                fetch(pollUrl)
                    .then(response => {
                        logEvent(`📡 Response status: ${response.status}`, 'info');
                        return response.json();
                    })
                    .then(data => {
                        logEvent(`📊 Response data:`, 'info');
                        console.log(data);

                        if (data.success !== false && data.notifications && data.notifications.length > 0) {
                            logEvent(`📨 Received ${data.notifications.length} new notifications`, 'success');

                            data.notifications.forEach(notification => {
                                eventsReceived++;

                                logEvent(`🔔 NOTIFICATION: ${notification.title}`, 'success');
                                logEvent(`📋 Message: ${notification.message}`, 'info');
                                logEvent(`🏷️ Type: ${notification.type}, Source: ${notification.source}`, 'info');

                                // Update counters
                                document.getElementById('eventsReceived').textContent = eventsReceived;
                                document.getElementById('lastEvent').textContent = notification.title;

                                // Show notification using multiple methods
                                showMultipleNotifications(notification);

                                // Update last timestamp
                                if (notification.timestamp > window.lastTimestamp) {
                                    window.lastTimestamp = notification.timestamp;
                                }
                            });
                        } else {
                            // No new notifications
                            if (data.notifications && data.notifications.length === 0) {
                                logEvent(`📭 No new notifications (since: ${window.lastTimestamp})`, 'info');
                            }
                        }

                        // Update connection status
                        if (!pollingInterval) {
                            connectionStartTime = new Date();
                            updateConnectionStatus('connected', 'Connected via AJAX polling');
                            document.getElementById('connectionTime').textContent = connectionStartTime.toLocaleTimeString();
                            logEvent('✅ AJAX polling connection established!', 'success');
                        }
                    })
                    .catch(error => {
                        logEvent('❌ Polling error: ' + error.message, 'error');
                        updateConnectionStatus('disconnected', 'Polling error: ' + error.message);

                        // Try to reconnect after 5 seconds
                        setTimeout(() => {
                            logEvent('🔄 Attempting to reconnect...', 'warning');
                            connectToRealtimeBridge();
                        }, 5000);
                    });
            }

            // Start polling every 1 second
            pollForNotifications(); // Initial poll
            pollingInterval = setInterval(pollForNotifications, 1000);

            logEvent('📡 AJAX polling started (1 second interval)', 'info');

            // Store polling interval for cleanup
            window.currentPollingInterval = pollingInterval;
        }

        // 📊 UPDATE CONNECTION STATUS
        function updateConnectionStatus(status, message) {
            const indicator = document.getElementById('connectionIndicator');
            const statusText = document.getElementById('connectionStatus');

            indicator.className = 'status-indicator status-' + status;
            statusText.textContent = message;
        }

        // 🔔 SHOW MULTIPLE NOTIFICATIONS
        function showMultipleNotifications(data) {
            const title = data.title || 'Notification';
            const message = data.message || 'You have a new notification';
            const type = data.type || 'info';

            // Method 1: Toastr
            if (typeof toastr !== 'undefined') {
                const toastrType = type.includes('error') ? 'error' :
                    type.includes('warning') ? 'warning' :
                    type.includes('success') ? 'success' : 'info';
                toastr[toastrType](message, title);
                logEvent('✅ Toastr notification shown', 'success');
            }

            // Method 2: Browser notification
            if (Notification.permission === 'granted') {
                new Notification(title, {
                    body: message,
                    icon: '/favicon.ico'
                });
                logEvent('✅ Browser notification shown', 'success');
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        new Notification(title, {
                            body: message
                        });
                        logEvent('✅ Browser notification permission granted and shown', 'success');
                    }
                });
            }

            // Method 3: SweetAlert
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: title,
                    text: message,
                    icon: type.includes('error') ? 'error' : type.includes('warning') ? 'warning' : type.includes('success') ? 'success' : 'info',
                    timer: 4000,
                    showConfirmButton: false,
                    position: 'top-end',
                    toast: true
                });
                logEvent('✅ SweetAlert notification shown', 'success');
            }

            // Method 4: Custom HTML notification
            createCustomNotification(title, message, type);
        }

        // 🏷️ CREATE CUSTOM HTML NOTIFICATION
        function createCustomNotification(title, message, type = 'info') {
            const notificationEl = document.createElement('div');
            notificationEl.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
            notificationEl.style.cssText = 'margin-bottom: 10px;';

            notificationEl.innerHTML = `
                <strong>${title}</strong><br>
                ${message}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;

            document.getElementById('notificationDemo').appendChild(notificationEl);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notificationEl.parentNode) {
                    notificationEl.remove();
                }
            }, 5000);

            logEvent('✅ Custom HTML notification created', 'success');
        }

        // 🧪 MANUAL TESTING FUNCTIONS
        function testToastrNotification() {
            if (typeof toastr !== 'undefined') {
                toastr.success('This is a test Toastr notification!', 'Test Success');
                logEvent('🧪 Manual Toastr test triggered', 'info');
            } else {
                logEvent('❌ Toastr not available', 'error');
            }
        }

        function testBrowserNotification() {
            if (Notification.permission === 'granted') {
                new Notification('Test Browser Notification', {
                    body: 'This is a test browser notification!',
                    icon: '/favicon.ico'
                });
                logEvent('🧪 Manual browser notification test triggered', 'info');
            } else {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        new Notification('Permission Granted!', {
                            body: 'Browser notifications are now enabled'
                        });
                        logEvent('🧪 Browser notification permission granted', 'success');
                    } else {
                        logEvent('❌ Browser notification permission denied', 'error');
                    }
                });
            }
        }

        function testSweetAlertNotification() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Test SweetAlert',
                    text: 'This is a test SweetAlert notification!',
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                });
                logEvent('🧪 Manual SweetAlert test triggered', 'info');
            } else {
                logEvent('❌ SweetAlert not available', 'error');
            }
        }

        function testCustomNotification() {
            createCustomNotification('Test Custom Notification', 'This is a test custom HTML notification!', 'info');
            logEvent('🧪 Manual custom notification test triggered', 'info');
        }

        function triggerBackendTest() {
            logEvent('🚀 Triggering backend test event...', 'info');

            fetch('/testing/trigger_notification_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'trigger_status_change'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        logEvent('✅ Backend test triggered: ' + data.message, 'success');
                    } else {
                        logEvent('❌ Backend test failed: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    logEvent('❌ Backend test request failed: ' + error.message, 'error');
                });
        }

        // 🛠️ UTILITY FUNCTIONS
        function clearLog() {
            document.getElementById('eventLog').innerHTML = '📋 Event log cleared...<br>';
            eventsReceived = 0;
            document.getElementById('eventsReceived').textContent = '0';
            document.getElementById('lastEvent').textContent = 'None';
        }

        function testConnection() {
            logEvent('🔍 Testing connection...', 'info');

            // Test with a direct fetch
            fetch('notification_bridge.php?action=status')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        logEvent('✅ Connection test successful - Bridge is active', 'success');
                        logEvent(`📊 Bridge stats: ${data.total_notifications} notifications, ${data.stats.total_sent} sent, ${data.stats.total_received} received`, 'info');
                    } else {
                        logEvent('❌ Connection test failed', 'error');
                        reconnect();
                    }
                })
                .catch(error => {
                    logEvent('❌ Connection test failed: ' + error.message, 'error');
                    reconnect();
                });
        }

        function reconnect() {
            logEvent('🔄 Reconnecting to real-time bridge...', 'warning');

            // Clear existing polling
            if (window.currentPollingInterval) {
                clearInterval(window.currentPollingInterval);
                window.currentPollingInterval = null;
            }

            // Start new connection
            setTimeout(() => {
                connectToRealtimeBridge();
            }, 1000);
        }

        function forceCheckNotifications() {
            logEvent('🔔 Force checking for notifications...', 'info');

            // Get all notifications since timestamp 0
            fetch('notification_bridge.php?since=0')
                .then(response => response.json())
                .then(data => {
                    logEvent(`📊 Force check result: ${data.notifications ? data.notifications.length : 0} total notifications`, 'info');

                    if (data.notifications && data.notifications.length > 0) {
                        logEvent('📋 Recent notifications:', 'info');
                        data.notifications.slice(0, 5).forEach((notification, index) => {
                            logEvent(`${index + 1}. "${notification.title}" (${notification.type}) - ${notification.datetime}`, 'info');
                        });

                        // Show the most recent notification
                        const latest = data.notifications[0];
                        showMultipleNotifications(latest);
                        logEvent(`🔔 Showing latest notification: ${latest.title}`, 'success');
                    } else {
                        logEvent('📭 No notifications found in bridge', 'warning');
                    }
                })
                .catch(error => {
                    logEvent('❌ Force check failed: ' + error.message, 'error');
                });
        }

        function showLastTimestamp() {
            logEvent(`🕒 Current timestamp filter: ${window.lastTimestamp || 0}`, 'info');
            logEvent(`🕒 Server time: ${Math.floor(Date.now() / 1000)}`, 'info');

            // Reset timestamp to get all notifications
            if (confirm('Reset timestamp to get all notifications?')) {
                window.lastTimestamp = 0;
                logEvent('🔄 Timestamp reset - next poll will get all notifications', 'warning');
            }
        }

        // 🚀 INITIALIZE ON PAGE LOAD
        window.addEventListener('DOMContentLoaded', function() {
            logEvent('🚀 Real-time notification test client initialized', 'success');

            // Configure Toastr
            if (typeof toastr !== 'undefined') {
                toastr.options = {
                    positionClass: 'toast-top-right',
                    timeOut: 5000,
                    extendedTimeOut: 1000,
                    showMethod: 'fadeIn',
                    hideMethod: 'fadeOut'
                };
            }

            // Start real-time connection
            setTimeout(() => {
                connectToRealtimeBridge();
            }, 1000);

            // Request notification permission on load
            if (typeof Notification !== 'undefined' && Notification.permission === 'default') {
                logEvent('🔔 Requesting browser notification permission...', 'info');
                Notification.requestPermission().then(permission => {
                    logEvent('📋 Notification permission: ' + permission, permission === 'granted' ? 'success' : 'warning');
                });
            }
        });

        // Handle page unload
        window.addEventListener('beforeunload', function() {
            if (window.currentPollingInterval) {
                clearInterval(window.currentPollingInterval);
            }
        });
    </script>
</body>

</html>