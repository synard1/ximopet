<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Response; // Add this line for JSON response
use Spatie\Permission\Models\Role;


class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        addJavascriptFile('assets/js/custom/authentication/sign-up/general.js');

        return view('pages/auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'last_login_at' => \Illuminate\Support\Carbon::now()->toDateTimeString(),
                'last_login_ip' => $request->getClientIp()
            ]);
    
            // Send the verification email (queued)
            // $user->notify(new VerifyEmail);
            event(new Registered($user));

            // Assign the "Volunteer" role
            $volunteerRole = Role::whereName('Volunteer')->firstOrFail();
            $user->assignRole($volunteerRole); 

            // Return a response indicating successful registration and the need to verify
            return Response::json([
                'message' => 'Registration successful! Please check your email to verify your account.',
                'user_id' => $user->id, // Optional: include the user ID if needed
            ]);

        } catch (\Throwable $th) {
            //throw $th;
        }

        

        

        // event(new Registered($user));

        // Auth::login($user);

        // return redirect(RouteServiceProvider::HOME);
    }
}
