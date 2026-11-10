# Panduan Pengerjaan BUILD Minggu 10 — Oktavia Nur Rahmadani (10241060)

> **Mata Kuliah:** SI2514024 Pemrograman Web (Semester Ganjil 2026/2027)  
> **Proyek:** EduKampus LMS (Kelompok 06)  
> **Peran:** Frontend & UX / Form Validation  
> **Fokus Milestone M3:** Poin 4, 5, dan Tampilan UI Poin 6–7  
> **Branch Git:** `Oktavia-Nur-10`  

---

## 📌 Daftar Berkas yang Dikerjakan

| No | Nama Berkas | Lokasi Folder | Status | Deskripsi |
|:---:|---|---|:---:|---|
| 1 | `index.blade.php` | `resources/views/grades/` | **BARU (Buat File)** | Halaman antarmuka Rekap Nilai (Dosen: Matriks Nilai & Rata-rata, Mahasiswa: Kartu Evaluasi & Feedback). |
| 2 | `layout.blade.php` | `resources/views/components/` | **MODIFIKASI (Edit)** | Penambahan Dropdown Preview pada Lonceng Notifikasi, Tautan Menu "Nilai", dan Realtime Polling. |
| 3 | `index.blade.php` | `resources/views/notifications/` | **VERIFIKASI / POLISH** | Halaman daftar notifikasi lengkap dengan tab filter (Semua / Belum Dibaca) dan tombol aksi. |

---

## 🚀 Langkah 1: Persiapan Git di VS Code Oktavia

Sebelum menyalin kode, pastikan terminal VS Code Anda berada di branch `Oktavia-Nur-10` dan telah mengambil kode terbaru dari `main`:

```bash
# 1. Pindah ke branch kerja Oktavia
git checkout Oktavia-Nur-10

# 2. Tarik perubahan terbaru dari branch main (karena kode Tika & Shabrina sudah di-merge)
git pull origin main

# 3. Pastikan branch sudah bersih dan siap diedit
git status
```

---

## 💻 Langkah 2: Buat File Baru `resources/views/grades/index.blade.php`

### 📂 Lokasi Folder:
Buka folder `resources/views/`. Jika di dalamnya belum ada folder `grades`, **buat folder baru bernama `grades`**, lalu di dalamnya buat file bernama:
`resources/views/grades/index.blade.php`

### 📝 Salin Seluruh Kode Berikut ke `resources/views/grades/index.blade.php`:

