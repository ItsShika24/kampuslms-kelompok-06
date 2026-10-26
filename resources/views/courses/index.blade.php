<x-layout title="Mata Kuliah">

    <div class="space-y-6">

        {{-- Header Halaman --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <nav class="flex items-center gap-1.5 text-xs text-slate-400 font-medium mb-1.5">
                    <span>Akademik</span>
                    <span>/</span>
                    <span class="text-indigo-600 font-semibold">Mata Kuliah</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                    Katalog &amp; Manajemen Mata Kuliah
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Eksplorasi silabus, jadwal kurikulum, materi ajar, dan tugas perkuliahan terdaftar.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Semester 2024/2025 Genap
                </span>

                @can('create', \App\Models\Course::class)
                    <a href="{{ route('mata-kuliah.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Tambah Mata Kuliah
                    </a>
                @endcan
            </div>
        </div>

        {{-- Ringkasan Metrik Sederhana --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Mata Kuliah</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $courses->total() }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Tersedia di kurikulum</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">menu_book</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total SKS (Halaman Ini)</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $courses->sum('sks') }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Bobot sks kumulatif</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">fact_check</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Semester Berjalan</p>
                    <p class="text-2xl font-extrabold text-indigo-600 mt-1">Semester 5</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">T.A. Aktif</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">calendar_month</span>
                </div>
            </div>
        </div>

        {{-- Filter & Pencarian (State disimpan di Query String) --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 sm:p-5">
            <form method="GET" action="{{ route('mata-kuliah.index') }}" class="flex flex-col md:flex-row items-center gap-3">
                {{-- Input kata kunci pencarian --}}
                <div class="flex-1 w-full relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <span class="material-symbols-outlined text-[20px]">search</span>
                    </span>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari berdasarkan kode atau nama mata kuliah..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 shadow-2xs"
                    >
                </div>

                {{-- Filter status --}}
                <div class="w-full md:w-48">
                    <select
                        name="status"
                        class="w-full py-2.5 px-3 rounded-xl border border-slate-300 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 shadow-2xs"
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
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition w-full md:w-auto"
                    >
                        <span class="material-symbols-outlined text-[17px]">filter_alt</span>
                        Terapkan Filter
                    </button>

                    @if (request()->hasAny(['q', 'status']) && (request('q') || request('status')))
                        <a
                            href="{{ route('mata-kuliah.index') }}"
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 text-xs font-semibold hover:bg-slate-50 transition w-full md:w-auto"
                            title="Reset filter"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Tabel Data Mata Kuliah --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-white">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        Daftar Mata Kuliah
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Menampilkan data mata kuliah terdaftar (15 per halaman).
                    </p>
                </div>

                @if (request('q') || request('status'))
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
                            <th class="px-6 py-3.5 text-left">Kode</th>
                            <th class="px-6 py-3.5 text-left">Mata Kuliah</th>
                            <th class="px-6 py-3.5 text-center">SKS</th>
                            <th class="px-6 py-3.5 text-left">Dosen Pengampu</th>
                            <th class="px-6 py-3.5 text-center">Status</th>
                            <th class="px-6 py-3.5 text-right whitespace-nowrap">Aksi</th>
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

                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900">
                                        {{ $course->name }}
                                    </p>
                                    @if ($course->description)
                                        <p class="text-xs text-slate-400 mt-0.5 line-clamp-1">
                                            {{ Str::limit($course->description, 65) }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-center font-bold text-slate-700">
                                    {{ $course->sks }}
                                </td>

                                <td class="px-6 py-4 text-slate-700 text-xs font-medium">
                                    {{ $course->lecturer?->name ?? 'Belum Ditentukan' }}
                                </td>

                                <td class="px-6 py-4 text-center">
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
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold border {{ $statusBadges[$course->status] ?? 'bg-slate-50 text-slate-600' }}">
                                        {{ $statusLabels[$course->status] ?? ucfirst($course->status) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5 flex-nowrap">
                                        <a href="{{ route('mata-kuliah.show', $course->id) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-semibold text-xs transition whitespace-nowrap shrink-0">
                                            <span class="material-symbols-outlined text-[14px]">visibility</span>
                                            Detail
                                        </a>

                                        @can('update', $course)
                                            <a href="{{ route('mata-kuliah.edit', $course->id) }}"
                                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 font-semibold text-xs transition whitespace-nowrap shrink-0">
                                                <span class="material-symbols-outlined text-[14px]">edit</span>
                                                Edit
                                            </a>
                                        @endcan

                                        @can('delete', $course)
                                            <form action="{{ route('mata-kuliah.destroy', $course->id) }}"
                                                  method="POST"
                                                  class="inline-flex shrink-0"
                                                  onsubmit="return confirm('Yakin ingin menghapus mata kuliah ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-semibold text-xs transition whitespace-nowrap shrink-0">
                                                    <span class="material-symbols-outlined text-[14px]">delete</span>
                                                    Hapus
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <span class="material-symbols-outlined text-4xl text-slate-300">search_off</span>
                                        <p class="font-bold text-slate-700 text-sm">Tidak ada mata kuliah yang cocok</p>
                                        <p class="text-xs text-slate-400">Coba ubah kata kunci atau reset filter pencarian.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($courses->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                    {{ $courses->links() }}
                </div>
            @endif
        </div>

    </div>

</x-layout>