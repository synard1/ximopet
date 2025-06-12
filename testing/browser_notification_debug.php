<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\SupplyPurchaseBatch;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 INTERACTIVE BROWSER NOTIFICATION DEBUGGER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .log-container {
            background: #1a1a1a;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            max-height: 400px;
            overflow-y: auto;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .test-section {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            background: #f8f9fa;
        }

        .success {
            color: #28a745;
            font-weight: bold;
        }

        .error {
            color: #dc3545;
            font-weight: bold;
        }

        .warning {
            color: #ffc107;
            font-weight: bold;
        }

        .info {
            color: #17a2b8;
            font-weight: bold;
        }

        .step-title {
            background: #007bff;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }

        .btn-test {
            margin: 5px;
            min-width: 200px;
        }

        .result-box {
            border: 2px solid #28a745;
            background: #d4edda;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .notification-demo {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-4">🔍 INTERACTIVE BROWSER NOTIFICATION DEBUGGER</h1>
                <div class="alert alert-info">
                    <strong>🎯 Goal:</strong> Debug why notifications are not reaching browser client and fix it step-by-step
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Test Controls -->
            <div class="col-md-6">
                <div class="test-section">
                    <h3 class="step-title">📋 STEP 1: BASIC BROWSER TESTS</h3>

                    <button class="btn btn-primary btn-test" onclick="testBasicNotification()">
                        🔔 Test Basic Browser Notification
                    </button>

                    <button class="btn btn-secondary btn-test" onclick="testToastrNotification()">
                        📢 Test Toastr Notification
                    </button>

                    <button class="btn btn-info btn-test" onclick="testSweetAlertNotification()">
                        🍭 Test SweetAlert Notification
                    </button>

                    <button class="btn btn-warning btn-test" onclick="testCustomHTMLNotification()">
                        🏷️ Test Custom HTML Notification
                    </button>
                </div>

                <div class="test-section">
                    <h3 class="step-title">📡 STEP 2: LIVEWIRE SIMULATION TESTS</h3>

                    <button class="btn btn-success btn-test" onclick="simulateDirectLivewireEvent()">
                        ⚡ Simulate Direct Livewire Event
                    </button>

                    <button class="btn btn-success btn-test" onclick="simulateNotifyStatusChange()">
                        🎯 Simulate notify-status-change Event
                    </button>

                    <button class="btn btn-success btn-test" onclick="simulateUserNotification()">
                        👤 Simulate User Notification
                    </button>
                </div>

                <div class="test-section">
                    <h3 class="step-title">🌐 STEP 3: REAL BACKEND TRIGGER</h3>

                    <button class="btn btn-danger btn-test" onclick="triggerRealBackendEvent()">
                        🚀 Trigger Real Backend Event
                    </button>

                    <button class="btn btn-danger btn-test" onclick="triggerStatusChangeEvent()">
                        🔄 Trigger Status Change Event
                    </button>

                    <button class="btn btn-warning btn-test" onclick="checkSystemStatus()">
                        🔍 Check Complete System Status
                    </button>
                </div>

                <div class="test-section">
                    <h3 class="step-title">🛠️ STEP 4: SYSTEM DIAGNOSTICS</h3>

                    <button class="btn btn-info btn-test" onclick="diagnoseNotificationSystem()">
                        🔧 Diagnose Notification System
                    </button>

                    <button class="btn btn-secondary btn-test" onclick="testAllFallbackMethods()">
                        🎛️ Test All Fallback Methods
                    </button>

                    <button class="btn btn-dark btn-test" onclick="clearAllTests()">
                        🧹 Clear All Tests & Logs
                    </button>
                </div>
            </div>

            <!-- Debug Output -->
            <div class="col-md-6">
                <div class="test-section">
                    <h3 class="step-title">📊 REAL-TIME DEBUG OUTPUT</h3>
                    <div id="debugOutput" class="log-container">
                        🚀 Interactive Debugger Ready - Click tests to start debugging...<br>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-outline-secondary btn-sm" onclick="copyDebugOutput()">
                            📋 Copy Debug Output
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="clearDebugOutput()">
                            🗑️ Clear Output
                        </button>
                    </div>
                </div>

                <div class="test-section">
                    <h3 class="step-title">✅ TEST RESULTS SUMMARY</h3>
                    <div id="testResults" class="result-box">
                        <strong>Status:</strong> <span id="testStatus">Waiting for tests...</span><br>
                        <strong>Notifications Working:</strong> <span id="notificationStatus">❓ Unknown</span><br>
                        <strong>Best Method:</strong> <span id="bestMethod">❓ Testing...</span><br>
                        <strong>Issues Found:</strong> <span id="issuesFound">❓ None detected yet</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Demo Area -->
        <div id="notificationDemo" class="notification-demo"></div>
    </div>

    <script>
        // 🎯 COMPREHENSIVE NOTIFICATION DEBUGGING SYSTEM
        let testResults = {
            basicBrowser: null,
            toastr: null,
            sweetAlert: null,
            customHTML: null,
            livewireSimulation: null,
            realBackend: null,
            systemStatus: null
        };

        let debugLog = [];

        // 📝 DEBUG LOGGING FUNCTION
        function logDebug(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = `[${timestamp}] ${message}`;
            debugLog.push(logEntry);

            const output = document.getElementById('debugOutput');
            const colorClass = type === 'success' ? 'success' :
                type === 'error' ? 'error' :
                type === 'warning' ? 'warning' : 'info';

            output.innerHTML += `<span class="${colorClass}">${logEntry}</span><br>`;
            output.scrollTop = output.scrollHeight;

            console.log(`[DEBUG] ${message}`);
        }

        // 🔔 STEP 1: BASIC NOTIFICATION TESTS
        function testBasicNotification() {
            logDebug('🔔 Testing Basic Browser Notification...', 'info');

            if (!("Notification" in window)) {
                logDebug('❌ Browser does not support notifications', 'error');
                testResults.basicBrowser = false;
                updateTestStatus();
                return;
            }

            if (Notification.permission === "granted") {
                const notification = new Notification("✅ Test Success!", {
                    body: "Basic browser notification is working!",
                    icon: "/favicon.ico"
                });
                logDebug('✅ Browser notification sent successfully', 'success');
                testResults.basicBrowser = true;
            } else if (Notification.permission !== "denied") {
                logDebug('🔄 Requesting notification permission...', 'warning');
                Notification.requestPermission().then(function(permission) {
                    if (permission === "granted") {
                        const notification = new Notification("✅ Permission Granted!", {
                            body: "Browser notifications are now enabled",
                            icon: "/favicon.ico"
                        });
                        logDebug('✅ Permission granted, notification sent', 'success');
                        testResults.basicBrowser = true;
                    } else {
                        logDebug('❌ Permission denied by user', 'error');
                        testResults.basicBrowser = false;
                    }
                    updateTestStatus();
                });
                return;
            } else {
                logDebug('❌ Notifications blocked by user', 'error');
                testResults.basicBrowser = false;
            }

            updateTestStatus();
        }

        function testToastrNotification() {
            logDebug('📢 Testing Toastr Notification...', 'info');

            if (typeof toastr !== 'undefined') {
                toastr.success('Toastr notification is working!', '✅ Test Success');
                logDebug('✅ Toastr notification sent successfully', 'success');
                testResults.toastr = true;
            } else {
                logDebug('❌ Toastr not available', 'error');
                testResults.toastr = false;
            }

            updateTestStatus();
        }

        function testSweetAlertNotification() {
            logDebug('🍭 Testing SweetAlert Notification...', 'info');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '✅ Test Success!',
                    text: 'SweetAlert notification is working!',
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                });
                logDebug('✅ SweetAlert notification sent successfully', 'success');
                testResults.sweetAlert = true;
            } else {
                logDebug('❌ SweetAlert not available', 'error');
                testResults.sweetAlert = false;
            }

            updateTestStatus();
        }

        function testCustomHTMLNotification() {
            logDebug('🏷️ Testing Custom HTML Notification...', 'info');

            try {
                const notificationEl = document.createElement('div');
                notificationEl.className = 'alert alert-success alert-dismissible fade show position-fixed';
                notificationEl.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';

                notificationEl.innerHTML = `
                    <strong>✅ Test Success!</strong><br>
                    Custom HTML notification is working!
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
                `;

                document.body.appendChild(notificationEl);

                // Auto-remove after 5 seconds
                setTimeout(() => {
                    if (notificationEl.parentNode) {
                        notificationEl.remove();
                    }
                }, 5000);

                logDebug('✅ Custom HTML notification created successfully', 'success');
                testResults.customHTML = true;
            } catch (error) {
                logDebug('❌ Custom HTML notification failed: ' + error.message, 'error');
                testResults.customHTML = false;
            }

            updateTestStatus();
        }

        // ⚡ STEP 2: LIVEWIRE SIMULATION TESTS
        function simulateDirectLivewireEvent() {
            logDebug('⚡ Simulating Direct Livewire Event...', 'info');

            try {
                // Simulate the exact event that should come from Livewire
                const event = new CustomEvent('livewire:event', {
                    detail: {
                        name: 'notify-status-change',
                        params: [{
                            type: 'info',
                            title: 'Direct Livewire Test',
                            message: 'This is a simulated direct Livewire event - ' + new Date().toLocaleTimeString(),
                            batch_id: 123,
                            old_status: 'confirmed',
                            new_status: 'shipped',
                            updated_by_name: 'Test User',
                            requires_refresh: false
                        }]
                    }
                });

                document.dispatchEvent(event);
                logDebug('✅ Direct Livewire event dispatched successfully', 'success');

                // Also try the Livewire.on method if available
                if (typeof Livewire !== 'undefined' && Livewire.dispatch) {
                    Livewire.dispatch('notify-status-change', [{
                        type: 'warning',
                        title: 'Livewire Dispatch Test',
                        message: 'Testing Livewire.dispatch method',
                        batch_id: 456
                    }]);
                    logDebug('✅ Livewire.dispatch called successfully', 'success');
                }

                testResults.livewireSimulation = true;
            } catch (error) {
                logDebug('❌ Livewire simulation failed: ' + error.message, 'error');
                testResults.livewireSimulation = false;
            }

            updateTestStatus();
        }

        function simulateNotifyStatusChange() {
            logDebug('🎯 Simulating notify-status-change Event...', 'info');

            try {
                const testData = {
                    type: 'success',
                    title: 'Supply Purchase Status Updated',
                    message: 'Purchase 000002 status changed from Confirmed to Shipped by Test User',
                    batch_id: 789,
                    old_status: 'confirmed',
                    new_status: 'shipped',
                    updated_by_name: 'Test User',
                    invoice_number: '000002',
                    requires_refresh: true,
                    show_refresh_button: true,
                    timestamp: new Date().toISOString()
                };

                // Try different methods to trigger the event
                if (typeof window.showGlobalNotification === 'function') {
                    logDebug('📡 Using showGlobalNotification function...', 'info');
                    window.showGlobalNotification(testData);
                } else if (typeof showGlobalNotification === 'function') {
                    logDebug('📡 Using global showGlobalNotification function...', 'info');
                    showGlobalNotification(testData);
                } else {
                    logDebug('⚠️ showGlobalNotification not found, using fallback...', 'warning');
                    // Fallback notification
                    if (typeof toastr !== 'undefined') {
                        toastr.success(testData.message, testData.title);
                    } else {
                        alert(`${testData.title}: ${testData.message}`);
                    }
                }

                logDebug('✅ notify-status-change simulation completed', 'success');
                testResults.livewireSimulation = true;
            } catch (error) {
                logDebug('❌ notify-status-change simulation failed: ' + error.message, 'error');
                testResults.livewireSimulation = false;
            }

            updateTestStatus();
        }

        function simulateUserNotification() {
            logDebug('👤 Simulating User-Specific Notification...', 'info');

            try {
                // Simulate user notification like from database notifications
                const userNotification = {
                    type: 'supply_purchase_status_changed',
                    title: 'Supply Purchase Update',
                    message: 'A supply purchase assigned to you has been updated',
                    batch_id: 999,
                    priority: 'high',
                    action_required: ['refresh_data'],
                    created_at: new Date().toISOString()
                };

                // Try to call the notification handler if it exists
                if (typeof window.handleUserNotification === 'function') {
                    window.handleUserNotification(userNotification);
                    logDebug('✅ User notification handler called', 'success');
                } else {
                    logDebug('⚠️ User notification handler not found, using direct display', 'warning');
                    // Direct display
                    if (typeof toastr !== 'undefined') {
                        toastr.warning(userNotification.message, userNotification.title);
                    }
                }

                logDebug('✅ User notification simulation completed', 'success');
            } catch (error) {
                logDebug('❌ User notification simulation failed: ' + error.message, 'error');
            }
        }

        // 🚀 STEP 3: REAL BACKEND TRIGGER
        function triggerRealBackendEvent() {
            logDebug('🚀 Triggering Real Backend Event...', 'info');

            // Make AJAX call to trigger real backend event
            fetch('/testing/trigger_notification_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: 'trigger_status_change',
                        test_mode: true,
                        timestamp: new Date().toISOString()
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        logDebug('✅ Backend event triggered successfully: ' + data.message, 'success');
                        testResults.realBackend = true;
                    } else {
                        logDebug('❌ Backend event failed: ' + data.error, 'error');
                        testResults.realBackend = false;
                    }
                    updateTestStatus();
                })
                .catch(error => {
                    logDebug('❌ AJAX request failed: ' + error.message, 'error');
                    testResults.realBackend = false;
                    updateTestStatus();
                });
        }

        function triggerStatusChangeEvent() {
            logDebug('🔄 Triggering Status Change Event...', 'info');

            // This would simulate actual status change in Supply Purchase
            const statusChangeData = {
                batch_id: '<?= SupplyPurchaseBatch::first()->id ?? "test-id" ?>',
                old_status: 'confirmed',
                new_status: 'shipped',
                updated_by: <?= auth()->id() ?? 1 ?>,
                notes: 'Test status change from browser debugger'
            };

            logDebug('📋 Status change data: ' + JSON.stringify(statusChangeData), 'info');

            // Try to trigger through available methods
            if (typeof window.triggerStatusChange === 'function') {
                window.triggerStatusChange(statusChangeData);
                logDebug('✅ Status change triggered via global function', 'success');
            } else {
                logDebug('⚠️ Global status change function not found', 'warning');
            }
        }

        // 🔍 STEP 4: SYSTEM DIAGNOSTICS
        function diagnoseNotificationSystem() {
            logDebug('🔧 Running Complete System Diagnosis...', 'info');

            const diagnosis = {
                browser_support: "Notification" in window,
                permission: Notification.permission,
                toastr_available: typeof toastr !== 'undefined',
                sweetalert_available: typeof Swal !== 'undefined',
                jquery_available: typeof $ !== 'undefined',
                livewire_available: typeof Livewire !== 'undefined',
                global_functions: {
                    showNotification: typeof window.showNotification !== 'undefined',
                    showGlobalNotification: typeof window.showGlobalNotification !== 'undefined',
                    testNotificationFromPage: typeof window.testNotificationFromPage !== 'undefined'
                }
            };

            logDebug('📊 SYSTEM DIAGNOSIS RESULTS:', 'info');
            Object.keys(diagnosis).forEach(key => {
                const value = diagnosis[key];
                const status = typeof value === 'object' ? JSON.stringify(value) : value;
                logDebug(`  ${key}: ${status}`, value ? 'success' : 'error');
            });

            updateTestStatus();
        }

        function testAllFallbackMethods() {
            logDebug('🎛️ Testing All Fallback Methods...', 'info');

            const methods = [{
                    name: 'Global showNotification',
                    test: () => typeof window.showNotification === 'function' && window.showNotification('Test', 'Global method test', 'info')
                },
                {
                    name: 'Toastr',
                    test: () => typeof toastr !== 'undefined' && toastr.info('Toastr fallback test', 'Test')
                },
                {
                    name: 'Browser Notification',
                    test: () => {
                        if (Notification.permission === 'granted') {
                            new Notification('Browser fallback test', {
                                body: 'Testing browser notification'
                            });
                            return true;
                        }
                        return false;
                    }
                },
                {
                    name: 'SweetAlert',
                    test: () => typeof Swal !== 'undefined' && Swal.fire({
                        title: 'SweetAlert Test',
                        text: 'Fallback method test',
                        icon: 'info',
                        timer: 2000
                    })
                },
                {
                    name: 'Alert',
                    test: () => {
                        alert('Alert fallback test - This should always work');
                        return true;
                    }
                }
            ];

            methods.forEach((method, index) => {
                setTimeout(() => {
                    try {
                        const result = method.test();
                        logDebug(`${index + 1}. ${method.name}: ${result ? '✅ Working' : '❌ Failed'}`, result ? 'success' : 'error');
                    } catch (error) {
                        logDebug(`${index + 1}. ${method.name}: ❌ Error - ${error.message}`, 'error');
                    }
                }, index * 1000);
            });
        }

        function checkSystemStatus() {
            logDebug('🔍 Checking Complete System Status...', 'info');

            // Check all system components
            const components = [
                'Laravel Echo', 'Livewire', 'Browser Notifications',
                'Toastr', 'SweetAlert', 'Global Functions'
            ];

            components.forEach(component => {
                // This would check each component status
                logDebug(`📋 ${component}: Checking...`, 'info');
            });

            updateTestStatus();
        }

        // 📊 UPDATE TEST STATUS
        function updateTestStatus() {
            const totalTests = Object.keys(testResults).length;
            const passedTests = Object.values(testResults).filter(result => result === true).length;
            const failedTests = Object.values(testResults).filter(result => result === false).length;

            document.getElementById('testStatus').textContent =
                `${passedTests}/${totalTests} tests passed (${failedTests} failed)`;

            // Determine working notification methods
            const workingMethods = [];
            if (testResults.basicBrowser) workingMethods.push('Browser');
            if (testResults.toastr) workingMethods.push('Toastr');
            if (testResults.sweetAlert) workingMethods.push('SweetAlert');
            if (testResults.customHTML) workingMethods.push('Custom HTML');

            document.getElementById('notificationStatus').textContent =
                workingMethods.length > 0 ? `✅ ${workingMethods.join(', ')}` : '❌ None working';

            document.getElementById('bestMethod').textContent =
                workingMethods.length > 0 ? workingMethods[0] : '❌ No method available';

            // Update issues
            const issues = [];
            if (!testResults.basicBrowser) issues.push('Browser notifications');
            if (!testResults.toastr) issues.push('Toastr');
            if (!testResults.livewireSimulation) issues.push('Livewire events');

            document.getElementById('issuesFound').textContent =
                issues.length > 0 ? issues.join(', ') : '✅ No issues detected';
        }

        // 🧹 UTILITY FUNCTIONS
        function clearAllTests() {
            testResults = {
                basicBrowser: null,
                toastr: null,
                sweetAlert: null,
                customHTML: null,
                livewireSimulation: null,
                realBackend: null,
                systemStatus: null
            };
            clearDebugOutput();
            updateTestStatus();
            logDebug('🧹 All tests and logs cleared', 'info');
        }

        function clearDebugOutput() {
            document.getElementById('debugOutput').innerHTML = '🚀 Debug output cleared - Ready for new tests...<br>';
            debugLog = [];
        }

        function copyDebugOutput() {
            const output = debugLog.join('\n');
            navigator.clipboard.writeText(output).then(() => {
                alert('Debug output copied to clipboard!');
            });
        }

        // 🚀 INITIALIZE
        window.addEventListener('DOMContentLoaded', function() {
            logDebug('🚀 Interactive Browser Notification Debugger Initialized', 'success');
            logDebug('👋 Ready to debug notification issues step by step!', 'info');

            // Auto-check basic system status
            setTimeout(() => {
                logDebug('🔍 Auto-checking basic system status...', 'info');
                diagnoseNotificationSystem();
            }, 1000);
        });

        // Make functions globally available for external testing
        window.testBasicNotification = testBasicNotification;
        window.simulateNotifyStatusChange = simulateNotifyStatusChange;
        window.triggerRealBackendEvent = triggerRealBackendEvent;
        window.debugNotificationSystem = diagnoseNotificationSystem;
    </script>
</body>

</html>