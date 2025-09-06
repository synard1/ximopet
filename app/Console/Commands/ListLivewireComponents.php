<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ListLivewireComponents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livewire:list {--detailed : Show detailed component information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered Livewire components.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->info('Discovering Livewire components...');

            // Manually discover Livewire components by scanning the filesystem
            $components = $this->discoverComponents();

            if (empty($components)) {
                $this->info('No Livewire components found.');
                return 0;
            }

            $detailed = $this->option('detailed');

            if ($detailed) {
                $this->info('Discovered Livewire Components (' . count($components) . ' found):');
                $this->line('');

                // Create a table for detailed view
                $headers = ['Component Tag', 'Class Name', 'File Path'];
                $rows = [];

                foreach ($components as $tag => $info) {
                    $rows[] = [
                        "<fg=yellow>{$tag}</>",
                        "<fg=green>{$info['class']}</>",
                        $info['file']
                    ];
                }

                $this->table($headers, $rows);
            } else {
                $this->info('Discovered Livewire Components (' . count($components) . ' found):');
                foreach ($components as $tag => $info) {
                    $this->line("- <fg=yellow>{$tag}</> => <fg=green>{$info['class']}</>");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Error retrieving Livewire component list: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Manually discover Livewire components by scanning the filesystem
     */
    private function discoverComponents()
    {
        $components = [];
        $namespace = config('livewire.class_namespace', 'App\\Livewire');
        $directory = app_path('Livewire');

        if (!is_dir($directory)) {
            throw new \Exception("Livewire directory not found: {$directory}");
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                // Get the relative path from the Livewire directory
                $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $relativePath = str_replace('.php', '', $relativePath);

                // Convert directory separators to namespace separators
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

                // Convert to class name
                $className = $namespace . '\\' . str_replace('/', '\\', $relativePath);

                // Convert to component tag (dotted notation)
                $componentTag = str_replace('/', '.', $relativePath);
                $componentTag = str_replace('\\', '.', $componentTag);

                // Convert camelCase to kebab-case for the tag
                $componentTag = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $componentTag));
                $componentTag = str_replace('.-', '.', $componentTag); // Fix double dots
                $componentTag = preg_replace('/\-+/', '-', $componentTag); // Remove duplicate dashes

                if (class_exists($className)) {
                    $components[$componentTag] = [
                        'class' => $className,
                        'file' => $file->getPathname()
                    ];
                }
            }
        }

        // Sort components by tag name
        ksort($components);

        return $components;
    }
}
