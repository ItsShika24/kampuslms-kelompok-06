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
                        Kolaborasi pengembangan arsitektur web modern, keamanan data, dan pengalaman pengguna interaktif.
                    </p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    4 Anggota Tim
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Member 1: Raihandy Wijaya --}}
                <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 hover:border-indigo-300 hover:shadow-sm transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-extrabold text-base shadow-sm shrink-0 group-hover:scale-105 transition-transform">
                            RW
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-900 text-base truncate">Raihandy Wijaya</h3>
                            <p class="text-xs text-indigo-600 font-semibold mt-0.5">Frontend &amp; UI/UX Specialist</p>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                Bertanggung jawab atas desain antarmuka, tata letak responsif, dan interaktivitas komponen antarmuka pengguna LMS.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Member 2: Tika Mila Wahyuni --}}
                <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 hover:border-indigo-300 hover:shadow-sm transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-rose-600 text-white flex items-center justify-center font-extrabold text-base shadow-sm shrink-0 group-hover:scale-105 transition-transform">
                            TW
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-900 text-base truncate">Tika Mila Wahyuni</h3>
                            <p class="text-xs text-rose-600 font-semibold mt-0.5">Backend Architecture &amp; Security</p>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                Bertanggung jawab atas sistem event-driven, queue worker, proteksi IDOR, policy authorization, dan notifikasi realtime.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Member 3: Oktavia Nur Rahmadani --}}
                <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 hover:border-indigo-300 hover:shadow-sm transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-extrabold text-base shadow-sm shrink-0 group-hover:scale-105 transition-transform">
                            OR
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-900 text-base truncate">Oktavia Nur Rahmadani</h3>
                            <p class="text-xs text-emerald-600 font-semibold mt-0.5">Database &amp; System Analyst</p>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                Bertanggung jawab atas rancangan relasi database, optimasi query Eloquent, data migration, dan alur proses bisnis akademik.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Member 4: Shabrina Putri Az-Zahra --}}
                <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 hover:border-indigo-300 hover:shadow-sm transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-600 text-white flex items-center justify-center font-extrabold text-base shadow-sm shrink-0 group-hover:scale-105 transition-transform">
                            SA
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-900 text-base truncate">Shabrina Putri Az-Zahra</h3>
                            <p class="text-xs text-amber-600 font-semibold mt-0.5">QA &amp; Technical Documentation</p>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                Bertanggung jawab atas pengujian fungsionalitas sistem, penulisan modul panduan teknis, dan verifikasi alur aplikasi.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Arsitektur & Teknologi --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">code</span>
                </div>
                <h3 class="font-bold text-slate-900">Teknologi Modern</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Dibangun di atas fondasi Laravel 12, PHP 8.4, Tailwind CSS v4, dan Alpine.js untuk memberikan kinerja tinggi, rendering cepat, dan kode yang terstruktur rapi.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">security</span>
                </div>
                <h3 class="font-bold text-slate-900">Keamanan Ketat</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Menerapkan otorisasi berbasis Laravel Policy, mitigasi IDOR pada seluruh endpoint data, proteksi berkas privat via routing terotentikasi, dan CSRF protection.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">bolt</span>
                </div>
                <h3 class="font-bold text-slate-900">Event &amp; Notifikasi Realtime</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Memanfaatkan arsitektur asynchronous event-listener yang diproses di background queue, dilengkapi auto-polling realtime untuk notifikasi tugas dan nilai mahasiswa.
                </p>
            </div>

        </div>

    </div>

</x-layout>