<x-layout title="{{ $course->name }}">

    @php $role = session('demo_role', 'mahasiswa'); @endphp

    <div class="space-y-6">

        {{-- ── BREADCRUMB & HERO HEADER ──────────────────────────────── --}}
        <div>
            <a href="{{ route('mata-kuliah.index') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 transition mb-3">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                Kembali ke Katalog Mata Kuliah
            </a>

            <div class="relative rounded-3xl overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-indigo-900 border border-indigo-800/40 p-6 sm:p-8 text-white shadow-lg">
                <div class="absolute -top-12 -right-12 w-64 h-64 rounded-full bg-indigo-500/15 blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-start justify-between gap-6">
                    <div class="space-y-3 max-w-3xl">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <span class="px-3 py-1 rounded-xl bg-white/15 text-white font-extrabold text-xs tracking-wider border border-white/20">
                                {{ $course->code }}
                            </span>
                            @php
                                $statusBadge = match($course->status) {
                                    'active'   => 'bg-emerald-500/20 text-emerald-300 border-emerald-400/30',
                                    'archived' => 'bg-slate-500/20 text-slate-300 border-slate-400/30',
                                    default    => 'bg-amber-500/20 text-amber-300 border-amber-400/30',
                                };
                            @endphp
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $statusBadge }}">
                                {{ ucfirst($course->status) }}
                            </span>
                            <span class="text-xs text-slate-300 font-medium">
                                &bull;&nbsp; Semester 5 (Genap)
                            </span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                            {{ $course->name }}
                        </h1>

                        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-300 pt-1">
                            <span class="flex items-center gap-1.5 font-medium">
                                <span class="material-symbols-outlined text-[16px] text-indigo-400">person</span>
                                Dosen: <strong class="text-white">{{ $course->lecturer->name ?? 'Belum Ditentukan' }}</strong>
                            </span>
                            <span>&bull;</span>
                            <span class="flex items-center gap-1.5 font-medium">
                                <span class="material-symbols-outlined text-[16px] text-indigo-400">fact_check</span>
                                Bobot: <strong class="text-white">{{ $course->sks }} SKS</strong>
                            </span>
                            <span>&bull;</span>
                            <span class="flex items-center gap-1.5 font-medium">
                                <span class="material-symbols-outlined text-[16px] text-indigo-400">groups</span>
                                Peserta: <strong class="text-white">{{ $students->count() }} Mahasiswa</strong>
                            </span>
                        </div>

                        @if ($course->description)
                            <p class="text-slate-300 text-xs sm:text-sm leading-relaxed pt-1">
                                {{ $course->description }}
                            </p>
                        @endif
                    </div>

                    @can('update', $course)
                        <a href="{{ route('mata-kuliah.edit', $course->id) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl shrink-0 bg-white/10 hover:bg-white/20 text-white font-semibold text-xs border border-white/20 transition shadow-sm">
                            <span class="material-symbols-outlined text-[16px]">edit</span>
                            Edit Mata Kuliah
                        </a>
                    @endcan
                </div>
            </div>
        </div>


        {{-- ── TAB NAVIGASI (MATERI, TUGAS, MAHASISWA) ───────────────────── --}}
        <div x-data="{
                tab: (new URLSearchParams(window.location.search).get('tab'))
                     || (window.location.hash ? window.location.hash.substring(1) : null)
                     || '{{ request('tab', 'materi') }}',
                setTab(t) {
                    this.tab = t;
                    const url = new URL(window.location);
                    url.searchParams.set('tab', t);
                    window.history.replaceState({}, '', url);
                }
             }"
             x-init="
                window.addEventListener('popstate', () => {
                    tab = (new URLSearchParams(window.location.search).get('tab')) || 'materi';
                });
             ">

            {{-- Tab Header Capsule --}}
            <div class="inline-flex p-1.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs gap-1 mb-6 flex-wrap">
                <button @click="setTab('materi')"
                        :class="tab === 'materi'
                            ? 'bg-indigo-600 text-white shadow-sm font-bold'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                        class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-2">
                    <span class="material-symbols-outlined text-[17px]">menu_book</span>
                    Materi Perkuliahan
                    <span :class="tab === 'materi' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'"
                          class="text-[11px] px-2 py-0.5 rounded-full font-bold">
                        {{ $materials->count() }}
                    </span>
                </button>

                <button @click="setTab('tugas')"
                        :class="tab === 'tugas'
                            ? 'bg-indigo-600 text-white shadow-sm font-bold'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                        class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-2">
                    <span class="material-symbols-outlined text-[17px]">assignment</span>
                    Tugas &amp; Penilaian
                    <span :class="tab === 'tugas' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'"
                          class="text-[11px] px-2 py-0.5 rounded-full font-bold">
                        {{ $assignments->count() }}
                    </span>
                </button>

                <button @click="setTab('mahasiswa')"
                        :class="tab === 'mahasiswa'
                            ? 'bg-indigo-600 text-white shadow-sm font-bold'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                        class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-2">
                    <span class="material-symbols-outlined text-[17px]">school</span>
                    Peserta Kelas
                    <span :class="tab === 'mahasiswa' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'"
                          class="text-[11px] px-2 py-0.5 rounded-full font-bold">
                        {{ $students->count() }}
                    </span>
                </button>
            </div>


            {{-- ═══════════════ TAB 1: MATERI PERKULIAHAN ═══════════════ --}}
            <div x-show="tab === 'materi'" x-cloak>
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-200/80 flex items-center justify-between bg-white">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Materi Perkuliahan</h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Berkas referensi, dokumen modul, dan tautan pembelajaran eksternal.
                            </p>
                        </div>
                        @can('create', [\App\Models\Material::class, $course])
                            <a href="{{ route('materi.create', $course->id) }}"
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-sm transition">
                                <span class="material-symbols-outlined text-[16px]">add</span>
                                Unggah Materi
                            </a>
                        @endcan
                    </div>

                    @if ($materials->isEmpty())
                        <div class="px-6 py-16 text-center">
                            <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                <span class="material-symbols-outlined text-3xl">folder_open</span>
                            </div>
                            <h3 class="text-base font-bold text-slate-700">Belum ada materi perkuliahan</h3>
                            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                Dosen pengampu belum mengunggah materi atau berkas kuliah untuk kelas ini.
                            </p>
                            @can('create', [\App\Models\Material::class, $course])
                                <a href="{{ route('materi.create', $course->id) }}"
                                   class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm">
                                    <span class="material-symbols-outlined text-[16px]">add</span>
                                    Unggah Materi Pertama
                                </a>
                            @endcan
                        </div>
                    @else
                        <div class="divide-y divide-slate-100">
                            @foreach ($materials as $material)
                                @php
                                    $typeIcon = match($material->type) {
                                        'link'  => ['link',        'bg-sky-50 text-sky-600 border border-sky-100'],
                                        'text'  => ['article',     'bg-purple-50 text-purple-600 border border-purple-100'],
                                        default => ['attach_file', 'bg-emerald-50 text-emerald-600 border border-emerald-100'],
                                    };
                                    $sizeLabel = $material->file_size
                                        ? round($material->file_size / 1024, 0) . ' KB'
                                        : null;
                                @endphp
                                <div class="px-6 py-4 hover:bg-slate-50/80 transition-colors flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4 flex-1 min-w-0">
                                        <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 {{ $typeIcon[1] }}">
                                            <span class="material-symbols-outlined text-[22px]">{{ $typeIcon[0] }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 truncate text-sm">{{ $material->title }}</p>
                                            @if ($material->description)
                                                <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $material->description }}</p>
                                            @endif
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="px-2 py-0.2 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600">
                                                    {{ $material->type }}
                                                </span>
                                                @if ($sizeLabel)
                                                    <span class="text-xs text-slate-300">&bull;</span>
                                                    <span class="text-xs text-slate-400 font-medium">{{ $sizeLabel }}</span>
                                                @endif
                                                <span class="text-xs text-slate-300">&bull;</span>
                                                <span class="text-xs text-slate-400">
                                                    {{ $material->created_at->translatedFormat('d M Y') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        @if ($material->type === 'link')
                                            <a href="{{ $material->external_url }}" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 text-xs font-semibold transition">
                                                <span class="material-symbols-outlined text-[15px]">open_in_new</span>
                                                Buka Tautan
                                            </a>
                                        @elseif ($material->type === 'file')
                                            <a href="{{ route('materi.download', $material->id) }}"
                                               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold transition">
                                                <span class="material-symbols-outlined text-[15px]">download</span>
                                                Unduh Berkas
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-400 italic">Dokumen Teks</span>
                                        @endif

                                        @can('delete', $material)
                                            <form action="{{ route('materi.destroy', $material->id) }}"
                                                  method="POST" class="inline"
                                                  onsubmit="return confirm('Hapus berkas materi ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center px-2.5 py-1.5 rounded-xl bg-rose-50 text-rose-600 border border-rose-200 text-xs font-semibold hover:bg-rose-100 transition">
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>


            {{-- ═══════════════ TAB 2: TUGAS PERKULIAHAN ═══════════════ --}}
            <div x-show="tab === 'tugas'" x-cloak>
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-200/80 flex items-center justify-between bg-white">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Daftar Tugas</h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Seluruh tugas praktikum dan proyek yang harus dikerjakan mahasiswa.
                            </p>
                        </div>
                        @can('create', [\App\Models\Assignment::class, $course])
                            <a href="{{ route('tugas.create', $course->id) }}"
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-sm transition">
                                <span class="material-symbols-outlined text-[16px]">add_task</span>
                                Buat Tugas Baru
                            </a>
                        @endcan
                    </div>

                    @if ($assignments->isEmpty())
                        <div class="px-6 py-16 text-center">
                            <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                <span class="material-symbols-outlined text-3xl">assignment</span>
                            </div>
                            <h3 class="text-base font-bold text-slate-700">Belum ada tugas</h3>
                            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                Belum ada penugasan aktif pada mata kuliah ini.
                            </p>
                            @can('create', [\App\Models\Assignment::class, $course])
                                <a href="{{ route('tugas.create', $course->id) }}"
                                   class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm">
                                    <span class="material-symbols-outlined text-[16px]">add</span>
                                    Buat Tugas Pertama
                                </a>
                            @endcan
                        </div>
                    @else
                        <div class="divide-y divide-slate-100">
                            @foreach ($assignments as $assignment)
                                @php
                                    $isPast   = now()->isAfter($assignment->due_at);
                                    $isClosed = $assignment->status === 'closed';
                                    $isDraft  = $assignment->status === 'draft';
                                    $statusBadge = match(true) {
                                        $isDraft  => ['bg-slate-100 text-slate-600 border-slate-200',  'DRAFT'],
                                        $isClosed => ['bg-rose-50 text-rose-700 border-rose-200',       'DITUTUP'],
                                        $isPast   => ['bg-amber-50 text-amber-700 border-amber-200',   'LEWAT TENGGAT'],
                                        default   => ['bg-emerald-50 text-emerald-700 border-emerald-200','AKTIF'],
                                    };
                                @endphp

                                <div class="px-6 py-5 hover:bg-slate-50/80 transition-colors">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $statusBadge[0] }}">
                                                    {{ $statusBadge[1] }}
                                                </span>
                                                <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-lg">
                                                    Maks. {{ number_format($assignment->max_score, 0) }} Poin
                                                </span>
                                            </div>

                                            <h3 class="font-bold text-slate-900 text-base truncate">
                                                {{ $assignment->title }}
                                            </h3>

                                            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-[15px] text-slate-400">schedule</span>
                                                <span>Tenggat: <strong class="text-slate-700">{{ $assignment->due_at->translatedFormat('d M Y, H:i') }} WIB</strong></span>
                                                @if ($assignment->allow_late)
                                                    <span class="ml-1 text-amber-700 font-semibold bg-amber-50 px-2 py-0.2 rounded border border-amber-200">(Toleransi Keterlambatan)</span>
                                                @endif
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0">
                                            @if ($role === 'mahasiswa')
                                                @php
                                                    $mySub = $assignment->submissions->first();
                                                @endphp
                                                @if ($mySub)
                                                    @if ($mySub->grade)
                                                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            <span class="material-symbols-outlined text-[15px]">verified</span>
                                                            Nilai: {{ $mySub->grade->score }} / {{ $assignment->max_score }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            <span class="material-symbols-outlined text-[15px]">check_circle</span>
                                                            Sudah Dikumpulkan
                                                        </span>
                                                    @endif

                                                    <a href="{{ route('submissions.show', $mySub->id) }}"
                                                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition shadow-sm">
                                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                        Lihat Lembar Pengumpulan
                                                    </a>

                                                    <a href="{{ route('tugas.show', $assignment->id) }}"
                                                       class="inline-flex items-center gap-1 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                                                        Detail
                                                    </a>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                        <span class="material-symbols-outlined text-[14px]">pending</span>
                                                        Belum Mengumpulkan
                                                    </span>
                                                    <a href="{{ route('tugas.show', $assignment->id) }}"
                                                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition shadow-sm">
                                                        <span class="material-symbols-outlined text-[16px]">upload_file</span>
                                                        Kumpulkan Tugas
                                                    </a>
                                                @endif
                                            @elseif ($role === 'dosen')
                                                <span class="text-xs text-slate-500 font-semibold px-2.5 py-1 rounded-lg bg-slate-100">
                                                    {{ $assignment->submissions->count() }} Pengumpulan
                                                </span>
                                                <a href="{{ route('tugas.show', $assignment->id) }}#daftar-submission"
                                                   class="inline-flex items-center gap-1 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition shadow-sm">
                                                    <span class="material-symbols-outlined text-[15px]">rate_review</span>
                                                    Nilai Pengumpulan
                                                </a>
                                                @can('update', $assignment)
                                                    <a href="{{ route('tugas.edit', $assignment->id) }}"
                                                       class="inline-flex items-center gap-1 px-3 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-semibold transition">
                                                        <span class="material-symbols-outlined text-[15px]">edit</span>
                                                        Edit
                                                    </a>
                                                @endcan
                                            @elseif ($role === 'admin')
                                                <a href="{{ route('tugas.show', $assignment->id) }}"
                                                   class="inline-flex items-center gap-1 px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-semibold transition">
                                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                    Lihat Tugas
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>


            {{-- ═══════════════ TAB 3: PESERTA KELAS (ENROLLMENT) ═══════════════ --}}
            <div x-show="tab === 'mahasiswa'" x-cloak>
                <div class="space-y-6">

                    {{-- Form Daftarkan Mahasiswa (Khusus Admin & Dosen Pengampu) --}}
                    @can('update', $course)
                        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6">
                            <div class="flex items-center gap-2 mb-1.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[20px]">person_add</span>
                                </div>
                                <h3 class="text-base font-bold text-slate-900">Daftarkan Mahasiswa ke Kelas Ini</h3>
                            </div>
                            <p class="text-xs text-slate-500 mb-4">
                                Tambahkan mahasiswa aktif ke daftar peserta <strong>{{ $course->code }} &bull; {{ $course->name }}</strong>.
                            </p>

                            @if ($availableStudents->isEmpty())
                                <div class="bg-slate-50 text-slate-600 px-4 py-3 rounded-xl text-xs font-medium border border-slate-200 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-slate-400 text-[18px]">info</span>
                                    Semua mahasiswa aktif dalam sistem telah terdaftar pada kelas mata kuliah ini.
                                </div>
                            @else
                                <form action="{{ route('mata-kuliah.enroll', $course->id) }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                    @csrf
                                    <div class="flex-1">
                                        <label for="enroll_user_id" class="sr-only">Pilih Mahasiswa</label>
                                        <select name="user_id" id="enroll_user_id" required
                                                class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2.5">
                                            <option value="">-- Pilih Mahasiswa untuk Didaftarkan --</option>
                                            @foreach ($availableStudents as $mhs)
                                                <option value="{{ $mhs->id }}">
                                                    {{ $mhs->name }} ({{ $mhs->nim_nip ?? 'NIM belum ada' }}) — {{ $mhs->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit"
                                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shrink-0 shadow-sm">
                                        <span class="material-symbols-outlined text-[17px]">add_circle</span>
                                        Daftarkan Mahasiswa
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endcan

                    {{-- Tabel Mahasiswa Terdaftar --}}
                    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <div class="px-6 py-5 border-b border-slate-200/80 flex items-center justify-between bg-white">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Daftar Mahasiswa Terdaftar</h2>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Total {{ $students->count() }} mahasiswa aktif terdaftar pada kelas ini.
                                </p>
                            </div>
                        </div>

                        @if ($students->isEmpty())
                            <div class="p-12 text-center">
                                <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">group_off</span>
                                <p class="text-sm font-bold text-slate-700">Belum ada mahasiswa terdaftar</p>
                                <p class="text-xs text-slate-400 mt-1">Gunakan formulir pendaftaran di atas untuk menambahkan peserta kelas.</p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm text-slate-600">
                                    <thead class="bg-slate-50/80 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                                        <tr>
                                            <th class="px-6 py-3.5">#</th>
                                            <th class="px-6 py-3.5">Nama Mahasiswa</th>
                                            <th class="px-6 py-3.5">NIM</th>
                                            <th class="px-6 py-3.5">Email</th>
                                            <th class="px-6 py-3.5">Terdaftar Sejak</th>
                                            @can('update', $course)
                                                <th class="px-6 py-3.5 text-right">Aksi</th>
                                            @endcan
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($students as $index => $student)
                                            <tr class="hover:bg-slate-50/80 transition-colors">
                                                <td class="px-6 py-4 font-medium text-slate-400 text-xs">
                                                    {{ $index + 1 }}
                                                </td>
                                                <td class="px-6 py-4 font-bold text-slate-900">
                                                    <div class="flex items-center gap-2.5">
                                                        <span class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-extrabold shrink-0">
                                                            {{ strtoupper(substr($student->name, 0, 1)) }}
                                                        </span>
                                                        <span>{{ $student->name }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 font-mono text-xs text-slate-600 font-medium">
                                                    {{ $student->nim_nip ?? '—' }}
                                                </td>
                                                <td class="px-6 py-4 text-xs text-slate-500 font-mono">
                                                    {{ $student->email }}
                                                </td>
                                                <td class="px-6 py-4 text-xs text-slate-500">
                                                    {{ $student->pivot->enrolled_at ? \Carbon\Carbon::parse($student->pivot->enrolled_at)->translatedFormat('d M Y') : '—' }}
                                                </td>
                                                @can('update', $course)
                                                    <td class="px-6 py-4 text-right">
                                                        <form action="{{ route('mata-kuliah.unenroll', ['course' => $course->id, 'student' => $student->id]) }}"
                                                              method="POST"
                                                              onsubmit="return confirm('Keluarkan mahasiswa {{ $student->name }} dari mata kuliah ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-700 font-semibold px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200 transition"
                                                                    title="Keluarkan dari MK">
                                                                <span class="material-symbols-outlined text-[15px]">person_remove</span>
                                                                Keluarkan
                                                            </button>
                                                        </form>
                                                    </td>
                                                @endcan
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

        </div>{{-- end x-data --}}

    </div>

</x-layout>