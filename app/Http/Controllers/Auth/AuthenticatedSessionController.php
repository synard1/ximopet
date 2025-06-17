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
        // Ambil email dari request
        $email = $request->get('email');
        $userModel = \App\Models\User::where('email', $email)->first();

        // Cek mapping company berdasarkan email
        if ($userModel && !$userModel->hasRole('SuperAdmin')) {
            try {
                $companyMapping = \App\Models\CompanyUser::with('company')
                    ->where('user_id', $userModel->id)
                    ->where('status', 'active')
                    ->first();

                if (!$companyMapping) {
                    \Illuminate\Support\Facades\Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    \Illuminate\Support\Facades\Session::flash('error', [
                        'title' => 'Akses Ditolak',
                        'message' => 'Akun Anda belum terdaftar di perusahaan manapun. Silakan hubungi administrator perusahaan Anda untuk mendapatkan akses.',
                        'type' => 'error'
                    ]);
                    return redirect()->route('login');
                }

                if (!$companyMapping->company) {
                    \Illuminate\Support\Facades\Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    \Illuminate\Support\Facades\Session::flash('error', [
                        'title' => 'Akses Ditolak',
                        'message' => 'Data perusahaan tidak ditemukan. Silakan hubungi administrator perusahaan Anda.',
                        'type' => 'error'
                    ]);
                    return redirect()->route('login');
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                \Illuminate\Support\Facades\Session::flash('error', [
                    'title' => 'Error Sistem',
                    'message' => 'Terjadi kesalahan saat memverifikasi akses perusahaan. Silakan coba lagi.',
                    'type' => 'error'
                ]);
                return redirect()->route('login');
            }
            // Cek status company
            if ($companyMapping->company && $companyMapping->company->status !== 'active') {
                \Illuminate\Support\Facades\Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                \Illuminate\Support\Facades\Session::flash('error', [
                    'title' => 'Perusahaan Tidak Aktif',
                    'message' => 'Perusahaan Anda saat ini tidak aktif. Silakan hubungi administrator untuk informasi lebih lanjut.',
                    'type' => 'warning'
                ]);
                return redirect()->route('login');
            }
        }

        // Jika mapping valid, lanjutkan autentikasi
        try {
            $request->authenticate();
            $user = $request->user();
            $request->session()->regenerate();
            $user->update([
                'last_login_at' => \Illuminate\Support\Carbon::now()->toDateTimeString(),
                'last_login_ip' => $request->ip()
            ]);
            \App\Services\LoginLogService::log(
                $user->id,
                'success',
                'form',
                ['email' => $request->get('email')]
            );
            $token = $user->createToken('auth_token')->plainTextToken;
            $request->session()->put('auth_token', $token);
            return redirect()->intended(\App\Providers\RouteServiceProvider::HOME);
        } catch (\Exception $e) {
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
