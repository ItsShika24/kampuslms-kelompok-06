<x-layout title="Detail Pengguna">

    {{-- Header halaman untuk menampilkan detail pengguna. --}}
    <div class="mb-8">
        <p class="text-sm font-semibold text-indigo-600 mb-2">
            ADMINISTRASI / PENGGUNA
        </p>

        <h2 class="text-3xl font-bold tracking-tight text-slate-900">
            Detail Pengguna
        </h2>

        <p class="text-sm text-slate-500 mt-2">
            Informasi pengguna yang terdaftar dalam sistem.
        </p>
    </div>

    {{-- Informasi detail pengguna ditampilkan dalam bentuk kartu. --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        <div class="space-y-5">

            <div>
                <p class="text-sm text-slate-500">
                    Nama
                </p>

                <p class="text-base font-semibold text-slate-900 mt-1">
                    {{ $user->name }}
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">
                    Email
                </p>

                <p class="text-base font-semibold text-slate-900 mt-1">
                    {{ $user->email }}
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">
                    NIM/NIP
                </p>

                <p class="text-base font-semibold text-slate-900 mt-1">
                    {{ $user->nim_nip ?? '-' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">
                    Role
                </p>

                <p class="text-base font-semibold text-indigo-600 mt-1">
                    {{ ucfirst($user->role) }}
                </p>
            </div>

        </div>

        {{-- Tombol kembali ke daftar pengguna. --}}
        <div class="mt-8">
            <a
                href="{{ route('pengguna.index') }}"
                class="inline-flex items-center px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition"
            >
                Kembali ke Daftar Pengguna
            </a>
        </div>

    </div>

</x-layout>