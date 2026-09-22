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

            <div class="flex items-center gap-3">
                <span class="px-3 py-2 rounded-lg bg-emerald-50 text-emerald-700 text-sm font-semibold">
                    ● Semester Genap
                </span>

                @if (session('demo_role') === 'admin')
                    <a href="{{ route('mata-kuliah.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl
                              bg-indigo-600 text-white font-semibold text-sm
                              hover:bg-indigo-700 transition shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Tambah MK
                    </a>
                @endif
            </div>

        </div>
    </div>


    {{-- Ringkasan sederhana jumlah mata kuliah. --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Mata Kuliah</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">
                {{ $courses->total() }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total SKS (Halaman Ini)</p>
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


    {{-- Filter & Pencarian (State disimpan di Query String) --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6">
        <form method="GET" action="{{ route('mata-kuliah.index') }}" class="flex flex-col md:flex-row items-center gap-4">
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
                        placeholder="Cari berdasarkan kode atau nama mata kuliah..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>
            </div>

            {{-- Filter status --}}
            <div class="w-full md:w-48">
                <select
                    name="status"
                    class="w-full py-2.5 px-3 rounded-xl border border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Diarsipkan</option>
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

                @if (request()->hasAny(['q', 'status']) && (request('q') || request('status')))
                    <a
                        href="{{ route('mata-kuliah.index') }}"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 text-sm font-medium hover:bg-slate-50 transition w-full md:w-auto"
                        title="Reset filter"
                    >
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>


    {{-- Tabel data mata kuliah secara terstruktur --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="px-6 py-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-lg font-bold text-slate-900">
                    Daftar Mata Kuliah
                </h3>
                <p class="text-sm text-slate-500 mt-1">
                    Menampilkan data mata kuliah terdaftar (15 per halaman).
                </p>
            </div>

            @if (request('q') || request('status'))
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
                            Status
                        </th>

                        <th class="px-6 py-4 text-center font-semibold text-slate-500">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($courses as $course)

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

                                @if ($course->description)
                                    <p class="text-xs text-slate-400 mt-1 line-clamp-1">
                                        {{ Str::limit($course->description, 60) }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-center">
                                <span class="font-semibold text-slate-700">
                                    {{ $course->sks }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <p class="text-slate-700">
                                    {{ $course->lecturer?->name ?? 'Belum Ditentukan' }}
                                </p>
                            </td>

                            <td class="px-6 py-5 text-center">
                                @php
                                    $statusBadges = [
                                        'active'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'draft'    => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'archived' => 'bg-slate-100 text-slate-600 border-slate-300',
                                    ];
                                    $statusLabels = [
                                        'active'   => 'Aktif',
                                        'draft'    => 'Draft',
                                        'archived' => 'Diarsipkan',
                                    ];
                                @endphp
                                <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $statusBadges[$course->status] ?? 'bg-slate-50 text-slate-600' }}">
                                    {{ $statusLabels[$course->status] ?? ucfirst($course->status) }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="flex items-center justify-center gap-2">

                                    {{-- Link detail --}}
                                    <a
                                        href="{{ route('mata-kuliah.show', $course->id) }}"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-600 text-white font-semibold text-xs hover:bg-indigo-700 transition"
                                    >
                                        Detail
                                    </a>

                                    @if (session('demo_role') === 'admin')
                                        {{-- Link edit --}}
                                        <a
                                            href="{{ route('mata-kuliah.edit', $course->id) }}"
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg bg-amber-500 text-white font-semibold text-xs hover:bg-amber-600 transition"
                                        >
                                            Edit
                                        </a>

                                        {{-- Form hapus (method DELETE) --}}
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
                                                class="inline-flex items-center px-3 py-1.5 rounded-lg bg-red-600 text-white font-semibold text-xs hover:bg-red-700 transition"
                                            >
                                                Hapus
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-slate-300">search_off</span>
                                    <p class="font-medium text-slate-600">Tidak ada mata kuliah yang cocok</p>
                                    <p class="text-xs text-slate-400">Coba ubah kata kunci atau reset filter pencarian.</p>
                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- Pagination dengan mempertahankan Query String --}}
        @if ($courses->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $courses->links() }}
            </div>
        @endif

    </div>

</x-layout>