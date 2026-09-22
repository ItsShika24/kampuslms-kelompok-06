<x-layout title="Tambah Pengguna">

    {{-- Header halaman untuk form tambah pengguna. --}}
    <div class="mb-8">
        <p class="text-sm font-semibold text-indigo-600 mb-2">
            ADMINISTRASI / PENGGUNA
        </p>

        <h2 class="text-3xl font-bold tracking-tight text-slate-900">
            Tambah Pengguna
        </h2>

        <p class="text-sm text-slate-500 mt-2">
            Tambahkan pengguna baru ke dalam sistem.
        </p>
    </div>

    {{-- Form digunakan untuk memasukkan data pengguna baru. --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        <form action="{{ route('pengguna.store') }}" method="POST" novalidate>

            @csrf

            {{-- Input nama pengguna. --}}
            <div class="mb-5">
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-2">
                    Nama
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Masukkan nama pengguna"
                >

                @error('name')
                    <p class="text-sm text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Input email pengguna. --}}
            <div class="mb-5">
                <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Contoh: pengguna@kampuslms.test"
                >

                @error('email')
                    <p class="text-sm text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Input NIM/NIP pengguna. --}}
            <div class="mb-5">
                <label for="nim_nip" class="block text-sm font-semibold text-slate-700 mb-2">
                    NIM/NIP
                </label>

                <input
                    type="text"
                    id="nim_nip"
                    name="nim_nip"
                    value="{{ old('nim_nip') }}"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Masukkan NIM atau NIP"
                >

                @error('nim_nip')
                    <p class="text-sm text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Pilihan role pengguna. --}}
            <div class="mb-5">
                <label for="role" class="block text-sm font-semibold text-slate-700 mb-2">
                    Role
                </label>

                <select
                    id="role"
                    name="role"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">-- Pilih Role --</option>

                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>
                        Admin
                    </option>

                    <option value="dosen" {{ old('role') == 'dosen' ? 'selected' : '' }}>
                        Dosen
                    </option>

                    <option value="mahasiswa" {{ old('role') == 'mahasiswa' ? 'selected' : '' }}>
                        Mahasiswa
                    </option>
                </select>

                @error('role')
                    <p class="text-sm text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Input password pengguna. --}}
            <div class="mb-6">
                <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Masukkan password"
                >

                @error('password')
                    <p class="text-sm text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Tombol aksi form. --}}
            <div class="flex items-center gap-3">

                <a
                    href="{{ route('pengguna.index') }}"
                    class="inline-flex items-center px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2.5 rounded-lg bg-indigo-600 text-white font-semibold text-sm hover:bg-indigo-700 transition"
                >
                    Simpan Pengguna
                </button>

            </div>

        </form>

    </div>

</x-layout>