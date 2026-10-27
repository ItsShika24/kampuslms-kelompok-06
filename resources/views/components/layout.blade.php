
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

<body class="min-h-screen flex flex-col bg-slate-50 font-['Inter'] text-slate-800 antialiased selection:bg-indigo-500 selection:text-white overflow-x-clip">

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

            $demoRole = session('demo_role', 'mahasiswa');

            $headerUser = \App\Models\User::where(
                'email',
                $demoEmails[$demoRole] ?? $demoEmails['mahasiswa']
            )->first();
        } else {
            $demoRole = $headerUser->role;
        }

        $avatarColors = [
            'admin'     => 'bg-rose-600',
            'dosen'     => 'bg-amber-600',
            'mahasiswa' => 'bg-indigo-600',
        ];

        $avatarBg = $avatarColors[$demoRole] ?? 'bg-indigo-600';
        $initial = $headerUser ? strtoupper(substr($headerUser->name, 0, 1)) : 'U';
    @endphp

    <header class="sticky top-0 z-50 w-full bg-slate-900/95 backdrop-blur-md border-b border-slate-800 shadow-lg">
        <div class="w-full px-3 sm:px-6 lg:px-6 xl:px-8">
            <div class="flex items-center justify-between h-[70px] gap-2 sm:gap-4">

                {{-- Bagian Kiri: Brand & Navigasi Utama --}}
                <div class="flex items-center gap-3 lg:gap-4 xl:gap-5 shrink-0">

                    {{-- Logo & Brandmark --}}
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 sm:gap-3 shrink-0 group py-1">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-sky-400
                                    flex items-center justify-center text-white font-extrabold text-base sm:text-lg shadow-md shadow-indigo-500/20
                                    group-hover:scale-105 transition-transform duration-200 shrink-0">
                            E
                        </div>

                        <div class="shrink-0">
                            <div class="flex items-center gap-1.5">
                                <span class="text-white font-bold text-sm sm:text-base tracking-tight group-hover:text-indigo-300 transition-colors">
                                    EduKampus
                                </span>

                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                                    LMS
                                </span>
                            </div>

                            <p class="hidden sm:block text-[10px] text-slate-400 uppercase tracking-wider font-medium leading-none mt-0.5">
                                Sistem Akademik
                            </p>
                        </div>
                    </a>

                    {{-- Pemisah vertikal antara Logo & Menu Navigasi --}}
                    <div class="hidden xl:block h-6 w-px bg-slate-800 shrink-0"></div>

                    {{-- Navigasi Utama Desktop --}}
                    <nav class="hidden lg:flex items-center gap-1 xl:gap-1.5 2xl:gap-2 shrink-0">

                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center gap-1.5 xl:gap-2 px-2.5 py-1.5 xl:px-3 xl:py-1.5 2xl:px-3.5 2xl:py-2 rounded-xl text-xs xl:text-[13px] 2xl:text-sm font-semibold whitespace-nowrap transition-all duration-150 shrink-0
                                  {{ request()->routeIs('dashboard')
                                      ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25 ring-1 ring-indigo-400/40'
                                      : 'text-slate-300 hover:bg-slate-800/80 hover:text-white font-medium' }}">
                            <span class="material-symbols-outlined text-[17px] xl:text-[18px] shrink-0">dashboard</span>
                            <span>Dashboard</span>
                        </a>

                        <a href="{{ route('mata-kuliah.index') }}"
                           class="inline-flex items-center gap-1.5 xl:gap-2 px-2.5 py-1.5 xl:px-3 xl:py-1.5 2xl:px-3.5 2xl:py-2 rounded-xl text-xs xl:text-[13px] 2xl:text-sm font-semibold whitespace-nowrap transition-all duration-150 shrink-0
                                  {{ request()->routeIs('mata-kuliah.*') || request()->routeIs('courses.*')
                                      ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25 ring-1 ring-indigo-400/40'
                                      : 'text-slate-300 hover:bg-slate-800/80 hover:text-white font-medium' }}">
                            <span class="material-symbols-outlined text-[17px] xl:text-[18px] shrink-0">menu_book</span>
                            <span>Mata Kuliah</span>
                        </a>

                        {{-- Pusat Pengumpulan & Nilai Tugas --}}
                        @auth
                            <a href="{{ route('submissions.index') }}"
                               class="inline-flex items-center gap-1.5 xl:gap-2 px-2.5 py-1.5 xl:px-3 xl:py-1.5 2xl:px-3.5 2xl:py-2 rounded-xl text-xs xl:text-[13px] 2xl:text-sm font-semibold whitespace-nowrap transition-all duration-150 shrink-0
                                      {{ request()->routeIs('submissions.*') || request()->routeIs('pengumpulan.*')
                                          ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25 ring-1 ring-indigo-400/40'
                                          : 'text-slate-300 hover:bg-slate-800/80 hover:text-white font-medium' }}">
                                <span class="material-symbols-outlined text-[17px] xl:text-[18px] shrink-0">assignment_turned_in</span>

                                <span>
                                    @if ($demoRole === 'mahasiswa')
                                        Tugas Saya
                                    @else
                                        Penilaian<span class="hidden xl:inline"> Tugas</span>
                                    @endif
                                </span>
                            </a>

                            {{-- Rekapitulasi Nilai Akademik --}}
                            <a href="{{ route('grades.index') }}"
                               class="inline-flex items-center gap-1.5 xl:gap-2 px-2.5 py-1.5 xl:px-3 xl:py-1.5 2xl:px-3.5 2xl:py-2 rounded-xl text-xs xl:text-[13px] 2xl:text-sm font-semibold whitespace-nowrap transition-all duration-150 shrink-0
                                      {{ request()->routeIs('grades.*')
                                          ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25 ring-1 ring-indigo-400/40'
                                          : 'text-slate-300 hover:bg-slate-800/80 hover:text-white font-medium' }}">
                                <span class="material-symbols-outlined text-[17px] xl:text-[18px] shrink-0">workspace_premium</span>
                                <span>Rekap Nilai</span>
                            </a>
                        @endauth

                        {{-- Khusus Administrator --}}
                        @if ($demoRole === 'admin')
                            <a href="{{ route('pengguna.index') }}"
                               class="inline-flex items-center gap-1.5 xl:gap-2 px-2.5 py-1.5 xl:px-3 xl:py-1.5 2xl:px-3.5 2xl:py-2 rounded-xl text-xs xl:text-[13px] 2xl:text-sm font-semibold whitespace-nowrap transition-all duration-150 shrink-0
                                      {{ request()->routeIs('pengguna.*') || request()->routeIs('admin.users.*')
                                          ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25 ring-1 ring-indigo-400/40'
                                          : 'text-slate-300 hover:bg-slate-800/80 hover:text-white font-medium' }}">
                                <span class="material-symbols-outlined text-[17px] xl:text-[18px] shrink-0">manage_accounts</span>
                                <span>Pengguna</span>
                            </a>
                        @endif

                        <a href="{{ route('tentang') }}"
                           class="inline-flex items-center gap-1.5 xl:gap-2 px-2.5 py-1.5 xl:px-3 xl:py-1.5 2xl:px-3.5 2xl:py-2 rounded-xl text-xs xl:text-[13px] 2xl:text-sm font-semibold whitespace-nowrap transition-all duration-150 shrink-0
                                  {{ request()->routeIs('tentang')
                                      ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25 ring-1 ring-indigo-400/40'
                                      : 'text-slate-300 hover:bg-slate-800/80 hover:text-white font-medium' }}">
                            <span class="material-symbols-outlined text-[17px] xl:text-[18px] shrink-0">info</span>
                            <span>Tentang</span>
                        </a>

                    </nav>
                </div>

                {{-- Bagian Kanan: Notifikasi, Profil Pengguna & Keluar --}}
                <div class="flex items-center gap-2 sm:gap-3 xl:gap-3.5 shrink-0">

                    {{-- Lonceng Notifikasi Interaktif dengan Dropdown Preview --}}
                    @auth
                        @php
                            $unreadNotifCount = $headerUser?->unreadNotifications()->count() ?? 0;
                            $recentNotifications = $headerUser?->notifications()->latest()->take(5)->get() ?? collect();
                        @endphp

                        <div class="relative shrink-0" id="notif-dropdown-wrapper">

                            {{-- Tombol Lonceng --}}
                            <button
                                type="button"
                                id="notif-bell-button"
                                class="relative flex items-center justify-center w-9 h-9 xl:w-10 xl:h-10 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 transition-all duration-150 shadow-sm focus:outline-none"
                                title="Notifikasi"
                                aria-expanded="false"
                            >
                                <span class="material-symbols-outlined text-[20px]">notifications</span>

                                <span
                                    id="navbar-notif-badge"
                                    class="{{ $unreadNotifCount > 0 ? '' : 'hidden' }} absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white shadow ring-2 ring-slate-900"
                                >
                                    {{ $unreadNotifCount > 99 ? '99+' : $unreadNotifCount }}
                                </span>
                            </button>

                            {{-- Dropdown Preview Menu --}}
                            <div
                                id="notif-dropdown-menu"
                                class="hidden absolute right-0 mt-2 w-80 sm:w-96 max-w-[calc(100vw-2rem)] rounded-2xl bg-white shadow-2xl border border-slate-200/90 z-50 overflow-hidden divide-y divide-slate-100"
                            >

                                {{-- Header Dropdown --}}
                                <div class="p-4 bg-slate-50/80 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs text-slate-900">Notifikasi</span>

                                        <span
                                            id="dropdown-unread-count"
                                            class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-700"
                                        >
                                            {{ $unreadNotifCount }} baru
                                        </span>
                                    </div>

                                    @if ($unreadNotifCount > 0)
                                        <form method="POST" action="{{ route('notifications.markAllAsRead') }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 whitespace-nowrap"
                                            >
                                                Tandai semua dibaca
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                {{-- Isi 5 Notifikasi Terbaru --}}
                                <div class="max-h-72 overflow-y-auto divide-y divide-slate-50">
                                    @forelse ($recentNotifications as $notif)
                                        @php
                                            $notifData = $notif->data ?? [];
                                            $isNew = is_null($notif->read_at);
                                        @endphp

                                        <div class="p-3.5 hover:bg-slate-50 transition flex items-start gap-3 {{ $isNew ? 'bg-indigo-50/30' : '' }}">
                                            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 {{ ($notifData['type'] ?? '') === 'grade_submitted' ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700' }}">
                                                <span class="material-symbols-outlined text-[17px]">
                                                    {{ ($notifData['type'] ?? '') === 'grade_submitted' ? 'verified' : 'assignment' }}
                                                </span>
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-bold text-slate-800 truncate">
                                                    {{ $notifData['title'] ?? 'Pemberitahuan Baru' }}
                                                </p>

                                                <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                                                    {{ $notifData['message'] ?? 'Ada aktivitas terbaru di kelas Anda.' }}
                                                </p>

                                                <span class="text-[10px] text-slate-400 mt-1 block">
                                                    {{ $notif->created_at->diffForHumans() }}
                                                </span>
                                            </div>

                                            @if ($isNew)
                                                <form
                                                    method="POST"
                                                    action="{{ route('notifications.markAsRead', $notif->id) }}"
                                                    class="shrink-0"
                                                >
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="p-1 text-slate-300 hover:text-indigo-600"
                                                        title="Tandai dibaca"
                                                    >
                                                        <span class="material-symbols-outlined text-[16px]">done</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="p-6 text-center text-xs text-slate-400">
                                            Tidak ada notifikasi terbaru.
                                        </div>
                                    @endforelse
                                </div>

                                {{-- Footer Tautan ke Semua Notifikasi --}}
                                <div class="p-3 bg-slate-50 text-center">
                                    <a
                                        href="{{ route('notifications.index') }}"
                                        class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center justify-center gap-1"
                                    >
                                        <span>Lihat Seluruh Notifikasi</span>
                                        <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                    </a>
                                </div>

                            </div>
                        </div>
                    @endauth

                    {{-- Divider Antara Notifikasi & Profil --}}
                    @auth
                        <div class="hidden sm:block h-6 w-px bg-slate-800 shrink-0"></div>

                        {{-- Info Profil --}}
                        <div class="hidden sm:flex items-center gap-2 xl:gap-2.5 shrink-0">
                            <div class="w-8 h-8 xl:w-8.5 xl:h-8.5 rounded-xl {{ $avatarBg }} text-white
                                        flex items-center justify-center font-bold text-xs xl:text-sm shrink-0 shadow-sm ring-1 ring-white/10">
                                {{ $initial }}
                            </div>

                            <div class="text-left min-w-0">
                                <p class="text-xs font-semibold text-white leading-tight truncate hidden md:block max-w-[85px] lg:max-w-[105px] xl:max-w-[135px] 2xl:max-w-[170px] whitespace-nowrap" title="{{ $headerUser?->name ?? 'Pengguna' }}">
                                    {{ $headerUser?->name ?? 'Pengguna' }}
                                </p>

                                <span class="hidden md:inline-block text-[10px] font-medium text-slate-400 capitalize leading-none mt-0.5">
                                    {{ $demoRole }}
                                </span>
                            </div>
                        </div>

                        {{-- Tombol Logout --}}
                        <form method="POST" action="{{ route('logout') }}" class="inline-flex shrink-0">
                            @csrf
                            <button
                                type="submit"
                                title="Keluar dari Akun"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 xl:px-3 xl:py-1.5 2xl:px-3.5 2xl:py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 hover:text-white border border-rose-500/25 hover:border-rose-500/50 transition-all duration-150 text-xs font-semibold shadow-sm whitespace-nowrap"
                            >
                                <span class="material-symbols-outlined text-[17px]">logout</span>
                                <span class="hidden sm:inline">Keluar</span>
                            </button>
                        </form>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 xl:px-4 xl:py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow transition whitespace-nowrap"
                        >
                            <span class="material-symbols-outlined text-[16px]">login</span>
                            Masuk
                        </a>
                    @endauth

                    {{-- Hamburger Mobile --}}
                    <button
                        id="nav-toggle"
                        class="lg:hidden flex items-center justify-center w-9 h-9 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition"
                        aria-label="Toggle navigation"
                    >
                        <span class="material-symbols-outlined text-[22px]">menu</span>
                    </button>

                </div>
            </div>

            {{-- Mobile menu drawer --}}
            <nav id="mobile-nav" class="hidden lg:hidden pb-4 border-t border-slate-800 pt-3 space-y-1">

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
                        {{ $demoRole === 'mahasiswa' ? 'Tugas Saya' : 'Penilaian Pengumpulan' }}
                    </a>

                    <a href="{{ route('grades.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition
                              {{ request()->routeIs('grades.*')
                                  ? 'bg-indigo-600 text-white'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px]">workspace_premium</span>
                        Rekap Nilai
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

    {{-- Script Toggle Mobile, Dropdown Notifikasi & Realtime Auto-Polling --}}
    <script>
        // Toggle Mobile Navigation
        document.getElementById('nav-toggle')?.addEventListener('click', function () {
            const nav = document.getElementById('mobile-nav');
            nav.classList.toggle('hidden');
        });

        // Toggle Dropdown Lonceng Notifikasi
        const bellBtn = document.getElementById('notif-bell-button');
        const notifMenu = document.getElementById('notif-dropdown-menu');

        if (bellBtn && notifMenu) {
            bellBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                notifMenu.classList.toggle('hidden');
            });

            // Tutup dropdown jika klik di luar
            document.addEventListener('click', function (e) {
                if (!notifMenu.contains(e.target) && !bellBtn.contains(e.target)) {
                    notifMenu.classList.add('hidden');
                }
            });
        }

        @auth
        // Realtime Auto-Polling Notifikasi setiap 30 detik
        setInterval(function () {
            fetch('{{ route('notifications.unreadCount') }}')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('navbar-notif-badge');

                    if (badge) {
                        if (data.unread_count > 0) {
                            badge.textContent = data.unread_count > 99
                                ? '99+'
                                : data.unread_count;

                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }

                    const dropdownCount = document.getElementById('dropdown-unread-count');

                    if (dropdownCount) {
                        dropdownCount.textContent = data.unread_count + ' baru';
                    }
                })
                .catch(err => console.debug('Polling notifikasi error:', err));
        }, 30000);
        @endauth
    </script>

</body>
</html>
