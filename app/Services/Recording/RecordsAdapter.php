<?php

declare(strict_types=1);

namespace App\Services\Recording;

use Illuminate\Support\Facades\Log;

class RecordsAdapter
{
    public function __construct()
    {
        Log::debug('RecordsAdapter stub instantiated');
    }

    public function process(array $data = []): array
    {
        return $data;
    }
}
