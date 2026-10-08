<x-layout title="{{ $course->name }}">

    @php $role = session('demo_role', 'mahasiswa'); @endphp

    {{-- ── HEADER ──────────────────────────────────────────────────── --}}
    <div class="mb-6">
        <a href="{{ route('mata-kuliah.index') }}"
           class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600 transition mb-4">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Kembali ke Daftar MK
        </a>

        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-sm">
                        {{ $course->code }}
                    </span>
                    @php
                        $statusColor = match($course->status) {
                            'active'   => 'bg-emerald-50 text-emerald-700',
                            'archived' => 'bg-slate-100 text-slate-500',
                            default    => 'bg-amber-50 text-amber-700',
                        };
                    @endphp
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusColor }}">
                        {{ ucfirst($course->status) }}
                    </span>
                </div>

                <h1 class="text-3xl font-bold text-slate-900">{{ $course->name }}</h1>

                <p class="text-slate-500 mt-1 text-sm">
                    <span class="font-medium text-slate-700">Dosen:</span>
                    {{ $course->lecturer->name ?? '—' }}
                    &nbsp;·&nbsp;
                    <span class="font-medium text-slate-700">SKS:</span> {{ $course->sks }}
                    &nbsp;·&nbsp;
                    <span class="font-medium text-slate-700">Semester:</span> 5
                </p>

                @if ($course->description)
                    <p class="text-slate-600 mt-3 max-w-2xl text-sm leading-relaxed">
                        {{ $course->description }}
                    </p>
                @endif
            </div>

            @can('update', $course)
                <a href="{{ route('mata-kuliah.edit', $course->id) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl shrink-0
                          bg-amber-500 text-white font-semibold text-sm hover:bg-amber-600 transition">
                    <span class="material-symbols-outlined text-[18px]">edit</span>
                    Edit MK
                </a>
            @endcan
        </div>
    </div>


    {{-- ── TAB MATERI & TUGAS ───────────────────────────────────────── --}}
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

        {{-- Tab Header --}}
        <div class="flex gap-1 mb-4 bg-slate-100 p-1 rounded-xl w-fit">
            <button @click="setTab('materi')"
                    :class="tab === 'materi'
                        ? 'bg-white shadow text-indigo-700 font-semibold'
                        : 'text-slate-500 hover:text-slate-700'"
                    class="px-5 py-2 rounded-lg text-sm transition flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px]">menu_book</span>
                Materi
                <span class="text-xs px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-bold">
                    {{ $materials->count() }}
                </span>
            </button>
            <button @click="setTab('tugas')"
                    :class="tab === 'tugas'
                        ? 'bg-white shadow text-indigo-700 font-semibold'
                        : 'text-slate-500 hover:text-slate-700'"
                    class="px-5 py-2 rounded-lg text-sm transition flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px]">assignment</span>
                Tugas
                <span class="text-xs px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-bold">
                    {{ $assignments->count() }}
                </span>
            </button>
        </div>


        {{-- ═══════════════ TAB MATERI ═══════════════ --}}
        <div x-show="tab === 'materi'" x-cloak>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

                <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Daftar Materi</h2>
                        <p class="text-sm text-slate-500 mt-0.5">
                            {{ $materials->count() }} materi tersedia.
                        </p>
                    </div>
                    @can('create', [\App\Models\Material::class, $course])
                        <a href="{{ route('materi.create', $course->id) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl
                                  bg-indigo-600 text-white font-semibold text-sm
                                  hover:bg-indigo-700 transition shadow-sm">
                            <span class="material-symbols-outlined text-[18px]">add</span>
                            Tambah Materi
                        </a>
                    @endcan
                </div>

                @if ($materials->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <span class="material-symbols-outlined text-slate-300 text-6xl">folder_open</span>
                        <p class="text-slate-400 mt-3">Belum ada materi untuk mata kuliah ini.</p>
                        @can('create', [\App\Models\Material::class, $course])
                            <a href="{{ route('materi.create', $course->id) }}"
                               class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-xl
                                      bg-indigo-600 text-white font-semibold text-sm hover:bg-indigo-700 transition">
                                <span class="material-symbols-outlined text-[16px]">add</span>
                                Tambah Materi Pertama
                            </a>
                        @endcan
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($materials as $material)
                            @php
                                $typeIcon = match($material->type) {
                                    'link'  => ['link',        'bg-blue-50 text-blue-600'],
                                    'text'  => ['article',     'bg-purple-50 text-purple-600'],
                                    default => ['attach_file', 'bg-emerald-50 text-emerald-600'],
                                };
                                $sizeLabel = $material->file_size
                                    ? round($material->file_size / 1024, 0) . ' KB'
                                    : null;
                            @endphp
                            <div class="px-6 py-4 hover:bg-slate-50 transition flex items-center justify-between gap-4">
                                <div class="flex items-center gap-4 flex-1 min-w-0">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $typeIcon[1] }}">
                                        <span class="material-symbols-outlined text-[20px]">{{ $typeIcon[0] }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate">{{ $material->title }}</p>
                                        @if ($material->description)
                                            <p class="text-xs text-slate-400 mt-0.5 truncate">{{ $material->description }}</p>
                                        @endif
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-xs text-slate-400 uppercase tracking-wider">{{ $material->type }}</span>
                                            @if ($sizeLabel)
                                                <span class="text-xs text-slate-300">·</span>
                                                <span class="text-xs text-slate-400">{{ $sizeLabel }}</span>
                                            @endif
                                            <span class="text-xs text-slate-300">·</span>
                                            <span class="text-xs text-slate-400">
                                                {{ $material->created_at->format('d M Y') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    {{-- Download / buka link --}}
                                    @if ($material->type === 'link')
                                        <a href="{{ $material->external_url }}" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                                                  bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition">
                                            <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                                            Buka
                                        </a>
                                    @elseif ($material->type === 'file')
                                        <a href="{{ route('materi.download', $material->id) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                                                  bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition">
                                            <span class="material-symbols-outlined text-[14px]">download</span>
                                            Unduh
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Teks</span>
                                    @endif

                                    {{-- Hapus (dosen saja) --}}
                                    @can('delete', $material)
                                        <form action="{{ route('materi.destroy', $material->id) }}"
                                              method="POST" class="inline"
                                              onsubmit="return confirm('Hapus materi ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center px-2.5 py-1.5 rounded-lg
                                                           bg-red-50 text-red-600 text-xs font-semibold
                                                           hover:bg-red-100 transition">
                                                <span class="material-symbols-outlined text-[14px]">delete</span>
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


        {{-- ═══════════════ TAB TUGAS ═══════════════ --}}
        <div x-show="tab === 'tugas'" x-cloak>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

                <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Daftar Tugas</h2>
                        <p class="text-sm text-slate-500 mt-0.5">
                            {{ $assignments->count() }} tugas pada mata kuliah ini.
                        </p>
                    </div>
                    @can('create', [\App\Models\Assignment::class, $course])
                        <a href="{{ route('tugas.create', $course->id) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl
                                  bg-indigo-600 text-white font-semibold text-sm
                                  hover:bg-indigo-700 transition shadow-sm">
                            <span class="material-symbols-outlined text-[18px]">add_task</span>
                            Tambah Tugas
                        </a>
                    @endcan
                </div>

                @if ($assignments->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <span class="material-symbols-outlined text-slate-300 text-6xl">assignment</span>
                        <p class="text-slate-400 mt-3">Belum ada tugas untuk mata kuliah ini.</p>
                        @can('create', [\App\Models\Assignment::class, $course])
                            <a href="{{ route('tugas.create', $course->id) }}"
                               class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-xl
                                      bg-indigo-600 text-white font-semibold text-sm hover:bg-indigo-700 transition">
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
                                    $isDraft  => ['bg-slate-100 text-slate-500',  'DRAFT'],
                                    $isClosed => ['bg-red-50 text-red-600',       'DITUTUP'],
                                    $isPast   => ['bg-orange-50 text-orange-600', 'LEWAT TENGGAT'],
                                    default   => ['bg-emerald-50 text-emerald-700','AKTIF'],
                                };
                            @endphp

                            <div class="px-6 py-5 hover:bg-slate-50 transition">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $statusBadge[0] }}">
                                                {{ $statusBadge[1] }}
                                            </span>
                                            <span class="text-xs text-slate-400">
                                                Maks. {{ number_format($assignment->max_score, 0) }} poin
                                            </span>
                                        </div>
                                        <h3 class="font-semibold text-slate-900 truncate">
                                            {{ $assignment->title }}
                                        </h3>
                                        <p class="text-xs text-slate-500 mt-1">
                                            <span class="material-symbols-outlined text-[14px] align-middle">schedule</span>
                                            Tenggat: {{ $assignment->due_at->format('d M Y, H:i') }}
                                            @if ($assignment->allow_late)
                                                <span class="ml-1 text-amber-600">(boleh terlambat)</span>
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
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800">
                                                        <span class="material-symbols-outlined text-[14px]">grade</span>
                                                        {{ $mySub->grade->score }} / {{ $assignment->max_score }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                                        Sudah Dikumpulkan
                                                    </span>
                                                @endif

                                                <a href="{{ route('submissions.show', $mySub->id) }}"
                                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl
                                                          bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold
                                                          transition shadow-sm">
                                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                    Lihat Lembar Pengumpulan
                                                </a>

                                                <a href="{{ route('tugas.show', $assignment->id) }}"
                                                   class="inline-flex items-center gap-1 px-3 py-2 rounded-xl
                                                          bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                                                    Detail
                                                </a>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                    <span class="material-symbols-outlined text-[14px]">pending</span>
                                                    Belum Mengumpulkan
                                                </span>
                                                <a href="{{ route('tugas.show', $assignment->id) }}"
                                                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl
                                                          bg-indigo-600 text-white text-sm font-semibold
                                                          hover:bg-indigo-700 transition">
                                                    <span class="material-symbols-outlined text-[16px]">upload_file</span>
                                                    Kumpulkan Tugas
                                                </a>
                                            @endif
                                        @elseif ($role === 'dosen' || $role === 'admin')
                                            @can('viewSubmissions', $assignment)
                                                <span class="text-sm text-slate-500">
                                                    <span class="material-symbols-outlined text-[15px] align-middle text-emerald-600">group</span>
                                                    {{ $assignment->submissions->count() }} submission
                                                </span>
                                                <a href="{{ route('tugas.show', $assignment->id) }}#daftar-submission"
                                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg
                                                          bg-indigo-600 text-white text-xs font-semibold
                                                          hover:bg-indigo-700 transition shadow-sm">
                                                    <span class="material-symbols-outlined text-[14px]">rate_review</span>
                                                    Nilai &amp; Submissions
                                                </a>
                                            @endcan
                                            @can('update', $assignment)
                                                <a href="{{ route('tugas.edit', $assignment->id) }}"
                                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg
                                                          bg-slate-200 text-slate-700 text-xs font-semibold
                                                          hover:bg-slate-300 transition">
                                                    <span class="material-symbols-outlined text-[14px]">edit</span>
                                                    Edit
                                                </a>
                                            @endcan
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>{{-- end x-data --}}


    {{-- Flash success --}}
    @if (session('success'))
        <div class="fixed bottom-6 right-6 z-50 bg-emerald-600 text-white px-5 py-3 rounded-xl
                    shadow-lg flex items-center gap-2 text-sm font-semibold" id="flash-msg">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            {{ session('success') }}
        </div>
        <script>
            setTimeout(() => {
                const el = document.getElementById('flash-msg');
                if (el) el.remove();
            }, 3500);
        </script>
    @endif

</x-layout>