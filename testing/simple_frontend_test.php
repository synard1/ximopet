<?php

/**
 * Simple Frontend Notification Test Page
 * 
 * This page loads all the notification components and provides manual testing
 * to verify if the notification system works properly in the browser.
 * 
 * Access via: /testing/simple_frontend_test.php
 * Created: December 12, 2024
 */

// Simple auth check
session_start();
$isLoggedIn = isset($_SESSION['user_id']) || true; // Assume logged in for testing

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="test-csrf-token">
    <title>Frontend Notification Test</title>

    <!-- Bootstrap CSS for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Toastr CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">

    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        .console-log {
            background: #000;
            color: #0f0;
            font-family: monospace;
            padding: 15px;
            border-radius: 5px;
            height: 300px;
            overflow-y: scroll;
            margin: 20px 0;
        }

        .test-button {
            margin: 5px;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-ok {
            background-color: #28a745;
        }

        .status-error {
            background-color: #dc3545;
        }

        .status-warning {
            background-color: #ffc107;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">🧪 Frontend Notification System Test</h1>

                <!-- System Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>📊 System Status</h5>
                    </div>
                    <div class="card-body">
                        <div id="system-status">
                            <div><span id="status-browser-notification" class="status-indicator status-error"></span> Browser Notification Permission</div>
                            <div><span id="status-toastr" class="status-indicator status-error"></span> Toastr Library</div>
                            <div><span id="status-sweetalert" class="status-indicator status-error"></span> SweetAlert2 Library</div>
                            <div><span id="status-show-notification" class="status-indicator status-error"></span> showNotification Function</div>
                            <div><span id="status-echo" class="status-indicator status-error"></span> Laravel Echo</div>
                            <div><span id="status-livewire" class="status-indicator status-error"></span> Livewire</div>
                        </div>
                    </div>
                </div>

                <!-- Test Buttons -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>🧪 Manual Tests</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary test-button" onclick="testBrowserNotification()">🔔 Test Browser Notification</button>
                        <button class="btn btn-success test-button" onclick="testToastr()">🍞 Test Toastr</button>
                        <button class="btn btn-info test-button" onclick="testSweetAlert()">🍯 Test SweetAlert</button>
                        <button class="btn btn-warning test-button" onclick="testShowNotification()">⚡ Test showNotification()</button>
                        <button class="btn btn-secondary test-button" onclick="testAllMethods()">🎯 Test All Methods</button>
                        <button class="btn btn-danger test-button" onclick="clearConsole()">🧹 Clear Console</button>
                    </div>
                </div>

                <!-- Keyboard Shortcuts -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>⌨️ Keyboard Shortcuts</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>T</kbd> - Test Browser Notification<br>
                                <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>N</kbd> - Test showNotification()<br>
                                <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>P</kbd> - Test Supply Purchase Notification<br>
                            </div>
                            <div class="col-md-6">
                                <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>S</kbd> - System Check<br>
                                <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>C</kbd> - Clear Console<br>
                                <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>A</kbd> - Test All Methods<br>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Console Output -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>📝 Console Output</h5>
                    </div>
                    <div class="card-body">
                        <div id="console-output" class="console-log"></div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>📖 Testing Instructions</h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li><strong>Check System Status:</strong> All indicators should be green before testing</li>
                            <li><strong>Test Browser Notification:</strong> Should show OS notification (if permission granted)</li>
                            <li><strong>Test showNotification():</strong> Should show notification using available method</li>
                            <li><strong>Use Keyboard Shortcuts:</strong> Test using keyboard combinations</li>
                            <li><strong>Check Console:</strong> Look for success/error messages in the console below</li>
                            <li><strong>Verify Fallbacks:</strong> Even if one method fails, others should work</li>
                        </ol>

                        <div class="alert alert-info mt-3">
                            <strong>💡 Tip:</strong> If you see notifications appear in ANY form (toast, modal, alert, or OS notification),
                            the system is working correctly. The frontend integration is successful.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Mock Laravel and Livewire for testing -->
    <script>
        // Mock Laravel object
        window.Laravel = {
            user: {
                id: 1,
                name: 'Test User'
            }
        };

        // Mock Livewire object
        window.Livewire = {
            on: function(event, callback) {
                console.log('🔗 Livewire.on registered:', event);
                // Store listeners for manual triggering
                if (!window.livewireListeners) window.livewireListeners = {};
                window.livewireListeners[event] = callback;
            },
            dispatch: function(event, data) {
                console.log('📤 Livewire.dispatch called:', event, data);
            }
        };
    </script>

    <!-- Load our notification scripts -->
    <script src="../public/assets/js/browser-notification.js"></script>
    <script src="../public/assets/js/echo-setup.js"></script>

    <!-- Test Functions -->
    <script>
        // Console logging to page
        function logToConsole(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const colors = {
                info: '#0ff',
                success: '#0f0',
                error: '#f00',
                warning: '#ff0'
            };

            const consoleDiv = document.getElementById('console-output');
            const logLine = document.createElement('div');
            logLine.style.color = colors[type] || '#0f0';
            logLine.innerHTML = `[${timestamp}] ${message}`;
            consoleDiv.appendChild(logLine);
            consoleDiv.scrollTop = consoleDiv.scrollHeight;

            // Also log to browser console
            console.log(`[Frontend Test] ${message}`);
        }

        // Test Functions
        function testBrowserNotification() {
            logToConsole('🔔 Testing browser notification...', 'info');

            if (typeof window.testBrowserNotification === 'function') {
                window.testBrowserNotification();
                logToConsole('✅ Browser notification test called', 'success');
            } else {
                logToConsole('❌ testBrowserNotification function not found', 'error');
            }
        }

        function testToastr() {
            logToConsole('🍞 Testing Toastr...', 'info');

            if (typeof toastr !== 'undefined') {
                toastr.success('Toastr is working correctly!', 'Success');
                logToConsole('✅ Toastr test successful', 'success');
            } else {
                logToConsole('❌ Toastr library not available', 'error');
            }
        }

        function testSweetAlert() {
            logToConsole('🍯 Testing SweetAlert...', 'info');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'SweetAlert Test',
                    text: 'SweetAlert is working correctly!',
                    icon: 'success',
                    timer: 3000,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false
                });
                logToConsole('✅ SweetAlert test successful', 'success');
            } else {
                logToConsole('❌ SweetAlert library not available', 'error');
            }
        }

        function testShowNotification() {
            logToConsole('⚡ Testing showNotification function...', 'info');

            if (typeof showNotification === 'function') {
                showNotification(
                    'Test Notification',
                    'This is a test of the showNotification function!',
                    'info'
                );
                logToConsole('✅ showNotification test successful', 'success');
            } else {
                logToConsole('❌ showNotification function not available', 'error');
            }
        }

        function testAllMethods() {
            logToConsole('🎯 Testing all notification methods...', 'info');

            setTimeout(() => testBrowserNotification(), 500);
            setTimeout(() => testToastr(), 1000);
            setTimeout(() => testSweetAlert(), 1500);
            setTimeout(() => testShowNotification(), 2000);

            logToConsole('✅ All tests queued', 'success');
        }

        function clearConsole() {
            document.getElementById('console-output').innerHTML = '';
            logToConsole('🧹 Console cleared', 'info');
        }

        // System Status Check
        function checkSystemStatus() {
            logToConsole('🔍 Checking system status...', 'info');

            const checks = {
                'status-browser-notification': 'Notification' in window && Notification.permission !== 'denied',
                'status-toastr': typeof toastr !== 'undefined',
                'status-sweetalert': typeof Swal !== 'undefined',
                'status-show-notification': typeof showNotification === 'function',
                'status-echo': typeof window.Echo !== 'undefined',
                'status-livewire': typeof window.Livewire !== 'undefined'
            };

            for (const [statusId, isOk] of Object.entries(checks)) {
                const element = document.getElementById(statusId);
                if (element) {
                    element.className = `status-indicator ${isOk ? 'status-ok' : 'status-error'}`;
                }

                const component = statusId.replace('status-', '').replace('-', ' ');
                logToConsole(
                    `${isOk ? '✅' : '❌'} ${component}: ${isOk ? 'OK' : 'FAILED'}`,
                    isOk ? 'success' : 'error'
                );
            }
        }

        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey) {
                switch (e.key) {
                    case 'T':
                        e.preventDefault();
                        testBrowserNotification();
                        break;
                    case 'N':
                        e.preventDefault();
                        testShowNotification();
                        break;
                    case 'P':
                        e.preventDefault();
                        // Test supply purchase notification
                        if (typeof testNotificationFromPage === 'function') {
                            testNotificationFromPage();
                        } else {
                            testShowNotification();
                        }
                        break;
                    case 'S':
                        e.preventDefault();
                        checkSystemStatus();
                        break;
                    case 'C':
                        e.preventDefault();
                        clearConsole();
                        break;
                    case 'A':
                        e.preventDefault();
                        testAllMethods();
                        break;
                }
            }
        });

        // Simulate Livewire notification
        function simulateLivewireNotification() {
            logToConsole('📢 Simulating Livewire notification...', 'info');

            const testData = {
                type: 'info',
                title: 'Supply Purchase Update',
                message: 'This is a simulated notification from Livewire component',
                requires_refresh: false,
                priority: 'normal'
            };

            if (window.livewireListeners && window.livewireListeners['notify-status-change']) {
                window.livewireListeners['notify-status-change'](testData);
                logToConsole('✅ Livewire notification simulation completed', 'success');
            } else {
                logToConsole('❌ No Livewire listeners found', 'error');
            }
        }

        // Add simulation button
        window.simulateLivewireNotification = simulateLivewireNotification;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            logToConsole('🚀 Frontend Notification Test Page Loaded', 'success');
            logToConsole('📋 Use the buttons above or keyboard shortcuts to test', 'info');

            setTimeout(checkSystemStatus, 1000);
        });
    </script>
</body>

</html>