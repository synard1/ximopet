<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CleanupTempAuth extends Command
{
    protected $signature = 'temp-auth:cleanup {--force : Force cleanup without confirmation}';
    protected $description = 'Clean up expired temporary authorization sessions';

    public function handle()
    {
        $this->info('Starting temporary authorization cleanup...');

        $sessionPath = storage_path('framework/sessions');
        $cleanedCount = 0;
        $totalCount = 0;

        if (!File::exists($sessionPath)) {
            $this->warn('Session directory not found: ' . $sessionPath);
            return 1;
        }

        $sessionFiles = File::files($sessionPath);
        $totalCount = count($sessionFiles);

        if ($totalCount === 0) {
            $this->info('No session files found.');
            return 0;
        }

        $this->info("Found {$totalCount} session files to check...");

        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to proceed with cleanup?')) {
                $this->info('Cleanup cancelled.');
                return 0;
            }
        }

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        foreach ($sessionFiles as $file) {
            $progressBar->advance();

            try {
                $content = File::get($file->getPathname());

                if (strpos($content, 'temp_auth_') !== false) {
                    $sessionData = $this->decodeSessionData($content);

                    if ($sessionData && isset($sessionData['temp_auth_expiry'])) {
                        $expiry = Carbon::parse($sessionData['temp_auth_expiry']);

                        if (Carbon::now()->greaterThan($expiry)) {
                            $cleanedData = $this->cleanTempAuthFromSession($sessionData);
                            $encodedData = $this->encodeSessionData($cleanedData);

                            File::put($file->getPathname(), $encodedData);
                            $cleanedCount++;
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Cleanup completed!");
        $this->info("Total session files checked: {$totalCount}");
        $this->info("Expired temp auth sessions cleaned: {$cleanedCount}");

        return 0;
    }

    private function decodeSessionData($content)
    {
        try {
            if (strpos($content, 'laravel_session:') === 0) {
                $content = substr($content, 16);
            }

            $decoded = base64_decode($content);
            if ($decoded === false) {
                return null;
            }

            $data = unserialize($decoded);
            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function encodeSessionData($data)
    {
        $serialized = serialize($data);
        $encoded = base64_encode($serialized);
        return 'laravel_session:' . $encoded;
    }

    private function cleanTempAuthFromSession($sessionData)
    {
        $tempAuthKeys = [
            'temp_auth_authorized',
            'temp_auth_expiry',
            'temp_auth_reason',
            'temp_auth_user',
            'temp_auth_time'
        ];

        foreach ($tempAuthKeys as $key) {
            unset($sessionData[$key]);
        }

        return $sessionData;
    }
}
