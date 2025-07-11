<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class ActivateModularServices extends Command
{
    protected $signature = 'recording:activate-modular 
                            {--force : Force activation without confirmation}
                            {--clear-cache : Clear all caches after activation}';

    protected $description = 'Activate modular recording services and clear caches';

    public function handle()
    {
        $this->info('🔧 Activating Modular Recording Services...');

        try {
            // Check current configuration
            $currentModular = config('recording.features.use_modular_services');
            $currentFallback = config('recording.features.use_legacy_fallback');

            $this->info("Current configuration:");
            $this->line("  - use_modular_services: " . ($currentModular ? 'true' : 'false'));
            $this->line("  - use_legacy_fallback: " . ($currentFallback ? 'true' : 'false'));

            if (!$this->option('force')) {
                if (!$this->confirm('Do you want to activate modular services?')) {
                    $this->info('❌ Activation cancelled.');
                    return 0;
                }
            }

            // Clear all caches first
            $this->info('🧹 Clearing caches...');
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            // Set configuration
            $this->info('⚙️ Setting configuration...');
            Config::set('recording.features.use_modular_services', true);
            Config::set('recording.features.use_legacy_fallback', false);

            // Verify configuration
            $newModular = config('recording.features.use_modular_services');
            $newFallback = config('recording.features.use_legacy_fallback');

            $this->info("New configuration:");
            $this->line("  - use_modular_services: " . ($newModular ? 'true' : 'false'));
            $this->line("  - use_legacy_fallback: " . ($newFallback ? 'true' : 'false'));

            if ($newModular && !$newFallback) {
                $this->info('✅ Modular services activated successfully!');

                if ($this->option('clear-cache')) {
                    $this->info('🧹 Clearing additional caches...');
                    Cache::flush();
                    $this->info('✅ All caches cleared.');
                }

                $this->info('🚀 System is now using modular recording services.');
                $this->info('📝 Check logs for MODULAR_PATH entries to verify activation.');

                return 0;
            } else {
                $this->error('❌ Failed to activate modular services.');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error activating modular services: ' . $e->getMessage());
            return 1;
        }
    }
}