```blade
<x-layout title="Rekap Nilai Perkuliahan">

    <div class="space-y-6">

        {{-- Header Halaman --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm">
            <div>
                <nav class="flex items-center gap-1.5 text-xs text-slate-400 font-medium mb-2">
                    <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition">Dashboard</a>
                    <span>/</span>
                    <span class="text-indigo-600 font-semibold">Rekap Nilai</span>
                </nav>
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100 shadow-sm">
                        <span class="material-symbols-outlined text-2xl">workspace_premium</span>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                            Buku Rekap Nilai
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                            @if ($role === 'dosen')
                                Matriks rekapitulasi penilaian tugas mahasiswa per mata kuliah yang Anda ampu.
                            @elseif ($role === 'mahasiswa')
                                Rekapitulasi perolehan nilai dan catatan evaluasi dosen pada mata kuliah yang Anda ikuti.
                            @else
                                Ringkasan seluruh data penilaian akademik tingkat program studi.
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Filter Mata Kuliah --}}
            @if ($courses->isNotEmpty())
                <form method="GET" action="{{ route('grades.index') }}" class="flex items-center gap-2 self-start md:self-auto">
                    <label for="course_filter" class="text-xs font-bold text-slate-600 whitespace-nowrap hidden sm:inline">
                        Pilih Kelas:
                    </label>
                    <div class="relative">
                        <select id="course_filter" name="course_id" onchange="this.form.submit()"
                                class="pl-3.5 pr-8 py-2 rounded-xl text-xs font-semibold bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition appearance-none cursor-pointer shadow-sm">
                            <option value="">Semua Mata Kuliah ({{ $courses->count() }})</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c->id }}" {{ (string) $selectedCourseId === (string) $c->id ? 'selected' : '' }}>
                                    {{ $c->code }} — {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">
                            expand_more
                        </span>
                    </div>
                    @if ($selectedCourseId)
                        <a href="{{ route('grades.index') }}" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 transition" title="Reset Filter">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </a>
                    @endif
                </form>
            @endif
        </div>

        {{-- Konten Rekap Nilai --}}
        @if ($courses->isEmpty())
            {{-- State Kosong --}}
            <div class="p-16 text-center bg-white rounded-3xl border border-slate-200/80 space-y-3">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-3xl">grading</span>
                </div>
                <h3 class="text-base font-bold text-slate-800">
                    Belum Ada Data Perkuliahan
                </h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                    Tidak ada mata kuliah atau tugas yang terhubung dengan akun Anda saat ini.
                </p>
            </div>
        @else

            {{-- ============================================================== --}}
            {{-- A. TAMPILAN DOSEN & ADMINISTRATOR (MATRIKS NILAI KELAS)       --}}
            {{-- ============================================================== --}}
            @if ($role !== 'mahasiswa')
                <div class="space-y-8">
                    @foreach ($courses as $course)
                        @php
                            $assignments = $course->assignments;
                            $students = $course->students ?? collect();
                        @endphp

                        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                            {{-- Header Kartu Kelas --}}
                            <div class="p-6 bg-slate-50/70 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white font-mono font-bold text-xs shadow-sm">
                                        {{ $course->code }}
                                    </span>
                                    <div>
                                        <h2 class="text-base font-bold text-slate-900 leading-tight">
                                            {{ $course->name }}
                                        </h2>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            {{ $course->sks }} SKS &bull; {{ $students->count() }} Mahasiswa Terdaftar &bull; {{ $assignments->count() }} Tugas
                                        </p>
                                    </div>
                                </div>

                                <a href="{{ route('courses.show', $course) }}"
                                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-sm hover:border-indigo-300 transition shrink-0 self-start sm:self-auto">
                                    <span>Buka Kelas</span>
                                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                </a>
                            </div>

                            {{-- Tabel Matriks Rekapitulasi --}}
                            @if ($assignments->isEmpty())
                                <div class="p-10 text-center text-slate-400 text-xs">
                                    Belum ada tugas yang diterbitkan pada mata kuliah ini.
                                </div>
                            @elseif ($students->isEmpty())
                                <div class="p-10 text-center text-slate-400 text-xs">
                                    Belum ada mahasiswa yang terdaftar di kelas ini.
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse text-xs">
                                        <thead>
                                            <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                                                <th class="py-3 px-4 w-12 text-center">No</th>
                                                <th class="py-3 px-4 min-w-[120px]">NIM</th>
                                                <th class="py-3 px-4 min-w-[180px]">Nama Mahasiswa</th>
                                                @foreach ($assignments as $assignment)
                                                    <th class="py-3 px-4 text-center min-w-[110px]" title="{{ $assignment->title }}">
                                                        <div class="truncate max-w-[120px] mx-auto font-semibold">
                                                            {{ $assignment->title }}
                                                        </div>
                                                        <span class="text-[9px] font-normal text-slate-400 block lowercase">
                                                            (Maks: {{ $assignment->max_score ?? 100 }})
                                                        </span>
                                                    </th>
                                                @endforeach
                                                <th class="py-3 px-4 text-center min-w-[100px] bg-indigo-50/50 text-indigo-900 font-extrabold">
                                                    Rata-Rata
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 text-slate-700">
                                            @foreach ($students as $idx => $student)
                                                @php
                                                    $studentScores = [];
                                                @endphp
                                                <tr class="hover:bg-slate-50/60 transition">
                                                    <td class="py-3 px-4 text-center text-slate-400 font-medium">
                                                        {{ $idx + 1 }}
                                                    </td>
                                                    <td class="py-3 px-4 font-mono font-medium text-slate-600">
                                                        {{ $student->nim_nip ?? '-' }}
                                                    </td>
                                                    <td class="py-3 px-4 font-semibold text-slate-900">
                                                        {{ $student->name }}
                                                    </td>

                                                    {{-- Nilai per Tugas --}}
                                                    @foreach ($assignments as $assignment)
                                                        @php
                                                            // Cari submission milik mahasiswa ini
                                                            $sub = $assignment->submissions->firstWhere('user_id', $student->id);
                                                            $grade = $sub?->grade;
                                                            if ($grade && !is_null($grade->score)) {
                                                                $studentScores[] = (float) $grade->score;
                                                            }
                                                        @endphp
                                                        <td class="py-3 px-4 text-center">
                                                            @if ($grade && !is_null($grade->score))
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold text-xs {{ $grade->score >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($grade->score >= 60 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                                                                    {{ number_format($grade->score, 0) }}
                                                                </span>
                                                            @elseif ($sub)
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-sky-50 text-sky-700 border border-sky-200" title="Menunggu Penilaian">
                                                                    Terkirim
                                                                </span>
                                                            @else
                                                                <span class="text-slate-300 font-mono text-xs" title="Belum Mengumpulkan">
                                                                    &mdash;
                                                                </span>
                                                            @endif
                                                        </td>
                                                    @endforeach

                                                    {{-- Kolom Rata-Rata Nilai --}}
                                                    @php
                                                        $avg = count($studentScores) > 0 ? array_sum($studentScores) / count($studentScores) : null;
                                                    @endphp
                                                    <td class="py-3 px-4 text-center bg-indigo-50/30 font-bold">
                                                        @if (!is_null($avg))
                                                            <span class="text-xs font-black {{ $avg >= 80 ? 'text-emerald-600' : ($avg >= 60 ? 'text-amber-600' : 'text-rose-600') }}">
                                                                {{ number_format($avg, 1) }}
                                                            </span>
                                                        @else
                                                            <span class="text-slate-400 font-normal text-xs">&mdash;</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

            {{-- ============================================================== --}}
            {{-- B. TAMPILAN MAHASISWA (REKAP EVALUASI & FEEDBACK PRIBADI)       --}}
            {{-- ============================================================== --}}
            @else
                <div class="space-y-6">
                    @foreach ($courses as $course)
                        @php
                            $assignments = $course->assignments;
                            $gradedCount = 0;
                            $totalScore = 0;
                        @endphp

                        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                            {{-- Header Mata Kuliah --}}
                            <div class="p-6 bg-slate-50/70 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white font-mono font-bold text-xs shadow-sm">
                                        {{ $course->code }}
                                    </span>
                                    <div>
                                        <h2 class="text-base font-bold text-slate-900 leading-tight">
                                            {{ $course->name }}
                                        </h2>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Dosen Pengampu: {{ $course->lecturer?->name ?? 'Dosen Pengampu' }}
                                        </p>
                                    </div>
                                </div>

                                <a href="{{ route('courses.show', $course) }}"
                                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-sm hover:border-indigo-300 transition shrink-0 self-start sm:self-auto">
                                    <span>Detail Kelas</span>
                                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                </a>
                            </div>

                            {{-- Daftar Tugas & Nilai Mahasiswa --}}
                            @if ($assignments->isEmpty())
                                <div class="p-8 text-center text-slate-400 text-xs">
                                    Belum ada tugas perkuliahan untuk mata kuliah ini.
                                </div>
                            @else
                                <div class="divide-y divide-slate-100">
                                    @foreach ($assignments as $assignment)
                                        @php
                                            $sub = $assignment->submissions->first(); // Sudah di-filter khusus milik mahasiswa login di controller
                                            $grade = $sub?->grade;
                                            if ($grade && !is_null($grade->score)) {
                                                $gradedCount++;
                                                $totalScore += $grade->score;
                                            }
                                        @endphp

                                        <div class="p-5 sm:p-6 hover:bg-slate-50/50 transition flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                            <div class="space-y-1.5 max-w-xl">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <h3 class="text-sm font-bold text-slate-900">
                                                        {{ $assignment->title }}
                                                    </h3>
                                                    @if ($grade && !is_null($grade->score))
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                                            Dinilai
                                                        </span>
                                                    @elseif ($sub)
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-sky-100 text-sky-700 border border-sky-200">
                                                            Terkumpul
                                                        </span>
                                                    @else
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-700 border border-amber-200">
                                                            Belum Kumpul
                                                        </span>
                                                    @endif
                                                </div>

                                                <p class="text-xs text-slate-500 flex items-center gap-1.5">
                                                    <span class="material-symbols-outlined text-[14px]">event</span>
                                                    Tenggat: {{ $assignment->due_at ? \Carbon\Carbon::parse($assignment->due_at)->translatedFormat('d M Y, H:i') : '-' }}
                                                </p>

                                                {{-- Umpan Balik Dosen --}}
                                                @if ($grade && !empty($grade->feedback))
                                                    <div class="mt-2.5 p-3 rounded-2xl bg-indigo-50/60 border border-indigo-100/80 text-xs text-indigo-900">
                                                        <span class="font-bold flex items-center gap-1 text-[11px] text-indigo-700 mb-0.5">
                                                            <span class="material-symbols-outlined text-[15px]">rate_review</span>
                                                            Catatan Evaluasi Dosen:
                                                        </span>
                                                        <p class="italic text-slate-700 pl-4 border-l-2 border-indigo-400 mt-1">
                                                            "{{ $grade->feedback }}"
                                                        </p>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Skor Nilai & Link Tugas --}}
                                            <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-start gap-2 shrink-0">
                                                @if ($grade && !is_null($grade->score))
                                                    <div class="text-right">
                                                        <div class="text-2xl font-black {{ $grade->score >= 80 ? 'text-emerald-600' : ($grade->score >= 60 ? 'text-amber-600' : 'text-rose-600') }}">
                                                            {{ number_format($grade->score, 0) }}
                                                            <span class="text-xs font-semibold text-slate-400">/ {{ $assignment->max_score ?? 100 }}</span>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-xs font-semibold text-slate-400 italic">
                                                        Belum ada nilai
                                                    </span>
                                                @endif

                                                <a href="{{ route('tugas.show', $assignment) }}"
                                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 transition">
                                                    <span>Lihat Tugas</span>
                                                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Footer Kartu Mahasiswa: Rata-Rata Mata Kuliah --}}
                                @if ($gradedCount > 0)
                                    @php
                                        $finalAvg = $totalScore / $gradedCount;
                                    @endphp
                                    <div class="p-4 bg-indigo-50/40 border-t border-slate-100 flex items-center justify-between text-xs">
                                        <span class="font-semibold text-slate-600">
                                            Rata-Rata Nilai Tugas ({{ $gradedCount }} Tugas Dinilai):
                                        </span>
                                        <span class="text-sm font-black {{ $finalAvg >= 80 ? 'text-emerald-600' : ($finalAvg >= 60 ? 'text-amber-600' : 'text-rose-600') }}">
                                            {{ number_format($finalAvg, 1) }}
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

        @endif

    </div>

</x-layout>
```

