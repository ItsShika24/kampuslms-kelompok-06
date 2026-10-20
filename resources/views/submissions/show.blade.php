
<x-layout title="Detail Pengumpulan Tugas - EduKampus">

    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Breadcrumb --}}
        <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition">
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

            <span class="text-slate-800 font-semibold">
                Pengumpulan #{{ $submission->id }}
            </span>
        </div>

        {{-- Detail pengumpulan --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">

            {{-- Header dan status --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        @if ($submission->is_late)
                            <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-red-50 text-red-700 border border-red-200">
                                Terlambat
                            </span>
                        @else
                            <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Tepat Waktu
                            </span>
                        @endif

                        <span class="text-xs text-slate-400">
                            ID Submission: {{ $submission->id }}
                        </span>
                    </div>

                    <h1 class="text-2xl font-bold text-slate-900">
                        {{ $submission->assignment->title }}
                    </h1>

                    <p class="text-sm text-slate-500 mt-1">
                        Mata Kuliah:
                        <strong class="text-slate-700">
                            {{ $submission->assignment->course->name }}
                            ({{ $submission->assignment->course->code }})
                        </strong>
                    </p>
                </div>

                <div class="sm:text-right">
                    @if ($submission->grade)
                        <div class="text-3xl font-extrabold text-emerald-600">
                            {{ $submission->grade->score }}
                            <span class="text-sm text-slate-400 font-normal">
                                / {{ $submission->assignment->max_score }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Sudah Dinilai</p>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm font-semibold">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Belum Dinilai
                        </span>
                    @endif
                </div>
            </div>

            {{-- Informasi mahasiswa dan waktu --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-6 border-b border-slate-100 text-sm">
                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold">
                        Mahasiswa
                    </span>
                    <p class="font-bold text-slate-800 mt-1 text-base">
                        {{ $submission->student->name ?? 'Mahasiswa' }}
                    </p>
                    <p class="text-slate-500 text-xs">
                        {{ $submission->student->nim_nip ?? '-' }}
                        &bull;
                        {{ $submission->student->email ?? '-' }}
                    </p>
                </div>

                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold">
                        Waktu Pengumpulan
                    </span>
                    <p class="font-semibold text-slate-800 mt-1">
                        {{ $submission->submitted_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB
                    </p>
                    <p class="text-xs text-slate-500">
                        Batas Waktu:
                        {{ $submission->assignment->due_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB
                    </p>
                </div>
            </div>

            {{-- Jawaban atau catatan mahasiswa --}}
            <div class="pt-6">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-3">
                    Catatan / Jawaban Mahasiswa
                </h2>

                <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 text-sm whitespace-pre-wrap leading-relaxed">
                    {{ $submission->note ?: '(Tidak ada catatan terlampir)' }}
                </div>
            </div>

            {{-- Informasi berkas dan tombol unduh --}}
            @if ($submission->file_path)
                <div class="mt-6 rounded-xl border border-indigo-200 bg-indigo-50/70 p-5 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="flex items-center justify-center shrink-0 w-12 h-12 rounded-xl bg-indigo-100">
                                <span class="material-symbols-outlined text-indigo-600 text-2xl">
                                    description
                                </span>
                            </div>

                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">
                                    Berkas Jawaban Mahasiswa
                                </p>

                                <p class="text-sm font-bold text-slate-800 break-all">
                                    {{ $submission->original_name ?? basename($submission->file_path) }}
                                </p>

                                <p class="text-xs text-slate-500 mt-1">
                                    {{ $submission->formatted_file_size ?? ($submission->file_size !== null ? number_format($submission->file_size / 1024, 1) . ' KB' : 'Ukuran tidak tersedia') }}
                                </p>
                            </div>
                        </div>

                        <a href="{{ route('submissions.download', $submission) }}"
                           class="inline-flex shrink-0 items-center justify-center gap-2 px-5 py-3 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition shadow-sm">
                            <span class="material-symbols-outlined text-lg">download</span>
                            Unduh Berkas
                        </a>
                    </div>
                </div>
            @else
                <div class="mt-6 flex items-center gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50 text-slate-500">
                    <span class="material-symbols-outlined">attach_file</span>
                    <p class="text-sm">Tidak ada berkas yang dilampirkan pada pengumpulan ini.</p>
                </div>
            @endif
        </div>

        {{-- Form penilaian untuk pengguna yang berwenang --}}
        @can('grade', $submission)
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900 mb-1 flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-600">rate_review</span>
                    Form Penilaian Pengajar
                </h2>

                <p class="text-xs text-slate-500 mb-6">
                    Beri skor dan umpan balik kepada mahasiswa untuk pengumpulan tugas ini.
                </p>

                <form action="{{ route('submissions.grade', $submission->id) }}"
                      method="POST"
                      class="space-y-4">
                    @csrf

                    <div>
                        <label for="score" class="block text-sm font-semibold text-slate-700 mb-1">
                            Nilai (Maksimal: {{ $submission->assignment->max_score }})
                        </label>

                        <input
                            type="number"
                            name="score"
                            id="score"
                            step="0.01"
                            min="0"
                            max="{{ $submission->assignment->max_score }}"
                            value="{{ old('score', optional($submission->grade)->score) }}"
                            class="w-full sm:w-48 px-4 py-2.5 rounded-xl border border-slate-300 text-slate-800 font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            required
                        >

                        @error('score')
                            <p class="text-xs text-rose-600 mt-1 font-semibold">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="feedback" class="block text-sm font-semibold text-slate-700 mb-1">
                            Umpan Balik (Feedback)
                        </label>

                        <textarea
                            name="feedback"
                            id="feedback"
                            rows="4"
                            placeholder="Tuliskan catatan evaluasi atau alasan pemberian nilai..."
                            class="w-full px-4 py-3 rounded-xl border border-slate-300 text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
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
                            class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm transition shadow-sm"
                        >
                            <span class="material-symbols-outlined text-lg">save</span>
                            Simpan Nilai
                        </button>
                    </div>
                </form>
            </div>
        @elseif ($submission->grade && $submission->grade->feedback)
            {{-- Feedback yang dapat dilihat pengguna tanpa hak menilai --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-600">comment</span>
                    Umpan Balik dari Pengajar
                </h2>

                <div class="p-4 rounded-xl bg-indigo-50/50 border border-indigo-100 text-slate-700 text-sm leading-relaxed whitespace-pre-line">
                    {{ $submission->grade->feedback }}
                </div>
            </div>
        @endcan

    </div>

</x-layout>
