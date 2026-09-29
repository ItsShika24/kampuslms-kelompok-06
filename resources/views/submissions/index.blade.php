<x-layout title="Pengumpulan & Penilaian Tugas - EduKampus">

    <div class="w-full px-4 sm:px-6 lg:px-8 py-6 max-w-7xl mx-auto space-y-6">

        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                        <span class="material-symbols-outlined text-2xl">assignment_turned_in</span>
                    </span>
                    <h1 class="text-2xl font-bold text-slate-900">
                        @if ($role === 'mahasiswa')
                            Tugas &amp; Nilai Saya
                        @elseif ($role === 'dosen')
                            Penilaian Pengumpulan Mahasiswa
                        @else
                            Semua Pengumpulan Tugas
                        @endif
                    </h1>
                </div>
                <p class="text-sm text-slate-500">
                    @if ($role === 'mahasiswa')
                        Pantau seluruh riwayat tugas yang telah Anda kumpulkan beserta nilai dan umpan balik dosen pengampu.
                    @elseif ($role === 'dosen')
                        Daftar pengumpulan tugas mahasiswa pada seluruh mata kuliah yang Anda ampu untuk diperiksa dan dinilai.
                    @else
                        Seluruh pengumpulan tugas mahasiswa di sistem EduKampus.
                    @endif
                </p>
            </div>

            {{-- Filter Status Penilaian --}}
            <div class="flex items-center gap-1.5 bg-slate-100 p-1.5 rounded-xl text-xs font-semibold shrink-0">
                <a href="{{ route('submissions.index') }}"
                   class="px-3 py-1.5 rounded-lg transition {{ !request()->filled('status') ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                    Semua
                </a>
                <a href="{{ route('submissions.index', ['status' => 'ungraded']) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ request('status') === 'ungraded' ? 'bg-white text-amber-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                    Belum Dinilai
                </a>
                <a href="{{ route('submissions.index', ['status' => 'graded']) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ request('status') === 'graded' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                    Sudah Dinilai
                </a>
            </div>
        </div>

        {{-- Daftar Submissions --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            @if ($submissions->isEmpty())
                <div class="p-12 text-center text-slate-400">
                    <span class="material-symbols-outlined text-5xl text-slate-300 mb-3">inbox</span>
                    <p class="text-base font-semibold text-slate-600">Tidak ada pengumpulan ditemukan</p>
                    <p class="text-xs text-slate-400 mt-1">
                        @if ($role === 'mahasiswa')
                            Anda belum mengumpulkan tugas apa pun atau filter tidak cocok.
                        @else
                            Belum ada pengumpulan mahasiswa untuk kriteria ini.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-6 py-4">#</th>
                                @if ($role !== 'mahasiswa')
                                    <th class="px-6 py-4">Mahasiswa</th>
                                @endif
                                <th class="px-6 py-4">Tugas &amp; Mata Kuliah</th>
                                <th class="px-6 py-4">Waktu Pengumpulan</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center">Nilai</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($submissions as $index => $sub)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-6 py-4 text-xs font-medium text-slate-400">
                                        {{ $submissions->firstItem() + $index }}
                                    </td>

                                    {{-- Kolom Mahasiswa (Dosen & Admin) --}}
                                    @if ($role !== 'mahasiswa')
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-slate-800">{{ $sub->student->name ?? 'Mahasiswa' }}</p>
                                            <p class="text-xs text-slate-400">
                                                {{ $sub->student->nim_nip ?? '-' }} &bull; {{ $sub->student->email ?? '-' }}
                                            </p>
                                        </td>
                                    @endif

                                    {{-- Tugas & MK --}}
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-900 leading-snug">
                                            {{ $sub->assignment->title ?? 'Tugas' }}
                                        </p>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            {{ $sub->assignment->course->name ?? 'Mata Kuliah' }}
                                            <span class="text-slate-400">({{ $sub->assignment->course->code ?? '-' }})</span>
                                        </p>
                                    </td>

                                    {{-- Waktu --}}
                                    <td class="px-6 py-4 text-xs text-slate-600">
                                        <div class="flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[15px] text-slate-400">schedule</span>
                                            {{ $sub->submitted_at ? $sub->submitted_at->translatedFormat('d M Y, H:i') : '-' }} WIB
                                        </div>
                                    </td>

                                    {{-- Status Tepat Waktu / Terlambat --}}
                                    <td class="px-6 py-4 text-center">
                                        @if ($sub->is_late)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Terlambat
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Tepat Waktu
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Nilai --}}
                                    <td class="px-6 py-4 text-center">
                                        @if ($sub->grade)
                                            <div class="inline-flex items-baseline gap-1 px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 font-extrabold text-sm border border-emerald-200">
                                                {{ $sub->grade->score }}
                                                <span class="text-[11px] font-normal text-emerald-600">/ {{ $sub->assignment->max_score }}</span>
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                Belum Dinilai
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Aksi Buka Halaman Pengumpulan --}}
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('submissions.show', $sub->id) }}"
                                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold transition shadow-sm
                                                  {{ ($role === 'dosen' || $role === 'admin') && !$sub->grade
                                                      ? 'bg-indigo-600 hover:bg-indigo-700 text-white'
                                                      : 'bg-emerald-600 hover:bg-emerald-700 text-white' }}">
                                            <span class="material-symbols-outlined text-[15px]">
                                                {{ ($role === 'dosen' || $role === 'admin') && !$sub->grade ? 'rate_review' : 'visibility' }}
                                            </span>
                                            @if ($role === 'mahasiswa')
                                                Lihat Lembar Pengumpulan
                                            @elseif ($sub->grade)
                                                Lihat / Edit Nilai
                                            @else
                                                Buka &amp; Nilai
                                            @endif
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Links --}}
                <div class="px-6 py-4 border-t border-slate-200">
                    {{ $submissions->links() }}
                </div>
            @endif
        </div>

    </div>

</x-layout>
