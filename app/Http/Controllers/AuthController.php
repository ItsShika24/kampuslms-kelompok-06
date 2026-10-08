<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Menampilkan form login.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Memproses request login pengguna.
     * Menerapkan session fixation protection (session()->regenerate()).
     */
    public function login(LoginRequest $request)
    {
        $request->authenticate();

        // ⚠️ Wajib: Regenerasi session ID untuk mencegah serangan Session Fixation
        $request->session()->regenerate();

        // Sinkronkan demo_role session dengan role sebenarnya dari akun yang login
        $user = Auth::user();
        session(['demo_role' => $user->role]);

        return redirect()->intended(route('dashboard'))
            ->with('success', "Selamat datang kembali, {$user->name}!");
    }

    /**
     * Memproses logout pengguna.
     * Membersihkan session dan meregenerasi CSRF token.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        // Invalidate session data & regenerate CSRF token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil keluar dari sistem EduKampus.');
    }

    /**
     * Menampilkan halaman lupa kata sandi.
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Mengirim tautan/token reset kata sandi.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'email.exists'   => 'Alamat email tidak terdaftar dalam sistem.',
        ]);

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token'      => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $resetUrl = route('password.reset', ['token' => $token, 'email' => $request->email]);

        // Simpan url di flash session untuk simulasi praktikum / testing lokal tanpa SMTP server
        return back()->with([
            'status'   => 'Tautan pengaturan ulang kata sandi telah dibuat!',
            'resetUrl' => $resetUrl,
        ]);
    }

    /**
     * Menampilkan form reset kata sandi baru.
     */
    public function showResetPasswordForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Memproses pengaturan ulang kata sandi baru.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'                 => ['required'],
            'email'                 => ['required', 'email', 'exists:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ], [
            'email.required'        => 'Alamat email wajib diisi.',
            'email.exists'          => 'Alamat email tidak ditemukan.',
            'password.required'     => 'Kata sandi baru wajib diisi.',
            'password.min'          => 'Kata sandi minimal terdiri dari 8 karakter.',
            'password.confirmed'    => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Token reset kata sandi tidak valid atau telah kedaluwarsa.']);
        }

        // Cek umur token (maksimal 60 menit)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['email' => 'Token reset kata sandi telah kedaluwarsa.']);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            // Password otomatis di-hash oleh casts() model User
            $user->password = $request->password;
            $user->save();
        }

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')
            ->with('success', 'Kata sandi berhasil diatur ulang! Silakan login dengan kata sandi baru Anda.');
    }
}
