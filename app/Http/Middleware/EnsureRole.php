<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->global_user_is_active) {
            auth()->logout();
            return redirect()->route('login')->withErrors(['login' => 'Akun Anda telah dinonaktifkan.']);
        }

        // If user is super admin, always allow
        if ($user->global_user_role === 'admin') {
            return $next($request);
        }

        if (!empty($roles) && !in_array($user->global_user_role, $roles)) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Akses ditolak: Role Anda tidak memiliki izin untuk fitur ini.'], 403);
            }
            abort(403, 'Akses ditolak: Role Anda (' . $user->global_user_role . ') tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
