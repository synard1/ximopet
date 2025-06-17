<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Session;

class CheckCompanyUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        // Skip check for SuperAdmin
        if (Auth::user()->hasRole('SuperAdmin')) {
            return $next($request);
        }

        // Check if user is mapped to a company
        if (!CompanyUser::isUserMapped()) {
            Auth::logout();

            // Set error message with more details
            Session::flash('error', [
                'title' => 'Akses Ditolak',
                'message' => 'Akun Anda belum terdaftar di perusahaan manapun. Silakan hubungi administrator perusahaan Anda untuk mendapatkan akses.',
                'type' => 'error'
            ]);

            return redirect()->route('login');
        }

        // Check if user's company is active
        $companyMapping = CompanyUser::getUserMapping();
        if ($companyMapping && $companyMapping->company && $companyMapping->company->status !== 'active') {
            Auth::logout();

            Session::flash('error', [
                'title' => 'Perusahaan Tidak Aktif',
                'message' => 'Perusahaan Anda saat ini tidak aktif. Silakan hubungi administrator untuk informasi lebih lanjut.',
                'type' => 'warning'
            ]);

            return redirect()->route('login');
        }

        return $next($request);
    }
}