---

## 💻 Langkah 3: Perbarui `resources/views/components/layout.blade.php`

### 📂 Lokasi Folder:
Buka file yang sudah ada: `resources/views/components/layout.blade.php`.

### 📝 Perubahan 1: Tambahkan Tombol Menu "Nilai" di Navbar Navigasi (Desktop & Mobile)
Cari bagian tombol menu navigasi (sekitar baris 120–145), tambahkan menu **Nilai** di sebelah tombol **Tugas** / **Mata Kuliah**:

```blade
{{-- Tombol Navigasi Rekap Nilai --}}
<a href="{{ route('grades.index') }}"
   class="inline-flex items-center gap-2 px-3 py-1.5 xl:px-3.5 xl:py-2 rounded-xl text-xs xl:text-sm font-semibold whitespace-nowrap transition-all duration-150 shrink-0
          {{ request()->routeIs('grades.*')
              ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25 ring-1 ring-indigo-400/40'
              : 'text-slate-300 hover:bg-slate-800/80 hover:text-white font-medium' }}">
    <span class="material-symbols-outlined text-[18px] xl:text-[19px] shrink-0">workspace_premium</span>
    <span>Nilai</span>
</a>
```

*(Lakukan hal yang sama pada menu mobile di dalam `#mobile-nav`).*

