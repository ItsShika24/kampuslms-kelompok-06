<x-layout title="{{ $assignment->title }}">

    @php
        $user = auth()->user();
        $role = $user?->role ?? 'mahasiswa';
    @endphp

    {{-- Kembali --}}
    <div class="mb-6">
        <a href="{{ route('mata-kuliah.show', ['course' => $assignment->course_id, 'tab' => 'tugas']) }}"
           class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600 transition mb-4">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Kembali ke {{ $assignment->course->name }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── Kolom kiri: Detail Tugas ─────────────────────────── --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Info tugas --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                @php
                    $isPast  = now()->isAfter($assignment->due_at);
                    $isClosed = $assignment->status === 'closed';
                    $badgeClass = match(true) {
                        $assignment->status === 'draft'  => 'bg-slate-100 text-slate-500',
                        $isClosed                        => 'bg-red-50 text-red-600',
                        $isPast                          => 'bg-orange-50 text-orange-600',
                        default                          => 'bg-emerald-50 text-emerald-700',
                    };
                    $badgeText = match(true) {
                        $assignment->status === 'draft'  => 'DRAFT',
                        $isClosed                        => 'DITUTUP',
                        $isPast                          => 'TERLAMBAT',
                        default                          => 'AKTIF',
                    };
                @endphp

                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <span class="px-2 py-0.5 rounded text-xs font-bold {{ $badgeClass }}">
                            {{ $badgeText }}
                        </span>
                        <h1 class="text-2xl font-bold text-slate-900 mt-2">
                            {{ $assignment->title }}
                        </h1>
                    </div>
                </div>

                {{-- Banner Status Pengumpulan Mahasiswa / Dosen --}}
                @if ($role === 'mahasiswa' && $submission)
                    <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="p-2 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                <span class="material-symbols-outlined text-xl">check_circle</span>
                            </span>
                            <div>
                                <p class="text-sm font-bold text-emerald-900">Anda telah mengumpulkan tugas ini</p>
                                <p class="text-xs text-emerald-700">
                                    Dikirim pada {{ $submission->submitted_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB
                                    @if ($submission->grade)
                                        &bull; <strong>Nilai: {{ $submission->grade->score }} / {{ $assignment->max_score }}</strong>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('submissions.show', $submission->id) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition shadow-sm shrink-0">
                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                            Buka Lembar Pengumpulan
                        </a>
                    </div>
                @elseif (($role === 'dosen' || $role === 'admin') && $allSubmissions->isNotEmpty())
                    <div class="mb-5 p-4 rounded-xl bg-indigo-50 border border-indigo-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="p-2 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center">
                                <span class="material-symbols-outlined text-xl">rate_review</span>
                            </span>
                            <div>
                                <p class="text-sm font-bold text-indigo-900">{{ $allSubmissions->count() }} Mahasiswa Telah Mengumpulkan</p>
                                <p class="text-xs text-indigo-700">
                                    {{ $allSubmissions->filter(fn($s) => $s->grade !== null)->count() }} sudah dinilai &bull; {{ $allSubmissions->filter(fn($s) => $s->grade === null)->count() }} belum dinilai
                                </p>
                            </div>
                        </div>
                        <a href="#daftar-submission"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm shrink-0">
                            <span class="material-symbols-outlined text-[15px]">arrow_downward</span>
                            Lihat &amp; Nilai Pengumpulan
                        </a>
                    </div>
                @endif

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-5 text-sm">
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-slate-500 text-xs mb-1">Mata Kuliah</p>
                        <p class="font-semibold text-slate-800 text-xs">
                            {{ $assignment->course->name }}
                        </p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-slate-500 text-xs mb-1">Tenggat</p>
                        <p class="font-semibold {{ $isPast ? 'text-red-600' : 'text-slate-800' }} text-xs">
                            {{ $assignment->due_at->format('d M Y') }}
                        </p>
                        <p class="text-slate-400 text-xs">
                            {{ $assignment->due_at->format('H:i') }} WIB
                        </p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-slate-500 text-xs mb-1">Nilai Maks.</p>
                        <p class="font-bold text-indigo-700">
                            {{ number_format($assignment->max_score, 0) }}
                        </p>
                    </div>
                </div>

                @if ($assignment->allow_late)
                    <div class="mb-4 flex items-center gap-2 text-sm text-amber-600 bg-amber-50
                                border border-amber-200 rounded-xl px-4 py-2">
                        <span class="material-symbols-outlined text-[16px]">info</span>
                        Tugas ini mengizinkan pengumpulan terlambat.
                    </div>
                @endif

                @if ($assignment->instructions)
                    <div>
                        <p class="text-sm font-semibold text-slate-700 mb-2">Instruksi Tugas</p>
                        <div class="prose prose-sm max-w-none text-slate-600 bg-slate-50
                                    rounded-xl p-4 text-sm leading-relaxed whitespace-pre-line">
                            {{ $assignment->instructions }}
                        </div>
                    </div>
                @endif
            </div>

        </div>

        {{-- ── Kolom kanan: Form Submit (Mahasiswa) ──────────────── --}}
        <div class="space-y-4">

            @if ($role === 'mahasiswa')

                @if ($submission)
                    {{-- Sudah pernah submit --}}
                    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                            <p class="font-bold text-emerald-800 text-sm">Sudah Dikumpulkan</p>
                        </div>
                        <p class="text-xs text-emerald-700 mb-1">
                            Dikirim pada: {{ $submission->submitted_at?->format('d M Y, H:i') ?? '—' }}
                        </p>
                        @if ($submission->is_late)
                            <span class="text-xs text-orange-600 font-semibold">⚠ Terlambat</span>
                        @endif

                        @if ($submission->grade)
                            <div class="mt-3 pt-3 border-t border-emerald-200">
                                <p class="text-xs text-slate-500 mb-1">Nilai</p>
                                <p class="text-2xl font-bold text-indigo-700">
                                    {{ number_format($submission->grade->score, 1) }}
                                    <span class="text-sm font-normal text-slate-500">
                                        / {{ number_format($assignment->max_score, 0) }}
                                    </span>
                                </p>
                            </div>
                        @else
                            <p class="text-xs text-slate-400 mt-2">Belum dinilai.</p>
                        @endif

                        <div class="mt-4 pt-3 border-t border-emerald-200">
                            <a href="{{ route('submissions.show', $submission->id) }}"
                               class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition shadow-sm">
                                <span class="material-symbols-outlined text-[16px]">visibility</span>
                                Lihat Lembar Pengumpulan
                            </a>
                        </div>
                    </div>

                    {{-- Catatan yang sudah dikirim --}}
                    @if ($submission->note)
                        <div class="bg-white rounded-2xl border border-slate-200 p-5">
                            <p class="text-sm font-semibold text-slate-700 mb-2">Catatan Anda</p>
                            <p class="text-sm text-slate-600 whitespace-pre-line">{{ $submission->note }}</p>
                        </div>
                    @endif

                @endif

                {{-- Form kumpulkan (tetap tampil untuk revisi jika belum dinilai) --}}
                @if (!$submission || !$submission->grade)
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <h2 class="font-bold text-slate-900 mb-1">
                            {{ $submission ? 'Perbarui Jawaban' : 'Kumpulkan Tugas' }}
                        </h2>
                        <p class="text-xs text-slate-500 mb-4">
                            Tuliskan jawaban atau catatan singkat Anda.
                        </p>

                        @if (session('success'))
                            <div class="mb-3 p-3 bg-emerald-50 border border-emerald-200
                                        text-emerald-700 rounded-xl text-sm">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($isClosed && !$assignment->allow_late)
                            <div class="p-3 bg-red-50 border border-red-200 text-red-700
                                        rounded-xl text-sm">
                                Tugas ini sudah ditutup dan tidak menerima submission baru.
                            </div>
                        @else
                            <form action="{{ route('tugas.submit', $assignment->id) }}" method="POST">
                                @csrf
                                <div class="mb-4">
                                    <label for="note"
                                           class="block text-sm font-semibold text-slate-700 mb-2">
                                        Jawaban / Catatan
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <textarea id="note" name="note" rows="6"
                                              placeholder="Tuliskan jawaban, tautan, atau catatan Anda di sini…"
                                              class="w-full rounded-xl border border-slate-300 px-4 py-3
                                                     text-sm focus:outline-none focus:border-indigo-500
                                                     focus:ring-1 focus:ring-indigo-500 resize-none"
                                    >{{ old('note', $submission?->note) }}</textarea>
                                    @error('note')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                @if ($isPast && $assignment->allow_late)
                                    <div class="mb-3 flex items-center gap-2 text-xs text-orange-600
                                                bg-orange-50 border border-orange-200 rounded-xl px-3 py-2">
                                        <span class="material-symbols-outlined text-[14px]">warning</span>
                                        Pengumpulan ini akan dicatat sebagai <strong>terlambat</strong>.
                                    </div>
                                @endif

                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2
                                               px-4 py-2.5 rounded-xl bg-indigo-600 text-white
                                               font-semibold text-sm hover:bg-indigo-700 transition">
                                    <span class="material-symbols-outlined text-[18px]">send</span>
                                    {{ $submission ? 'Perbarui' : 'Kumpulkan Sekarang' }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endif

            @elseif ($role === 'dosen')
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
                    <p class="text-sm font-semibold text-slate-700 mb-1">Statistik Submission</p>
                    <p class="text-3xl font-bold text-indigo-700">
                        {{ $assignment->submissions()->count() }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">mahasiswa telah mengumpulkan</p>
                    <a href="#daftar-submission" class="mt-4 w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm">
                        <span class="material-symbols-outlined text-[15px]">arrow_downward</span>
                        Lihat Daftar Nilai
                    </a>
                </div>

            @elseif ($role === 'admin')
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
                    <p class="text-sm font-semibold text-slate-700 mb-1">Total Submission</p>
                    <p class="text-3xl font-bold text-indigo-700">
                        {{ $assignment->submissions()->count() }}
                    </p>
                    <a href="#daftar-submission" class="mt-4 w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm">
                        <span class="material-symbols-outlined text-[15px]">arrow_downward</span>
                        Lihat Pengumpulan
                    </a>
                </div>
            @endif

        </div>

    </div>

    {{-- ── Tabel Daftar Pengumpulan Mahasiswa (Khusus Dosen Pengampu & Admin) ── --}}
    @can('viewSubmissions', $assignment)
        <div id="daftar-submission" class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600">assignment_turned_in</span>
                        Daftar Pengumpulan Mahasiswa
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Terdapat {{ $allSubmissions->count() }} mahasiswa yang mengumpulkan tugas ini.
                    </p>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200">
                        Sudah Dinilai: {{ $allSubmissions->filter(fn($s) => $s->grade !== null)->count() }}
                    </span>
                    <span class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 font-semibold border border-amber-200">
                        Belum Dinilai: {{ $allSubmissions->filter(fn($s) => $s->grade === null)->count() }}
                    </span>
                </div>
            </div>

            @if ($allSubmissions->isEmpty())
                <div class="px-6 py-12 text-center text-slate-400">
                    <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">inbox</span>
                    <p class="text-sm">Belum ada mahasiswa yang mengumpulkan tugas ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-6 py-3.5">#</th>
                                <th class="px-6 py-3.5">Mahasiswa</th>
                                <th class="px-6 py-3.5">Waktu Kumpul</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5">Nilai</th>
                                <th class="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($allSubmissions as $index => $sub)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-6 py-4 text-xs font-medium text-slate-400">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-800">{{ $sub->student->name ?? 'Mahasiswa' }}</p>
                                        <p class="text-xs text-slate-400">{{ $sub->student->nim_nip ?? '-' }} &bull; {{ $sub->student->email ?? '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-600">
                                        {{ $sub->submitted_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($sub->is_late)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Terlambat
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Tepat Waktu
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($sub->grade)
                                            <div class="font-extrabold text-emerald-600 text-base">
                                                {{ $sub->grade->score }}
                                                <span class="text-xs font-normal text-slate-400">/ {{ $assignment->max_score }}</span>
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-500">
                                                Belum Dinilai
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('submissions.show', $sub->id) }}"
                                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm">
                                            <span class="material-symbols-outlined text-[15px]">rate_review</span>
                                            {{ $sub->grade ? 'Lihat / Edit Nilai' : 'Buka & Nilai' }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endcan

</x-layout>
