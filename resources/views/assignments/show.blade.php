<x-layout title="{{ $assignment->title }}">

    @php $role = session('demo_role', 'mahasiswa'); @endphp

    {{-- Kembali --}}
    <div class="mb-6">
        <a href="{{ route('mata-kuliah.show', $assignment->course_id) }}"
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
                </div>

            @elseif ($role === 'admin')
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
                    <p class="text-sm font-semibold text-slate-700 mb-1">Total Submission</p>
                    <p class="text-3xl font-bold text-indigo-700">
                        {{ $assignment->submissions()->count() }}
                    </p>
                </div>
            @endif

        </div>

    </div>

</x-layout>
