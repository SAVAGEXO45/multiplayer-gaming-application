<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\PlayerStatistic;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'username' => strtolower($request->validated('username')),
            'email' => strtolower($request->validated('email')),
            'password' => Hash::make($request->validated('password')),
            'role' => 'player',
            'is_online' => true,
            'last_seen_at' => now(),
            'email_verified_at' => now(),
        ]);

        PlayerStatistic::create([
            'user_id' => $user->id,
            'game_id' => null,
            'games_played' => 0,
            'games_won' => 0,
            'games_lost' => 0,
            'games_drawn' => 0,
            'win_percentage' => 0,
            'total_points' => 0,
        ]);

        $this->notificationService->notify(
            $user,
            'system',
            'Welcome to Nexus Arena!',
            'Your gaming profile is active. Jump into the Games Library or join a live room to earn your first victory!'
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Welcome to Nexus Arena, ' . $user->username . '!');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $input = strtolower(trim($request->input('email')));
        $loginField = filter_var($input, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($loginField, $input)->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        if ($user->is_suspended) {
            return back()->withErrors([
                'email' => 'Your account has been suspended by an administrator.',
            ])->onlyInput('email');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->update([
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        return redirect()->intended(route('dashboard'))->with('success', 'Welcome back, ' . $user->username . '!');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            $user->update([
                'is_online' => false,
                'last_seen_at' => now(),
            ]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', strtolower($request->input('email')))->first();
        if ($user) {
            $plainToken = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($plainToken),
                    'created_at' => now(),
                ]
            );

            return redirect()->route('password.reset', ['token' => $plainToken, 'email' => $user->email])
                ->with('success', 'Password reset token generated! Enter your new password below.');
        }

        return back()->with('success', 'If an account matches that email, a password reset link has been generated.');
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', strtolower($request->input('email')))
            ->first();

        if (!$record || !Hash::check($request->input('token'), $record->token)) {
            return back()->withErrors(['email' => 'Invalid or expired password reset token.']);
        }

        $user = User::where('email', strtolower($request->input('email')))->firstOrFail();
        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Your password has been reset successfully.');
    }

    public function verifyEmail(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user && !$user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        return back()->with('success', 'Email address verified.');
    }
}
