<div>
    <div class="h-full bg-white dark:bg-gray-900 overflow-y-auto">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">AI Chat Settings</h2>
                <button wire:click="toggleAdvanced" 
                        class="text-sm text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                    {{ $showAdvanced ? 'Hide Advanced' : 'Show Advanced' }}
                </button>
            </div>
        </div>
        
        <!-- Messages -->
        @if($error)
            <div class="mx-6 mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <div class="flex items-center justify-between">
                    <span>{{ $error }}</span>
                    <button wire:click="clearMessages" class="text-red-500 hover:text-red-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif
        
        @if($successMessage)
            <div class="mx-6 mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                <div class="flex items-center justify-between">
                    <span>{{ $successMessage }}</span>
                    <button wire:click="clearMessages" class="text-green-500 hover:text-green-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif
        
        <!-- Settings Form -->
        <form wire:submit.prevent="saveSettings" class="p-6 space-y-6">
            
            <!-- Provider & Model Settings -->
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Provider & Model</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Provider Selection -->
                    <div>
                        <label for="provider" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            AI Provider
                        </label>
                        <select wire:model="provider" id="provider" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                            @foreach($availableProviders as $providerOption)
                                <option value="{{ $providerOption }}">{{ ucfirst($providerOption) }}</option>
                            @endforeach
                        </select>
                        @error('provider') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    
                    <!-- Model Selection -->
                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Model
                        </label>
                        <select wire:model="model" id="model" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                            @foreach($availableModels as $modelOption)
                                <option value="{{ $modelOption }}">{{ $modelOption }}</option>
                            @endforeach
                        </select>
                        @error('model') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
                
                <!-- Test Connection -->
                <div class="flex items-center space-x-3">
                    <button type="button" wire:click="testConnection" 
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                            {{ $isLoading ? 'disabled' : '' }}>
                        @if($isLoading)
                            <svg class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        @endif
                        Test Connection
                    </button>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Verify that the selected provider is available
                    </span>
                </div>
            </div>
            
            <!-- Advanced Settings -->
            @if($showAdvanced)
                <div class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Advanced Settings</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Temperature -->
                        <div>
                            <label for="temperature" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Temperature ({{ $temperature }})
                            </label>
                            <input type="range" wire:model="temperature" id="temperature" 
                                   min="0" max="2" step="0.1"
                                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span>Conservative (0)</span>
                                <span>Creative (2)</span>
                            </div>
                            @error('temperature') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- Max Tokens -->
                        <div>
                            <label for="maxTokens" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Max Tokens
                            </label>
                            <input type="number" wire:model="maxTokens" id="maxTokens" 
                                   min="1" max="8192" step="1"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                            @error('maxTokens') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- Context Length -->
                        <div>
                            <label for="contextLength" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Context Length
                            </label>
                            <input type="number" wire:model="contextLength" id="contextLength" 
                                   min="512" max="32768" step="512"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                            @error('contextLength') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- Language -->
                        <div>
                            <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Language
                            </label>
                            <select wire:model="language" id="language" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach($this->getLanguageOptions() as $code => $name)
                                    <option value="{{ $code }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('language') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- UI & Behavior Settings -->
            <div class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Interface & Behavior</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Theme -->
                    <div>
                        <label for="theme" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Theme
                        </label>
                        <select wire:model="theme" id="theme" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                            @foreach($this->getThemeOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('theme') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    
                    <!-- Auto Save -->
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="autoSave" id="autoSave" 
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="autoSave" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            Auto-save conversations
                        </label>
                    </div>
                </div>
                
                <!-- Notifications -->
                <div class="space-y-3">
                    <h4 class="text-md font-medium text-gray-900 dark:text-white">Notifications</h4>
                    
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="enableNotifications" id="enableNotifications" 
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="enableNotifications" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Enable notifications
                            </label>
                        </div>
                        
                        <div class="flex items-center ml-6">
                            <input type="checkbox" wire:model="enableSoundNotifications" id="enableSoundNotifications" 
                                   {{ !$enableNotifications ? 'disabled' : '' }}
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 disabled:opacity-50">
                            <label for="enableSoundNotifications" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300 {{ !$enableNotifications ? 'opacity-50' : '' }}">
                                Enable sound notifications
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex space-x-3">
                    <button type="button" wire:click="exportSettings" 
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                        Export Settings
                    </button>
                    
                    <label class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors cursor-pointer">
                        Import Settings
                        <input type="file" accept=".json" class="hidden" 
                               onchange="handleFileImport(this)">
                    </label>
                    
                    <button type="button" wire:click="resetToDefaults" 
                            onclick="return confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')"
                            class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition-colors">
                        Reset to Defaults
                    </button>
                </div>
                
                <button type="submit" 
                        {{ $isLoading ? 'disabled' : '' }}
                        class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    @if($isLoading)
                        <svg class="w-5 h-5 animate-spin mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    @endif
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    <script>
        // Handle file import
        function handleFileImport(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const content = e.target.result;
                        @this.call('importSettings', content);
                    } catch (error) {
                        alert('Error reading file: ' + error.message);
                    }
                };
                reader.readAsText(file);
            }
            // Reset input
            input.value = '';
        }
        
        // Download file event listener
        window.addEventListener('downloadFile', function(event) {
            const { filename, content, mimeType } = event.detail;
            
            const blob = new Blob([content], { type: mimeType });
            const url = window.URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        });
        
        // Clear message event listener
        window.addEventListener('clear-message', function(event) {
            setTimeout(() => {
                @this.set('successMessage', null);
                @this.set('error', null);
            }, event.detail.delay || 3000);
        });
    </script>
</div>