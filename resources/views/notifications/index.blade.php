<x-layout title="Notifikasi Saya">

    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

        <div class="max-w-4xl mx-auto space-y-6">

            {{-- Header & Aksi Utama --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400 text-3xl">notifications</span>
                        Notifikasi Saya
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Pemberitahuan tugas baru, nilai, dan pembaruan perkuliahan secara realtime.
                    </p>
                </div>

                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.markAllAsRead') }}" class="shrink-0">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/80 dark:text-indigo-300 font-semibold text-xs transition border border-indigo-200 dark:border-indigo-800 shadow-sm">
                            <span class="material-symbols-outlined text-[18px]">done_all</span>
                            Tandai Semua Dibaca
                        </button>
                    </form>
                @endif
            </div>

            {{-- Filter Tab --}}
            <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                <a href="{{ route('notifications.index', ['filter' => 'all']) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition {{ $filter !== 'unread' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Semua
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $filter !== 'unread' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                        {{ $notifications->total() }}
                    </span>
                </a>

                <a href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition {{ $filter === 'unread' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Belum Dibaca
                    @if ($unreadCount > 0)
                        <span class="px-2 py-0.5 rounded-full text-xs bg-rose-500 text-white font-bold">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </a>
            </div>

            {{-- Pesan Flash --}}
            @if (session('success'))
                <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center gap-3">
                    <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- Daftar Notifikasi --}}
            @if ($notifications->count() > 0)
                <div class="space-y-3">
                    @foreach ($notifications as $notification)
                        @php
                            $data = $notification->data ?? [];
                            $isUnread = is_null($notification->read_at);
                            $type = $data['type'] ?? 'general';
                            $url = $data['url'] ?? null;
                        @endphp

                        <div class="p-5 rounded-2xl border transition duration-150 {{ $isUnread ? 'bg-indigo-50/40 dark:bg-indigo-950/20 border-indigo-200 dark:border-indigo-900/60 shadow-sm' : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800' }}">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-4">
                                    {{-- Ikon Jenis Notifikasi --}}
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $type === 'grade_submitted' ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400' : 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400' }}">
                                        @if ($type === 'grade_submitted')
                                            <span class="material-symbols-outlined text-[22px]">verified</span>
                                        @else
                                            <span class="material-symbols-outlined text-[22px]">assignment</span>
                                        @endif
                                    </div>

                                    {{-- Teks Konten --}}
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            @if (!empty($data['course_name']))
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                    {{ $data['course_name'] }}
                                                </span>
                                            @endif
                                            @if ($isUnread)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300">
                                                    Baru
                                                </span>
                                            @endif
                                            <span class="text-xs text-slate-400">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </span>
                                        </div>

                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                            {{ $data['title'] ?? ($data['assignment_title'] ?? 'Pemberitahuan Sistem') }}
                                        </p>

                                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                            {{ $data['message'] ?? 'Ada pembaruan pada aktivitas perkuliahan Anda.' }}
                                        </p>

                                        @if (!empty($data['due_at']))
                                            <p class="text-[11px] text-amber-600 dark:text-amber-400 font-medium pt-1 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">schedule</span>
                                                Tenggat Waktu: {{ $data['due_at'] }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Aksi / Tombol --}}
                                <div class="flex items-center gap-2 shrink-0">
                                    @if ($url)
                                        <a href="{{ $url }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                                            <span>Lihat</span>
                                            <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                        </a>
                                    @endif

                                    @if ($isUnread)
                                        <form method="POST" action="{{ route('notifications.markAsRead', $notification->id) }}">
                                            @csrf
                                            <button type="submit"
                                                    title="Tandai Sudah Dibaca"
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 transition">
                                                <span class="material-symbols-outlined text-[18px]">done</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="pt-4">
                        {{ $notifications->links() }}
                    </div>
                </div>
            @else
                {{-- State Kosong --}}
                <div class="p-12 text-center bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
                    <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                        <span class="material-symbols-outlined text-3xl">notifications_off</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Belum Ada Notifikasi
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                        @if ($filter === 'unread')
                            Seluruh notifikasi telah Anda baca.
                        @else
                            Anda belum memiliki notifikasi perkuliahan saat ini. Notifikasi akan otomatis muncul ketika dosen mempublikasikan tugas atau memberi nilai.
                        @endif
                    </p>
                </div>
            @endif

        </div>

    </div>

</x-layout>
