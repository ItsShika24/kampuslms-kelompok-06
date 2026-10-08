<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Kata Sandi Baru — EduKampus LMS</title>

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
    </div>

    {{-- Main Content --}}
    <div class="max-w-md w-full mx-auto my-8">

        <div class="bg-slate-900/90 backdrop-blur-xl border border-slate-800 shadow-2xl rounded-3xl p-6 sm:p-8 relative overflow-hidden" x-data="{ showPass: false }">
            <div class="mb-6">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 mb-4">
                    <span class="material-symbols-outlined text-[26px]">vpn_key</span>
                </div>
                <h1 class="text-2xl font-black text-white tracking-tight">Perbarui Kata Sandi</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1.5 leading-relaxed">
                    Tetapkan kata sandi baru untuk akun Anda. Pastikan minimal 8 karakter.
                </p>
            </div>

            <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Alamat Email
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-[18px]">mail</span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email', $email) }}"
                            required
                            readonly
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-950/40 border border-slate-800 rounded-xl text-sm text-slate-400 cursor-not-allowed"
                        >
                    </div>
                    @error('email')
                        <p class="text-rose-400 text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Kata Sandi Baru --}}
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Kata Sandi Baru
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-[18px]">lock</span>
                        <input
                            :type="showPass ? 'text' : 'password'"
                            id="password"
                            name="password"
                            required
                            placeholder="Minimal 8 karakter"
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

                {{-- Konfirmasi Kata Sandi Baru --}}
                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Konfirmasi Kata Sandi Baru
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-[18px]">lock_reset</span>
                        <input
                            :type="showPass ? 'text' : 'password'"
                            id="password_confirmation"
                            name="password_confirmation"
                            required
                            placeholder="Ulangi kata sandi baru"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-950/70 border border-slate-800 focus:border-indigo-500 rounded-xl text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition"
                        >
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full mt-2 py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-bold text-sm shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-2 group"
                >
                    <span>Simpan Kata Sandi Baru</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-0.5 transition-transform">check</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Footer --}}
    <div class="text-center text-xs text-slate-500 pb-2">
        <p>© 2026 EduKampus LMS (Kelompok 06). SI2514024 Pemrograman Web.</p>
    </div>

</body>
</html>
