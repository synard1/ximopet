<?php

namespace App\Services;

use App\Models\QaChecklist;

class QaChecklistMonitorService
{
    /**
     * Get QA Checklist items relevant to a given URL or route prefix.
     */
    public static function getForUrl($url)
    {
        // Normalize: remove trailing slash
        $url = rtrim($url, '/');

        // Cek exact match dan prefix match
        $qaChecklist =   QaChecklist::where('url', 'like', '%' . $url . '%')
            ->orWhere('url', 'like', $url . '/%')
            // ->orderBy('test_date', 'desc')
            ->get();

        // dd($qaChecklist);

        return $qaChecklist;
    }
}
