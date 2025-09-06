@extends('layouts.chat')

@section('content')
<div class=\"container\">
    <div class=\"row justify-content-center\">
        <div class=\"col-lg-12\">
            <!-- Header -->
            <div class=\"d-flex justify-content-between align-items-center mb-4\">
                <div>
                    <h1 class=\"h3 mb-0\">
                        <i class=\"fas fa-robot text-primary me-2\"></i>
                        AI Chat Integration Demo
                    </h1>
                    <p class=\"text-muted mb-0\">Test the AI chat functionality with your farm data</p>
                </div>
                
                <div class=\"d-flex gap-2\">
                    <button class=\"btn btn-outline-primary\" onclick=\"window.aiChatUtils.openChatWithMessage('Hello! Tell me about my farm')\">
                        <i class=\"fas fa-comment me-2\"></i>
                        Open Chat
                    </button>
                    
                    <button class=\"btn btn-outline-secondary\" onclick=\"testChatFunctionality()\">
                        <i class=\"fas fa-flask me-2\"></i>
                        Test Features
                    </button>
                </div>
            </div>
            
            <!-- Feature Cards -->
            <div class=\"row g-4\">
                <!-- Quick Actions -->
                <div class=\"col-md-6 col-lg-4\">
                    <div class=\"card h-100\">
                        <div class=\"card-header bg-primary text-white\">
                            <h5 class=\"card-title mb-0\">
                                <i class=\"fas fa-lightning-bolt me-2\"></i>
                                Quick Actions
                            </h5>
                        </div>
                        <div class=\"card-body\">
                            <p class=\"card-text\">Try these common farm management queries:</p>
                            
                            <div class=\"d-grid gap-2\">
                                <button class=\"btn btn-outline-primary btn-sm\" 
                                        onclick=\"sendQuickMessage('How many active livestock do I have?')\">
                                    <i class=\"fas fa-cow me-2\"></i>
                                    Livestock Count
                                </button>
                                
                                <button class=\"btn btn-outline-success btn-sm\" 
                                        onclick=\"sendQuickMessage('Show me recent feed purchases')\">
                                    <i class=\"fas fa-seedling me-2\"></i>
                                    Feed Purchases
                                </button>
                                
                                <button class=\"btn btn-outline-info btn-sm\" 
                                        onclick=\"sendQuickMessage('What supplies do I need to order?')\">
                                    <i class=\"fas fa-boxes me-2\"></i>
                                    Supply Needs
                                </button>
                                
                                <button class=\"btn btn-outline-warning btn-sm\" 
                                        onclick=\"sendQuickMessage('Generate a performance report for this month')\">
                                    <i class=\"fas fa-chart-line me-2\"></i>
                                    Performance Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Context Types -->
                <div class=\"col-md-6 col-lg-4\">
                    <div class=\"card h-100\">
                        <div class=\"card-header bg-success text-white\">
                            <h5 class=\"card-title mb-0\">
                                <i class=\"fas fa-layer-group me-2\"></i>
                                Context Types
                            </h5>
                        </div>
                        <div class=\"card-body\">
                            <p class=\"card-text\">AI can access different types of your farm data:</p>
                            
                            <ul class=\"list-unstyled\">
                                <li class=\"mb-2\">
                                    <span class=\"badge bg-primary me-2\">Livestock</span>
                                    Animal tracking and health
                                </li>
                                <li class=\"mb-2\">
                                    <span class=\"badge bg-success me-2\">Feed</span>
                                    Feed management and usage
                                </li>
                                <li class=\"mb-2\">
                                    <span class=\"badge bg-info me-2\">Supply</span>
                                    Equipment and supplies
                                </li>
                                <li class=\"mb-2\">
                                    <span class=\"badge bg-warning me-2\">Analytics</span>
                                    Performance metrics
                                </li>
                                <li class=\"mb-2\">
                                    <span class=\"badge bg-secondary me-2\">Financial</span>
                                    Cost analysis and profits
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Chat Features -->
                <div class=\"col-md-12 col-lg-4\">
                    <div class=\"card h-100\">
                        <div class=\"card-header bg-info text-white\">
                            <h5 class=\"card-title mb-0\">
                                <i class=\"fas fa-cogs me-2\"></i>
                                Chat Features
                            </h5>
                        </div>
                        <div class=\"card-body\">
                            <p class=\"card-text\">Advanced features available in the chat:</p>
                            
                            <div class=\"row g-2\">
                                <div class=\"col-6\">
                                    <div class=\"text-center p-2 border rounded\">
                                        <i class=\"fas fa-exchange-alt text-primary d-block mb-1\"></i>
                                        <small>Switch AI Providers</small>
                                    </div>
                                </div>
                                <div class=\"col-6\">
                                    <div class=\"text-center p-2 border rounded\">
                                        <i class=\"fas fa-history text-success d-block mb-1\"></i>
                                        <small>Session History</small>
                                    </div>
                                </div>
                                <div class=\"col-6\">
                                    <div class=\"text-center p-2 border rounded\">
                                        <i class=\"fas fa-download text-info d-block mb-1\"></i>
                                        <small>Export Chats</small>
                                    </div>
                                </div>
                                <div class=\"col-6\">
                                    <div class=\"text-center p-2 border rounded\">
                                        <i class=\"fas fa-copy text-warning d-block mb-1\"></i>
                                        <small>Copy Messages</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Provider Status -->
            <div class=\"row mt-4\">
                <div class=\"col-12\">
                    <div class=\"card\">
                        <div class=\"card-header\">
                            <h5 class=\"card-title mb-0\">
                                <i class=\"fas fa-server me-2\"></i>
                                AI Provider Status
                            </h5>
                        </div>
                        <div class=\"card-body\">
                            <div class=\"row g-3\" id=\"provider-status\">
                                <div class=\"col-md-6\">
                                    <div class=\"d-flex align-items-center p-3 border rounded\">
                                        <div class=\"me-3\">
                                            <i class=\"fas fa-brain fa-2x text-primary\"></i>
                                        </div>
                                        <div class=\"flex-grow-1\">
                                            <h6 class=\"mb-1\">Ollama</h6>
                                            <p class=\"mb-0 text-muted\">Local AI processing</p>
                                            <div class=\"mt-2\">
                                                <span class=\"badge bg-secondary\" id=\"ollama-status\">Checking...</span>
                                                <span class=\"badge bg-light text-dark ms-1\" id=\"ollama-models\">Models: -</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class=\"col-md-6\">
                                    <div class=\"d-flex align-items-center p-3 border rounded\">
                                        <div class=\"me-3\">
                                            <i class=\"fas fa-cloud fa-2x text-info\"></i>
                                        </div>
                                        <div class=\"flex-grow-1\">
                                            <h6 class=\"mb-1\">OpenWebUI</h6>
                                            <p class=\"mb-0 text-muted\">Cloud AI services</p>
                                            <div class=\"mt-2\">
                                                <span class=\"badge bg-secondary\" id=\"openwebui-status\">Checking...</span>
                                                <span class=\"badge bg-light text-dark ms-1\" id=\"openwebui-models\">Models: -</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class=\"mt-3 text-center\">
                                <button class=\"btn btn-outline-primary\" onclick=\"checkProviderStatus()\">
                                    <i class=\"fas fa-sync-alt me-2\"></i>
                                    Refresh Status
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Keyboard Shortcuts -->
            <div class=\"row mt-4\">
                <div class=\"col-12\">
                    <div class=\"card\">
                        <div class=\"card-header\">
                            <h5 class=\"card-title mb-0\">
                                <i class=\"fas fa-keyboard me-2\"></i>
                                Keyboard Shortcuts
                            </h5>
                        </div>
                        <div class=\"card-body\">
                            <div class=\"row g-3\">
                                <div class=\"col-md-6\">
                                    <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>C</kbd>
                                    <span class=\"ms-2\">Toggle chat widget</span>
                                </div>
                                <div class=\"col-md-6\">
                                    <kbd>Ctrl</kbd> + <kbd>Enter</kbd>
                                    <span class=\"ms-2\">Send message (when typing)</span>
                                </div>
                                <div class=\"col-md-6\">
                                    <kbd>Esc</kbd>
                                    <span class=\"ms-2\">Close chat widget</span>
                                </div>
                                <div class=\"col-md-6\">
                                    <kbd>F1</kbd>
                                    <span class=\"ms-2\">Show chat help (coming soon)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * Demo page functionality
 */

