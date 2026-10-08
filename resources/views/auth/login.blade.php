<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — EduKampus LMS</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 font-['Inter'] text-slate-100 flex flex-col justify-between p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between max-w-5xl mx-auto w-full pt-2">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-extrabold text-lg shadow-lg shadow-indigo-500/30">
                E
            </div>
            <div>
                <p class="text-white font-extrabold text-lg leading-none">EduKampus</p>
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-0.5">Portal Akademik Terpadu</p>
            </div>
        </a>
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition bg-slate-800/60 hover:bg-slate-800 px-3.5 py-2 rounded-xl border border-slate-700/60">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Beranda
        </a>
    </div>

    {{-- Main Content --}}
    <div class="max-w-md w-full mx-auto my-8">

        {{-- Alerts --}}
        @if (session('success'))
            <div class="mb-4 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 flex items-center gap-3 text-sm">
                <span class="material-symbols-outlined text-emerald-400 text-[20px]">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('status'))
            <div class="mb-4 p-4 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 flex items-center gap-3 text-sm">
                <span class="material-symbols-outlined text-indigo-400 text-[20px]">info</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        {{-- Card Login --}}
        <div class="bg-slate-900/90 backdrop-blur-xl border border-slate-800 shadow-2xl rounded-3xl p-6 sm:p-8 relative overflow-hidden" x-data="{ showPass: false }">
            {{-- Decorative glow --}}
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="mb-6">
                <h1 class="text-2xl font-black text-white tracking-tight">Selamat Datang</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">Masuk dengan kredensial akun Anda untuk mengakses sistem pembelajaran.</p>
            </div>

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Alamat Email Kampus
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-[18px]">mail</span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            placeholder="nama@kampuslms.test"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-950/70 border @error('email') border-rose-500/80 ring-1 ring-rose-500/50 @else border-slate-800 focus:border-indigo-500 @enderror rounded-xl text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition"
                        >
                    </div>
                    @error('email')
                        <p class="text-rose-400 text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-semibold text-slate-300">
                            Kata Sandi
                        </label>
                        <a href="{{ route('password.request') }}" class="text-[11px] font-semibold text-indigo-400 hover:text-indigo-300 transition">
                            Lupa sandi?
                        </a>
                    </div>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-[18px]">lock</span>
                        <input
                            :type="showPass ? 'text' : 'password'"
                            id="password"
                            name="password"
                            required
                            placeholder="••••••••"
                            class="w-full pl-10 pr-11 py-2.5 bg-slate-950/70 border @error('password') border-rose-500/80 ring-1 ring-rose-500/50 @else border-slate-800 focus:border-indigo-500 @enderror rounded-xl text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition"
                        >
                        <button
                            type="button"
                            @click="showPass = !showPass"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition p-1"
                            aria-label="Toggle password"
                        >
                            <span class="material-symbols-outlined text-[18px]" x-text="showPass ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-rose-400 text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Remember Me --}}
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-indigo-600 focus:ring-indigo-500/30">
                        <span class="text-xs text-slate-400">Ingat sesi saya di perangkat ini</span>
                    </label>
                </div>

                {{-- Submit Button --}}
                <button
                    type="submit"
                    class="w-full mt-2 py-3 px-4 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-bold text-sm shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-2 group"
                >
                    <span>Masuk ke Akun</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                </button>
            </form>

        </div>

        {{-- Security Note --}}
        <div class="mt-4 p-3 rounded-2xl bg-slate-900/40 border border-slate-800/60 text-center">
            <p class="text-[11px] text-slate-400 flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-[15px] text-indigo-400">lock</span>
                Dilindungi Rate Limiter, Anti-Session Fixation, & Enkripsi Hash Argon/Bcrypt.
            </p>
        </div>
    </div>

    {{-- Footer --}}
    <div class="text-center text-xs text-slate-500 pb-2">
        <p>© 2026 EduKampus LMS (Kelompok 06). SI2514024 Pemrograman Web.</p>
    </div>

</body>
</html>
