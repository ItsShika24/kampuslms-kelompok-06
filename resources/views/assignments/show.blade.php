<x-layout title="{{ $assignment->title }}">

    @php
        $role = session('demo_role', 'mahasiswa');
        $isPast = $assignment->due_at && now()->isAfter($assignment->due_at);
        $isClosed = $assignment->status === 'closed';

        $badgeClass = match (true) {
            $assignment->status === 'draft' => 'bg-slate-100 text-slate-600 border-slate-200',
            $isClosed => 'bg-rose-50 text-rose-700 border-rose-200',
            $isPast => 'bg-amber-50 text-amber-700 border-amber-200',
            default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        };

        $badgeText = match (true) {
            $assignment->status === 'draft' => 'DRAFT',
            $isClosed => 'DITUTUP',
            $isPast => 'LEWAT TENGGAT',
            default => 'AKTIF',
        };
    @endphp

    <div class="space-y-6">

        {{-- Breadcrumb --}}
        <div>
            <a href="{{ route('mata-kuliah.show', ['course' => $assignment->course_id, 'tab' => 'tugas']) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 transition">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                Kembali ke Mata Kuliah: {{ $assignment->course->name }}
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Kolom kiri: Detail Tugas --}}
            <div class="lg:col-span-2 space-y-6">

                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 space-y-5">

                    <div class="space-y-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $badgeClass }}">
                                {{ $badgeText }}
                            </span>
                            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-lg">
                                Maks. {{ number_format($assignment->max_score, 0) }} Poin
                            </span>
                            @if ($assignment->allow_late)
                                <span class="text-xs font-semibold text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-lg border border-amber-200">
                                    Toleransi Terlambat
                                </span>
                            @endif
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                            {{ $assignment->title }}
                        </h1>
                    </div>

                    {{-- Banner Notifikasi Status Pengumpulan Mahasiswa --}}
                    @if ($role === 'mahasiswa' && $submission)
                        <div class="p-4 sm:p-5 rounded-2xl bg-emerald-50/80 border border-emerald-200">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-start sm:items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-2xl">check_circle</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-emerald-900">
                                            Anda telah mengumpulkan tugas ini
                                        </p>
                                        <p class="text-xs text-emerald-700 mt-0.5">
                                            Dikirim pada {{ $submission->submitted_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB
                                        </p>
                                    </div>
                                </div>

                                <a href="{{ route('submissions.show', $submission->id) }}"
                                   class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition shadow-sm shrink-0">
                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                    Buka Lembar Pengumpulan
                                </a>
                            </div>
                        </div>

                    @elseif (in_array($role, ['dosen', 'admin']) && $allSubmissions->isNotEmpty())
                        <div class="p-4 sm:p-5 rounded-2xl bg-indigo-50/80 border border-indigo-200">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-start sm:items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-2xl">rate_review</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-indigo-900">
                                            {{ $allSubmissions->count() }} Berkas Pengumpulan Mahasiswa
                                        </p>
                                        <p class="text-xs text-indigo-700 mt-0.5">
                                            {{ $allSubmissions->filter(fn ($s) => $s->grade !== null)->count() }} telah dinilai &bull;
                                            {{ $allSubmissions->filter(fn ($s) => $s->grade === null)->count() }} menunggu penilaian
                                        </p>
                                    </div>
                                </div>

                                <a href="#daftar-submission"
                                   class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm shrink-0">
                                    <span class="material-symbols-outlined text-[15px]">arrow_downward</span>
                                    Periksa &amp; Nilai
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- Kotak Info Tugas --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Mata Kuliah</span>
                            <p class="font-bold text-slate-800 text-xs mt-1 truncate">
                                {{ $assignment->course->name }}
                            </p>
                            <p class="text-[11px] text-slate-500 font-mono mt-0.5">
                                {{ $assignment->course->code }}
                            </p>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Batas Waktu</span>
                            <p class="font-bold {{ $isPast ? 'text-amber-700' : 'text-slate-800' }} text-xs mt-1">
                                {{ $assignment->due_at?->translatedFormat('d M Y') ?? '-' }}
                            </p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Pukul {{ $assignment->due_at?->format('H:i') ?? '-' }} WIB
                            </p>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Nilai Maksimal</span>
                            <p class="font-extrabold text-indigo-600 text-lg mt-0.5">
                                {{ number_format($assignment->max_score, 0) }} Poin
                            </p>
                            <p class="text-[11px] text-slate-400">Skor skala penuh</p>
                        </div>
                    </div>

                    {{-- Instruksi Tugas --}}
                    @if ($assignment->instructions)
                        <div class="space-y-2 pt-2">
                            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                Petunjuk &amp; Instruksi Pengerjaan
                            </h2>
                            <div class="text-slate-700 bg-slate-50/70 border border-slate-200/80 rounded-2xl p-5 text-sm leading-relaxed whitespace-pre-line">
                                {{ $assignment->instructions }}
                            </div>
                        </div>
                    @endif

                </div>
            </div>

            {{-- Kolom kanan: Lembar Pengumpulan Mahasiswa --}}
            <div class="space-y-6">

                @if ($role === 'mahasiswa')

                    {{-- Ringkasan Pengumpulan Sebelumnya --}}
                    @if ($submission)
                        <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-sm space-y-4">
                            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                                <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                                <h2 class="font-bold text-slate-900 text-sm">
                                    Berkas Pengumpulan Anda
                                </h2>
                            </div>

                            <div class="space-y-3 text-xs">
                                <div>
                                    <span class="text-slate-400 uppercase tracking-wider font-bold">Nama Berkas</span>
                                    <p class="font-bold text-slate-800 break-all mt-0.5">
                                        {{ $submission->original_name ?? ($submission->file_path ? basename($submission->file_path) : 'Tidak ada berkas') }}
                                    </p>
                                </div>

                                <div>
                                    <span class="text-slate-400 uppercase tracking-wider font-bold">Waktu Dikirim</span>
                                    <p class="text-slate-700 font-medium mt-0.5">
                                        {{ $submission->submitted_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB
                                    </p>
                                </div>

                                <div>
                                    <span class="text-slate-400 uppercase tracking-wider font-bold">Ketepatan Pengumpulan</span>
                                    <div class="mt-1">
                                        @if ($submission->is_late)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-bold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Terlambat
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Tepat Waktu
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if ($submission->grade)
                                    <div class="pt-3 border-t border-slate-100">
                                        <span class="text-slate-400 uppercase tracking-wider font-bold">Perolehan Nilai</span>
                                        <div class="text-3xl font-black text-emerald-600 mt-1">
                                            {{ number_format($submission->grade->score, 1) }}
                                            <span class="text-xs font-normal text-slate-400">
                                                / {{ number_format($assignment->max_score, 0) }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if ($submission->file_path)
                                <a href="{{ route('submissions.download', $submission) }}"
                                   class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 text-white font-bold text-xs hover:bg-indigo-700 transition shadow-sm">
                                    <span class="material-symbols-outlined text-[16px]">download</span>
                                    Unduh Berkas Saya
                                </a>
                            @endif

                            <a href="{{ route('submissions.show', $submission->id) }}"
                               class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                                <span class="material-symbols-outlined text-[15px]">visibility</span>
                                Lihat Detail Penilaian
                            </a>
                        </div>
                    @endif

                    {{-- Form Pengumpulan atau Revisi Tugas --}}
                    @if (!$submission || !$submission->grade)
                        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 space-y-4">
                            <div>
                                <h2 class="font-bold text-slate-900 text-base">
                                    {{ $submission ? 'Perbarui Jawaban Tugas' : 'Form Pengumpulan Tugas' }}
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Unggah berkas tugas Anda dan berikan catatan bila diperlukan.
                                </p>
                            </div>

                            @if (($isClosed || $isPast) && !$assignment->allow_late)
                                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-medium">
                                    Tugas ini sudah ditutup atau melewati batas tenggat waktu dan tidak mengizinkan pengumpulan susulan.
                                </div>
                            @else
                                <form action="{{ route('tugas.submit', $assignment->id) }}"
                                      method="POST"
                                      enctype="multipart/form-data"
                                      class="space-y-4">
                                    @csrf

                                    <div>
                                        <label for="file" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                            Berkas Tugas <span class="text-rose-500">*</span>
                                        </label>

                                        <div class="p-4 rounded-2xl border-2 border-dashed border-indigo-200 bg-indigo-50/30 hover:bg-indigo-50/60 transition">
                                            <input
                                                type="file"
                                                id="file"
                                                name="file"
                                                {{ $submission ? '' : 'required' }}
                                                accept=".pdf,.doc,.docx,.zip"
                                                class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-indigo-600 file:text-white file:font-semibold hover:file:bg-indigo-700 file:cursor-pointer"
                                            >
                                            <p class="mt-2 text-[11px] text-slate-500">
                                                Format yang didukung: PDF, DOC, DOCX, ZIP (Maksimal 10 MB)
                                            </p>
                                        </div>

                                        @error('file')
                                            <p class="text-xs text-rose-600 mt-1 font-semibold">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="note" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                            Catatan / Tautan Tambahan
                                        </label>

                                        <textarea
                                            id="note"
                                            name="note"
                                            rows="4"
                                            placeholder="Tuliskan catatan pengerjaan, tautan GitHub/Google Drive, atau pesan ke dosen..."
                                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 resize-none shadow-2xs"
                                        >{{ old('note', $submission?->note) }}</textarea>

                                        @error('note')
                                            <p class="text-xs text-rose-600 mt-1 font-semibold">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    @if ($isPast && $assignment->allow_late)
                                        <div class="flex items-center gap-2 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 font-medium">
                                            <span class="material-symbols-outlined text-[15px] text-amber-600">warning</span>
                                            Tenggat telah lewat. Pengumpulan akan dicatat <strong>Terlambat</strong>.
                                        </div>
                                    @endif

                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition shadow-sm">
                                        <span class="material-symbols-outlined text-[17px]">send</span>
                                        {{ $submission ? 'Perbarui Pengumpulan' : 'Kirim Jawaban Tugas' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif

                @else
                    {{-- Side card untuk Dosen / Admin --}}
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-sm space-y-4">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Rekap Pengumpulan</span>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-black text-slate-900 tracking-tight">{{ $allSubmissions->count() }}</span>
                            <span class="text-xs font-semibold text-slate-500">mahasiswa mengumpulkan</span>
                        </div>
                        <a href="#daftar-submission"
                           class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition shadow-sm">
                            <span class="material-symbols-outlined text-[16px]">rate_review</span>
                            Buka Daftar Nilai
                        </a>
                    </div>
                @endif

            </div>
        </div>

        {{-- Daftar Pengumpulan untuk Dosen dan Admin --}}
        @if (in_array($role, ['dosen', 'admin']))
            <div id="daftar-submission" class="mt-8 bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-indigo-600">assignment_turned_in</span>
                            Daftar Pengumpulan Mahasiswa
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Terdapat {{ $allSubmissions->count() }} pengumpulan tugas dari mahasiswa terdaftar.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">
                            Sudah Dinilai: {{ $allSubmissions->filter(fn ($s) => $s->grade !== null)->count() }}
                        </span>
                        <span class="px-3 py-1 rounded-full bg-amber-50 text-amber-700 font-bold border border-amber-200">
                            Belum Dinilai: {{ $allSubmissions->filter(fn ($s) => $s->grade === null)->count() }}
                        </span>
                    </div>
                </div>

                @if ($allSubmissions->isEmpty())
                    <div class="px-6 py-12 text-center text-slate-400">
                        <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">inbox</span>
                        <p class="text-sm font-bold text-slate-700">Belum ada pengumpulan mahasiswa</p>
                        <p class="text-xs text-slate-400 mt-0.5">Mahasiswa belum mengumpulkan berkas tugas pada penugasan ini.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-3.5">#</th>
                                    <th class="px-6 py-3.5">Mahasiswa</th>
                                    <th class="px-6 py-3.5">Waktu Kumpul</th>
                                    <th class="px-6 py-3.5 text-center">Status</th>
                                    <th class="px-6 py-3.5 text-center">Nilai</th>
                                    <th class="px-6 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($allSubmissions as $index => $sub)
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 text-xs font-medium text-slate-400">
                                            {{ $index + 1 }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-slate-900">
                                                {{ $sub->student->name ?? 'Mahasiswa' }}
                                            </p>
                                            <p class="text-xs text-slate-400 font-mono mt-0.5">
                                                {{ $sub->student->nim_nip ?? '-' }} &bull; {{ $sub->student->email ?? '-' }}
                                            </p>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-600 font-medium">
                                            {{ $sub->submitted_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @if ($sub->is_late)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                    Terlambat
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    Tepat Waktu
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @if ($sub->grade)
                                                <div class="inline-flex items-baseline gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-extrabold text-xs border border-emerald-200">
                                                    {{ $sub->grade->score }}
                                                    <span class="text-[10px] font-normal text-emerald-600">/ {{ $assignment->max_score }}</span>
                                                </div>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">
                                                    Belum Dinilai
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-1.5">
                                            @if ($sub->file_path)
                                                <a href="{{ route('submissions.download', $sub) }}"
                                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                                                    <span class="material-symbols-outlined text-[15px]">download</span>
                                                    Unduh
                                                </a>
                                            @endif

                                            <a href="{{ route('submissions.show', $sub->id) }}"
                                               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl {{ $sub->grade ? 'bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200' : 'bg-indigo-600 hover:bg-indigo-700 text-white' }} font-bold text-xs transition shadow-sm">
                                                <span class="material-symbols-outlined text-[15px]">
                                                    {{ $sub->grade ? 'visibility' : 'rate_review' }}
                                                </span>
                                                {{ $sub->grade ? 'Lihat / Edit' : 'Buka & Nilai' }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

    </div>

</x-layout>
