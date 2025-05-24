<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MenuBackupService
{
    /**
     * Create a backup of the current menu state
     */
    public function createBackup(string $event = 'manual'): bool
    {
        try {
            DB::beginTransaction();

            // Get all menus with their relationships - using exact same query as export
            $menus = Menu::with(['children', 'roles', 'permissions'])
                ->whereNull('parent_id')
                ->orderBy('order_number')
                ->get();

            Log::info('Starting menu backup', [
                'event' => $event,
                'menus_count' => $menus->count()
            ]);

            // Transform the data for backup - using exact same format as export
            $menuConfig = $menus->toArray();

            // Create backup directory if it doesn't exist
            $backupDir = storage_path('app/backups/menus');
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
                Log::info('Created backup directory', ['path' => $backupDir]);
            }

            // Save backup with timestamp and event type
            $timestamp = now()->format('Y-m-d_H-i-s');
            // Determine the event name for the filename
            $filenameEvent = ($event === 'manual') ? 'manual' : 'auto'; // Use 'auto' for any non-manual event
            $filename = "menu_backup_{$filenameEvent}_{$timestamp}.json";
            $filePath = storage_path("app/backups/menus/{$filename}");

            File::put(
                $filePath,
                json_encode($menuConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            Log::info('Backup file created', [
                'filename' => $filename,
                'path' => $filePath,
                'size' => File::size($filePath)
            ]);

            // Keep only the last 5 backups
            $files = File::files($backupDir);
            if (count($files) > 5) {
                usort($files, function ($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $filesToDelete = array_slice($files, 5);
                foreach ($filesToDelete as $file) {
                    File::delete($file);
                    Log::info('Deleted old backup file', ['file' => $file->getFilename()]);
                }
            }

            DB::commit();
            Log::info("Menu backup completed successfully", [
                'event' => $event,
                'filename' => $filename
            ]);
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create menu backup", [
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
