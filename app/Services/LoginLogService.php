<?php

namespace App\Services;

use App\Models\LoginLog;
use Illuminate\Http\Request;

class LoginLogService
{
    public static function log($userId, $status, $type = 'form', $details = null)
    {
        return LoginLog::create([
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'login_status' => $status,
            'login_type' => $type,
            'login_details' => $details
        ]);
    }
}
