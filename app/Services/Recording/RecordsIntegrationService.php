<?php

declare(strict_types=1);

namespace App\Services\Recording;

use Illuminate\Support\Facades\Log;
use App\Services\Recording\DTOs\ProcessingResult;

class RecordsIntegrationService
{
    public function __construct()
    {
        Log::debug('RecordsIntegrationService stub instantiated');
    }

    public function saveRecording(array $data): ProcessingResult
    {
        return ProcessingResult::success(['recording_id' => null], 'Stub integration save');
    }
}
