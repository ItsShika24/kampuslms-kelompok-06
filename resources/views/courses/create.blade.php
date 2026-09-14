<x-layout title="Tambah Mata Kuliah">

    {{-- Header halaman untuk form tambah mata kuliah. --}}
    <div class="mb-8">
        <p class="text-sm font-semibold text-indigo-600 mb-2">
            AKADEMIK / MATA KULIAH
        </p>

        <h2 class="text-3xl font-bold tracking-tight text-slate-900">
            Tambah Mata Kuliah
        </h2>

        <p class="text-sm text-slate-500 mt-2">
            Tambahkan informasi mata kuliah baru ke dalam sistem.
        </p>
    </div>


    {{-- Form digunakan untuk memasukkan data mata kuliah baru. --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        <form action="{{ route('mata-kuliah.store') }}" method="POST">

            @csrf

            {{-- Input kode mata kuliah. --}}
            <div class="mb-5">
                <label for="code" class="block text-sm font-semibold text-slate-700 mb-2">
                    Kode Mata Kuliah
                </label>

                <input
                    type="text"
                    id="code"
                    name="code"
                    value="{{ old('code') }}"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Contoh: SI301"
                >

                @error('code')
                    <p class="text-sm text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>


            {{-- Input nama mata kuliah. --}}
            <div class="mb-5">
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-2">
                    Nama Mata Kuliah
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Contoh: Sistem Informasi Manajemen"
                >

                @error('name')
                    <p class="text-sm text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>


            {{-- Input deskripsi mata kuliah. --}}
            <div class="mb-5">
                <label for="description" class="block text-sm font-semibold text-slate-700 mb-2">
                    Deskripsi
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Masukkan deskripsi mata kuliah"
                >{{ old('description') }}</textarea>

                @error('description')
                    <p class="text-sm text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>


            {{-- Input jumlah SKS. --}}
            <div class="mb-5">
                <label for="sks" class="block text-sm font-semibold text-slate-700 mb-2">
                    SKS
                </label>

                <input
                    type="number"
                    id="sks"
                    name="sks"
                    value="{{ old('sks') }}"
                    min="1"
                    max="6"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Contoh: 3"
                >

                @error('sks')
                    <p class="text-sm text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>


            {{-- Pilihan dosen pengampu. --}}
            <div class="mb-5">
                <label for="lecturer_id" class="block text-sm font-semibold text-slate-700 mb-2">
                    Dosen Pengampu
                </label>

                <select
                    id="lecturer_id"
                    name="lecturer_id"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">-- Pilih Dosen --</option>

                    @foreach ($lecturers as $lecturer)
                        <option
                            value="{{ $lecturer->id }}"
                            {{ old('lecturer_id') == $lecturer->id ? 'selected' : '' }}
                        >
                            {{ $lecturer->name }}
                        </option>
                    @endforeach
                </select>

                @error('lecturer_id')
                    <p class="text-sm text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>


            {{-- Pilihan status mata kuliah. --}}
            <div class="mb-6">
                <label for="status" class="block text-sm font-semibold text-slate-700 mb-2">
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>
                        Draft
                    </option>

                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
                        Aktif
                    </option>

                    <option value="archived" {{ old('status') == 'archived' ? 'selected' : '' }}>
                        Diarsipkan
                    </option>
                </select>

                @error('status')
                    <p class="text-sm text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>


            {{-- Tombol aksi form. --}}
            <div class="flex items-center gap-3">

                <a
                    href="{{ route('mata-kuliah.index') }}"
                    class="inline-flex items-center px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2.5 rounded-lg bg-indigo-600 text-white font-semibold text-sm hover:bg-indigo-700 transition"
                >
                    Simpan Mata Kuliah
                </button>

            </div>

        </form>

    </div>

</x-layout>