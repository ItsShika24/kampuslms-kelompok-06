<x-layout title="Dashboard">

    <div class="space-y-8">

        {{-- ============================================================
             BANNER SELAMAT DATANG (Hero Section)
             ============================================================ --}}
        <section class="relative rounded-3xl overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-indigo-900 border border-indigo-800/40 p-6 sm:p-9 text-white shadow-lg">

            {{-- Dekorasi ambient lighting di background --}}
            <div class="absolute -top-16 -right-16 w-80 h-80 rounded-full bg-indigo-500/15 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-16 left-1/3 w-64 h-64 rounded-full bg-purple-500/10 blur-2xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">

                <div class="max-w-2xl space-y-3">
                    {{-- Badge role dengan warna fungsional --}}
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold tracking-wide
                        @if ($role === 'admin') bg-rose-500/20 text-rose-300 border border-rose-500/30
                        @elseif ($role === 'dosen') bg-amber-500/20 text-amber-300 border border-amber-500/30
                        @else bg-emerald-500/20 text-emerald-300 border border-emerald-500/30
                        @endif">
                        <span class="material-symbols-outlined text-[16px]">
                            @if ($role === 'admin') admin_panel_settings
                            @elseif ($role === 'dosen') school
                            @else person
                            @endif
                        </span>
                        <span>
                            @if ($role === 'admin') Administrator LMS
                            @elseif ($role === 'dosen') Dosen Pengampu
                            @else Mahasiswa Aktif
                            @endif
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-white leading-tight">
                        Selamat Datang, {{ $activeUser?->name ?? 'Pengguna' }}!
                    </h1>

                    <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                        @if ($role === 'admin')
                            Kelola seluruh ekosistem akademik: data pengguna, kelas mata kuliah, enrollment mahasiswa, materi pembelajaran, dan rekapitulasi penilaian.
                        @elseif ($role === 'dosen')
                            Kelola mata kuliah yang Anda ampu, publikasikan materi perkuliahan, buat tugas baru, dan periksa berkas pengumpulan mahasiswa.
                        @else
                            Akses materi perkuliahan, pantau tenggat waktu tugas, kirim berkas pengumpulan tugas, dan pantau hasil penilaian dosen pengampu.
                        @endif
                    </p>
                </div>

                {{-- Quick Actions --}}
                <div class="flex flex-wrap sm:flex-nowrap md:flex-col gap-2.5 shrink-0">
                    <a href="{{ route('mata-kuliah.index') }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-sm transition hover:shadow-indigo-500/25">
                        <span class="material-symbols-outlined text-[18px]">menu_book</span>
                        Jelajahi Mata Kuliah
                    </a>
                    <a href="{{ route('submissions.index') }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold border border-white/15 transition backdrop-blur-sm">
                        <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
                        {{ $role === 'mahasiswa' ? 'Tugas & Nilai' : 'Penilaian Tugas' }}
                    </a>
                </div>

            </div>
        </section>


        {{-- ============================================================
             KARTU STATISTIK (Harmonis & Berstandar LMS)
             ============================================================ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 @if($role==='admin') lg:grid-cols-5 @else lg:grid-cols-4 @endif gap-4">

            {{-- Stat 1: Mata Kuliah --}}
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/80 hover:shadow-md hover:border-slate-300 transition-all duration-200 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mata Kuliah</span>
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-xs">
                        <span class="material-symbols-outlined text-[20px]">menu_book</span>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $stats['mata_kuliah'] }}</p>
                    <p class="text-xs text-slate-500 mt-1 font-medium">
                        @if($role==='dosen') yang Anda ampu
                        @elseif($role==='mahasiswa') yang Anda ikuti
                        @else total di sistem
                        @endif
                    </p>
                </div>
            </div>

            {{-- Stat 2: Semester --}}
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/80 hover:shadow-md hover:border-slate-300 transition-all duration-200 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Semester</span>
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shadow-xs">
                        <span class="material-symbols-outlined text-[20px]">school</span>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $stats['semester'] }}</p>
                    <p class="text-xs text-slate-500 mt-1 font-medium">Semester Genap Aktif</p>
                </div>
            </div>

            {{-- Stat 3: Total SKS --}}
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/80 hover:shadow-md hover:border-slate-300 transition-all duration-200 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total SKS</span>
                    <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shadow-xs">
                        <span class="material-symbols-outlined text-[20px]">fact_check</span>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $stats['total_sks'] }}</p>
                    <p class="text-xs text-slate-500 mt-1 font-medium">
                        @if($role==='mahasiswa') SKS yang diambil @else SKS kurikulum aktif @endif
                    </p>
                </div>
            </div>

            {{-- Stat 4 & 5: Disesuaikan per Peran --}}
            @if ($role === 'admin')
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/80 hover:shadow-md hover:border-slate-300 transition-all duration-200 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mahasiswa</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs">
                            <span class="material-symbols-outlined text-[20px]">groups</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $stats['total_mahasiswa'] }}</p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Mahasiswa terdaftar</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/80 hover:shadow-md hover:border-slate-300 transition-all duration-200 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Dosen</span>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shadow-xs">
                            <span class="material-symbols-outlined text-[20px]">person_pin</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $stats['total_dosen'] }}</p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Tenaga pengajar aktif</p>
                    </div>
                </div>

            @elseif ($role === 'dosen')
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/80 hover:shadow-md hover:border-slate-300 transition-all duration-200 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mahasiswa Bimbingan</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs">
                            <span class="material-symbols-outlined text-[20px]">groups</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $stats['total_mahasiswa'] }}</p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Enrolled pada kelas Anda</p>
                    </div>
                </div>

            @else
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/80 hover:shadow-md hover:border-slate-300 transition-all duration-200 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tugas Aktif</span>
                        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shadow-xs">
                            <span class="material-symbols-outlined text-[20px]">task_alt</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $stats['tugas_aktif'] }}</p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Perlu Anda kerjakan</p>
                    </div>
                </div>
            @endif

        </div>


        {{-- ============================================================
             KONTEN UTAMA SESUAI PERAN
             ============================================================ --}}

        {{-- ==================== PERAN: ADMINISTRATOR ==================== --}}
        @if ($role === 'admin')

            {{-- Tabel Manajemen Pengguna --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-indigo-600">manage_accounts</span>
                            Manajemen Pengguna
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Kelola akun administrator, dosen pengajar, dan mahasiswa.</p>
                    </div>
                    <a href="{{ route('pengguna.create') }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-sm transition">
                        <span class="material-symbols-outlined text-[16px]">person_add</span>
                        Tambah Pengguna
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Nama</th>
                                <th class="px-6 py-3.5 text-left">Email</th>
                                <th class="px-6 py-3.5 text-left">NIM / NIP</th>
                                <th class="px-6 py-3.5 text-center">Peran</th>
                                <th class="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($allUsers as $user)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-800">
                                        {{ $user->name }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 text-xs font-mono">{{ $user->email }}</td>
                                    <td class="px-6 py-4 text-slate-500 text-xs font-medium">
                                        {{ $user->nim_nip ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $rolePill = match($user->role) {
                                                'admin' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                'dosen' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                default => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $rolePill }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-1.5">
                                        <a href="{{ route('pengguna.edit', $user->id) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-semibold transition">
                                            <span class="material-symbols-outlined text-[14px]">edit</span>
                                            Edit
                                        </a>
                                        <form action="{{ route('pengguna.destroy', $user->id) }}"
                                              method="POST" class="inline"
                                              onsubmit="return confirm('Hapus akun pengguna ini? Data terkait mungkin ikut terpengaruh.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold transition">
                                                <span class="material-symbols-outlined text-[14px]">delete</span>
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                                        Belum ada data pengguna yang terdaftar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tabel Manajemen Mata Kuliah (Admin) --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-indigo-600">library_books</span>
                            Manajemen Mata Kuliah
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Kelola kurikulum, alokasi dosen pengampu, dan status perkuliahan.</p>
                    </div>
                    <a href="{{ route('mata-kuliah.create') }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-sm transition">
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        Tambah Mata Kuliah
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Kode</th>
                                <th class="px-6 py-3.5 text-left">Nama Mata Kuliah</th>
                                <th class="px-6 py-3.5 text-center">SKS</th>
                                <th class="px-6 py-3.5 text-left">Dosen Pengampu</th>
                                <th class="px-6 py-3.5 text-center">Status</th>
                                <th class="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($courses as $course)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold text-xs">
                                            {{ $course->code }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-slate-800">
                                        {{ $course->name }}
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-slate-700">
                                        {{ $course->sks }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 text-xs font-medium">
                                        {{ $course->lecturer->name ?? 'Belum Ditugaskan' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $courseStatusBadge = match($course->status) {
                                                'active'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'archived' => 'bg-slate-100 text-slate-600 border-slate-200',
                                                default    => 'bg-amber-50 text-amber-700 border-amber-200',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $courseStatusBadge }}">
                                            {{ ucfirst($course->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-1.5">
                                        <a href="{{ route('mata-kuliah.show', $course->id) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-semibold transition">
                                            <span class="material-symbols-outlined text-[14px]">visibility</span>
                                            Detail
                                        </a>
                                        <a href="{{ route('mata-kuliah.edit', $course->id) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-semibold transition">
                                            <span class="material-symbols-outlined text-[14px]">edit</span>
                                            Edit
                                        </a>
                                        <form action="{{ route('mata-kuliah.destroy', $course->id) }}"
                                              method="POST" class="inline"
                                              onsubmit="return confirm('Hapus mata kuliah ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold transition">
                                                <span class="material-symbols-outlined text-[14px]">delete</span>
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                        Belum ada mata kuliah yang terdaftar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tabel Pengumpulan Tugas Mahasiswa Terbaru (Admin) --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200/80 flex items-center justify-between bg-white">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-indigo-600">assignment_turned_in</span>
                            Pengumpulan Mahasiswa Terbaru
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Pantauan seluruh pengumpulan tugas mahasiswa di sistem EduKampus.</p>
                    </div>
                    <a href="{{ route('submissions.index') }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                        Lihat Semua
                        <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Mahasiswa</th>
                                <th class="px-6 py-3.5 text-left">Tugas &amp; Mata Kuliah</th>
                                <th class="px-6 py-3.5 text-left">Waktu Kumpul</th>
                                <th class="px-6 py-3.5 text-center">Status Nilai</th>
                                <th class="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($recentSubmissions as $sub)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-800">{{ $sub->student->name ?? 'Mahasiswa' }}</p>
                                        <p class="text-xs text-slate-400 font-medium">{{ $sub->student->nim_nip ?? '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-800">{{ $sub->assignment->title ?? 'Tugas' }}</p>
                                        <p class="text-xs text-slate-500">{{ $sub->assignment->course->name ?? '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-600 font-medium">
                                        {{ $sub->submitted_at ? $sub->submitted_at->translatedFormat('d M Y, H:i') : '-' }} WIB
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($sub->grade)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ $sub->grade->score }} / {{ $sub->assignment->max_score }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                Belum Dinilai
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('submissions.show', $sub->id) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition shadow-sm bg-slate-100 hover:bg-slate-200 text-slate-700">
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            Lihat
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                        Belum ada pengumpulan tugas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


        {{-- ==================== PERAN: DOSEN ==================== --}}
        @elseif ($role === 'dosen')

            {{-- Tabel Mata Kuliah yang Diampu --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-indigo-600">school</span>
                            Mata Kuliah yang Anda Ampu
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Kelola enrollment mahasiswa, unggah materi perkuliahan, dan buat tugas.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Kode</th>
                                <th class="px-6 py-3.5 text-left">Nama Mata Kuliah</th>
                                <th class="px-6 py-3.5 text-center">SKS</th>
                                <th class="px-6 py-3.5 text-center">Mahasiswa</th>
                                <th class="px-6 py-3.5 text-center">Status</th>
                                <th class="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($courses as $course)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold text-xs">
                                            {{ $course->code }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-slate-800">
                                        {{ $course->name }}
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-slate-700">
                                        {{ $course->sks }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                            <span class="material-symbols-outlined text-[15px] text-emerald-600">group</span>
                                            {{ $course->students->count() }} orang
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $courseStatusBadge = match($course->status) {
                                                'active'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'archived' => 'bg-slate-100 text-slate-600 border-slate-200',
                                                default    => 'bg-amber-50 text-amber-700 border-amber-200',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $courseStatusBadge }}">
                                            {{ ucfirst($course->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-1.5">
                                        <a href="{{ route('mata-kuliah.show', $course->id) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition shadow-sm"
                                           title="Buka Kelas">
                                            <span class="material-symbols-outlined text-[14px]">visibility</span>
                                            Kelola Kelas
                                        </a>
                                        <a href="{{ route('mata-kuliah.edit', $course->id) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-semibold transition"
                                           title="Edit Mata Kuliah">
                                            <span class="material-symbols-outlined text-[14px]">edit</span>
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                        Anda belum mengampu mata kuliah apa pun.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tabel Pengumpulan Tugas Mahasiswa Terbaru (Dosen) --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200/80 flex items-center justify-between bg-white">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-indigo-600">assignment_turned_in</span>
                            Pengumpulan Mahasiswa Perlu Dinilai
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Berkas tugas yang dikumpulkan mahasiswa pada mata kuliah yang Anda ampu.</p>
                    </div>
                    <a href="{{ route('submissions.index') }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                        Semua Pengumpulan
                        <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Mahasiswa</th>
                                <th class="px-6 py-3.5 text-left">Tugas &amp; Mata Kuliah</th>
                                <th class="px-6 py-3.5 text-left">Waktu Kumpul</th>
                                <th class="px-6 py-3.5 text-center">Status Nilai</th>
                                <th class="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($recentSubmissions as $sub)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-800">{{ $sub->student->name ?? 'Mahasiswa' }}</p>
                                        <p class="text-xs text-slate-400 font-medium">{{ $sub->student->nim_nip ?? '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-800">{{ $sub->assignment->title ?? 'Tugas' }}</p>
                                        <p class="text-xs text-slate-500">{{ $sub->assignment->course->name ?? '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-600 font-medium">
                                        {{ $sub->submitted_at ? $sub->submitted_at->translatedFormat('d M Y, H:i') : '-' }} WIB
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($sub->grade)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ $sub->grade->score }} / {{ $sub->assignment->max_score }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                Belum Dinilai
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('submissions.show', $sub->id) }}"
                                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold transition shadow-sm
                                                  {{ $sub->grade ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-indigo-600 hover:bg-indigo-700 text-white' }}">
                                            <span class="material-symbols-outlined text-[15px]">rate_review</span>
                                            {{ $sub->grade ? 'Lihat / Edit' : 'Buka & Nilai' }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                        Belum ada pengumpulan tugas dari mahasiswa.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


        {{-- ==================== PERAN: MAHASISWA ==================== --}}
        @else

            {{-- Tabel Mata Kuliah yang Diikuti (Mahasiswa) --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-indigo-600">menu_book</span>
                            Mata Kuliah yang Anda Ikuti
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Akses materi kuliah terbaru dan periksa tugas yang perlu dikumpulkan.</p>
                    </div>
                    <a href="{{ route('mata-kuliah.index') }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                        Katalog Mata Kuliah
                        <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Kode</th>
                                <th class="px-6 py-3.5 text-left">Nama Mata Kuliah</th>
                                <th class="px-6 py-3.5 text-center">SKS</th>
                                <th class="px-6 py-3.5 text-left">Dosen Pengampu</th>
                                <th class="px-6 py-3.5 text-center">Tugas Aktif</th>
                                <th class="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($courses as $course)
                                @php
                                    $activeTasks = $course->assignments()->where('status', 'active')->count();
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold text-xs">
                                            {{ $course->code }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-slate-800">
                                        {{ $course->name }}
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-slate-700">
                                        {{ $course->sks }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 text-xs font-medium">
                                        {{ $course->lecturer->name ?? 'Belum Ditentukan' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($activeTasks > 0)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                <span class="material-symbols-outlined text-[14px]">notifications_active</span>
                                                {{ $activeTasks }} tugas
                                            </span>
                                        @else
                                            <span class="text-slate-400 text-xs font-medium">— Tidak ada tugas —</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('mata-kuliah.show', $course->id) }}"
                                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-sm transition">
                                            <span class="material-symbols-outlined text-[15px]">menu_book</span>
                                            Buka Kelas
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                        Anda belum terdaftar di mata kuliah apa pun.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Riwayat Pengumpulan & Nilai Tugas (Mahasiswa) --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200/80 flex items-center justify-between bg-white">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-indigo-600">assignment_turned_in</span>
                            Riwayat Pengumpulan &amp; Nilai Saya
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Pantau status pengerjaan, nilai perolehan, dan umpan balik dari dosen.</p>
                    </div>
                    <a href="{{ route('submissions.index') }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                        Semua Nilai
                        <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Tugas &amp; Mata Kuliah</th>
                                <th class="px-6 py-3.5 text-left">Waktu Kumpul</th>
                                <th class="px-6 py-3.5 text-center">Ketepatan</th>
                                <th class="px-6 py-3.5 text-center">Nilai</th>
                                <th class="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($recentSubmissions as $sub)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-900">{{ $sub->assignment->title ?? 'Tugas' }}</p>
                                        <p class="text-xs text-slate-500 font-medium">{{ $sub->assignment->course->name ?? '-' }} ({{ $sub->assignment->course->code ?? '-' }})</p>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-600 font-medium">
                                        {{ $sub->submitted_at ? $sub->submitted_at->translatedFormat('d M Y, H:i') : '-' }} WIB
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($sub->is_late)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Terlambat
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Tepat Waktu
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($sub->grade)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ $sub->grade->score }} / {{ $sub->assignment->max_score }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">
                                                Menunggu Penilaian
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('submissions.show', $sub->id) }}"
                                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-semibold text-xs transition">
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            Detail Jawaban
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                        Anda belum mengumpulkan tugas apa pun.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        @endif

    </div>

</x-layout>