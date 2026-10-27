<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Portal Akademik — EduKampus</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .login-input {
            padding-left: 2.85rem !important;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-950 font-['Inter'] text-slate-100 flex flex-col justify-center items-center py-10 px-4 selection:bg-indigo-500 selection:text-white relative overflow-x-hidden">

    {{-- Latar Belakang Efek Glow Halus --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-indigo-600/15 rounded-full blur-[100px]"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-purple-600/15 rounded-full blur-[100px]"></div>
    </div>

    {{-- Wadah Kontainer Terpusat (Maksimal 450px) --}}
    <div class="w-full max-w-md mx-auto relative z-10 space-y-6" x-data="{
        showPassword: false,
        fillAccount(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
        }
    }">

        {{-- Header & Logo --}}
        <div class="text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-600 shadow-xl shadow-indigo-500/25 mb-4 ring-1 ring-white/20 hover:scale-105 transition duration-200">
                <span class="material-symbols-outlined text-white text-[32px]">school</span>
            </a>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">EduKampus LMS</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Sistem Manajemen Pembelajaran & Portal Akademik</p>
        </div>

        {{-- Kartu Login Utama --}}
        <div class="bg-slate-900/90 backdrop-blur-xl border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">

            <div class="mb-6">
                <h2 class="text-lg sm:text-xl font-bold text-white">Masuk ke Akun Anda</h2>
                <p class="text-xs text-slate-400 mt-1">Gunakan kredensial resmi untuk mengakses sistem pembelajaran.</p>
            </div>

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mb-5 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-emerald-400 text-[20px] shrink-0">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-5 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-rose-400 text-[20px] shrink-0">error</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- Form Login --}}
            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                @csrf

                {{-- Input Email --}}
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5">Alamat Email</label>
                    <div class="relative flex items-center">
                        <span class="absolute left-3.5 flex items-center pointer-events-none text-slate-400 z-10">
                            <span class="material-symbols-outlined text-[20px]">mail</span>
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            placeholder="nama@kampuslms.test"
                            class="login-input w-full pr-4 py-3 rounded-xl bg-slate-950/80 border text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 transition {{ $errors->any() ? 'border-rose-500/80 focus:ring-rose-500/30' : 'border-slate-800 focus:border-indigo-500 focus:ring-indigo-500/30' }}"
                        />
                    </div>
                </div>

                {{-- Input Password --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-semibold text-slate-300">Kata Sandi</label>
                    </div>
                    <div class="relative flex items-center">
                        <span class="absolute left-3.5 flex items-center pointer-events-none text-slate-400 z-10">
                            <span class="material-symbols-outlined text-[20px]">lock</span>
                        </span>
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="login-input w-full pr-11 py-3 rounded-xl bg-slate-950/80 border text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 transition {{ $errors->any() ? 'border-rose-500/80 focus:ring-rose-500/30' : 'border-slate-800 focus:border-indigo-500 focus:ring-indigo-500/30' }}"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 text-slate-400 hover:text-slate-200 transition focus:outline-none p-1 rounded-lg"
                            title="Tampilkan / Sembunyikan Kata Sandi"
                        >
                            <span class="material-symbols-outlined text-[20px]" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>

                {{-- Notifikasi Error --}}
                @if ($errors->any())
                    <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px] text-rose-400 shrink-0">info</span>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                {{-- Ingat Saya --}}
                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-400 hover:text-slate-200 transition">
                        <input
                            type="checkbox"
                            name="remember"
                            class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-slate-900 cursor-pointer"
                        />
                        <span>Ingat saya di perangkat ini</span>
                    </label>
                </div>

                {{-- Tombol Submit --}}
                <button
                    type="submit"
                    class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 hover:shadow-indigo-600/50 transition duration-150 active:scale-[0.99] flex items-center justify-center gap-2 mt-4 cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[20px]">login</span>
                    <span>Masuk ke Akun</span>
                </button>
            </form>

            {{-- Tombol Cepat Demo (Fitur Kemudahan Demo Dosen & Tim) --}}
            <div class="mt-6 pt-5 border-t border-slate-800">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2.5 text-center">
                    Masuk Cepat Akun Demo
                </p>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        type="button"
                        @click="fillAccount('admin@kampuslms.test', 'password')"
                        class="px-2.5 py-2 rounded-xl bg-slate-950/80 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-rose-400 text-xs font-medium transition text-center cursor-pointer"
                    >
                        <span class="block font-bold">Admin</span>
                        <span class="text-[10px] text-slate-500">Kelola Sistem</span>
                    </button>
                    <button
                        type="button"
                        @click="fillAccount('dosen@kampuslms.test', 'password')"
                        class="px-2.5 py-2 rounded-xl bg-slate-950/80 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-amber-400 text-xs font-medium transition text-center cursor-pointer"
                    >
                        <span class="block font-bold">Dosen</span>
                        <span class="text-[10px] text-slate-500">Beri Nilai/Tugas</span>
                    </button>
                    <button
                        type="button"
                        @click="fillAccount('mahasiswa@kampuslms.test', 'password')"
                        class="px-2.5 py-2 rounded-xl bg-slate-950/80 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-indigo-400 text-xs font-medium transition text-center cursor-pointer"
                    >
                        <span class="block font-bold">Mahasiswa</span>
                        <span class="text-[10px] text-slate-500">Kumpul/Cek Nilai</span>
                    </button>
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div class="text-center text-xs text-slate-500 space-y-1">
            <p>&copy; {{ date('Y') }} EduKampus LMS &bull; SI2514024 Pemrograman Web</p>
            <p><a href="{{ route('home') }}" class="text-indigo-400 hover:underline inline-flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">arrow_back</span> Kembali ke Beranda</a></p>
        </div>

    </div>

</body>
</html>