// Send a quick message to the chat
function sendQuickMessage(message) {
    if (window.aiChatUtils) {
        window.aiChatUtils.openChatWithMessage(message);
    } else {
        alert('Chat system not ready. Please refresh the page.');
    }
}

// Test chat functionality
function testChatFunctionality() {
    const tests = [
        'Testing basic connectivity...',
        'How many livestock records do I have?',
        'Show me feed usage this month',
        'What\\'s my inventory status?'
    ];
    
    Swal.fire({
        title: 'Test Chat Functionality',
        html: `
            <p>This will send a series of test messages to verify chat functionality:</p>
            <ul class=\"text-start\">
                ${tests.map(test => `<li>${test}</li>`).join('')}
            </ul>
            <p><strong>Note:</strong> Make sure your AI providers are online.</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Start Tests',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            runChatTests(tests);
        }
    });
}

// Run automated chat tests
function runChatTests(tests) {
    let currentTest = 0;
    
    function runNextTest() {
        if (currentTest >= tests.length) {
            Swal.fire({
                title: 'Tests Completed',
                text: 'All test messages have been sent. Check the chat for responses.',
                icon: 'success'
            });
            return;
        }
        
        const message = tests[currentTest];
        sendQuickMessage(message);
        
        currentTest++;
        
        // Wait 3 seconds between tests
        setTimeout(runNextTest, 3000);
    }
    
    // Open chat first
    if (window.aiChatUtils) {
        window.aiChatUtils.openChatWithMessage('');
        setTimeout(runNextTest, 1000);
    }
}

// Check provider status
function checkProviderStatus() {
    // Reset status indicators
    document.getElementById('ollama-status').textContent = 'Checking...';
    document.getElementById('ollama-status').className = 'badge bg-secondary';
    document.getElementById('openwebui-status').textContent = 'Checking...';
    document.getElementById('openwebui-status').className = 'badge bg-secondary';
    
    // Check Ollama
    fetch('/api/chat/providers/ollama/models', {
        headers: {
            'Authorization': `Bearer ${document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content')}`,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        const statusEl = document.getElementById('ollama-status');
        const modelsEl = document.getElementById('ollama-models');
        
        if (data.success && data.available) {
            statusEl.textContent = 'Online';
            statusEl.className = 'badge bg-success';
            modelsEl.textContent = `Models: ${data.models?.length || 0}`;
        } else {
            statusEl.textContent = 'Offline';
            statusEl.className = 'badge bg-danger';
            modelsEl.textContent = 'Models: 0';
        }
    })
    .catch(error => {
        console.error('Error checking Ollama:', error);
        document.getElementById('ollama-status').textContent = 'Error';
        document.getElementById('ollama-status').className = 'badge bg-warning';
    });
    
    // Check OpenWebUI
    fetch('/api/chat/providers/openwebui/models', {
        headers: {
            'Authorization': `Bearer ${document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content')}`,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        const statusEl = document.getElementById('openwebui-status');
        const modelsEl = document.getElementById('openwebui-models');
        
        if (data.success && data.available) {
            statusEl.textContent = 'Online';
            statusEl.className = 'badge bg-success';
            modelsEl.textContent = `Models: ${data.models?.length || 0}`;
        } else {
            statusEl.textContent = 'Offline';
            statusEl.className = 'badge bg-danger';
            modelsEl.textContent = 'Models: 0';
        }
    })
    .catch(error => {
        console.error('Error checking OpenWebUI:', error);
        document.getElementById('openwebui-status').textContent = 'Error';
        document.getElementById('openwebui-status').className = 'badge bg-warning';
    });
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Check provider status on load
    setTimeout(checkProviderStatus, 1000);
    
    // Show welcome message
    Swal.fire({
        title: 'Welcome to AI Chat Demo!',
        text: 'Click the chat button in the bottom-right corner to start chatting with AI about your farm data.',
        icon: 'info',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000
    });
});
</script>
@endpush

@push('styles')
<style>
.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.badge {
    font-size: 0.75em;
}

kbd {
    font-size: 0.8em;
    padding: 0.2em 0.4em;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.25);
}

[data-bs-theme=\"dark\"] kbd {
    background-color: #495057;
    border-color: #6c757d;
    color: #fff;
}
</style>
@endpush