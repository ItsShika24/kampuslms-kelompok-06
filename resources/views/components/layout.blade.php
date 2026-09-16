<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'EduKampus' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
    />

    {{-- Alpine.js untuk tab switcher dan komponen interaktif --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="min-h-screen bg-slate-100 font-['Inter'] text-slate-800">

    {{-- ============================================================
         TOP NAVBAR — menggantikan sidebar
         ============================================================ --}}
    @php
        $demoRole   = session('demo_role', 'mahasiswa');
        $headerUser = \App\Models\User::getDemoUser($demoRole);
        $avatarColors = [
            'admin'     => 'bg-rose-600',
            'dosen'     => 'bg-amber-500',
            'mahasiswa' => 'bg-indigo-600',
        ];
        $avatarBg = $avatarColors[$demoRole] ?? 'bg-indigo-600';
        $initial  = $headerUser ? strtoupper(substr($headerUser->name, 0, 1)) : 'U';
    @endphp

    <header class="sticky top-0 z-50 bg-slate-950 shadow-lg">

        <div class="mx-auto px-4 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- Logo --}}
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 shrink-0">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600
                                flex items-center justify-center text-white font-bold text-base shadow-lg">
                        E
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-white font-extrabold text-base leading-none">EduKampus</p>
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest leading-none mt-0.5">
                            Portal Akademik
                        </p>
                    </div>
                </a>

                {{-- Navigasi tengah (desktop) --}}
                <nav class="hidden md:flex items-center gap-1">

                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition
                              {{ request()->routeIs('dashboard')
                                  ? 'bg-indigo-600 text-white shadow'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px]">dashboard</span>
                        Dashboard
                    </a>

                    <a href="{{ route('mata-kuliah.index') }}"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition
                              {{ request()->routeIs('mata-kuliah.*')
                                  ? 'bg-indigo-600 text-white shadow'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px]">menu_book</span>
                        Mata Kuliah
                    </a>

                    {{-- Hanya admin --}}
                    @if ($demoRole === 'admin')
                        <a href="{{ route('pengguna.index') }}"
                           class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition
                                  {{ request()->routeIs('pengguna.*')
                                      ? 'bg-indigo-600 text-white shadow'
                                      : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <span class="material-symbols-outlined text-[18px]">manage_accounts</span>
                            Pengguna
                        </a>
                    @endif

                    <a href="{{ route('tentang') }}"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition
                              {{ request()->routeIs('tentang')
                                  ? 'bg-indigo-600 text-white shadow'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px]">info</span>
                        Tentang
                    </a>

                </nav>

                {{-- Profil pengguna (kanan) --}}
                <div class="flex items-center gap-3">

                    {{-- Status & tahun akademik --}}
                    <div class="hidden lg:flex items-center gap-1.5 text-xs text-slate-400">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
                        T.A. 2024/2025 Genap
                    </div>

                    {{-- Divider --}}
                    <div class="hidden lg:block w-px h-6 bg-slate-700"></div>

                    {{-- Info nama & role --}}
                    <div class="hidden sm:block text-right">
                        <p class="text-sm font-semibold text-white leading-none">
                            {{ $headerUser?->name ?? 'Pengguna' }}
                        </p>
                        <p class="text-[11px] text-slate-400 capitalize leading-none mt-0.5">
                            {{ $demoRole }}
                        </p>
                    </div>

                    {{-- Avatar --}}
                    <div class="w-9 h-9 rounded-full {{ $avatarBg }} text-white
                                flex items-center justify-center font-bold text-sm shrink-0">
                        {{ $initial }}
                    </div>

                    {{-- Hamburger mobile --}}
                    <button
                        id="nav-toggle"
                        class="md:hidden flex items-center justify-center w-9 h-9 rounded-xl
                               text-slate-300 hover:bg-slate-800 hover:text-white transition"
                        aria-label="Toggle navigation"
                    >
                        <span class="material-symbols-outlined text-[22px]">menu</span>
                    </button>

                </div>

            </div>

            {{-- Mobile menu (tersembunyi secara default) --}}
            <nav id="mobile-nav" class="hidden md:hidden pb-4 border-t border-slate-800 mt-0 pt-3 space-y-1">

                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition
                          {{ request()->routeIs('dashboard')
                              ? 'bg-indigo-600 text-white'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <span class="material-symbols-outlined text-[18px]">dashboard</span>
                    Dashboard
                </a>

                <a href="{{ route('mata-kuliah.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition
                          {{ request()->routeIs('mata-kuliah.*')
                              ? 'bg-indigo-600 text-white'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <span class="material-symbols-outlined text-[18px]">menu_book</span>
                    Mata Kuliah
                </a>

                @if ($demoRole === 'admin')
                    <a href="{{ route('pengguna.index') }}"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition
                              {{ request()->routeIs('pengguna.*')
                                  ? 'bg-indigo-600 text-white'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px]">manage_accounts</span>
                        Pengguna
                    </a>
                @endif

                <a href="{{ route('tentang') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition
                          {{ request()->routeIs('tentang')
                              ? 'bg-indigo-600 text-white'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <span class="material-symbols-outlined text-[18px]">info</span>
                    Tentang
                </a>

            </nav>
        </div>

    </header>


    {{-- Konten halaman tanpa offset sidebar --}}
    <main class="mx-auto px-4 lg:px-8 py-8">
        {{ $slot }}
    </main>


    {{-- Script toggle mobile menu --}}
    <script>
        document.getElementById('nav-toggle')?.addEventListener('click', function () {
            const nav = document.getElementById('mobile-nav');
            nav.classList.toggle('hidden');
        });
    </script>

</body>
</html>