<?php

namespace App\Console\Commands;

use App\Helpers\EnvironmentHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class EnvironmentPackageManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:packages 
                            {action : Action to perform (install|publish|status)}
                            {--force : Force the action even in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage development packages based on environment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $force = $this->option('force');

        if (!EnvironmentHelper::shouldLoadDevPackages() && !$force) {
            $this->error('Development packages should only be installed in local environment with debug enabled.');
            $this->info('Current environment: ' . app()->environment());
            $this->info('Debug mode: ' . (config('app.debug') ? 'enabled' : 'disabled'));
            $this->info('Use --force to override this check.');
            return 1;
        }

        switch ($action) {
            case 'install':
                $this->installPackages();
                break;
            case 'publish':
                $this->publishAssets();
                break;
            case 'status':
                $this->showStatus();
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    /**
     * Install development packages
     */
    private function installPackages(): void
    {
        $this->info('Installing development packages...');

        $packages = EnvironmentHelper::getDevPackages();

        foreach ($packages as $package) {
            if (EnvironmentHelper::shouldLoadPackage($package)) {
                $this->info("Installing {$package}...");
                // Note: In real implementation, you might want to run composer require
                // This is just for demonstration
            }
        }

        $this->info('Development packages installation completed.');
    }

    /**
     * Publish package assets
     */
    private function publishAssets(): void
    {
        $this->info('Publishing package assets...');

        if (EnvironmentHelper::shouldLoadPackage('laravel/telescope')) {
            $this->info('Publishing Telescope assets...');
            Artisan::call('vendor:publish', [
                '--tag' => 'telescope-assets',
                '--force' => true
            ]);
        }

        if (EnvironmentHelper::shouldLoadPackage('laravel/pulse')) {
            $this->info('Publishing Pulse assets...');
            Artisan::call('vendor:publish', [
                '--tag' => 'pulse-assets',
                '--force' => true
            ]);
        }

        $this->info('Package assets published successfully.');
    }

    /**
     * Show current status
     */
    private function showStatus(): void
    {
        $this->info('Environment Package Manager Status');
        $this->info('==================================');
        $this->info('Environment: ' . app()->environment());
        $this->info('Debug Mode: ' . (config('app.debug') ? 'enabled' : 'disabled'));
        $this->info('Dev Packages Enabled: ' . (EnvironmentHelper::shouldLoadDevPackages() ? 'yes' : 'no'));

        $this->info('');
        $this->info('Development Packages:');

        $devPackages = EnvironmentHelper::getDevPackages();
        $installedPackages = EnvironmentHelper::getInstalledDevPackages();

        foreach ($devPackages as $package) {
            $installed = EnvironmentHelper::isPackageInstalled($package) ? '✓' : '✗';
            $enabled = EnvironmentHelper::shouldLoadPackage($package) ? 'ENABLED' : 'DISABLED';
            $this->info("  {$installed} {$package} ({$enabled})");
        }

        $this->info('');
        $this->info('Other Installed Dev Packages:');
        foreach ($installedPackages as $package) {
            if (!in_array($package, $devPackages)) {
                $this->info("  ✓ {$package}");
            }
        }
    }
}
