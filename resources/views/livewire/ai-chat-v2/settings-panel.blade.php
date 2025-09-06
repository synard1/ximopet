<div class="h-full flex flex-col">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Settings</h2>
    </div>

    <!-- Settings Content -->
    <div class="flex-1 overflow-y-auto p-4">
        @if($error)
        <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 text-red-400 dark:text-red-500 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Error</h3>
                    <div class="mt-1 text-sm text-red-700 dark:text-red-300">
                        <p>{{ $error }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Provider Settings -->
        <div class="mb-6">
            <h3 class="text-md font-medium text-gray-900 dark:text-white mb-3">AI Provider</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Provider</label>
                    <select wire:model="settings.provider"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @foreach($availableProviders as $providerKey => $provider)
                        <option value="{{ $providerKey }}">{{ $provider['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model</label>
                    <select wire:model="settings.model"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @if(isset($availableModels) && count($availableModels) > 0)
                        @foreach($availableModels as $model)
                        <option value="{{ $model }}">{{ $model }}</option>
                        @endforeach
                        @else
                        <option value="">No models available</option>
                        @endif
                    </select>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Test
                            Connection</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Verify that the provider is working</p>
                    </div>
                    <button wire:click="testConnection" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50">
                        {{-- @if($testingConnection)
                        <svg class="w-4 h-4 animate-spin inline mr-1" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Testing...
                        @else
                        Test
                        @endif --}}
                    </button>
                </div>

                {{-- @if($connectionTestResult)
                <div
                    class="p-3 rounded-lg {{ $connectionTestResult['success'] ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
                    <div class="flex">
                        @if($connectionTestResult['success'])
                        <svg class="w-5 h-5 text-green-400 dark:text-green-500 mt-0.5 mr-2" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"></path>
                        </svg>
                        @else
                        <svg class="w-5 h-5 text-red-400 dark:text-red-500 mt-0.5 mr-2" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd"></path>
                        </svg>
                        @endif
                        <div>
                            <h3
                                class="text-sm font-medium {{ $connectionTestResult['success'] ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                {{ $connectionTestResult['success'] ? 'Connection Successful' : 'Connection Failed' }}
                            </h3>
                            <div
                                class="mt-1 text-sm {{ $connectionTestResult['success'] ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                <p>{{ $connectionTestResult['message'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif --}}
            </div>
        </div>

        <!-- Chat Settings -->
        <div class="mb-6">
            <h3 class="text-md font-medium text-gray-900 dark:text-white mb-3">Chat Settings</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Auto-save
                            Sessions</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Automatically save chat sessions</p>
                    </div>
                    <button wire:click="toggleSetting('auto_save_enabled')" type="button"
                        class="{{ $settings['auto_save_enabled'] ? 'bg-blue-500' : 'bg-gray-200 dark:bg-gray-700' }} relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span
                            class="{{ $settings['auto_save_enabled'] ? 'translate-x-5' : 'translate-x-0' }} relative inline-block w-5 h-5 rounded-full bg-white shadow transform transition ease-in-out duration-200">
                            <span
                                class="{{ $settings['auto_save_enabled'] ? 'opacity-0 ease-out duration-100' : 'opacity-100 ease-in duration-200' }} absolute inset-0 h-full w-full flex items-center justify-center transition-opacity">
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </span>
                            <span
                                class="{{ $settings['auto_save_enabled'] ? 'opacity-100 ease-in duration-200' : 'opacity-0 ease-out duration-100' }} absolute inset-0 h-full w-full flex items-center justify-center transition-opacity">
                                <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </span>
                        </span>
                    </button>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Typing
                            Indicator</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Show typing animation when AI is responding
                        </p>
                    </div>
                    <button wire:click="toggleSetting('typing_indicator_enabled')" type="button"
                        class="{{ $settings['typing_indicator_enabled'] ? 'bg-blue-500' : 'bg-gray-200 dark:bg-gray-700' }} relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span
                            class="{{ $settings['typing_indicator_enabled'] ? 'translate-x-5' : 'translate-x-0' }} relative inline-block w-5 h-5 rounded-full bg-white shadow transform transition ease-in-out duration-200">
                            <span
                                class="{{ $settings['typing_indicator_enabled'] ? 'opacity-0 ease-out duration-100' : 'opacity-100 ease-in duration-200' }} absolute inset-0 h-full w-full flex items-center justify-center transition-opacity">
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </span>
                            <span
                                class="{{ $settings['typing_indicator_enabled'] ? 'opacity-100 ease-in duration-200' : 'opacity-0 ease-out duration-100' }} absolute inset-0 h-full w-full flex items-center justify-center transition-opacity">
                                <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </span>
                        </span>
                    </button>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Markdown
                            Support</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Enable markdown formatting in messages</p>
                    </div>
                    <button wire:click="toggleSetting('markdown_enabled')" type="button"
                        class="{{ $settings['markdown_enabled'] ? 'bg-blue-500' : 'bg-gray-200 dark:bg-gray-700' }} relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span
                            class="{{ $settings['markdown_enabled'] ? 'translate-x-5' : 'translate-x-0' }} relative inline-block w-5 h-5 rounded-full bg-white shadow transform transition ease-in-out duration-200">
                            <span
                                class="{{ $settings['markdown_enabled'] ? 'opacity-0 ease-out duration-100' : 'opacity-100 ease-in duration-200' }} absolute inset-0 h-full w-full flex items-center justify-center transition-opacity">
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </span>
                            <span
                                class="{{ $settings['markdown_enabled'] ? 'opacity-100 ease-in duration-200' : 'opacity-0 ease-out duration-100' }} absolute inset-0 h-full w-full flex items-center justify-center transition-opacity">
                                <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </span>
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- UI Settings -->
        <div class="mb-6">
            <h3 class="text-md font-medium text-gray-900 dark:text-white mb-3">UI Settings</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Theme</label>
                    <select wire:model="settings.theme"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="light">Light</option>
                        <option value="dark">Dark</option>
                        <option value="auto">Auto</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                    <select wire:model="settings.position"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="bottom-right">Bottom Right</option>
                        <option value="bottom-left">Bottom Left</option>
                        <option value="top-right">Top Right</option>
                        <option value="top-left">Top Left</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Size</label>
                    <select wire:model="settings.size"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="small">Small</option>
                        <option value="medium">Medium</option>
                        <option value="large">Large</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
            <button wire:click="resetToDefaults" type="button"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Reset to Defaults
            </button>
            <button wire:click="saveSettings" wire:loading.attr="disabled"
                class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50">
                @if($saving)
                <svg class="w-4 h-4 animate-spin inline mr-1" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Saving...
                @else
                Save Settings
                @endif
            </button>
        </div>
    </div>
</div>