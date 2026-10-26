<x-layout title="Detail Pengumpulan Tugas - EduKampus">

    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Breadcrumb Navigation --}}
        <nav class="flex flex-wrap items-center gap-2 text-xs font-medium text-slate-400">
            <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px]">home</span>
                Dashboard
            </a>
            <span>/</span>
            <a href="{{ route('mata-kuliah.show', ['course' => $submission->assignment->course_id, 'tab' => 'tugas']) }}"
               class="hover:text-indigo-600 transition">
                {{ $submission->assignment->course->name }}
            </a>
            <span>/</span>
            <a href="{{ route('tugas.show', $submission->assignment_id) }}"
               class="hover:text-indigo-600 transition">
                {{ $submission->assignment->title }}
            </a>
            <span>/</span>
            <span class="text-slate-800 font-bold">
                Pengumpulan #{{ $submission->id }}
            </span>
        </nav>

        {{-- Kartu Detail Pengumpulan --}}
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6">

            {{-- Header dan Status Nilai --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        @if ($submission->is_late)
                            <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Terlambat
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Tepat Waktu
                            </span>
                        @endif

                        <span class="text-xs font-mono text-slate-400 bg-slate-100 px-2.5 py-0.5 rounded-md">
                            ID: #{{ $submission->id }}
                        </span>
                    </div>

                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                        {{ $submission->assignment->title }}
                    </h1>

                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Mata Kuliah:
                        <strong class="text-slate-700 font-semibold">
                            {{ $submission->assignment->course->name }}
                            ({{ $submission->assignment->course->code }})
                        </strong>
                    </p>
                </div>

                {{-- Status / Nilai --}}
                <div class="sm:text-right bg-slate-50/80 sm:bg-transparent p-4 sm:p-0 rounded-2xl border sm:border-0 border-slate-100">
                    @if ($submission->grade)
                        <div class="flex sm:flex-col items-center sm:items-end justify-between gap-1">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Perolehan Nilai</span>
                            <div class="text-3xl sm:text-4xl font-black text-emerald-600 tracking-tight">
                                {{ $submission->grade->score }}
                                <span class="text-sm text-slate-400 font-semibold">
                                    / {{ $submission->assignment->max_score }}
                                </span>
                            </div>
                        </div>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            Menunggu Penilaian Pengajar
                        </span>
                    @endif
                </div>
            </div>

            {{-- Informasi Mahasiswa dan Waktu --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 py-2 border-b border-slate-100 text-sm">
                <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-100">
                    <span class="text-[11px] text-slate-400 uppercase tracking-wider font-bold">
                        Mahasiswa Pengumpul
                    </span>
                    <p class="font-bold text-slate-900 mt-1 text-base">
                        {{ $submission->student->name ?? 'Mahasiswa' }}
                    </p>
                    <p class="text-slate-500 text-xs mt-0.5 font-mono">
                        {{ $submission->student->nim_nip ?? '-' }} &bull; {{ $submission->student->email ?? '-' }}
                    </p>
                </div>

                <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-100">
                    <span class="text-[11px] text-slate-400 uppercase tracking-wider font-bold">
                        Waktu Pengiriman
                    </span>
                    <p class="font-bold text-slate-900 mt-1 text-sm">
                        {{ $submission->submitted_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Tenggat Tugas:
                        <strong class="text-slate-700">{{ $submission->assignment->due_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB</strong>
                    </p>
                </div>
            </div>

            {{-- Jawaban / Catatan Mahasiswa --}}
            <div class="space-y-2">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                    Catatan / Jawaban Mahasiswa
                </h2>

                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 text-slate-800 text-sm whitespace-pre-wrap leading-relaxed">
                    {{ $submission->note ?: '(Tidak ada catatan terlampir)' }}
                </div>
            </div>

            {{-- Berkas Lampiran & Tombol Unduh --}}
            @if ($submission->file_path)
                <div class="rounded-2xl border border-indigo-100 bg-indigo-50/40 p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3.5 min-w-0">
                            <div class="flex items-center justify-center shrink-0 w-12 h-12 rounded-2xl bg-indigo-600 text-white shadow-sm">
                                <span class="material-symbols-outlined text-2xl">
                                    description
                                </span>
                            </div>

                            <div class="min-w-0">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 bg-indigo-100/70 px-2 py-0.5 rounded-md">
                                    Berkas Jawaban Mahasiswa
                                </span>

                                <p class="text-sm font-bold text-slate-900 break-all mt-1">
                                    {{ $submission->original_name ?? basename($submission->file_path) }}
                                </p>

                                <p class="text-xs text-slate-500 mt-0.5 font-medium">
                                    {{ $submission->formatted_file_size ?? ($submission->file_size !== null ? number_format($submission->file_size / 1024, 1) . ' KB' : 'Ukuran berkas') }}
                                </p>
                            </div>
                        </div>

                        <a href="{{ route('submissions.download', $submission) }}"
                           class="inline-flex shrink-0 items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-sm">
                            <span class="material-symbols-outlined text-[17px]">download</span>
                            Unduh Berkas
                        </a>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3 p-4 rounded-2xl border border-slate-200/80 bg-slate-50 text-slate-500 text-xs">
                    <span class="material-symbols-outlined text-slate-400">attach_file</span>
                    <p>Tidak ada berkas yang dilampirkan pada pengumpulan tugas ini.</p>
                </div>
            @endif
        </div>

        {{-- Form Penilaian (Dosen & Admin) --}}
        @can('grade', $submission)
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-4">
                <div class="border-b border-slate-100 pb-4">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600">rate_review</span>
                        Formulir Penilaian Pengajar
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Berikan skor numerik dan evaluasi umpan balik kepada mahasiswa untuk pengumpulan ini.
                    </p>
                </div>

                <form action="{{ route('submissions.grade', $submission->id) }}"
                      method="POST"
                      class="space-y-4">
                    @csrf

                    <div>
                        <label for="score" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Skor Nilai (Maksimal: {{ $submission->assignment->max_score }})
                        </label>

                        <div class="relative w-full sm:w-48">
                            <input
                                type="number"
                                name="score"
                                id="score"
                                step="0.01"
                                min="0"
                                max="{{ $submission->assignment->max_score }}"
                                value="{{ old('score', optional($submission->grade)->score) }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-slate-900 font-extrabold text-base focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs"
                                placeholder="0 - 100"
                                required
                            >
                        </div>

                        @error('score')
                            <p class="text-xs text-rose-600 mt-1 font-semibold">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="feedback" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Catatan &amp; Umpan Balik (Feedback)
                        </label>

                        <textarea
                            name="feedback"
                            id="feedback"
                            rows="4"
                            placeholder="Tuliskan catatan evaluasi, kelebihan jawaban, atau saran perbaikan untuk mahasiswa..."
                            class="w-full px-4 py-3 rounded-xl border border-slate-300 text-slate-800 text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs"
                        >{{ old('feedback', optional($submission->grade)->feedback) }}</textarea>

                        @error('feedback')
                            <p class="text-xs text-rose-600 mt-1 font-semibold">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition shadow-sm"
                        >
                            <span class="material-symbols-outlined text-[17px]">save</span>
                            Simpan &amp; Publikasikan Nilai
                        </button>
                    </div>
                </form>
            </div>
        @elseif ($submission->grade && $submission->grade->feedback)
            {{-- Feedback dari Dosen (Tampilan Mahasiswa) --}}
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-600 text-[18px]">comment</span>
                    Umpan Balik dari Dosen Pengampu
                </h2>

                <div class="p-5 rounded-2xl bg-indigo-50/50 border border-indigo-100 text-slate-800 text-sm leading-relaxed whitespace-pre-line font-medium">
                    {{ $submission->grade->feedback }}
                </div>
            </div>
        @endcan

    </div>

</x-layout>
