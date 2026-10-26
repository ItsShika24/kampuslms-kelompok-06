<x-layout title="Pengguna">

    <div class="space-y-6">

        {{-- Header Halaman --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <nav class="flex items-center gap-1.5 text-xs text-slate-400 font-medium mb-1.5">
                    <span>Administrasi</span>
                    <span>/</span>
                    <span class="text-indigo-600 font-semibold">Pengguna</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                    Manajemen Akun Pengguna
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Kelola data administrator, dosen pengajar, dan mahasiswa aktif dalam sistem EduKampus.
                </p>
            </div>

            @if (session('demo_role') === 'admin' || auth()->user()?->role === 'admin')
                <a href="{{ route('pengguna.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm shrink-0 self-start md:self-auto">
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    Tambah Pengguna
                </a>
            @endif
        </div>

        {{-- Ringkasan Jumlah Pengguna --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Pengguna</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $users->total() }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Semua entitas terdaftar</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">manage_accounts</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Dosen (Halaman Ini)</p>
                    <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ $users->where('role', 'dosen')->count() }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Tenaga pengajar</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">person_pin</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Mahasiswa (Halaman Ini)</p>
                    <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $users->where('role', 'mahasiswa')->count() }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Peserta aktif</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">groups</span>
                </div>
            </div>
        </div>

        {{-- Filter & Pencarian Pengguna --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 sm:p-5">
            <form method="GET" action="{{ route('pengguna.index') }}" class="flex flex-col md:flex-row items-center gap-3">
                <div class="flex-1 w-full relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <span class="material-symbols-outlined text-[20px]">search</span>
                    </span>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari berdasarkan nama, email, atau NIM/NIP..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 shadow-2xs"
                    >
                </div>

                <div class="w-full md:w-48">
                    <select
                        name="role"
                        class="w-full py-2.5 px-3 rounded-xl border border-slate-300 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 shadow-2xs"
                    >
                        <option value="">Semua Role</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="dosen" {{ request('role') === 'dosen' ? 'selected' : '' }}>Dosen</option>
                        <option value="mahasiswa" {{ request('role') === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition w-full md:w-auto"
                    >
                        <span class="material-symbols-outlined text-[17px]">filter_alt</span>
                        Terapkan Filter
                    </button>

                    @if (request()->hasAny(['q', 'role']) && (request('q') || request('role')))
                        <a
                            href="{{ route('pengguna.index') }}"
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 text-xs font-semibold hover:bg-slate-50 transition w-full md:w-auto"
                            title="Reset filter"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Tabel Data Pengguna --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-white">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        Daftar Pengguna
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Menampilkan seluruh akun yang terdaftar dalam sistem (15 per halaman).
                    </p>
                </div>

                @if (request('q') || request('role'))
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 self-start sm:self-auto">
                        <span class="material-symbols-outlined text-[14px]">tune</span>
                        Filter Aktif
                    </span>
                @endif
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
                        @forelse ($users as $user)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    {{ $user->name }}
                                </td>
                                <td class="px-6 py-4 text-slate-600 text-xs font-mono">
                                    {{ $user->email }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 text-xs font-mono">
                                    {{ $user->nim_nip ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $roleBadges = [
                                            'admin'     => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'dosen'     => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'mahasiswa' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        ];
                                    @endphp
                                    <span class="inline-flex px-2.5 py-1 rounded-full border text-xs font-bold {{ $roleBadges[$user->role] ?? 'bg-slate-50 text-slate-700' }}">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-1.5">
                                    <a href="{{ route('pengguna.edit', $user->id) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-amber-50 text-amber-700 font-semibold text-xs hover:bg-amber-100 transition border border-amber-200">
                                        <span class="material-symbols-outlined text-[14px]">edit</span>
                                        Edit
                                    </a>

                                    <form action="{{ route('pengguna.destroy', $user->id) }}"
                                          method="POST"
                                          class="inline"
                                          onsubmit="return confirm('Apakah kamu yakin ingin menghapus akun pengguna ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-50 text-rose-700 font-semibold text-xs hover:bg-rose-100 transition border border-rose-200">
                                            <span class="material-symbols-outlined text-[14px]">delete</span>
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <span class="material-symbols-outlined text-4xl text-slate-300">person_off</span>
                                        <p class="font-bold text-slate-700 text-sm">Tidak ada pengguna yang cocok</p>
                                        <p class="text-xs text-slate-400">Coba ubah kata kunci atau reset filter pencarian.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($users->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

    </div>

</x-layout>