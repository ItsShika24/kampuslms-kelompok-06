<x-layout title="Rekap Nilai Perkuliahan">

    <div class="space-y-6">

        {{-- Header Halaman --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm">
            <div>
                <nav class="flex items-center gap-1.5 text-xs text-slate-400 font-medium mb-2">
                    <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition">Dashboard</a>
                    <span>/</span>
                    <span class="text-indigo-600 font-semibold">Rekap Nilai</span>
                </nav>
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100 shadow-sm">
                        <span class="material-symbols-outlined text-2xl">workspace_premium</span>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                            Buku Rekap Nilai
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                            @if ($role === 'dosen')
                                Matriks rekapitulasi penilaian tugas mahasiswa per mata kuliah yang Anda ampu.
                            @elseif ($role === 'mahasiswa')
                                Rekapitulasi perolehan nilai dan catatan evaluasi dosen pada mata kuliah yang Anda ikuti.
                            @else
                                Ringkasan seluruh data penilaian akademik tingkat program studi.
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Filter Mata Kuliah --}}
            @if ($courses->isNotEmpty())
                <form method="GET" action="{{ route('grades.index') }}" class="flex items-center gap-2 self-start md:self-auto">
                    <label for="course_filter" class="text-xs font-bold text-slate-600 whitespace-nowrap hidden sm:inline">
                        Pilih Kelas:
                    </label>
                    <div class="relative">
                        <select id="course_filter" name="course_id" onchange="this.form.submit()"
                                class="pl-3.5 pr-8 py-2 rounded-xl text-xs font-semibold bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition appearance-none cursor-pointer shadow-sm">
                            <option value="">Semua Mata Kuliah ({{ $courses->count() }})</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c->id }}" {{ (string) $selectedCourseId === (string) $c->id ? 'selected' : '' }}>
                                    {{ $c->code }} — {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">
                            expand_more
                        </span>
                    </div>
                    @if ($selectedCourseId)
                        <a href="{{ route('grades.index') }}" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 transition" title="Reset Filter">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </a>
                    @endif
                </form>
            @endif
        </div>

        {{-- Konten Rekap Nilai --}}
        @if ($courses->isEmpty())
            {{-- State Kosong --}}
            <div class="p-16 text-center bg-white rounded-3xl border border-slate-200/80 space-y-3">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-3xl">grading</span>
                </div>
                <h3 class="text-base font-bold text-slate-800">
                    Belum Ada Data Perkuliahan
                </h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                    Tidak ada mata kuliah atau tugas yang terhubung dengan akun Anda saat ini.
                </p>
            </div>
        @else

            {{-- ============================================================== --}}
            {{-- A. TAMPILAN DOSEN & ADMINISTRATOR (MATRIKS NILAI KELAS)       --}}
            {{-- ============================================================== --}}
            @if ($role !== 'mahasiswa')
                <div class="space-y-8">
                    @foreach ($courses as $course)
                        @php
                            $assignments = $course->assignments;
                            $students = $course->students ?? collect();
                        @endphp

                        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                            {{-- Header Kartu Kelas --}}
                            <div class="p-6 bg-slate-50/70 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white font-mono font-bold text-xs shadow-sm">
                                        {{ $course->code }}
                                    </span>
                                    <div>
                                        <h2 class="text-base font-bold text-slate-900 leading-tight">
                                            {{ $course->name }}
                                        </h2>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            {{ $course->sks }} SKS &bull; {{ $students->count() }} Mahasiswa Terdaftar &bull; {{ $assignments->count() }} Tugas
                                        </p>
                                    </div>
                                </div>

                                <a href="{{ route('mata-kuliah.show', $course) }}"
                                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-sm hover:border-indigo-300 transition shrink-0 self-start sm:self-auto">
                                    <span>Buka Kelas</span>
                                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                </a>
                            </div>

                            {{-- Tabel Matriks Rekapitulasi --}}
                            @if ($assignments->isEmpty())
                                <div class="p-10 text-center text-slate-400 text-xs">
                                    Belum ada tugas yang diterbitkan pada mata kuliah ini.
                                </div>
                            @elseif ($students->isEmpty())
                                <div class="p-10 text-center text-slate-400 text-xs">
                                    Belum ada mahasiswa yang terdaftar di kelas ini.
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse text-xs">
                                        <thead>
                                            <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                                                <th class="py-3 px-4 w-12 text-center">No</th>
                                                <th class="py-3 px-4 min-w-[120px]">NIM</th>
                                                <th class="py-3 px-4 min-w-[180px]">Nama Mahasiswa</th>
                                                @foreach ($assignments as $assignment)
                                                    <th class="py-3 px-4 text-center min-w-[110px]" title="{{ $assignment->title }}">
                                                        <div class="truncate max-w-[120px] mx-auto font-semibold">
                                                            {{ $assignment->title }}
                                                        </div>
                                                        <span class="text-[9px] font-normal text-slate-400 block lowercase">
                                                            (Maks: {{ $assignment->max_score ?? 100 }})
                                                        </span>
                                                    </th>
                                                @endforeach
                                                <th class="py-3 px-4 text-center min-w-[100px] bg-indigo-50/50 text-indigo-900 font-extrabold">
                                                    Rata-Rata
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 text-slate-700">
                                            @foreach ($students as $idx => $student)
                                                @php
                                                    $studentScores = [];
                                                @endphp
                                                <tr class="hover:bg-slate-50/60 transition">
                                                    <td class="py-3 px-4 text-center text-slate-400 font-medium">
                                                        {{ $idx + 1 }}
                                                    </td>
                                                    <td class="py-3 px-4 font-mono font-medium text-slate-600">
                                                        {{ $student->nim_nip ?? '-' }}
                                                    </td>
                                                    <td class="py-3 px-4 font-semibold text-slate-900">
                                                        {{ $student->name }}
                                                    </td>

                                                    {{-- Nilai per Tugas --}}
                                                    @foreach ($assignments as $assignment)
                                                        @php
                                                            // Cari submission milik mahasiswa ini
                                                            $sub = $assignment->submissions->firstWhere('user_id', $student->id);
                                                            $grade = $sub?->grade;
                                                            if ($grade && !is_null($grade->score)) {
                                                                $studentScores[] = (float) $grade->score;
                                                            }
                                                        @endphp
                                                        <td class="py-3 px-4 text-center">
                                                            @if ($grade && !is_null($grade->score))
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold text-xs {{ $grade->score >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($grade->score >= 60 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                                                                    {{ number_format($grade->score, 0) }}
                                                                </span>
                                                            @elseif ($sub)
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-sky-50 text-sky-700 border border-sky-200" title="Menunggu Penilaian">
                                                                    Terkirim
                                                                </span>
                                                            @else
                                                                <span class="text-slate-300 font-mono text-xs" title="Belum Mengumpulkan">
                                                                    &mdash;
                                                                </span>
                                                            @endif
                                                        </td>
                                                    @endforeach

                                                    {{-- Kolom Rata-Rata Nilai --}}
                                                    @php
                                                        $avg = count($studentScores) > 0 ? array_sum($studentScores) / count($studentScores) : null;
                                                    @endphp
                                                    <td class="py-3 px-4 text-center bg-indigo-50/30 font-bold">
                                                        @if (!is_null($avg))
                                                            <span class="text-xs font-black {{ $avg >= 80 ? 'text-emerald-600' : ($avg >= 60 ? 'text-amber-600' : 'text-rose-600') }}">
                                                                {{ number_format($avg, 1) }}
                                                            </span>
                                                        @else
                                                            <span class="text-slate-400 font-normal text-xs">&mdash;</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

            {{-- ============================================================== --}}
            {{-- B. TAMPILAN MAHASISWA (REKAP EVALUASI & FEEDBACK PRIBADI)       --}}
            {{-- ============================================================== --}}
            @else
                <div class="space-y-6">
                    @foreach ($courses as $course)
                        @php
                            $assignments = $course->assignments;
                            $gradedCount = 0;
                            $totalScore = 0;
                        @endphp

                        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                            {{-- Header Mata Kuliah --}}
                            <div class="p-6 bg-slate-50/70 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white font-mono font-bold text-xs shadow-sm">
                                        {{ $course->code }}
                                    </span>
                                    <div>
                                        <h2 class="text-base font-bold text-slate-900 leading-tight">
                                            {{ $course->name }}
                                        </h2>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Dosen Pengampu: {{ $course->lecturer?->name ?? 'Dosen Pengampu' }}
                                        </p>
                                    </div>
                                </div>

                                <a href="{{ route('mata-kuliah.show', $course) }}"
                                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-sm hover:border-indigo-300 transition shrink-0 self-start sm:self-auto">
                                    <span>Detail Kelas</span>
                                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                </a>
                            </div>

                            {{-- Daftar Tugas & Nilai Mahasiswa --}}
                            @if ($assignments->isEmpty())
                                <div class="p-8 text-center text-slate-400 text-xs">
                                    Belum ada tugas perkuliahan untuk mata kuliah ini.
                                </div>
                            @else
                                <div class="divide-y divide-slate-100">
                                    @foreach ($assignments as $assignment)
                                        @php
                                            $sub = $assignment->submissions->first(); // Sudah di-filter khusus milik mahasiswa login di controller
                                            $grade = $sub?->grade;
                                            if ($grade && !is_null($grade->score)) {
                                                $gradedCount++;
                                                $totalScore += $grade->score;
                                            }
                                        @endphp

                                        <div class="p-5 sm:p-6 hover:bg-slate-50/50 transition flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                            <div class="space-y-1.5 max-w-xl">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <h3 class="text-sm font-bold text-slate-900">
                                                        {{ $assignment->title }}
                                                    </h3>
                                                    @if ($grade && !is_null($grade->score))
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                                            Dinilai
                                                        </span>
                                                    @elseif ($sub)
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-sky-100 text-sky-700 border border-sky-200">
                                                            Terkumpul
                                                        </span>
                                                    @else
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-700 border border-amber-200">
                                                            Belum Kumpul
                                                        </span>
                                                    @endif
                                                </div>

                                                <p class="text-xs text-slate-500 flex items-center gap-1.5">
                                                    <span class="material-symbols-outlined text-[14px]">event</span>
                                                    Tenggat: {{ $assignment->due_at ? \Carbon\Carbon::parse($assignment->due_at)->translatedFormat('d M Y, H:i') : '-' }}
                                                </p>

                                                {{-- Umpan Balik Dosen --}}
                                                @if ($grade && !empty($grade->feedback))
                                                    <div class="mt-2.5 p-3 rounded-2xl bg-indigo-50/60 border border-indigo-100/80 text-xs text-indigo-900">
                                                        <span class="font-bold flex items-center gap-1 text-[11px] text-indigo-700 mb-0.5">
                                                            <span class="material-symbols-outlined text-[15px]">rate_review</span>
                                                            Catatan Evaluasi Dosen:
                                                        </span>
                                                        <p class="italic text-slate-700 pl-4 border-l-2 border-indigo-400 mt-1">
                                                            "{{ $grade->feedback }}"
                                                        </p>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Skor Nilai & Link Tugas --}}
                                            <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-start gap-2 shrink-0">
                                                @if ($grade && !is_null($grade->score))
                                                    <div class="text-right">
                                                        <div class="text-2xl font-black {{ $grade->score >= 80 ? 'text-emerald-600' : ($grade->score >= 60 ? 'text-amber-600' : 'text-rose-600') }}">
                                                            {{ number_format($grade->score, 0) }}
                                                            <span class="text-xs font-semibold text-slate-400">/ {{ $assignment->max_score ?? 100 }}</span>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-xs font-semibold text-slate-400 italic">
                                                        Belum ada nilai
                                                    </span>
                                                @endif

                                                <a href="{{ route('tugas.show', $assignment) }}"
                                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 transition">
                                                    <span>Lihat Tugas</span>
                                                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Footer Kartu Mahasiswa: Rata-Rata Mata Kuliah --}}
                                @if ($gradedCount > 0)
                                    @php
                                        $finalAvg = $totalScore / $gradedCount;
                                    @endphp
                                    <div class="p-4 bg-indigo-50/40 border-t border-slate-100 flex items-center justify-between text-xs">
                                        <span class="font-semibold text-slate-600">
                                            Rata-Rata Nilai Tugas ({{ $gradedCount }} Tugas Dinilai):
                                        </span>
                                        <span class="text-sm font-black {{ $finalAvg >= 80 ? 'text-emerald-600' : ($finalAvg >= 60 ? 'text-amber-600' : 'text-rose-600') }}">
                                            {{ number_format($finalAvg, 1) }}
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

        @endif

    </div>

</x-layout>