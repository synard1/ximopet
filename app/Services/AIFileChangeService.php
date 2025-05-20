<?php

namespace App\Services;

use App\Models\AIFileChange;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AIFileChangeService
{
    public function trackFileChange(string $filePath, string $originalContent, string $modifiedContent, string $changeType, ?string $description = null)
    {
        // Create storage directory if it doesn't exist
        $storagePath = 'ai-changes/' . date('Y-m-d');
        if (!Storage::exists($storagePath)) {
            Storage::makeDirectory($storagePath);
        }

        // Generate unique filename for this change
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = basename($filePath) . '_' . $timestamp;

        // Store original and modified content in separate files
        Storage::put($storagePath . '/original_' . $filename, $originalContent);
        Storage::put($storagePath . '/modified_' . $filename, $modifiedContent);

        // Create database record
        return AIFileChange::create([
            'file_path' => $filePath,
            'original_content' => $originalContent,
            'modified_content' => $modifiedContent,
            'changed_at' => Carbon::now(),
            'change_type' => $changeType,
            'description' => $description
        ]);
    }

    public function getRecentChanges(int $limit = 10)
    {
        return AIFileChange::latest('changed_at')
            ->limit($limit)
            ->get();
    }

    public function getChangesByDate(string $date)
    {
        return AIFileChange::whereDate('changed_at', $date)
            ->orderBy('changed_at', 'desc')
            ->get();
    }
}
