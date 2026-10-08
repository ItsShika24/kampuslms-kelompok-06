<?php

namespace App\Http\Controllers;

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Menangani request yang masuk untuk memeriksa hak akses peran (role).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles  Daftar role yang diizinkan (contoh: 'admin', 'dosen')
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Dukungan sinkronisasi HANYA jika sesi demo_role eksplisit disetel (misal lewat /set-role/{role})
        if (! $request->user() && session()->has('demo_role')) {
            $demoRole = session('demo_role');
            $demoUser = User::where('role', $demoRole)->first();
            if ($demoUser) {
                auth()->login($demoUser);
            }
        }

        // 2. Jika pengguna belum terautentikasi -> HTTP 401 Unauthorized / Redirect Login
        if (! $request->user()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect()->guest(route('login'));
        }

        // 3. Jika pengguna terautentikasi tetapi role tidak termasuk yang diizinkan -> HTTP 403 Forbidden
        abort_unless(
            in_array($request->user()->role, $roles, true),
            403,
            'Akses Ditolak: Anda tidak memiliki hak akses yang sesuai untuk area ini.'
        );

        return $next($request);
    }
}
