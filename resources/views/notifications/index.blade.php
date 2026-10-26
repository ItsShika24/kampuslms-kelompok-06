<x-layout title="Notifikasi Saya">

    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Header & Aksi Utama --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl shadow-sm border border-slate-200/80">
            <div>
                <nav class="flex items-center gap-1.5 text-xs text-slate-400 font-medium mb-1.5">
                    <span>Portal</span>
                    <span>/</span>
                    <span class="text-indigo-600 font-semibold">Notifikasi</span>
                </nav>
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">notifications</span>
                    </div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                        Notifikasi Perkuliahan
                    </h1>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Pemberitahuan tugas baru, publikasi nilai, dan aktivitas perkuliahan terintegrasi.
                </p>
            </div>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.markAllAsRead') }}" class="shrink-0 self-start sm:self-auto">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs transition border border-indigo-200 shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">done_all</span>
                        Tandai Semua Dibaca
                    </button>
                </form>
            @endif
        </div>

        {{-- Filter Tab Capsule --}}
        <div class="inline-flex items-center gap-1.5 bg-slate-100 p-1.5 rounded-2xl text-xs font-semibold">
            <a href="{{ route('notifications.index', ['filter' => 'all']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl transition {{ $filter !== 'unread' ? 'bg-white text-indigo-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                Semua Notifikasi
                <span class="px-2 py-0.2 rounded-full text-[11px] {{ $filter !== 'unread' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-700' }}">
                    {{ $notifications->total() }}
                </span>
            </a>

            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl transition {{ $filter === 'unread' ? 'bg-white text-indigo-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                Belum Dibaca
                @if ($unreadCount > 0)
                    <span class="px-2 py-0.2 rounded-full text-[11px] bg-rose-500 text-white font-bold">
                        {{ $unreadCount }}
                    </span>
                @endif
            </a>
        </div>

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

                    <div class="p-5 rounded-2xl border transition duration-150 {{ $isUnread ? 'bg-indigo-50/40 border-indigo-200/80 shadow-sm' : 'bg-white border-slate-200/80' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3.5">
                                {{-- Ikon Jenis Notifikasi --}}
                                <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 {{ $type === 'grade_submitted' ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700' }}">
                                    @if ($type === 'grade_submitted')
                                        <span class="material-symbols-outlined text-[20px]">verified</span>
                                    @else
                                        <span class="material-symbols-outlined text-[20px]">assignment</span>
                                    @endif
                                </div>

                                {{-- Teks Konten --}}
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if (!empty($data['course_name']))
                                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                {{ $data['course_name'] }}
                                            </span>
                                        @endif
                                        @if ($isUnread)
                                            <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-700 border border-rose-200">
                                                Baru
                                            </span>
                                        @endif
                                        <span class="text-xs text-slate-400 font-medium">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </span>
                                    </div>

                                    <p class="text-sm font-bold text-slate-900">
                                        {{ $data['title'] ?? ($data['assignment_title'] ?? 'Pemberitahuan Sistem') }}
                                    </p>

                                    <p class="text-xs text-slate-600 leading-relaxed">
                                        {{ $data['message'] ?? 'Ada pembaruan pada aktivitas perkuliahan Anda.' }}
                                    </p>

                                    @if (!empty($data['due_at']))
                                        <p class="text-[11px] text-amber-700 font-semibold pt-1 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]">schedule</span>
                                            Tenggat Waktu: {{ $data['due_at'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Aksi / Tombol --}}
                            <div class="flex items-center gap-1.5 shrink-0">
                                @if ($url)
                                    <a href="{{ $url }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                        <span>Buka</span>
                                        <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                    </a>
                                @endif

                                @if ($isUnread)
                                    <form method="POST" action="{{ route('notifications.markAsRead', $notification->id) }}">
                                        @csrf
                                        <button type="submit"
                                                title="Tandai Sudah Dibaca"
                                                class="p-1.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-100 transition">
                                            <span class="material-symbols-outlined text-[18px]">done</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                @if ($notifications->hasPages())
                    <div class="pt-4">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        @else
            {{-- State Kosong --}}
            <div class="p-16 text-center bg-white rounded-3xl border border-slate-200/80 space-y-3">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-3xl">notifications_off</span>
                </div>
                <h3 class="text-base font-bold text-slate-800">
                    Belum Ada Notifikasi
                </h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                    @if ($filter === 'unread')
                        Seluruh notifikasi telah Anda baca.
                    @else
                        Anda belum memiliki notifikasi perkuliahan. Notifikasi akan otomatis muncul ketika dosen mempublikasikan tugas atau memberikan nilai.
                    @endif
                </p>
            </div>
        @endif

    </div>

</x-layout>