---

### 📝 Perubahan 2: Ganti Ikon Lonceng Biasa Menjadi Lonceng Interaktif + Dropdown Preview
Cari blok `@auth ... @endauth` pada bagian lonceng notifikasi (sekitar baris 153–167):

**Ganti kode lonceng lama menjadi kode interaktif dengan dropdown berikut:**

```blade
{{-- Lonceng Notifikasi Interaktif dengan Dropdown Preview --}}
@auth
    @php
        $unreadNotifCount = $headerUser?->unreadNotifications()->count() ?? 0;
        $recentNotifications = $headerUser?->notifications()->latest()->take(5)->get() ?? collect();
    @endphp

    <div class="relative shrink-0" id="notif-dropdown-wrapper">
        <button
            type="button"
            id="notif-bell-button"
            class="relative flex items-center justify-center w-9 h-9 xl:w-10 xl:h-10 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 transition-all duration-150 shadow-sm focus:outline-none"
            title="Notifikasi"
            aria-expanded="false"
        >
            <span class="material-symbols-outlined text-[20px]">notifications</span>
            <span id="navbar-notif-badge"
                  class="{{ $unreadNotifCount > 0 ? '' : 'hidden' }} absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white shadow ring-2 ring-slate-900">
                {{ $unreadNotifCount > 99 ? '99+' : $unreadNotifCount }}
            </span>
        </button>

        {{-- Dropdown Preview Menu --}}
        <div id="notif-dropdown-menu"
             class="hidden absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white shadow-2xl border border-slate-200/90 z-50 overflow-hidden divide-y divide-slate-100">
            
            {{-- Header Dropdown --}}
            <div class="p-4 bg-slate-50/80 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-xs text-slate-900">Notifikasi</span>
                    <span id="dropdown-unread-count" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-700">
                        {{ $unreadNotifCount }} baru
                    </span>
                </div>
                @if ($unreadNotifCount > 0)
                    <form method="POST" action="{{ route('notifications.markAllAsRead') }}">
                        @csrf
                        <button type="submit" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">
                            Tandai semua dibaca
                        </button>
                    </form>
                @endif
            </div>

            {{-- Isi 5 Notifikasi Terbaru --}}
            <div class="max-h-72 overflow-y-auto divide-y divide-slate-50">
                @forelse ($recentNotifications as $notif)
                    @php
                        $notifData = $notif->data ?? [];
                        $isNew = is_null($notif->read_at);
                    @endphp
                    <div class="p-3.5 hover:bg-slate-50 transition flex items-start gap-3 {{ $isNew ? 'bg-indigo-50/30' : '' }}">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 {{ ($notifData['type'] ?? '') === 'grade_submitted' ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700' }}">
                            <span class="material-symbols-outlined text-[17px]">
                                {{ ($notifData['type'] ?? '') === 'grade_submitted' ? 'verified' : 'assignment' }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-800 truncate">
                                {{ $notifData['title'] ?? 'Pemberitahuan Baru' }}
                            </p>
                            <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                                {{ $notifData['message'] ?? 'Ada aktivitas terbaru di kelas Anda.' }}
                            </p>
                            <span class="text-[10px] text-slate-400 mt-1 block">
                                {{ $notif->created_at->diffForHumans() }}
                            </span>
                        </div>
                        @if ($isNew)
                            <form method="POST" action="{{ route('notifications.markAsRead', $notif->id) }}" class="shrink-0">
                                @csrf
                                <button type="submit" class="p-1 text-slate-300 hover:text-indigo-600" title="Tandai dibaca">
                                    <span class="material-symbols-outlined text-[16px]">done</span>
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-slate-400">
                        Tidak ada notifikasi terbaru.
                    </div>
                @endforelse
            </div>

            {{-- Footer Tautan ke Semua Notifikasi --}}
            <div class="p-3 bg-slate-50 text-center">
                <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center justify-center gap-1">
                    <span>Lihat Seluruh Notifikasi</span>
                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                </a>
            </div>
        </div>
    </div>
@endauth
```

