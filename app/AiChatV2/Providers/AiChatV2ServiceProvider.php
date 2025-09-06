<?php

namespace App\AiChatV2\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use App\AiChatV2\Services\ChatSessionService;
use App\AiChatV2\Services\SettingsService;

class AiChatV2ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Use static variable to prevent multiple registrations
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;
        
        // Log only once per application lifecycle
        Log::debug('AiChatV2ServiceProvider: register method called');
        
        $this->app->singleton(SettingsService::class, function ($app) {
            return new SettingsService();
        });

        $this->app->singleton(ChatSessionService::class, function ($app) {
            return new ChatSessionService(
                $app->make(\App\Services\OpenWebUIService::class),
                $app->make(\App\Services\OllamaChatService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Use static variable to prevent multiple boots
        static $booted = false;
        if ($booted) {
            return;
        }
        $booted = true;
        
        // Log only once per application lifecycle
        Log::debug('AiChatV2ServiceProvider: boot method called');
        
        // Load views
        $this->loadViewsFrom(resource_path('views/ai-chat-v2'), 'ai-chat-v2');
        
        // Load routes
        $this->loadRoutesFrom(base_path('routes/ai-chat-v2.php'));
        
        // Publish config
        $this->publishes([
            __DIR__.'/../../config/ai-chat-v2.php' => config_path('ai-chat-v2.php'),
        ], 'ai-chat-v2-config');
        
        // Publish views
        $this->publishes([
            __DIR__.'/../../resources/views/ai-chat-v2' => resource_path('views/ai-chat-v2'),
        ], 'ai-chat-v2-views');
        
        // Register Livewire components
        $this->registerLivewireComponents();
    }
    
    /**
     * Register Livewire components manually
     */
    protected function registerLivewireComponents(): void
    {
        // Use static variable to prevent multiple component registrations
        static $componentsRegistered = false;
        if ($componentsRegistered) {
            return;
        }
        
        // Log only once per application lifecycle
        Log::debug('AiChatV2ServiceProvider: registerLivewireComponents method called');
        
        // Check if Livewire is installed and available
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component('ai-chat-v2.chat-bubble', \App\AiChatV2\Livewire\ChatBubble::class);
            \Livewire\Livewire::component('ai-chat-v2.chat-interface', \App\AiChatV2\Livewire\ChatInterface::class);
            \Livewire\Livewire::component('ai-chat-v2.session-list', \App\AiChatV2\Livewire\SessionList::class);
            \Livewire\Livewire::component('ai-chat-v2.settings-panel', \App\AiChatV2\Livewire\SettingsPanel::class);
            $componentsRegistered = true;
            Log::debug('AiChatV2ServiceProvider: Livewire components registered successfully');
        } else {
            Log::warning('AiChatV2ServiceProvider: Livewire class does not exist');
        }
    }
}