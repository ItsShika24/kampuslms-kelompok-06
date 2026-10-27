<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduKampus LMS — Portal Pembelajaran Akademik Terpadu</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body class="min-h-screen bg-slate-950 font-['Inter'] text-slate-100 flex flex-col selection:bg-indigo-500 selection:text-white relative overflow-x-hidden">

    {{-- Latar Belakang Efek Glow & Grid Ambient --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-40 -right-40 w-[600px] h-[600px] bg-indigo-600/15 rounded-full blur-[140px]"></div>
        <div class="absolute top-1/2 -left-40 w-[500px] h-[500px] bg-purple-600/10 rounded-full blur-[130px]"></div>
        <div class="absolute -bottom-40 right-1/4 w-[600px] h-[600px] bg-sky-600/10 rounded-full blur-[140px]"></div>
    </div>

    {{-- Header / Navbar Publik --}}
    <header class="sticky top-0 z-50 bg-slate-900/80 backdrop-blur-md border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-sky-400 flex items-center justify-center text-white font-extrabold text-lg shadow-md group-hover:scale-105 transition-transform duration-200">
                    E
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-white font-bold text-base tracking-tight">EduKampus</span>
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-indigo-500/30 text-indigo-300 border border-indigo-400/30">LMS</span>
                    </div>
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider font-medium leading-none mt-0.5">Portal Akademik</p>
                </div>
            </a>

            <nav class="flex items-center gap-3">
                <a href="{{ route('tentang') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/80 transition">
                    Tentang Tim
                </a>

                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">dashboard</span>
                        Buka Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">login</span>
                        Masuk ke Akun
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20 relative z-10 space-y-16">

        {{-- Hero Section --}}
        <div class="text-center max-w-3xl mx-auto space-y-6">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>EduKampus LMS &bull; Semester 2024/2025 Genap</span>
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-tight">
                Platform Pembelajaran &amp; Manajemen Akademik
                <span class="bg-gradient-to-r from-indigo-400 via-sky-300 to-indigo-200 bg-clip-text text-transparent">Terpadu</span>
            </h1>

            <p class="text-slate-400 text-sm sm:text-base leading-relaxed max-w-2xl mx-auto">
                Solusi modern manajemen perkuliahan daring dengan sistem otorisasi multi-peran, pengumpulan tugas aman dengan private storage, serta arsitektur event-listener dan notifikasi realtime.
            </p>

            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/30 transition hover:scale-105 duration-200">
                        <span class="material-symbols-outlined text-lg">dashboard</span>
                        Menuju Dashboard Utama
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/30 transition hover:scale-105 duration-200">
                        <span class="material-symbols-outlined text-lg">login</span>
                        Masuk ke Portal LMS
                    </a>
                @endauth

                <a href="{{ route('tentang') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white font-semibold text-sm border border-slate-800 transition">
                    <span class="material-symbols-outlined text-lg">info</span>
                    Tentang Kelompok 06
                </a>
            </div>
        </div>

        {{-- Grid Fitur Utama --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6">

            <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800/80 backdrop-blur-sm space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">menu_book</span>
                </div>
                <h3 class="font-bold text-lg text-white">Manajemen Mata Kuliah</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Eksplorasi katalog perkuliahan, alokasi dosen pengampu, distribusi SKS, publikasi materi modul, dan enrollment mahasiswa.
                </p>
            </div>

            <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800/80 backdrop-blur-sm space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-600/20 border border-emerald-500/30 text-emerald-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">security</span>
                </div>
                <h3 class="font-bold text-lg text-white">Pengumpulan Tugas Aman</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Pusat penugasan dengan validasi tenggat waktu, toleransi keterlambatan, penyimpanan file privat, dan proteksi IDOR berbasis Laravel Policy.
                </p>
            </div>

            <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800/80 backdrop-blur-sm space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">notifications_active</span>
                </div>
                <h3 class="font-bold text-lg text-white">Event &amp; Notifikasi Realtime</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Pemberitahuan tugas baru dan nilai masuk via background queue worker serta auto-polling realtime di lonceng navbar.
                </p>
            </div>

        </div>

        {{-- Akun Demo Cepat (Quick Access Guide) --}}
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6 sm:p-8 backdrop-blur-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-400">key</span>
                        Akses Akun Demo Tersedia
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Gunakan akun berikut pada halaman login untuk menguji berbagai peran.</p>
                </div>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition">
                    Buka Halaman Login
                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-1">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">ADMINISTRATOR</span>
                    <p class="font-bold text-white text-sm mt-1">admin@kampuslms.test</p>
                    <p class="text-slate-400 font-mono">Password: password</p>
                </div>

                <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-1">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">DOSEN</span>
                    <p class="font-bold text-white text-sm mt-1">dosen@kampuslms.test</p>
                    <p class="text-slate-400 font-mono">Password: password</p>
                </div>

                <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-1">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">MAHASISWA</span>
                    <p class="font-bold text-white text-sm mt-1">mahasiswa@kampuslms.test</p>
                    <p class="text-slate-400 font-mono">Password: password</p>
                </div>
            </div>
        </div>

    </main>

    {{-- Footer --}}
    <footer class="mt-auto border-t border-slate-800/80 bg-slate-900/50 py-6 text-xs text-slate-500 text-center relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p>&copy; 2026 EduKampus LMS &bull; Praktikum Pemrograman Web</p>
            <p>Kelompok 06 &bull; Raihandy Wijaya, Tika Mila Wahyuni, Oktavia Nur Rahmadani, Shabrina Putri Az-Zahra</p>
        </div>
    </footer>

</body>
</html>
