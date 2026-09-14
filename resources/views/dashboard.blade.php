<x-layout title="Dashboard">

    <div class="w-full px-6 lg:px-8 py-6">
        <div class="flex flex-col gap-6">

            {{-- ============================================================
                 BANNER SELAMAT DATANG
                 ============================================================ --}}
            <section
                class="relative rounded-2xl overflow-hidden
                       bg-gradient-to-br from-[#4648d4] via-[#6063ee] to-[#6b38d4]
                       p-8 text-white shadow-lg"
            >
                {{-- Dekorasi lingkaran besar di pojok kanan atas --}}
                <div class="absolute -top-10 -right-10 w-64 h-64 rounded-full
                            bg-white/5 pointer-events-none"></div>
                <div class="absolute bottom-0 right-20 w-40 h-40 rounded-full
                            bg-white/5 pointer-events-none"></div>

                <div class="relative z-10">

                    {{-- Badge role --}}
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full
                                 bg-white/15 text-[11px] uppercase tracking-wider">
                        <span class="material-symbols-outlined text-[16px]">
                            @if ($role === 'admin') admin_panel_settings
                            @elseif ($role === 'dosen') school
                            @else person
                            @endif
                        </span>
                        @if ($role === 'admin') Administrator
                        @elseif ($role === 'dosen') Dosen
                        @else Mahasiswa
                        @endif
                    </span>

                    <h1 class="text-3xl font-bold tracking-tight mt-4">
                        Selamat Datang, {{ $activeUser?->name ?? 'Pengguna' }}!
                    </h1>

                    <p class="text-[#e1e0ff] mt-2 max-w-2xl">
                        @if ($role === 'admin')
                            Kelola seluruh data akademik: pengguna, mata kuliah, enrollment, materi, dan tugas.
                        @elseif ($role === 'dosen')
                            Lihat dan kelola mata kuliah yang Anda ampu, materi, tugas, dan penilaian mahasiswa.
                        @else
                            Lihat mata kuliah yang Anda ikuti, kumpulkan tugas, dan pantau nilai Anda.
                        @endif
                    </p>

                    {{-- Switcher role (hanya untuk demo) --}}
                    <div class="flex flex-wrap items-center gap-2 mt-5">
                        <span class="text-white/60 text-xs">Simulasi Role:</span>
                        @foreach (['admin', 'dosen', 'mahasiswa'] as $r)
                            <a href="{{ route('set-role', $r) }}"
                               class="px-3 py-1 rounded-full text-xs font-semibold transition
                                      {{ $role === $r
                                          ? 'bg-white text-indigo-700'
                                          : 'bg-white/20 text-white hover:bg-white/30' }}">
                                {{ ucfirst($r) }}
                            </a>
                        @endforeach
                    </div>

                </div>
            </section>


            {{-- ============================================================
                 KARTU STATISTIK (data dari database)
                 ============================================================ --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 @if($role==='admin') lg:grid-cols-5 @else lg:grid-cols-4 @endif gap-4">

                {{-- Mata Kuliah --}}
                <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100
                            flex flex-col gap-1">
                    <span class="material-symbols-outlined text-[#4648d4]">menu_book</span>
                    <p class="text-sm text-[#464554] mt-2">Mata Kuliah</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $stats['mata_kuliah'] }}</p>
                    <p class="text-xs text-slate-400">
                        @if($role==='dosen') yang Anda ampu
                        @elseif($role==='mahasiswa') yang Anda ikuti
                        @else total di sistem
                        @endif
                    </p>
                </div>

                {{-- Semester --}}
                <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100
                            flex flex-col gap-1">
                    <span class="material-symbols-outlined text-[#6b38d4]">school</span>
                    <p class="text-sm text-[#464554] mt-2">Semester</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $stats['semester'] }}</p>
                    <p class="text-xs text-slate-400">semester aktif</p>
                </div>

                {{-- Total SKS --}}
                <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100
                            flex flex-col gap-1">
                    <span class="material-symbols-outlined text-[#00628d]">assignment</span>
                    <p class="text-sm text-[#464554] mt-2">Total SKS</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $stats['total_sks'] }}</p>
                    <p class="text-xs text-slate-400">
                        @if($role==='mahasiswa') SKS yang diambil @else SKS keseluruhan @endif
                    </p>
                </div>

                {{-- Stat ke-4: beda per role --}}
                @if ($role === 'admin')
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100
                                flex flex-col gap-1">
                        <span class="material-symbols-outlined text-emerald-600">groups</span>
                        <p class="text-sm text-[#464554] mt-2">Mahasiswa</p>
                        <p class="text-2xl font-bold text-slate-900">{{ $stats['total_mahasiswa'] }}</p>
                        <p class="text-xs text-slate-400">terdaftar</p>
                    </div>

                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100
                                flex flex-col gap-1">
                        <span class="material-symbols-outlined text-amber-600">person_pin</span>
                        <p class="text-sm text-[#464554] mt-2">Dosen</p>
                        <p class="text-2xl font-bold text-slate-900">{{ $stats['total_dosen'] }}</p>
                        <p class="text-xs text-slate-400">pengajar aktif</p>
                    </div>

                @elseif ($role === 'dosen')
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100
                                flex flex-col gap-1">
                        <span class="material-symbols-outlined text-emerald-600">groups</span>
                        <p class="text-sm text-[#464554] mt-2">Total Mahasiswa</p>
                        <p class="text-2xl font-bold text-slate-900">{{ $stats['total_mahasiswa'] }}</p>
                        <p class="text-xs text-slate-400">di semua MK Anda</p>
                    </div>

                @else
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100
                                flex flex-col gap-1">
                        <span class="material-symbols-outlined text-rose-500">task_alt</span>
                        <p class="text-sm text-[#464554] mt-2">Tugas Aktif</p>
                        <p class="text-2xl font-bold text-slate-900">{{ $stats['tugas_aktif'] }}</p>
                        <p class="text-xs text-slate-400">perlu dikerjakan</p>
                    </div>
                @endif

            </div>


            {{-- ============================================================
                 KONTEN UTAMA PER ROLE
                 ============================================================ --}}

            {{-- ==================== ADMIN ==================== --}}
            @if ($role === 'admin')

                {{-- Tabel Pengguna --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

                    <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Manajemen Pengguna</h2>
                            <p class="text-sm text-slate-500 mt-1">CRUD pengguna &amp; role.</p>
                        </div>
                        <a href="{{ route('pengguna.create') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg
                                  bg-indigo-600 text-white font-semibold text-sm
                                  hover:bg-indigo-700 transition">
                            <span class="material-symbols-outlined text-[18px]">person_add</span>
                            Tambah Pengguna
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-500">Nama</th>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-500">Email</th>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-500">NIM / NIP</th>
                                    <th class="px-6 py-4 text-center font-semibold text-slate-500">Role</th>
                                    <th class="px-6 py-4 text-center font-semibold text-slate-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($allUsers as $user)
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-6 py-4 font-semibold text-slate-800">
                                            {{ $user->name }}
                                        </td>
                                        <td class="px-6 py-4 text-slate-500">{{ $user->email }}</td>
                                        <td class="px-6 py-4 text-slate-500">
                                            {{ $user->nim_nip ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @php
                                                $roleColor = match($user->role) {
                                                    'admin'     => 'bg-rose-50 text-rose-700',
                                                    'dosen'     => 'bg-amber-50 text-amber-700',
                                                    default     => 'bg-indigo-50 text-indigo-700',
                                                };
                                            @endphp
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $roleColor }}">
                                                {{ ucfirst($user->role) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center space-x-2">
                                            <a href="{{ route('pengguna.edit', $user->id) }}"
                                               class="inline-flex items-center px-3 py-1.5 rounded-lg
                                                      bg-amber-500 text-white text-xs font-semibold
                                                      hover:bg-amber-600 transition">
                                                Edit
                                            </a>
                                            <form action="{{ route('pengguna.destroy', $user->id) }}"
                                                  method="POST" class="inline"
                                                  onsubmit="return confirm('Hapus pengguna ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center px-3 py-1.5 rounded-lg
                                                               bg-red-600 text-white text-xs font-semibold
                                                               hover:bg-red-700 transition">
                                                    Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                                            Belum ada pengguna.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tabel Mata Kuliah (Admin) --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

                    <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Manajemen Mata Kuliah</h2>
                            <p class="text-sm text-slate-500 mt-1">CRUD mata kuliah &amp; kelola enrollment.</p>
                        </div>
                        <a href="{{ route('mata-kuliah.create') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg
                                  bg-indigo-600 text-white font-semibold text-sm
                                  hover:bg-indigo-700 transition">
                            <span class="material-symbols-outlined text-[18px]">add</span>
                            Tambah MK
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-500">Kode</th>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-500">Nama Mata Kuliah</th>
                                    <th class="px-6 py-4 text-center font-semibold text-slate-500">SKS</th>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-500">Dosen</th>
                                    <th class="px-6 py-4 text-center font-semibold text-slate-500">Status</th>
                                    <th class="px-6 py-4 text-center font-semibold text-slate-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($courses as $course)
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-lg bg-indigo-50
                                                         text-indigo-700 font-semibold text-xs">
                                                {{ $course->code }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-slate-800">
                                            {{ $course->name }}
                                        </td>
                                        <td class="px-6 py-4 text-center font-semibold text-slate-700">
                                            {{ $course->sks }}
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            {{ $course->lecturer->name ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @php
                                                $statusColor = match($course->status) {
                                                    'active'   => 'bg-emerald-50 text-emerald-700',
                                                    'archived' => 'bg-slate-100 text-slate-500',
                                                    default    => 'bg-amber-50 text-amber-700',
                                                };
                                            @endphp
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusColor }}">
                                                {{ ucfirst($course->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center space-x-1">
                                            <a href="{{ route('mata-kuliah.show', $course->id) }}"
                                               class="inline-flex items-center px-3 py-1.5 rounded-lg
                                                      bg-indigo-600 text-white text-xs font-semibold
                                                      hover:bg-indigo-700 transition">
                                                Detail
                                            </a>
                                            <a href="{{ route('mata-kuliah.edit', $course->id) }}"
                                               class="inline-flex items-center px-3 py-1.5 rounded-lg
                                                      bg-amber-500 text-white text-xs font-semibold
                                                      hover:bg-amber-600 transition">
                                                Edit
                                            </a>
                                            <form action="{{ route('mata-kuliah.destroy', $course->id) }}"
                                                  method="POST" class="inline"
                                                  onsubmit="return confirm('Hapus mata kuliah ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center px-3 py-1.5 rounded-lg
                                                               bg-red-600 text-white text-xs font-semibold
                                                               hover:bg-red-700 transition">
                                                    Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                            Belum ada mata kuliah.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>


            {{-- ==================== DOSEN ==================== --}}
            @elseif ($role === 'dosen')

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

                    <div class="px-6 py-5 border-b border-slate-200">
                        <h2 class="text-lg font-bold text-slate-900">Mata Kuliah yang Anda Ampu</h2>
                        <p class="text-sm text-slate-500 mt-1">
                            Kelola enrollment, materi, tugas, dan penilaian mahasiswa.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-500">Kode</th>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-500">Nama Mata Kuliah</th>
                                    <th class="px-6 py-4 text-center font-semibold text-slate-500">SKS</th>
                                    <th class="px-6 py-4 text-center font-semibold text-slate-500">Mahasiswa</th>
                                    <th class="px-6 py-4 text-center font-semibold text-slate-500">Status</th>
                                    <th class="px-6 py-4 text-center font-semibold text-slate-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($courses as $course)
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-lg bg-indigo-50
                                                         text-indigo-700 font-semibold text-xs">
                                                {{ $course->code }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-slate-800">
                                            {{ $course->name }}
                                        </td>
                                        <td class="px-6 py-4 text-center font-semibold">
                                            {{ $course->sks }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center gap-1 text-slate-700">
                                                <span class="material-symbols-outlined text-[16px] text-emerald-600">
                                                    group
                                                </span>
                                                {{ $course->students->count() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @php
                                                $statusColor = match($course->status) {
                                                    'active'   => 'bg-emerald-50 text-emerald-700',
                                                    'archived' => 'bg-slate-100 text-slate-500',
                                                    default    => 'bg-amber-50 text-amber-700',
                                                };
                                            @endphp
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusColor }}">
                                                {{ ucfirst($course->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center flex-wrap gap-1">
                                                {{-- Detail MK --}}
                                                <a href="{{ route('mata-kuliah.show', $course->id) }}"
                                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg
                                                          bg-indigo-600 text-white text-xs font-semibold
                                                          hover:bg-indigo-700 transition"
                                                   title="Detail & Enrollment">
                                                    <span class="material-symbols-outlined text-[14px]">visibility</span>
                                                    Detail
                                                </a>
                                                {{-- Edit MK sendiri --}}
                                                <a href="{{ route('mata-kuliah.edit', $course->id) }}"
                                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg
                                                          bg-amber-500 text-white text-xs font-semibold
                                                          hover:bg-amber-600 transition"
                                                   title="Edit Mata Kuliah">
                                                    <span class="material-symbols-outlined text-[14px]">edit</span>
                                                    Edit
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                            Anda belum mengampu mata kuliah apapun.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>




            {{-- ==================== MAHASISWA ==================== --}}
            @else

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

                    <div class="px-6 py-5 border-b border-slate-200">
                        <h2 class="text-lg font-bold text-slate-900">Mata Kuliah yang Anda Ikuti</h2>
                        <p class="text-sm text-slate-500 mt-1">
                            Lihat materi, kumpulkan tugas, dan pantau nilai Anda.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-500">Kode</th>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-500">Nama Mata Kuliah</th>
                                    <th class="px-6 py-4 text-center font-semibold text-slate-500">SKS</th>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-500">Dosen Pengampu</th>
                                    <th class="px-6 py-4 text-center font-semibold text-slate-500">Tugas Aktif</th>
                                    <th class="px-6 py-4 text-center font-semibold text-slate-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($courses as $course)
                                    @php
                                        $activeTasks = $course->assignments()->where('status', 'active')->count();
                                    @endphp
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-lg bg-indigo-50
                                                         text-indigo-700 font-semibold text-xs">
                                                {{ $course->code }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-slate-800">
                                            {{ $course->name }}
                                        </td>
                                        <td class="px-6 py-4 text-center font-semibold text-slate-700">
                                            {{ $course->sks }}
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            {{ $course->lecturer->name ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @if ($activeTasks > 0)
                                                <span class="inline-flex items-center gap-1
                                                             px-2.5 py-1 rounded-full text-xs font-semibold
                                                             bg-rose-50 text-rose-700">
                                                    <span class="material-symbols-outlined text-[13px]">
                                                        notifications_active
                                                    </span>
                                                    {{ $activeTasks }} tugas
                                                </span>
                                            @else
                                                <span class="text-slate-400 text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center flex-wrap gap-1">
                                                {{-- Lihat detail (materi, tugas) --}}
                                                <a href="{{ route('mata-kuliah.show', $course->id) }}"
                                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg
                                                          bg-indigo-600 text-white text-xs font-semibold
                                                          hover:bg-indigo-700 transition">
                                                    <span class="material-symbols-outlined text-[14px]">
                                                        menu_book
                                                    </span>
                                                    Materi &amp; Tugas
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                            Anda belum terdaftar di mata kuliah apapun.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>



            @endif

        </div>
    </div>

</x-layout>