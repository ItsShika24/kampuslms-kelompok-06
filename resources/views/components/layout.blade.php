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

<body class="min-h-screen flex flex-col bg-slate-50 font-['Inter'] text-slate-800 antialiased selection:bg-indigo-500 selection:text-white">

    {{-- ============================================================
         TOP NAVBAR — Header LMS Akademik Terpadu
         ============================================================ --}}
    @php
        $headerUser = auth()->user();
        if (!$headerUser) {
            $demoEmails = [
                'admin'     => 'admin@kampuslms.test',
                'dosen'     => 'dosen@kampuslms.test',
                'mahasiswa' => 'mahasiswa@kampuslms.test',
            ];
            $demoRole   = session('demo_role', 'mahasiswa');
            $headerUser = \App\Models\User::where('email', $demoEmails[$demoRole] ?? $demoEmails['mahasiswa'])->first();
        } else {
            $demoRole = $headerUser->role;
        }

        $avatarColors = [
            'admin'     => 'bg-rose-600',
            'dosen'     => 'bg-amber-600',
            'mahasiswa' => 'bg-indigo-600',
        ];
        $avatarBg = $avatarColors[$demoRole] ?? 'bg-indigo-600';
        $initial  = $headerUser ? strtoupper(substr($headerUser->name, 0, 1)) : 'U';
    @endphp

    <header class="sticky top-0 z-50 bg-slate-900 border-b border-slate-800 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- Logo & Brandmark --}}
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 shrink-0 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-sky-400
                                flex items-center justify-center text-white font-extrabold text-lg shadow-md
                                group-hover:scale-105 transition-transform duration-200">
                        E
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-white font-bold text-base tracking-tight group-hover:text-indigo-300 transition-colors">
                                EduKampus
                            </span>
                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-indigo-500/30 text-indigo-300 border border-indigo-400/30">
                                LMS
                            </span>
                        </div>
                        <p class="text-[10px] text-slate-400 uppercase tracking-wider font-medium leading-none mt-0.5">
                            Sistem Akademik
                        </p>
                    </div>
                </a>

                {{-- Navigasi Utama (Desktop) --}}
                <nav class="hidden md:flex items-center gap-1">

                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150
                              {{ request()->routeIs('dashboard')
                                  ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-500/50'
                                  : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px]">dashboard</span>
                        Dashboard
                    </a>

                    <a href="{{ route('mata-kuliah.index') }}"
                       class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150
                              {{ request()->routeIs('mata-kuliah.*') || request()->routeIs('courses.*')
                                  ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-500/50'
                                  : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px]">menu_book</span>
                        Mata Kuliah
                    </a>

                    {{-- Pusat Pengumpulan & Nilai Tugas --}}
                    @auth
                        <a href="{{ route('submissions.index') }}"
                           class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150
                                  {{ request()->routeIs('submissions.*') || request()->routeIs('pengumpulan.*')
                                      ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-500/50'
                                      : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                            <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
                            @if ($demoRole === 'mahasiswa')
                                Tugas &amp; Nilai
                            @else
                                Penilaian Tugas
                            @endif
                        </a>
                    @endauth

                    {{-- Khusus Administrator --}}
                    @if ($demoRole === 'admin')
                        <a href="{{ route('pengguna.index') }}"
                           class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150
                                  {{ request()->routeIs('pengguna.*') || request()->routeIs('admin.users.*')
                                      ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-500/50'
                                      : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                            <span class="material-symbols-outlined text-[18px]">manage_accounts</span>
                            Pengguna
                        </a>
                    @endif

                    <a href="{{ route('tentang') }}"
                       class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150
                              {{ request()->routeIs('tentang')
                                  ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-500/50'
                                  : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px]">info</span>
                        Tentang
                    </a>

                </nav>

                {{-- Status, Notifikasi & Profil Pengguna (Kanan) --}}
                <div class="flex items-center gap-3">

                    {{-- Tahun Akademik Aktif --}}
                    <div class="hidden lg:flex items-center gap-2 px-2.5 py-1 rounded-lg bg-slate-800/60 border border-slate-700/60 text-xs font-medium text-slate-300">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span>2024/2025 Genap</span>
                    </div>

                    {{-- Lonceng Notifikasi (Realtime Database Notification) --}}
                    @auth
                        @php
                            $unreadNotifCount = $headerUser?->unreadNotifications()->count() ?? 0;
                        @endphp
                        <a href="{{ route('notifications.index') }}"
                           class="relative flex items-center justify-center w-9 h-9 rounded-xl bg-slate-800/90 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition shadow-sm"
                           title="Notifikasi">
                            <span class="material-symbols-outlined text-[20px]">notifications</span>
                            <span id="navbar-notif-badge"
                                  class="{{ $unreadNotifCount > 0 ? '' : 'hidden' }} absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white shadow ring-2 ring-slate-900">
                                {{ $unreadNotifCount > 99 ? '99+' : $unreadNotifCount }}
                            </span>
                        </a>
                    @endauth

                    {{-- Info Profil & Tombol Keluar --}}
                    @auth
                        <div class="hidden sm:flex items-center gap-2.5 pl-2 border-l border-slate-700/80">
                            <div class="w-9 h-9 rounded-xl {{ $avatarBg }} text-white
                                        flex items-center justify-center font-bold text-sm shrink-0 shadow-sm ring-1 ring-white/10">
                                {{ $initial }}
                            </div>
                            <div class="text-left">
                                <p class="text-xs font-semibold text-white leading-tight truncate max-w-[120px]">
                                    {{ $headerUser?->name ?? 'Pengguna' }}
                                </p>
                                <span class="inline-block text-[10px] font-medium text-slate-400 capitalize leading-none">
                                    {{ $demoRole }}
                                </span>
                            </div>
                        </div>

                        {{-- Tombol Logout --}}
                        <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                            @csrf
                            <button
                                type="submit"
                                title="Keluar dari Akun"
                                class="flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-950/70 hover:bg-rose-900 text-rose-300 hover:text-white border border-rose-800/80 hover:border-rose-600 transition text-xs font-semibold shadow-sm"
                            >
                                <span class="material-symbols-outlined text-[17px]">logout</span>
                                <span class="hidden sm:inline">Keluar</span>
                            </button>
                        </form>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow transition"
                        >
                            <span class="material-symbols-outlined text-[16px]">login</span>
                            Masuk
                        </a>
                    @endauth

                    {{-- Hamburger Mobile --}}
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

            {{-- Mobile menu drawer --}}
            <nav id="mobile-nav" class="hidden md:hidden pb-4 border-t border-slate-800 pt-3 space-y-1">

                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition
                          {{ request()->routeIs('dashboard')
                              ? 'bg-indigo-600 text-white'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <span class="material-symbols-outlined text-[18px]">dashboard</span>
                    Dashboard
                </a>

                <a href="{{ route('mata-kuliah.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition
                          {{ request()->routeIs('mata-kuliah.*') || request()->routeIs('courses.*')
                              ? 'bg-indigo-600 text-white'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <span class="material-symbols-outlined text-[18px]">menu_book</span>
                    Mata Kuliah
                </a>

                @auth
                    <a href="{{ route('submissions.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition
                              {{ request()->routeIs('submissions.*') || request()->routeIs('pengumpulan.*')
                                  ? 'bg-indigo-600 text-white'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
                        {{ $demoRole === 'mahasiswa' ? 'Tugas & Nilai Saya' : 'Penilaian Pengumpulan' }}
                    </a>
                @endauth

                @if ($demoRole === 'admin')
                    <a href="{{ route('pengguna.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition
                              {{ request()->routeIs('pengguna.*') || request()->routeIs('admin.users.*')
                                  ? 'bg-indigo-600 text-white'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px]">manage_accounts</span>
                        Pengguna
                    </a>
                @endif

                <a href="{{ route('tentang') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition
                          {{ request()->routeIs('tentang')
                              ? 'bg-indigo-600 text-white'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <span class="material-symbols-outlined text-[18px]">info</span>
                    Tentang
                </a>

                @auth
                    <form method="POST" action="{{ route('logout') }}" class="pt-2 border-t border-slate-800">
                        @csrf
                        <button
                            type="submit"
                            class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold text-rose-400 hover:bg-rose-950/50 transition"
                        >
                            <span class="material-symbols-outlined text-[18px]">logout</span>
                            Keluar dari Akun
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold text-indigo-400 hover:bg-slate-800 transition">
                        <span class="material-symbols-outlined text-[18px]">login</span>
                        Masuk (Login)
                    </a>
                @endauth

            </nav>
        </div>
    </header>

    {{-- ============================================================
         KONTEN UTAMA
         ============================================================ --}}
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Flash Messages --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="mb-6 flex items-center justify-between p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-sm">
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
                 class="mb-6 flex items-center justify-between p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-rose-600 text-[22px]">error</span>
                    <p class="text-sm font-medium">{{ session('error') }}</p>
                </div>
                <button @click="show = false" type="button" class="text-rose-500 hover:text-rose-700 p-1 rounded-lg hover:bg-rose-100 transition">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        @endif

        @if (session('warning'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="mb-6 flex items-center justify-between p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-amber-600 text-[22px]">warning</span>
                    <p class="text-sm font-medium">{{ session('warning') }}</p>
                </div>
                <button @click="show = false" type="button" class="text-amber-500 hover:text-amber-700 p-1 rounded-lg hover:bg-amber-100 transition">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        @endif

        {{ $slot }}
    </main>

    {{-- ============================================================
         FOOTER AKADEMIK RESMI
         ============================================================ --}}
    <footer class="mt-auto border-t border-slate-200/80 bg-white py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800">EduKampus LMS</span>
                <span>&bull;</span>
                <span>Portal Pembelajaran Terintegrasi</span>
            </div>
            <div class="flex items-center gap-4">
                <span>Praktikum Pemrograman Web &bull; Kelompok 06</span>
                <span>&bull;</span>
                <a href="{{ route('tentang') }}" class="hover:text-indigo-600 transition font-medium">Tentang Pengembang</a>
            </div>
        </div>
    </footer>

    {{-- Script toggle mobile menu & Realtime Auto-Polling Notifikasi (Modul Minggu 10) --}}
    <script>
        document.getElementById('nav-toggle')?.addEventListener('click', function () {
            const nav = document.getElementById('mobile-nav');
            nav.classList.toggle('hidden');
        });

        @auth
        // Realtime Auto-Polling Notifikasi setiap 30 detik (Modul Minggu 10)
        setInterval(function () {
            fetch('{{ route('notifications.unreadCount') }}')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('navbar-notif-badge');
                    if (badge) {
                        if (data.unread_count > 0) {
                            badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                })
                .catch(err => console.debug('Polling notifikasi error:', err));
        }, 30000);
        @endauth
    </script>

</body>
</html>