
<x-layout title="{{ $assignment->title }}">

    @php
        $role = session('demo_role', 'mahasiswa');
        $isPast = $assignment->due_at && now()->isAfter($assignment->due_at);
        $isClosed = $assignment->status === 'closed';

        $badgeClass = match (true) {
            $assignment->status === 'draft' => 'bg-slate-100 text-slate-500',
            $isClosed => 'bg-red-50 text-red-600',
            $isPast => 'bg-orange-50 text-orange-600',
            default => 'bg-emerald-50 text-emerald-700',
        };

        $badgeText = match (true) {
            $assignment->status === 'draft' => 'DRAFT',
            $isClosed => 'DITUTUP',
            $isPast => 'TERLAMBAT',
            default => 'AKTIF',
        };
    @endphp

    <div class="mb-6">
        <a href="{{ route('mata-kuliah.show', ['course' => $assignment->course_id, 'tab' => 'tugas']) }}"
           class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600 transition">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Kembali ke {{ $assignment->course->name }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Kolom kiri: Detail Tugas --}}
        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

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

                {{-- Status pengumpulan --}}
                @if ($role === 'mahasiswa' && $submission)
                    <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="p-2 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-xl">check_circle</span>
                                </span>

                                <div>
                                    <p class="text-sm font-bold text-emerald-900">
                                        Anda telah mengumpulkan tugas ini
                                    </p>
                                    <p class="text-xs text-emerald-700">
                                        Dikirim pada
                                        {{ $submission->submitted_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB
                                    </p>
                                </div>
                            </div>

                            <a href="{{ route('submissions.show', $submission->id) }}"
                               class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition shadow-sm">
                                <span class="material-symbols-outlined text-[15px]">visibility</span>
                                Lihat Pengumpulan
                            </a>
                        </div>
                    </div>

                @elseif (in_array($role, ['dosen', 'admin']) && $allSubmissions->isNotEmpty())
                    <div class="mb-5 p-4 rounded-xl bg-indigo-50 border border-indigo-200">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="p-2 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-xl">rate_review</span>
                                </span>

                                <div>
                                    <p class="text-sm font-bold text-indigo-900">
                                        {{ $allSubmissions->count() }} Pengumpulan Mahasiswa
                                    </p>
                                    <p class="text-xs text-indigo-700">
                                        {{ $allSubmissions->filter(fn ($s) => $s->grade !== null)->count() }} sudah dinilai
                                        &bull;
                                        {{ $allSubmissions->filter(fn ($s) => $s->grade === null)->count() }} belum dinilai
                                    </p>
                                </div>
                            </div>

                            <a href="#daftar-submission"
                               class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm">
                                <span class="material-symbols-outlined text-[15px]">arrow_downward</span>
                                Lihat Pengumpulan
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Informasi tugas --}}
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
                            {{ $assignment->due_at?->format('d M Y') ?? '-' }}
                        </p>
                        <p class="text-slate-400 text-xs">
                            {{ $assignment->due_at?->format('H:i') ?? '-' }} WIB
                        </p>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-slate-500 text-xs mb-1">Nilai Maksimal</p>
                        <p class="font-bold text-indigo-700">
                            {{ number_format($assignment->max_score, 0) }}
                        </p>
                    </div>
                </div>

                @if ($assignment->allow_late)
                    <div class="mb-4 flex items-center gap-2 text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded-xl px-4 py-2">
                        <span class="material-symbols-outlined text-[16px]">info</span>
                        Tugas ini mengizinkan pengumpulan terlambat.
                    </div>
                @endif

                @if ($assignment->instructions)
                    <div>
                        <p class="text-sm font-semibold text-slate-700 mb-2">
                            Instruksi Tugas
                        </p>
                        <div class="prose prose-sm max-w-none text-slate-600 bg-slate-50 rounded-xl p-4 text-sm leading-relaxed whitespace-pre-line">
                            {{ $assignment->instructions }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Kolom kanan: Pengumpulan Mahasiswa --}}
        <div class="space-y-4">

            @if ($role === 'mahasiswa')

                {{-- Ringkasan pengumpulan sebelumnya --}}
                @if ($submission)
                    <div class="bg-white border border-emerald-200 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                            <h2 class="font-bold text-emerald-800 text-sm">
                                Sudah Dikumpulkan
                            </h2>
                        </div>

                        <div class="space-y-4 text-sm">
                            <div>
                                <p class="text-xs text-slate-500 mb-1">Nama Berkas</p>
                                <p class="font-semibold text-slate-800 break-all">
                                    {{ $submission->original_name ?? ($submission->file_path ? basename($submission->file_path) : 'Tidak ada berkas') }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500 mb-1">Ukuran Berkas</p>
                                <p class="text-slate-700">
                                    {{ $submission->formatted_file_size ?? ($submission->file_size !== null ? number_format($submission->file_size / 1024, 1) . ' KB' : '-') }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500 mb-1">Waktu Pengumpulan</p>
                                <p class="text-slate-700">
                                    {{ $submission->submitted_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500 mb-1">Status Pengumpulan</p>

                                @if ($submission->is_late)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-red-50 text-red-700 border border-red-200 text-xs font-semibold">
                                        <span class="material-symbols-outlined text-sm">warning</span>
                                        Terlambat
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold">
                                        <span class="material-symbols-outlined text-sm">check_circle</span>
                                        Tepat Waktu
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if ($submission->file_path)
                            <a href="{{ route('submissions.download', $submission) }}"
                               class="mt-5 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold text-sm hover:bg-indigo-700 transition shadow-sm">
                                <span class="material-symbols-outlined text-lg">download</span>
                                Unduh Berkas
                            </a>
                        @else
                            <p class="mt-4 text-xs text-slate-500">
                                Tidak ada berkas yang tersedia untuk diunduh.
                            </p>
                        @endif

                        @if ($submission->grade)
                            <div class="mt-4 pt-4 border-t border-slate-200">
                                <p class="text-xs text-slate-500 mb-1">Nilai</p>
                                <p class="text-2xl font-bold text-indigo-700">
                                    {{ number_format($submission->grade->score, 1) }}
                                    <span class="text-sm font-normal text-slate-500">
                                        / {{ number_format($assignment->max_score, 0) }}
                                    </span>
                                </p>
                            </div>
                        @else
                            <p class="text-xs text-slate-400 mt-3">
                                Belum dinilai.
                            </p>
                        @endif

                        <a href="{{ route('submissions.show', $submission->id) }}"
                           class="mt-3 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                            Lihat Lembar Pengumpulan
                        </a>
                    </div>

                    @if ($submission->note)
                        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                            <p class="text-sm font-semibold text-slate-700 mb-2">
                                Catatan Anda
                            </p>
                            <p class="text-sm text-slate-600 whitespace-pre-line">{{ $submission->note }}</p>
                        </div>
                    @endif
                @endif

                {{-- Form pengumpulan atau revisi --}}
                @if (!$submission || !$submission->grade)
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <h2 class="font-bold text-slate-900 mb-1">
                            {{ $submission ? 'Perbarui Jawaban' : 'Kumpulkan Tugas' }}
                        </h2>

                        <p class="text-xs text-slate-500 mb-4">
                            Unggah berkas tugas dan tambahkan jawaban atau catatan jika diperlukan.
                        </p>

                        @if (session('success'))
                            <div class="mb-3 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="mb-3 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if (($isClosed || $isPast) && !$assignment->allow_late)
                            <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                                Tugas ini sudah ditutup atau melewati batas tenggat waktu dan tidak menerima pengumpulan baru.
                            </div>
                        @else
                            <form action="{{ route('tugas.submit', $assignment->id) }}"
                                  method="POST"
                                  enctype="multipart/form-data">
                                @csrf

                                <div class="mb-4">
                                    <label for="file" class="block text-sm font-semibold text-slate-700 mb-2">
                                        Berkas Pengumpulan <span class="text-red-500">*</span>
                                    </label>

                                    <div class="p-4 rounded-xl border border-dashed border-indigo-300 bg-indigo-50/50">
                                        <input
                                            type="file"
                                            id="file"
                                            name="file"
                                            {{ $submission ? '' : 'required' }}
                                            accept=".pdf,.doc,.docx,.zip"
                                            class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-100 file:text-indigo-700 file:font-semibold hover:file:bg-indigo-200 file:cursor-pointer"
                                        >

                                        <p class="mt-2 text-xs text-slate-500">
                                            Format berkas: PDF, DOCX, ZIP (Maksimal 10 MB)
                                        </p>

                                        @if ($submission)
                                            <p class="mt-1 text-xs text-slate-500">
                                                Kosongkan jika tidak ingin mengganti berkas, jika controller mendukung pembaruan tanpa unggah file.
                                            </p>
                                        @endif
                                    </div>

                                    @error('file')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="note" class="block text-sm font-semibold text-slate-700 mb-2">
                                        Jawaban / Catatan
                                    </label>

                                    <textarea
                                        id="note"
                                        name="note"
                                        rows="6"
                                        placeholder="Tuliskan jawaban, tautan, atau catatan Anda di sini..."
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none"
                                    >{{ old('note', $submission?->note) }}</textarea>

                                    @error('note')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                @if ($isPast && $assignment->allow_late)
                                    <div class="mb-3 flex items-center gap-2 text-xs text-orange-600 bg-orange-50 border border-orange-200 rounded-xl px-3 py-2">
                                        <span class="material-symbols-outlined text-[14px]">warning</span>
                                        Pengumpulan ini akan dicatat sebagai <strong>terlambat</strong>.
                                    </div>
                                @endif

                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold text-sm hover:bg-indigo-700 transition shadow-sm">
                                    <span class="material-symbols-outlined text-[18px]">send</span>
                                    {{ $submission ? 'Perbarui Pengumpulan' : 'Kumpulkan Sekarang' }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endif

            @elseif ($role === 'dosen')
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-700 mb-1">Statistik Pengumpulan</p>
                    <p class="text-3xl font-bold text-indigo-700">
                        {{ $allSubmissions->count() }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">mahasiswa telah mengumpulkan</p>

                    <a href="#daftar-submission"
                       class="mt-4 w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm">
                        <span class="material-symbols-outlined text-[15px]">arrow_downward</span>
                        Lihat Daftar Nilai
                    </a>
                </div>

            @elseif ($role === 'admin')
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-700 mb-1">Total Pengumpulan</p>
                    <p class="text-3xl font-bold text-indigo-700">
                        {{ $allSubmissions->count() }}
                    </p>

                    <a href="#daftar-submission"
                       class="mt-4 w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm">
                        <span class="material-symbols-outlined text-[15px]">arrow_downward</span>
                        Lihat Pengumpulan
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Daftar Pengumpulan untuk Dosen dan Admin --}}
    @if (in_array($role, ['dosen', 'admin']))
        <div id="daftar-submission" class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

            <div class="px-6 py-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600">assignment_turned_in</span>
                        Daftar Pengumpulan Mahasiswa
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Terdapat {{ $allSubmissions->count() }} pengumpulan untuk tugas ini.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200">
                        Sudah Dinilai: {{ $allSubmissions->filter(fn ($s) => $s->grade !== null)->count() }}
                    </span>
                    <span class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 font-semibold border border-amber-200">
                        Belum Dinilai: {{ $allSubmissions->filter(fn ($s) => $s->grade === null)->count() }}
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
                                        <p class="font-bold text-slate-800">
                                            {{ $sub->student->name ?? 'Mahasiswa' }}
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            {{ $sub->student->nim_nip ?? '-' }} &bull;
                                            {{ $sub->student->email ?? '-' }}
                                        </p>
                                    </td>

                                    <td class="px-6 py-4 text-xs text-slate-600">
                                        {{ $sub->submitted_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB
                                    </td>

                                    <td class="px-6 py-4">
                                        @if ($sub->is_late)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                                Terlambat
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Tepat Waktu
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4">
                                        @if ($sub->grade)
                                            <div class="font-extrabold text-emerald-600 text-base">
                                                {{ $sub->grade->score }}
                                                <span class="text-xs font-normal text-slate-400">
                                                    / {{ $assignment->max_score }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-500">
                                                Belum Dinilai
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex flex-col items-stretch gap-2 min-w-[140px]">
                                            @if ($sub->file_path)
                                                <a href="{{ route('submissions.download', $sub) }}"
                                                   class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm">
                                                    <span class="material-symbols-outlined text-[15px]">download</span>
                                                    Unduh Berkas
                                                </a>
                                            @endif

                                            <a href="{{ route('submissions.show', $sub->id) }}"
                                               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl {{ $sub->grade ? 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' : 'bg-emerald-600 hover:bg-emerald-700 text-white' }} font-semibold text-xs transition shadow-sm">
                                                <span class="material-symbols-outlined text-[15px]">
                                                    {{ $sub->grade ? 'visibility' : 'rate_review' }}
                                                </span>
                                                {{ $sub->grade ? 'Lihat / Edit Nilai' : 'Buka & Nilai' }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

</x-layout>
