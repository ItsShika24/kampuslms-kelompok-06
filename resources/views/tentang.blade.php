<x-layout title="Tentang EduKampus LMS">

    <div class="space-y-8">

        {{-- Hero Header --}}
        <div class="relative rounded-3xl overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-indigo-900 border border-indigo-800/40 p-6 sm:p-10 text-white shadow-lg">
            <div class="absolute -top-12 -right-12 w-64 h-64 rounded-full bg-indigo-500/20 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 max-w-2xl space-y-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                    <span class="material-symbols-outlined text-[15px]">info</span>
                    Tentang Sistem &amp; Tim Pengembang
                </span>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
                    EduKampus LMS
                </h1>
                <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                    Learning Management System modern, aman, dan terintegrasi untuk mendukung aktivitas pembelajaran daring perguruan tinggi. Dikembangkan untuk memenuhi tugas mata kuliah Praktikum Pemrograman Web — Semester 5.
                </p>
            </div>
        </div>

        {{-- Tim Pengembang (Kelompok 06) --}}
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600">groups</span>
                        Tim Pengembang &bull; Kelompok 06
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Anggota pengembang proyek EduKampus LMS
                    </p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    4 Anggota
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Member 1: Raihandy Wijaya --}}
                <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 hover:border-indigo-300 hover:bg-indigo-50/20 transition-all flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-extrabold text-base shadow-sm shrink-0 group-hover:scale-105 transition-transform">
                        RW
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-slate-900 text-base truncate">Raihandy Wijaya</h3>
                    </div>
                </div>

                {{-- Member 2: Tika Mila Wahyuni --}}
                <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 hover:border-rose-300 hover:bg-rose-50/20 transition-all flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-2xl bg-rose-600 text-white flex items-center justify-center font-extrabold text-base shadow-sm shrink-0 group-hover:scale-105 transition-transform">
                        TW
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-slate-900 text-base truncate">Tika Mila Wahyuni</h3>
                    </div>
                </div>

                {{-- Member 3: Oktavia Nur Rahmadani --}}
                <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 hover:border-emerald-300 hover:bg-emerald-50/20 transition-all flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-extrabold text-base shadow-sm shrink-0 group-hover:scale-105 transition-transform">
                        OR
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-slate-900 text-base truncate">Oktavia Nur Rahmadani</h3>
                    </div>
                </div>

                {{-- Member 4: Shabrina Putri Az-Zahra --}}
                <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 hover:border-amber-300 hover:bg-amber-50/20 transition-all flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-2xl bg-amber-600 text-white flex items-center justify-center font-extrabold text-base shadow-sm shrink-0 group-hover:scale-105 transition-transform">
                        SA
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-slate-900 text-base truncate">Shabrina Putri Az-Zahra</h3>
                    </div>
                </div>

            </div>
        </div>
    </div>

</x-layout>