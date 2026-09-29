<?php

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
        // 1. Dukungan sinkronisasi simulasi demo role ke Laravel Auth Guard
        if (! $request->user()) {
            $demoRole = session('demo_role', 'mahasiswa');
            $demoUser = User::where('role', $demoRole)->first() ?? User::first();
            if ($demoUser) {
                auth()->login($demoUser);
                if (! session()->has('demo_role')) {
                    session(['demo_role' => $demoUser->role]);
                }
            }
        }

        // 2. Jika pengguna belum terautentikasi -> HTTP 401 Unauthorized
        if (! $request->user()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated. Silakan login terlebih dahulu.',
                ], 401);
            }

            abort(401, 'Unauthenticated. Silakan login terlebih dahulu.');
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
