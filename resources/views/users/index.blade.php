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

        </div>
    </div>

    {{-- Ringkasan jumlah pengguna berdasarkan data yang tersedia. --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Pengguna</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">
                {{ $users->count() }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Dosen</p>
            <p class="text-2xl font-bold text-indigo-600 mt-1">
                {{ $users->where('role', 'dosen')->count() }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Mahasiswa</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">
                {{ $users->where('role', 'mahasiswa')->count() }}
            </p>
        </div>

    </div>

    {{-- Tabel digunakan untuk menampilkan data pengguna secara terstruktur. --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="px-6 py-5 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-900">
                Daftar Pengguna
            </h3>

            <p class="text-sm text-slate-500 mt-1">
                Data pengguna yang terdaftar pada sistem.
            </p>
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

                    @foreach ($users as $user)

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
                                <p class="text-slate-700">
                                    {{ $user->nim_nip ?? '-' }}
                                </p>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 font-semibold text-xs">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="flex items-center justify-center gap-2">

                                    {{-- Tombol untuk membuka form edit pengguna. --}}
                                    <a href="{{ route('pengguna.edit', $user->id) }}"
                                       class="px-3 py-2 rounded-lg bg-amber-50 text-amber-700 font-semibold text-xs hover:bg-amber-100 transition">
                                        Edit
                                    </a>

                                    {{-- Form digunakan untuk menghapus pengguna dengan method DELETE. --}}
                                    <form action="{{ route('pengguna.destroy', $user->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Apakah kamu yakin ingin menghapus pengguna ini?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="px-3 py-2 rounded-lg bg-red-50 text-red-700 font-semibold text-xs hover:bg-red-100 transition">
                                            Hapus
                                        </button>
                                    </form>

                                </div>
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</x-layout>