<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Services\LoginLogService;

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
            $request->authenticate();

            $request->session()->regenerate();

            $user = $request->user();

            // Update last login info
            $user->update([
                'last_login_at' => Carbon::now()->toDateTimeString(),
                'last_login_ip' => $request->getClientIp()
            ]);

            // Log successful login
            LoginLogService::log(
                $user->id,
                'success',
                'form',
                ['email' => $request->email]
            );

            // Create Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;
            $request->session()->put('auth_token', $token);

            return redirect()->intended(RouteServiceProvider::HOME);
        } catch (\Exception $e) {
            // Log failed login attempt
            LoginLogService::log(
                null,
                'failed',
                'form',
                ['email' => $request->email, 'error' => $e->getMessage()]
            );

            throw $e;
        }
    }
    // public function store(LoginRequest $request)
    // {
    //     $request->authenticate();

    //     $request->session()->regenerate();

    //     $user = $request->user();

    //     $user->update([
    //         'last_login_at' => Carbon::now()->toDateTimeString(),
    //         'last_login_ip' => $request->getClientIp()
    //     ]);

    //     // Create a new Sanctum token for the user
    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     // You might want to store this token in the session or return it in the response
    //     // For this example, we'll store it in the session
    //     $request->session()->put('auth_token', $token);

    //     return redirect()->intended(RouteServiceProvider::HOME);
    // }

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
}