---

### 📝 Perubahan 3: Tambahkan Script Toggle Dropdown & Realtime Auto-Polling
Cari bagian `<script>` di paling bawah file `layout.blade.php` (sebelum `</body>`), pastikan terdapat skrip toggle dan polling berikut:

```javascript
<script>
    // Toggle Mobile Navigation
    document.getElementById('nav-toggle')?.addEventListener('click', function () {
        const nav = document.getElementById('mobile-nav');
        nav.classList.toggle('hidden');
    });

    // Toggle Dropdown Lonceng Notifikasi
    const bellBtn = document.getElementById('notif-bell-button');
    const notifMenu = document.getElementById('notif-dropdown-menu');

    if (bellBtn && notifMenu) {
        bellBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            notifMenu.classList.toggle('hidden');
        });

        // Tutup dropdown jika klik di luar
        document.addEventListener('click', function (e) {
            if (!notifMenu.contains(e.target) && !bellBtn.contains(e.target)) {
                notifMenu.classList.add('hidden');
            }
        });
    }

    @auth
    // Realtime Auto-Polling Notifikasi (Poin 4 Modul: setiap 30 detik panggil /notifications/unread-count)
    setInterval(function () {
        fetch('{{ route('notifications.unreadCount') }}')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('navbar-notif-badge');
                if (badge) {
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
                const dropdownCount = document.getElementById('dropdown-unread-count');
                if (dropdownCount) {
                    dropdownCount.textContent = data.unread_count + ' baru';
                }
            })
            .catch(err => console.debug('Polling notifikasi error:', err));
    }, 30000);
    @endauth
</script>
```

