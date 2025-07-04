<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PermissionPreset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Permission;
use App\Models\User;

class PermissionPresetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Actions:
     *  list                       Display all presets.
     *  create  --name= --file=    Create new preset from JSON file containing permission_ids array.
     *  show    --id=              Show detail of preset (JSON).
     *  update  --id= --file=      Replace permission_ids with file content.
     *  delete  --id=              Delete preset.
     *  export  --id= --file=      Export preset to file (default storage/app/presets/{name}.json).
     */
    protected $signature = 'permission:preset
                            {action : list|create|show|update|delete|export}
                            {--name= : Preset name (for create)}
                            {--id= : Preset UUID (for show/update/delete/export)}
                            {--file= : Path to JSON file (for create/update/export)}
                            {--json : Output result as raw JSON (only for show)}';

    protected $description = 'Manage Permission Presets (CRUD)';

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list'   => $this->listPresets(),
            'create' => $this->createPreset(),
            'show'   => $this->showPreset(),
            'update' => $this->updatePreset(),
            'delete' => $this->deletePreset(),
            'export' => $this->exportPreset(),
            default  => $this->error('Unknown action.'),
        } ?? Command::FAILURE;
    }

    private function listPresets(): int
    {
        $rows = PermissionPreset::all()->map(function ($p) {
            return [
                $p->id,
                $p->name,
                count($p->permission_ids ?? []),
            ];
        })->toArray();
        if (empty($rows)) {
            $this->info('No presets found.');
            return Command::SUCCESS;
        }
        $this->table(['ID', 'Name', '# Perms'], $rows);
        return Command::SUCCESS;
    }

    private function createPreset(): int
    {
        $name = $this->option('name');
        $file = $this->option('file');

        if (!$name || !$file) {
            $this->error('--name and --file are required.');
            return Command::FAILURE;
        }

        if (!file_exists($file)) {
            $this->error("File $file not found.");
            return Command::FAILURE;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) {
            $this->error('Invalid JSON file.');
            return Command::FAILURE;
        }

        $preset = PermissionPreset::create([
            'id'            => Str::uuid(),
            'name'          => $name,
            'permission_ids' => array_values($data),
            'created_by'    => null,
        ]);

        $this->info("Preset created: {$preset->id}");
        return Command::SUCCESS;
    }

    private function showPreset(): int
    {
        $id = $this->option('id');
        if (!$id) {
            $this->error('--id required');
            return Command::FAILURE;
        }

        $preset = PermissionPreset::find($id);
        if (!$preset) {
            $this->error('Preset not found');
            return Command::FAILURE;
        }

        // Fetch permission names once to avoid N+1
        $names = Permission::whereIn('id', $preset->permission_ids ?? [])
            ->pluck('name', 'id')
            ->toArray();

        // Build payload for potential JSON output
        $data = $preset->toArray();
        $data['created_by'] = $data['created_by'] ? User::find($data['created_by'])?->name : null;
        $data['updated_by'] = $data['updated_by'] ? User::find($data['updated_by'])?->name : null;
        $data['permissions'] = collect($preset->permission_ids ?? [])->map(function ($pid) use ($names) {
            return [
                'id'   => $pid,
                'name' => $names[$pid] ?? null,
            ];
        })->values();

        // If --json flag passed, output raw JSON and exit
        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        // Human-readable output
        $this->info("Preset: {$preset->name}  (ID: {$preset->id})");
        $this->line('Created by : ' . ($data['created_by'] ?? '-'));
        $this->line('Updated by : ' . ($data['updated_by'] ?? '-'));
        $this->line('Total permissions: ' . count($data['permissions']));

        $permRows = collect($data['permissions'])->map(function ($p) {
            // split name into ability / module if possible
            if ($p['name'] && str_contains($p['name'], ' ')) {
                [$ability, $module] = explode(' ', $p['name'], 2);
            } else {
                $ability = $p['name'];
                $module  = '';
            }
            return [$p['id'], $ability, $module];
        })->toArray();

        $this->newLine();
        $this->table(['ID', 'Ability', 'Module'], $permRows);
        return Command::SUCCESS;
    }

    private function updatePreset(): int
    {
        $id = $this->option('id');
        $file = $this->option('file');
        if (!$id || !$file) {
            $this->error('--id and --file required');
            return Command::FAILURE;
        }

        if (!file_exists($file)) {
            $this->error('File not found');
            return Command::FAILURE;
        }
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) {
            $this->error('Invalid JSON');
            return Command::FAILURE;
        }

        $preset = PermissionPreset::find($id);
        if (!$preset) {
            $this->error('Preset not found');
            return Command::FAILURE;
        }

        $preset->update(['permission_ids' => array_values($data), 'updated_by' => null]);
        $this->info('Preset updated');
        return Command::SUCCESS;
    }

    private function deletePreset(): int
    {
        $id = $this->option('id');
        if (!$id) {
            $this->error('--id required');
            return Command::FAILURE;
        }
        $preset = PermissionPreset::find($id);
        if (!$preset) {
            $this->error('Preset not found');
            return Command::FAILURE;
        }
        $preset->delete();
        $this->info('Preset deleted');
        return Command::SUCCESS;
    }

    private function exportPreset(): int
    {
        $id = $this->option('id');
        $file = $this->option('file');
        if (!$id) {
            $this->error('--id required');
            return Command::FAILURE;
        }

        $preset = PermissionPreset::find($id);
        if (!$preset) {
            $this->error('Preset not found');
            return Command::FAILURE;
        }

        if (!$file) {
            $file = storage_path('app/presets/' . Str::slug($preset->name) . '.json');
        }

        if (!file_exists(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }

        file_put_contents($file, json_encode($preset->permission_ids, JSON_PRETTY_PRINT));
        $this->info("Preset exported to $file");
        return Command::SUCCESS;
    }
}
