<?php

namespace App\Http\Controllers;

use App\Models\GlobalUser;
use App\Models\GlobalActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/');
        }
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string', // can be email, username, or telegram_id
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
        ]);

        $login = trim($credentials['login']);
        $password = $credentials['password'];
        $remember = $request->boolean('remember', false);

        // Find user by email, username, or telegram_id
        $user = GlobalUser::where('global_user_email', $login)
            ->orWhere('global_user_username', $login)
            ->orWhere('global_user_username', '@' . ltrim($login, '@'))
            ->orWhere('global_user_telegram_id', $login)
            ->first();

        if ($user && $user->global_user_is_active && Hash::check($password, $user->global_user_password)) {
            Auth::login($user, $remember);
            $request->session()->regenerate();

            GlobalActivityLog::create([
                'global_activity_action' => 'user_login',
                'global_activity_description' => "User {$user->global_user_nama} ({$user->global_user_email}) berhasil login ke Dashboard",
                'global_activity_status' => 'success',
            ]);

            return redirect()->intended('/');
        }

        return back()->withErrors([
            'login' => 'Kredensial login tidak cocok atau akun dinonaktifkan.',
        ]);
    }

    public function logout(Request $request)
    {
        $userName = Auth::user()?->name ?? 'User';
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        GlobalActivityLog::create([
            'global_activity_action' => 'user_logout',
            'global_activity_description' => "User {$userName} berhasil logout dari Dashboard",
            'global_activity_status' => 'success',
        ]);

        return redirect('/login');
    }
}
