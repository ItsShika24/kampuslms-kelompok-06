<x-layout title="Pengguna">

    {{-- Header halaman memberikan informasi utama tentang daftar pengguna. --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

            <div>
                <p class="text-sm font-semibold text-indigo-600 mb-2">
                    ADMINISTRASI / PENGGUNA
                </p>

                <h2 class="text-3xl font-bold tracking-tight text-slate-900">
                    Manajemen Pengguna
                </h2>

                <p class="text-sm text-slate-500 mt-2">
                    Kelola data pengguna yang terdaftar dalam sistem.
                </p>
            </div>

            @if (session('demo_role') === 'admin')
                <a href="{{ route('pengguna.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl
                          bg-indigo-600 text-white font-semibold text-sm
                          hover:bg-indigo-700 transition shadow-sm shrink-0">
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    Tambah Pengguna
                </a>
            @endif

        </div>
    </div>

    {{-- Ringkasan jumlah pengguna berdasarkan data yang tersedia. --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Pengguna</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">
                {{ $users->total() }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Dosen (Halaman Ini)</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">
                {{ $users->where('role', 'dosen')->count() }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Mahasiswa (Halaman Ini)</p>
            <p class="text-2xl font-bold text-indigo-600 mt-1">
                {{ $users->where('role', 'mahasiswa')->count() }}
            </p>
        </div>

    </div>

    {{-- Filter & Pencarian Pengguna (State disimpan di Query String) --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6">
        <form method="GET" action="{{ route('pengguna.index') }}" class="flex flex-col md:flex-row items-center gap-4">
            {{-- Input kata kunci pencarian --}}
            <div class="flex-1 w-full">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <span class="material-symbols-outlined text-[20px]">search</span>
                    </span>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari berdasarkan nama, email, atau NIM/NIP..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>
            </div>

            {{-- Filter role --}}
            <div class="w-full md:w-48">
                <select
                    name="role"
                    class="w-full py-2.5 px-3 rounded-xl border border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">Semua Role</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="dosen" {{ request('role') === 'dosen' ? 'selected' : '' }}>Dosen</option>
                    <option value="mahasiswa" {{ request('role') === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                </select>
            </div>

            {{-- Tombol aksi filter --}}
            <div class="flex items-center gap-2 w-full md:w-auto">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition w-full md:w-auto shadow-sm"
                >
                    <span class="material-symbols-outlined text-[18px]">filter_alt</span>
                    Filter
                </button>

                @if (request()->hasAny(['q', 'role']) && (request('q') || request('role')))
                    <a
                        href="{{ route('pengguna.index') }}"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 text-sm font-medium hover:bg-slate-50 transition w-full md:w-auto"
                        title="Reset filter"
                    >
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabel data pengguna secara terstruktur --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="px-6 py-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-lg font-bold text-slate-900">
                    Daftar Pengguna
                </h3>
                <p class="text-sm text-slate-500 mt-1">
                    Data pengguna yang terdaftar pada sistem (15 per halaman).
                </p>
            </div>

            @if (request('q') || request('role'))
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 self-start sm:self-auto">
                    Filter aktif
                </span>
            @endif
        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold text-slate-500">
                            Nama
                        </th>

                        <th class="px-6 py-4 text-left font-semibold text-slate-500">
                            Email
                        </th>

                        <th class="px-6 py-4 text-left font-semibold text-slate-500">
                            NIM/NIP
                        </th>

                        <th class="px-6 py-4 text-center font-semibold text-slate-500">
                            Role
                        </th>

                        <th class="px-6 py-4 text-center font-semibold text-slate-500">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($users as $user)

                        <tr class="hover:bg-slate-50 transition">

                            <td class="px-6 py-5">
                                <p class="font-semibold text-slate-900">
                                    {{ $user->name }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <p class="text-slate-700">
                                    {{ $user->email }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <span class="font-mono text-xs text-slate-600">
                                    {{ $user->nim_nip ?? '-' }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                @php
                                    $roleBadges = [
                                        'admin'     => 'bg-rose-50 text-rose-700 border-rose-200',
                                        'dosen'     => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'mahasiswa' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    ];
                                @endphp
                                <span class="inline-flex px-2.5 py-1 rounded-lg border text-xs font-semibold {{ $roleBadges[$user->role] ?? 'bg-slate-50 text-slate-700' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="flex items-center justify-center gap-2">

                                    {{-- Tombol untuk membuka form edit pengguna --}}
                                    <a href="{{ route('pengguna.edit', $user->id) }}"
                                       class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 font-semibold text-xs hover:bg-amber-100 transition border border-amber-200">
                                        Edit
                                    </a>

                                    {{-- Form menghapus pengguna dengan method DELETE --}}
                                    <form action="{{ route('pengguna.destroy', $user->id) }}"
                                          method="POST"
                                          class="inline"
                                          onsubmit="return confirm('Apakah kamu yakin ingin menghapus pengguna ini?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-red-50 text-red-700 font-semibold text-xs hover:bg-red-100 transition border border-red-200">
                                            Hapus
                                        </button>
                                    </form>

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-slate-300">person_off</span>
                                    <p class="font-medium text-slate-600">Tidak ada pengguna yang cocok</p>
                                    <p class="text-xs text-slate-400">Coba ubah kata kunci atau reset filter pencarian.</p>
                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- Pagination dengan mempertahankan Query String --}}
        @if ($users->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $users->links() }}
            </div>
        @endif

    </div>

</x-layout>