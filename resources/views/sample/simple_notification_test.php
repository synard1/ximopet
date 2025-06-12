<?php

/**
 * Simple Notification Test for Browser
 * 
 * Open this in browser to test notifications
 * 
 * @author AI Assistant
 * @date 2024-12-11
 */

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Test</title>
    <meta name="csrf-token" content="test-token">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn:hover {
            background: #0056b3;
        }

        .status {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
        }

        .log {
            background: #2d3748;
            color: #a0aec0;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
            height: 300px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔔 Supply Purchase Notification Test</h1>
        <p>Test the notification system here. This page loads the same JavaScript files as your main application.</p>

        <div class="status" id="status">
            <strong>System Status:</strong> Loading...
        </div>

        <h3>🧪 Test Buttons</h3>
        <button class="btn" onclick="testBasicNotification()">📢 Test Basic Notification</button>
        <button class="btn" onclick="testEchoEvent()">📡 Test Echo Event</button>
        <button class="btn" onclick="testUserNotification()">👤 Test User Notification</button>
        <button class="btn" onclick="testBrowserPermission()">🔔 Request Browser Permission</button>
        <button class="btn" onclick="runSystemCheck()">🔍 System Check</button>
        <button class="btn" onclick="clearLog()">🗑️ Clear Log</button>

        <h3>📋 Keyboard Shortcuts</h3>
        <ul>
            <li><kbd>Ctrl+Shift+T</kbd> - Test Notification</li>
            <li><kbd>Ctrl+Shift+S</kbd> - System Check</li>
            <li><kbd>Ctrl+Shift+N</kbd> - Simulate Notification</li>
        </ul>

        <h3>📊 Console Log</h3>
        <div class="log" id="console-log">
            <div>Loading notification system...</div>
        </div>
    </div>

    <!-- Load the same scripts as main application -->
    <script>
        // Set up Laravel user simulation
        window.Laravel = {
            user: {
                id: 1,
                name: "Test User",
                email: "test@example.com"
            }
        };
        console.log('✅ Laravel user info set:', window.Laravel.user);
    </script>

    <!-- Include Toastr for notifications -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Include SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Load our notification system -->
    <script src="/assets/js/browser-notification.js"></script>
    <script src="/assets/js/echo-setup.js"></script>
    <script src="/assets/js/app.bundle.js"></script>

    <script>
        // Custom console.log that also displays in our log div
        const originalConsoleLog = console.log;
        const logDiv = document.getElementById('console-log');

        console.log = function(...args) {
            originalConsoleLog.apply(console, args);

            // Add to our custom log
            const logEntry = document.createElement('div');
            logEntry.textContent = new Date().toLocaleTimeString() + ' - ' + args.join(' ');
            logDiv.appendChild(logEntry);
            logDiv.scrollTop = logDiv.scrollHeight;
        };

        // Test functions
        function testBasicNotification() {
            console.log('🧪 Testing basic notification...');
            if (typeof showNotification === 'function') {
                showNotification('Test Notification', 'This is a basic test notification!', 'success');
            } else if (typeof window.testBrowserNotification === 'function') {
                window.testBrowserNotification();
            } else {
                alert('Notification function not found!');
            }
        }

        function testEchoEvent() {
            console.log('🧪 Testing Echo event...');
            if (window.testEcho && window.testEcho.triggerSupplyPurchaseEvent) {
                window.testEcho.triggerSupplyPurchaseEvent();
            } else {
                console.log('❌ testEcho not available');
                alert('Echo test function not found!');
            }
        }

        function testUserNotification() {
            console.log('🧪 Testing user notification...');
            if (window.testEcho && window.testEcho.triggerUserNotification) {
                window.testEcho.triggerUserNotification();
            } else {
                console.log('❌ testEcho user notification not available');
                alert('User notification test function not found!');
            }
        }

        function testBrowserPermission() {
            console.log('🧪 Testing browser permission...');
            if ("Notification" in window) {
                Notification.requestPermission().then(function(permission) {
                    console.log('Permission result:', permission);
                    if (permission === "granted") {
                        new Notification("Permission Test", {
                            body: "Browser notifications are now enabled!",
                            icon: "/favicon.ico"
                        });
                    }
                });
            } else {
                alert('Browser notifications not supported');
            }
        }

        function runSystemCheck() {
            console.log('🔍 Running system check...');

            const checks = {
                'Laravel User': !!(window.Laravel && window.Laravel.user),
                'Echo Available': !!window.Echo,
                'showNotification': typeof showNotification === 'function',
                'testEcho': !!(window.testEcho),
                'Toastr': typeof toastr !== 'undefined',
                'SweetAlert': typeof Swal !== 'undefined',
                'Browser Notifications': "Notification" in window,
                'Notification Permission': Notification.permission
            };

            console.log('System Check Results:');
            Object.entries(checks).forEach(([key, value]) => {
                console.log(`${value ? '✅' : '❌'} ${key}: ${value}`);
            });

            // Update status div
            const statusDiv = document.getElementById('status');
            const passedChecks = Object.values(checks).filter(v => v === true || v === 'granted').length;
            const totalChecks = Object.keys(checks).length;

            statusDiv.innerHTML = `
                <strong>System Status:</strong> ${passedChecks}/${totalChecks} checks passed<br>
                ${Object.entries(checks).map(([key, value]) => 
                    `${value ? '✅' : '❌'} ${key}: ${value}`
                ).join('<br>')}
            `;
        }

        function clearLog() {
            const logDiv = document.getElementById('console-log');
            logDiv.innerHTML = '<div>Log cleared...</div>';
        }

        // Auto-run system check when page loads
        window.addEventListener('load', function() {
            setTimeout(() => {
                runSystemCheck();
            }, 2000);
        });

        // Show initial status
        document.getElementById('status').innerHTML = '<strong>System Status:</strong> Loading JavaScript files...';
    </script>
</body>

</html>