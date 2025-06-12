<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CheckTempAuthorization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user has bypass permissions
        if (auth()->user() && auth()->user()->can('override temp auth')) {
            return $next($request);
        }

        // Check for temporary authorization in session
        $sessionAuth = session('temp_auth_authorized', false);
        $sessionExpiry = session('temp_auth_expiry');

        if ($sessionAuth && $sessionExpiry) {
            $expiry = Carbon::parse($sessionExpiry);
            if (Carbon::now()->lessThan($expiry)) {
                // Authorization is valid, proceed
                return $next($request);
            } else {
                // Authorization expired, clear session
                $this->clearTempAuth();
            }
        }

        // No valid authorization, check if this is an AJAX request
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => 'Temporary authorization required',
                'message' => 'Data is locked and requires temporary authorization to modify',
                'code' => 'TEMP_AUTH_REQUIRED'
            ], 403);
        }

        // Redirect with error message for regular requests
        return redirect()->back()->with('error', 'Data terkunci dan memerlukan autorisasi temporer untuk diubah.');
    }

    /**
     * Clear temporary authorization from session
     */
    private function clearTempAuth()
    {
        session()->forget([
            'temp_auth_authorized',
            'temp_auth_expiry',
            'temp_auth_reason',
            'temp_auth_user',
            'temp_auth_time'
        ]);
    }
}
