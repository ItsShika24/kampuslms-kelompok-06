<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Menampilkan formulir login.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Memproses otentikasi pengguna (Login).
     * Menerapkan session regeneration untuk mencegah Session Fixation (Modul Minggu 7).
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Alamat email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        // Otentikasi menggunakan Auth::attempt
        if (Auth::attempt($credentials, $remember)) {
            // Regenerasi ID session untuk mencegah serangan Session Fixation (Cek Modul 7.1)
            $request->session()->regenerate();

            // Sinkronkan peran ke session demo_role agar kompatibel dengan modul sebelumnya
            $user = Auth::user();
            session(['demo_role' => $user->role]);

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang kembali, ' . $user->name . ' (' . ucfirst($user->role) . ')!');
        }

        // Pesan kegagalan generik agar tidak membocorkan apakah email terdaftar (Security Best Practice)
        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan tidak sesuai.',
        ])->onlyInput('email');
    }

    /**
     * Mengeluarkan pengguna dari sistem (Logout).
     * Membersihkan dan meregenerasi token session.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil keluar dari sistem EduKampus.');
    }
}
