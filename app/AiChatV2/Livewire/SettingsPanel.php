<?php

namespace App\AiChatV2\Livewire;

use Livewire\Component;
use App\AiChatV2\Services\SettingsService;
use App\Services\OpenWebUIService;
use App\Services\OllamaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class SettingsPanel extends Component
{
    public $settings = [];
    public $availableProviders = [];
    public $availableModels = [];
    public $error = null;
    public $saving = false;
    public $testingConnection = false;
    public $connectionTestResult = null;
    
    protected SettingsService $settingsService;
    protected OpenWebUIService $openWebUIService;
    protected OllamaService $ollamaService;

    public function boot(
        SettingsService $settingsService,
        OpenWebUIService $openWebUIService,
        OllamaService $ollamaService
    ) {
        $this->settingsService = $settingsService;
        $this->openWebUIService = $openWebUIService;
        $this->ollamaService = $ollamaService;
    }

    public function mount()
    {
        $this->loadSettings();
        $this->loadAvailableProviders();
        $this->updateAvailableModels();
    }

    public function loadSettings()
    {
        try {
            $this->settings = $this->settingsService->getUserSettings(Auth::id());
        } catch (Exception $e) {
            Log::error('Failed to load user settings in SettingsPanel', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            $this->error = 'Failed to load settings';
        }
    }

    public function loadAvailableProviders()
    {
        try {
            $this->availableProviders = $this->settingsService->getAvailableProviders();
        } catch (Exception $e) {
            Log::error('Failed to load available providers', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            $this->error = 'Failed to load providers';
        }
    }

    public function updateAvailableModels()
    {
        $provider = $this->settings['provider'] ?? config('ai-chat-v2.default_provider', 'openwebui');
        $this->availableModels = $this->settingsService->getAvailableModels($provider);
    }

    public function updatedSettingsProvider()
    {
        $this->updateAvailableModels();
        
        // Set default model for the selected provider
        $providerConfig = $this->availableProviders[$this->settings['provider']] ?? null;
        if ($providerConfig && isset($providerConfig['default_model'])) {
            $this->settings['model'] = $providerConfig['default_model'];
        }
    }

    public function toggleSetting($settingKey)
    {
        $currentValue = data_get($this->settings, $settingKey, false);
        data_set($this->settings, $settingKey, !$currentValue);
    }

    public function saveSettings()
    {
        $this->saving = true;
        $this->error = null;
        
        try {
            $result = $this->settingsService->updateUserSettings($this->settings, Auth::id());
            
            if ($result) {
                $this->dispatch('notify', message: 'Settings saved successfully', type: 'success');
                $this->dispatch('settings-updated', settings: $this->settings);
            } else {
                $this->error = 'Failed to save settings';
            }
        } catch (Exception $e) {
            Log::error('Failed to save settings', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            $this->error = 'Failed to save settings: ' . $e->getMessage();
        } finally {
            $this->saving = false;
        }
    }

    public function resetToDefaults()
    {
        try {
            $result = $this->settingsService->resetToDefault(Auth::id());
            
            if ($result) {
                $this->loadSettings();
                $this->updateAvailableModels();
                $this->dispatch('notify', message: 'Settings reset to defaults', type: 'success');
                $this->dispatch('settings-updated', settings: $this->settings);
            } else {
                $this->error = 'Failed to reset settings';
            }
        } catch (Exception $e) {
            Log::error('Failed to reset settings', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            $this->error = 'Failed to reset settings: ' . $e->getMessage();
        }
    }

    public function testConnection()
    {
        $this->testingConnection = true;
        $this->connectionTestResult = null;
        $this->error = null;
        
        try {
            $provider = $this->settings['provider'] ?? config('ai-chat-v2.default_provider', 'openwebui');
            
            switch ($provider) {
                case 'openwebui':
                    $connected = $this->openWebUIService->checkServerConnection();
                    break;
                case 'ollama':
                    $connected = $this->ollamaService->checkServerConnection();
                    break;
                default:
                    $connected = false;
            }
            
            $this->connectionTestResult = [
                'success' => $connected,
                'message' => $connected 
                    ? 'Successfully connected to ' . ucfirst($provider) 
                    : 'Failed to connect to ' . ucfirst($provider)
            ];
            
            if (!$connected) {
                $this->error = 'Connection test failed';
            }
        } catch (Exception $e) {
            Log::error('Connection test failed', [
                'user_id' => Auth::id(),
                'provider' => $this->settings['provider'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            $this->connectionTestResult = [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ];
            $this->error = 'Connection test failed';
        } finally {
            $this->testingConnection = false;
        }
    }

    public function render()
    {
        return view('ai-chat-v2.livewire.settings-panel');
    }
}