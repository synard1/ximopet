<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Services\LoginLogService;
use App\Models\User;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Session;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        addJavascriptFile('assets/js/custom/authentication/sign-in/general.js');

        return view('pages/auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        try {
            // Ambil email dari request
            $email = $request->input('email');
            $userModel = \App\Models\User::where('email', $email)->first();

            // Log attempt
            \Illuminate\Support\Facades\Log::info('Login attempt', [
                'email' => $email,
                'ip' => request()->ip()
            ]);

            // Cek mapping company berdasarkan email
            if ($userModel && !$userModel->hasRole('SuperAdmin')) {
                try {
                    $companyMapping = \App\Models\CompanyUser::with('company')
                        ->where('user_id', $userModel->id)
                        ->where('status', 'active')
                        ->first();

                    if (!$companyMapping) {
                        \Illuminate\Support\Facades\Log::warning('No active company mapping found', [
                            'user_id' => $userModel->id,
                            'email' => $email
                        ]);

                        \Illuminate\Support\Facades\Auth::logout();
                        session()->invalidate();
                        session()->regenerateToken();

                        \Illuminate\Support\Facades\Session::flash('error', [
                            'title' => 'Akses Ditolak',
                            'message' => 'Akun Anda belum terdaftar di perusahaan manapun. Silakan hubungi administrator perusahaan Anda untuk mendapatkan akses.',
                            'type' => 'error'
                        ]);
                        return redirect()->route('login');
                    }

                    if (!$companyMapping->company) {
                        \Illuminate\Support\Facades\Log::error('Company not found for mapping', [
                            'company_mapping_id' => $companyMapping->id,
                            'user_id' => $userModel->id
                        ]);

                        \Illuminate\Support\Facades\Auth::logout();
                        session()->invalidate();
                        session()->regenerateToken();

                        \Illuminate\Support\Facades\Session::flash('error', [
                            'title' => 'Akses Ditolak',
                            'message' => 'Data perusahaan tidak ditemukan. Silakan hubungi administrator perusahaan Anda.',
                            'type' => 'error'
                        ]);
                        return redirect()->route('login');
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Company mapping error', [
                        'error' => $e->getMessage(),
                        'user_id' => $userModel->id
                    ]);

                    \Illuminate\Support\Facades\Auth::logout();
                    session()->invalidate();
                    session()->regenerateToken();

                    \Illuminate\Support\Facades\Session::flash('error', [
                        'title' => 'Error Sistem',
                        'message' => 'Terjadi kesalahan saat memverifikasi akses perusahaan. Silakan coba lagi.',
                        'type' => 'error'
                    ]);
                    return redirect()->route('login');
                }

                // Cek status company
                if ($companyMapping->company && $companyMapping->company->status !== 'active') {
                    \Illuminate\Support\Facades\Log::warning('Inactive company attempt', [
                        'company_id' => $companyMapping->company->id,
                        'user_id' => $userModel->id
                    ]);

                    \Illuminate\Support\Facades\Auth::logout();
                    session()->invalidate();
                    session()->regenerateToken();

                    \Illuminate\Support\Facades\Session::flash('error', [
                        'title' => 'Perusahaan Tidak Aktif',
                        'message' => 'Perusahaan Anda saat ini tidak aktif. Silakan hubungi administrator untuk informasi lebih lanjut.',
                        'type' => 'warning'
                    ]);
                    return redirect()->route('login');
                }
            }

            // Jika mapping valid, lanjutkan autentikasi
            $request->authenticate();
            $user = Auth::user();

            // Tambahkan pengecekan status suspended
            if ($user->status === 'suspended') {
                \Illuminate\Support\Facades\Log::warning('Suspended user attempt', [
                    'user_id' => $user->id,
                    'email' => $email
                ]);

                \Illuminate\Support\Facades\Auth::logout();
                session()->invalidate();
                session()->regenerateToken();

                \Illuminate\Support\Facades\Session::flash('error', [
                    'title' => 'Akun Ditangguhkan',
                    'message' => 'Akun Anda telah disuspend. Silakan hubungi administrator untuk informasi lebih lanjut.',
                    'type' => 'warning'
                ]);
                return redirect()->route('login');
            }

            session()->regenerate();
            $user->update([
                'last_login_at' => \Illuminate\Support\Carbon::now()->toDateTimeString(),
                'last_login_ip' => request()->ip()
            ]);

            \App\Services\LoginLogService::log(
                $user->id,
                'success',
                'form',
                ['email' => $request->input('email')]
            );

            $token = $user->createToken('auth_token')->plainTextToken;
            session()->put('auth_token', $token);

            \Illuminate\Support\Facades\Log::info('Login successful', [
                'user_id' => $user->id,
                'email' => $email
            ]);

            return redirect()->intended(\App\Providers\RouteServiceProvider::HOME);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Login error', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    // User Registration
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // Optionally, create a token for the user after registration
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user], 201);
    }

    // User Login
    public function login(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    // User Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    // Password Reset (You may need to implement this based on your requirements)
    public function resetPassword(Request $request)
    {
        // Implement password reset logic here
        // This typically involves sending a reset link to the user's email
    }
}
