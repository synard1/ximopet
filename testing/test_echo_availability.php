<?php

/**
 * Quick test to verify Echo availability in browser
 * 
 * @author AI Assistant
 * @date 2024-12-11
 */

echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "    <title>Echo Availability Test</title>\n";
echo "    <meta name='csrf-token' content='" . csrf_token() . "'>\n";
echo "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>\n";
echo "</head>\n";
echo "<body class='p-4'>\n";

echo "<div class='container'>\n";
echo "    <h2>🔍 Laravel Echo Availability Test</h2>\n";
echo "    <div class='alert alert-info'>\n";
echo "        This page will test if Laravel Echo is properly loaded and available.\n";
echo "    </div>\n";

echo "    <div id='test-results' class='mt-4'></div>\n";
echo "    <div id='console-output' class='mt-4'></div>\n";

echo "    <div class='mt-4'>\n";
echo "        <button onclick='runEchoTest()' class='btn btn-primary'>🧪 Run Echo Test</button>\n";
echo "        <button onclick='testNotification()' class='btn btn-success'>📧 Test Notification</button>\n";
echo "        <button onclick='clearResults()' class='btn btn-secondary'>🗑️ Clear Results</button>\n";
echo "    </div>\n";
echo "</div>\n";

// Laravel user info setup
echo "<script>\n";
echo "    window.Laravel = window.Laravel || {};\n";
if (auth()->check()) {
    echo "    window.Laravel.user = {\n";
    echo "        id: " . auth()->id() . ",\n";
    echo "        name: '" . addslashes(auth()->user()->name) . "',\n";
    echo "        email: '" . addslashes(auth()->user()->email) . "'\n";
    echo "    };\n";
    echo "    console.log('✅ User info set:', window.Laravel.user);\n";
} else {
    echo "    window.Laravel.user = null;\n";
    echo "    console.log('👤 No authenticated user');\n";
}
echo "</script>\n";

// Load Echo setup
echo "<script src='/assets/js/echo-setup.js'></script>\n";
echo "<script src='/assets/js/app.bundle.js'></script>\n";

echo "<script>\n";
echo "let testResults = [];\n";
echo "\n";
echo "function addResult(message, status = 'info') {\n";
echo "    const timestamp = new Date().toLocaleTimeString();\n";
echo "    testResults.push({ timestamp, message, status });\n";
echo "    updateResultsDisplay();\n";
echo "    console.log(`[${timestamp}] ${message}`);\n";
echo "}\n";
echo "\n";
echo "function updateResultsDisplay() {\n";
echo "    const resultsDiv = document.getElementById('test-results');\n";
echo "    let html = '<h4>Test Results:</h4>';\n";
echo "    \n";
echo "    testResults.forEach(result => {\n";
echo "        const alertClass = result.status === 'success' ? 'alert-success' : \n";
echo "                          result.status === 'error' ? 'alert-danger' : 'alert-info';\n";
echo "        html += `<div class='alert ${alertClass} py-2'>`;\n";
echo "        html += `<small class='text-muted'>[${result . timestamp}]</small> ${result . message}`;\n";
echo "        html += '</div>';\n";
echo "    });\n";
echo "    \n";
echo "    resultsDiv.innerHTML = html;\n";
echo "}\n";
echo "\n";
echo "function runEchoTest() {\n";
echo "    addResult('🧪 Starting Echo availability test...');\n";
echo "    \n";
echo "    // Check if Echo exists\n";
echo "    if (typeof window.Echo === 'undefined') {\n";
echo "        addResult('❌ window.Echo is undefined', 'error');\n";
echo "        return;\n";
echo "    }\n";
echo "    \n";
echo "    addResult('✅ window.Echo is available', 'success');\n";
echo "    \n";
echo "    // Check Echo methods\n";
echo "    if (typeof window.Echo.channel === 'function') {\n";
echo "        addResult('✅ Echo.channel method available', 'success');\n";
echo "    } else {\n";
echo "        addResult('❌ Echo.channel method missing', 'error');\n";
echo "    }\n";
echo "    \n";
echo "    if (typeof window.Echo.private === 'function') {\n";
echo "        addResult('✅ Echo.private method available', 'success');\n";
echo "    } else {\n";
echo "        addResult('❌ Echo.private method missing', 'error');\n";
echo "    }\n";
echo "    \n";
echo "    // Test creating a channel\n";
echo "    try {\n";
echo "        const channel = window.Echo.channel('test-channel');\n";
echo "        if (channel && typeof channel.listen === 'function') {\n";
echo "            addResult('✅ Successfully created test channel', 'success');\n";
echo "        } else {\n";
echo "            addResult('❌ Channel creation failed', 'error');\n";
echo "        }\n";
echo "    } catch (error) {\n";
echo "        addResult(`❌ Error creating channel: ${error . message}`, 'error');\n";
echo "    }\n";
echo "    \n";
echo "    // Test user info\n";
echo "    if (window.Laravel && window.Laravel.user) {\n";
echo "        addResult(`✅ User authenticated: ${window . Laravel . user . name}`, 'success');\n";
echo "    } else {\n";
echo "        addResult('⚠️ No user authentication data', 'error');\n";
echo "    }\n";
echo "    \n";
echo "    addResult('🎯 Echo test completed!');\n";
echo "}\n";
echo "\n";
echo "function testNotification() {\n";
echo "    addResult('📧 Testing notification system...');\n";
echo "    \n";
echo "    if (window.testNotification && typeof window.testNotification === 'function') {\n";
echo "        window.testNotification();\n";
echo "        addResult('✅ Test notification triggered', 'success');\n";
echo "    } else {\n";
echo "        addResult('❌ testNotification function not available', 'error');\n";
echo "    }\n";
echo "}\n";
echo "\n";
echo "function clearResults() {\n";
echo "    testResults = [];\n";
echo "    updateResultsDisplay();\n";
echo "    console.clear();\n";
echo "}\n";
echo "\n";
echo "// Auto-run test when page loads\n";
echo "document.addEventListener('DOMContentLoaded', function() {\n";
echo "    setTimeout(() => {\n";
echo "        addResult('🚀 Page loaded, running automatic Echo test...');\n";
echo "        runEchoTest();\n";
echo "    }, 2000);\n";
echo "});\n";
echo "</script>\n";

echo "</body>\n";
echo "</html>\n";
