<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Handles renter registration and the shared login and logout flow.
 */
class AuthController extends Controller
{
    /**
     * Show the registration form.
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Register a new renter. The account stays pending until an admin approves it.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Role and status are set here, never taken from the request.
        User::create([
            'full_name' => $data['full_name'],
            'nik' => $data['nik'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => User::ROLE_RENTER,
            'status' => User::STATUS_PENDING,
        ]);

        return redirect()
            ->route('login')
            ->with('success', 'Registration successful. Please wait for admin approval before logging in.');
    }

    /**
     * Show the login form.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Log in, store the JWT in an HttpOnly cookie, and redirect by role.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return $this->loginFailed('Invalid email or password.');
        }

        if ($user->status === User::STATUS_PENDING) {
            return $this->loginFailed('Your account is still waiting for admin approval.');
        }

        if ($user->status === User::STATUS_REJECTED) {
            return $this->loginFailed('Your registration was rejected by the admin.');
        }

        $token = auth('api')->login($user);
        $request->session()->regenerate();

        $cookie = cookie(
            'access_token',
            $token,
            (int) config('jwt.ttl'),
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        );

        $route = $user->isAdmin() ? 'admin.dashboard' : 'renter.dashboard';

        return redirect()->route($route)->withCookie($cookie);
    }

    /**
     * Invalidate the token, clear the cookie, and return to the login page.
     */
    public function logout(Request $request): RedirectResponse
    {
        auth('api')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withCookie(Cookie::forget('access_token'))
            ->with('success', 'You have been logged out.');
    }

    /**
     * Redirect back to the login form with an error message.
     */
    private function loginFailed(string $message): RedirectResponse
    {
        return back()->withErrors(['email' => $message])->onlyInput('email');
    }
}