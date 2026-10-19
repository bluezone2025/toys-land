<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Cart;
use App\Support\Favorites;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => tt('محاولات كثيرة، حاول بعد شوي', 'Too many attempts, please try again shortly')]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => tt('البريد أو كلمة المرور غير صحيحة', 'Invalid email or password')]);
        }

        RateLimiter::clear($key);
        $this->afterAuth($request);

        return redirect()->intended(Auth::user()->is_admin ? '/admin' : route('account'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create($data);
        Auth::login($user);
        $this->afterAuth($request);

        return redirect()->route('account')->with('toast', tt('حياك الله! تم إنشاء حسابك 🎉', 'Welcome! Your account is ready 🎉'));
    }

    public function logout(Request $request)
    {
        $locale = session('locale');
        $country = session('country');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session(array_filter(['locale' => $locale, 'country' => $country]));

        return redirect()->route('home');
    }

    protected function afterAuth(Request $request): void
    {
        $keep = $request->session()->only(['cart', 'favorites', 'orders', 'locale', 'country']);
        $request->session()->regenerate();
        session($keep);
        Favorites::mergeGuest();
        Cart::mergeOnLogin();
    }
}
