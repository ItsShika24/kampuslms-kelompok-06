<x-layout title="Mata Kuliah">

    {{-- Header halaman memberikan informasi utama tentang daftar mata kuliah. --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

            <div>
                <p class="text-sm font-semibold text-indigo-600 mb-2">
                    AKADEMIK / MATA KULIAH
                </p>

                <h2 class="text-3xl font-bold tracking-tight text-slate-900">
                    Manajemen & Eksplorasi Mata Kuliah
                </h2>

                <p class="text-sm text-slate-500 mt-2">
                    Kelola dan lihat informasi mata kuliah yang tersedia.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="px-3 py-2 rounded-lg bg-emerald-50 text-emerald-700 text-sm font-semibold">
                    ● Semester Genap
                </span>
            </div>

        </div>
    </div>


    {{-- Ringkasan sederhana jumlah mata kuliah. --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Mata Kuliah</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">
                {{ $courses->count() }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total SKS</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">
                {{ $courses->sum('sks') }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Semester</p>
            <p class="text-2xl font-bold text-indigo-600 mt-1">
                5
            </p>
        </div>

    </div>


    {{-- Tabel digunakan untuk menampilkan data mata kuliah secara terstruktur. --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="px-6 py-5 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-900">
                Daftar Mata Kuliah
            </h3>

            <p class="text-sm text-slate-500 mt-1">
                Mata kuliah yang tersedia pada semester aktif.
            </p>
        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold text-slate-500">
                            Kode
                        </th>

                        <th class="px-6 py-4 text-left font-semibold text-slate-500">
                            Mata Kuliah
                        </th>

                        <th class="px-6 py-4 text-center font-semibold text-slate-500">
                            SKS
                        </th>

                        <th class="px-6 py-4 text-left font-semibold text-slate-500">
                            Dosen
                        </th>

                        <th class="px-6 py-4 text-center font-semibold text-slate-500">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @foreach ($courses as $course)

                        <tr class="hover:bg-slate-50 transition">

                            <td class="px-6 py-5">
                                <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 font-semibold text-xs">
                                    {{ $course->code }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <p class="font-semibold text-slate-900">
                                    {{ $course->name }}
                                </p>

                                <p class="text-xs text-slate-400 mt-1">
                                    Semester 5
                                </p>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <span class="font-semibold text-slate-700">
                                    {{ $course->sks }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <p class="text-slate-700">
                                    {{ $course->lecturer->name }}
                                </p>
                            </td>

                            <td class="px-6 py-5 text-center">

                                {{-- Link detail menggunakan route() agar URL tidak ditulis manual. --}}
                                <a
                                    href="{{ route('mata-kuliah.show', $course->id) }}"
                                    class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold text-xs hover:bg-indigo-700 transition"
                                >
                                    Lihat Detail
                                </a>

                                {{-- Link edit digunakan untuk mengubah data mata kuliah. --}}
                                <a
                                    href="{{ route('mata-kuliah.edit', $course->id) }}"
                                    class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-500 text-white font-semibold text-xs hover:bg-amber-600 transition ml-2"
                                >
                                    Edit
                                </a>

                                {{-- Form digunakan untuk menghapus mata kuliah. --}}
                                <form
                                    action="{{ route('mata-kuliah.destroy', $course->id) }}"
                                    method="POST"
                                    class="inline"
                                    onsubmit="return confirm('Yakin ingin menghapus mata kuliah ini?');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="inline-flex items-center px-4 py-2 rounded-lg bg-red-600 text-white font-semibold text-xs hover:bg-red-700 transition ml-2"
                                    >
                                        Hapus
                                    </button>
                                </form>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</x-layout>