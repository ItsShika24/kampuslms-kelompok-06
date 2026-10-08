<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
    />

    {{-- Alpine.js untuk dropdown switcher dan komponen interaktif --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="min-h-screen bg-slate-100 font-['Inter'] text-slate-800">

    {{-- ============================================================
         TOP NAVBAR
         ============================================================ --}}
    @php
        $authUser   = auth()->user();
        $userRole   = $authUser ? $authUser->role : session('demo_role', 'mahasiswa');
        $avatarColors = [
            'admin'     => 'bg-rose-600',
            'dosen'     => 'bg-amber-500',
            'mahasiswa' => 'bg-indigo-600',
        ];
        $avatarBg = $avatarColors[$userRole] ?? 'bg-indigo-600';
        $initial  = $authUser ? strtoupper(substr($authUser->name, 0, 1)) : 'U';
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
                       class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-medium transition
                              {{ request()->routeIs('dashboard')
                                  ? 'bg-indigo-600 text-white shadow'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px]">dashboard</span>
                        Dashboard
                    </a>

                    <a href="{{ route('mata-kuliah.index') }}"
                       class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-medium transition
                              {{ request()->routeIs('mata-kuliah.*')
                                  ? 'bg-indigo-600 text-white shadow'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px]">menu_book</span>
                        Mata Kuliah
                    </a>

                    <a href="{{ route('submissions.index') }}"
                       class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-medium transition
                              {{ request()->routeIs('submissions.*') || request()->routeIs('pengumpulan.*')
                                  ? 'bg-indigo-600 text-white shadow'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
                        Pengumpulan
                    </a>

                    {{-- Khusus admin --}}
                    @if ($userRole === 'admin')
                        <a href="{{ route('pengguna.index') }}"
                           class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-medium transition
                                  {{ request()->routeIs('pengguna.*')
                                      ? 'bg-indigo-600 text-white shadow'
                                      : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <span class="material-symbols-outlined text-[18px]">manage_accounts</span>
                            Pengguna
                        </a>
                    @endif

                    <a href="{{ route('tentang') }}"
                       class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-medium transition
                              {{ request()->routeIs('tentang')
                                  ? 'bg-indigo-600 text-white shadow'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px]">info</span>
                        Tentang
                    </a>

                </nav>

                {{-- Profil pengguna & User Dropdown (kanan) --}}
                <div class="flex items-center gap-3">

                    {{-- Status & tahun akademik --}}
                    <div class="hidden xl:flex items-center gap-1.5 text-xs text-slate-400">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
                        T.A. 2024/2025 Genap
                    </div>

                    {{-- Divider --}}
                    <div class="hidden xl:block w-px h-6 bg-slate-700"></div>

                    @if ($authUser)
                        {{-- Dropdown Profil Pengguna --}}
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button
                                @click="open = !open"
                                type="button"
                                class="flex items-center gap-2.5 p-1 rounded-2xl hover:bg-slate-800/80 transition text-left"
                            >
                                <div class="hidden sm:block text-right">
                                    <p class="text-xs font-semibold text-white leading-none">
                                        {{ $authUser->name }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 capitalize leading-none mt-0.5">
                                        {{ $authUser->role }}
                                    </p>
                                </div>
                                <div class="w-9 h-9 rounded-full {{ $avatarBg }} text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-md">
                                    {{ $initial }}
                                </div>
                                <span class="material-symbols-outlined text-slate-400 text-[18px] hidden sm:block" :class="open ? 'rotate-180 transition-transform' : 'transition-transform'">expand_more</span>
                            </button>

                            {{-- Menu Dropdown --}}
                            <div
                                x-show="open"
                                x-cloak
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-56 rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl py-2 z-50 text-slate-200"
                            >
                                <div class="px-4 py-2 border-b border-slate-800">
                                    <p class="text-xs font-bold text-white truncate">{{ $authUser->name }}</p>
                                    <p class="text-[11px] text-slate-400 truncate">{{ $authUser->email }}</p>
                                    <span class="inline-block mt-1.5 px-2 py-0.5 text-[10px] font-bold rounded-md uppercase tracking-wider {{ $avatarBg }} text-white">
                                        {{ $authUser->role }}
                                    </span>
                                </div>

                                <div class="py-1">
                                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs hover:bg-slate-800 hover:text-white transition">
                                        <span class="material-symbols-outlined text-[18px] text-slate-400">dashboard</span>
                                        Dashboard
                                    </a>
                                    <a href="{{ route('submissions.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs hover:bg-slate-800 hover:text-white transition">
                                        <span class="material-symbols-outlined text-[18px] text-slate-400">task_alt</span>
                                        Pengumpulan Tugas
                                    </a>
                                </div>

                                <div class="border-t border-slate-800 pt-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="w-full flex items-center gap-2.5 px-4 py-2 text-xs text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition text-left"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">logout</span>
                                            Keluar (Logout)
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition">
                            <span class="material-symbols-outlined text-[16px]">login</span>
                            Masuk
                        </a>
                    @endif

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

                <a href="{{ route('submissions.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition
                          {{ request()->routeIs('submissions.*')
                              ? 'bg-indigo-600 text-white'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
                    Pengumpulan Tugas
                </a>

                @if ($userRole === 'admin')
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

                @if ($authUser)
                    <div class="pt-2 border-t border-slate-800">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-rose-400 hover:bg-rose-500/10 transition">
                                <span class="material-symbols-outlined text-[18px]">logout</span>
                                Keluar (Logout)
                            </button>
                        </form>
                    </div>
                @endif

            </nav>
        </div>

    </header>


    {{-- Konten halaman --}}
    <main class="mx-auto px-4 lg:px-8 py-8">

        {{-- Flash Messages --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="mb-6 flex items-center justify-between p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-emerald-600 text-[22px]">check_circle</span>
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
                <button @click="show = false" type="button" class="text-emerald-500 hover:text-emerald-700 p-1 rounded-lg hover:bg-emerald-100 transition">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="mb-6 flex items-center justify-between p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-rose-600 text-[22px]">error</span>
                    <p class="text-sm font-medium">{{ session('error') }}</p>
                </div>
                <button @click="show = false" type="button" class="text-rose-500 hover:text-rose-700 p-1 rounded-lg hover:bg-rose-100 transition">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        @endif

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