---

## 🧪 Langkah 4: Uji Coba di Browser Lokal

Setelah kode dimasukkan:
1. Jalankan aplikasi di terminal VS Code:
   ```bash
   php artisan serve
   ```
2. Buka `http://127.0.0.1:8000`:
   * **Login sebagai Mahasiswa (`mahasiswa@kampuslms.test` / `password`):**
     * Klik menu **Nilai** di navbar: Anda akan melihat daftar tugas dan catatan umpan balik dari dosen beserta rata-rata per mata kuliah.
     * Klik lonceng notifikasi: dropdown preview akan muncul menampilkan notifikasi terbaru dengan tombol tandai dibaca.
   * **Login sebagai Dosen (`dosen@kampuslms.test` / `password`):**
     * Klik menu **Nilai** di navbar: tabel matriks rekapitulasi nilai seluruh mahasiswa per tugas akan tampil rapi lengkap dengan perhitungan nilai rata-rata otomatis.

---

## 📤 Langkah 5: Commit dan Push ke GitHub

Jika tampilan sudah berjalan rapi, jalankan perintah git berikut dari terminal VS Code Oktavia:

```bash
# 1. Cek file yang berubah
git status

# 2. Tambahkan semua file yang diedit & dibuat
git add resources/views/grades/index.blade.php resources/views/components/layout.blade.php

# 3. Lakukan commit
git commit -m "feat(ui): implementasi halaman rekap nilai dan dropdown lonceng notifikasi (Oktavia Nur Rahmadani)"

# 4. Push ke GitHub
git push origin Oktavia-Nur-10
```

Setelah di-push, Oktavia tinggal membuka GitHub dan mengajukan Pull Request (PR) ke branch `main`. Seluruh test CI akan otomatis lulus dan Milestone M3 kelompok selesai 100%!
