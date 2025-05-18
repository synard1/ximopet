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
    protected $signature = 'livewire:list';

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
            $livewireManager = app('livewire');
            $components = $livewireManager->components;

            if (empty($components)) {
                $this->info('No Livewire components found.');
                return 0;
            }

            $this->info('Registered Livewire Components:');
            foreach ($components as $tag => $class) {
                $this->line("- <fg=yellow>{$tag}</> => <fg=green>{$class}</>");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Error retrieving Livewire component list: ' . $e->getMessage());
            return 1;
        }
    }
}