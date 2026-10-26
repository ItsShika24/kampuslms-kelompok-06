# Panduan & Pembagian Tugas BUILD Minggu 10
## Proyek: KampusLMS (Kelompok 06 — EduKampus)
**Mata Kuliah:** SI2514024 Pemrograman Web (Semester Ganjil 2026/2027)  
**Topik Minggu 10:** *Realtime, Event, Queue, dan Notifikasi*  
**Milestone:** **M3 — Tugas 3 (Bobot 7% + Interview Kelompok)**  

---

## 🎯 Target Utama BUILD Minggu 10 (Milestone M3)

Berdasarkan modul `05-modul-minggu-09-12.md`, terdapat **8 deliverable wajib** yang dinilai pada Tugas 3:

1. **Event + Listener + Notification** untuk dua peristiwa:
   - Tugas baru dipublikasikan (`AssignmentPublished` -> `NewAssignmentNotification`).
   - Nilai diberikan oleh dosen (`GradeSubmitted` -> `GradeGivenNotification`).
2. **Queue database aktif**, worker terdokumentasi di `README.md` (cara menjalankannya).
3. **`failed_jobs` terpasang**, job memiliki `$tries`, `$backoff`, dan method `failed()` yang mencatat error log.
4. **Lonceng notifikasi di navbar** dengan jumlah belum dibaca, didukung mekanisme auto-polling (interval 30 detik).
5. **Halaman daftar notifikasi**, dapat ditandai sudah dibaca dengan proteksi kepemilikan ketat (anti-IDOR).
6. **Alur penilaian lengkap**: Dosen melihat pengumpulan, memberi nilai + feedback, mahasiswa menerima notifikasi dan melihat nilainya.
7. **Rekap nilai** per mata kuliah (untuk dosen) dan per mahasiswa (untuk mahasiswa).
8. **Audit Keamanan Payload**: Payload notifikasi **tidak boleh memuat nilai atau feedback**, hanya ID, judul, nama mata kuliah, dan URL.

---

## 🌿 Aturan Git & Branch Tim

- Setiap anggota membuat branch kerja masing-masing dari branch `main` terbaru:
  - **Tika Mila Wahyuni:** `Tika-Mila-Wahyuni-10`
  - **Oktavia Nur Rahmadani:** `Oktavia-Nur-10`
  - **Shabrina Putri Az-Zahra:** `Shabrina-Azzahra-10`
- **Urutan Penggabungan (Merge PR):**
  1. Branch **Tika** di-merge terlebih dahulu ke `main` karena menyediakan Event, Listener, Notification, Queue configuration, dan route dasar.
  2. Branch **Oktavia** dan **Shabrina** melakukan pull/rebase dari `main` terbaru, lalu mengajukan PR untuk UI dan Testing.
  3. Review PR bersama sebelum digabungkan ke `main`.

---

## 📊 Matriks Pembagian Tugas (3 Orang)

| No | Anggota & NIM | Peran Utama | Fokus Deliverable BUILD Minggu 10 |
|:---:|---|---|---|
| 1 | **Tika Mila Wahyuni**<br>`10241070` | Backend & Security | **Poin 1, 2, 3, 8 & Backend Endpoint Notifikasi**<br>• Arsitektur Event, Listener (`ShouldQueue`), dan Notification database.<br>• Konfigurasi Queue database (`$tries`, `$backoff`, log `failed()`), `.env`, dan update `README.md`.<br>• Trigger dispatch di `AssignmentController` dan `SubmissionController`.<br>• Controller notifikasi (`unread-count`, `markAsRead` dengan proteksi anti-IDOR).<br>• Sanitasi keamanan data payload notifikasi. |
| 2 | **Oktavia Nur Rahmadani**<br>`10241060` | Frontend & UX / Form Validation | **Poin 4, 5, dan Tampilan UI Poin 6-7**<br>• Komponen Lonceng Notifikasi di navbar (`layout.blade.php`) + badge counter.<br>• Realtime auto-polling via JavaScript (setiap 30 detik ke `/notifications/unread-count`).<br>• Dropdown preview notifikasi cepat saat lonceng diklik.<br>• Halaman antarmuka daftar notifikasi (`resources/views/notifications/index.blade.php`).<br>• Tampilan Blade Halaman Rekap Nilai Dosen & Mahasiswa. |
| 3 | **Shabrina Putri Az-Zahra**<br>`10241066` | Database & Testing | **Poin 6, 7 (Logika Query Rekap Nilai) & Automated Test Suite**<br>• Controller & query agregasi Rekap Nilai Dosen & Mahasiswa (bebas N+1, otorisasi scoped).<br>• Seeder data submission ternilai & dummy notifikasi untuk demo.<br>• Automated Feature Tests lengkap (`tests/Feature/NotificationAndQueueTest.php`): dispatch event, queue listener, proteksi IDOR mark-as-read, dan audit keamanan payload. |

---

## 1. Tika Mila Wahyuni
- **NIM:** 10241070  
- **Peran:** *Backend & Security Architecture (Queue, Event, Notification & Anti-IDOR Scoping)*  
- **Fokus Deliverable:** Poin 1, 2, 3, 8, dan Backend Notifikasi  

### 📌 Rincian Tugas:
1. Membuat Event `app/Events/AssignmentPublished.php` (membawa model `Assignment`) dan `app/Events/GradeSubmitted.php` (membawa model `Grade` dan `Submission`).
2. Membuat Listener `app/Listeners/SendAssignmentNotification.php` dan `app/Listeners/SendGradeNotification.php`:
   - Mengimplementasikan `ShouldQueue`.
   - Hindari query N+1 saat mengambil daftar mahasiswa terdaftar: gunakan `Notification::send($students, ...)`.
   - Tambahkan keandalan: `public int $tries = 3;`, `public int $backoff = 10;`, dan method `failed(\Throwable $exception)` yang mencatat error ke `Log::error()`.
3. Membuat Notification `app/Notifications/NewAssignmentNotification.php` dan `app/Notifications/GradeGivenNotification.php`:
   - Saluran pengiriman via database (`return ['database'];`).
   - **Audit Keamanan Payload:** Dilarang menyimpan nilai atau feedback di kolom `data`. Simpan informasi aman saja (ID tugas, nama MK, judul, pesan, url).
4. Menyambungkan trigger dispatch:
   - Di `AssignmentController@store` saat tugas berstatus `active`.
   - Di `SubmissionController@grade` setelah nilai berhasil disimpan.
5. Membuat `app/Http/Controllers/NotificationController.php` dengan rute terproteksi:
   - `GET /notifications`: menampilkan halaman daftar notifikasi.
   - `GET /notifications/unread-count`: endpoint JSON untuk polling unread count.
   - `POST /notifications/{id}/read`: menandai notifikasi dibaca **dengan proteksi kepemilikan anti-IDOR** (`$request->user()->notifications()->findOrFail($id)->markAsRead()`).
   - `POST /notifications/mark-all-read`: menandai semua notifikasi pengguna dibaca.
6. Memperbarui `README.md` dengan instruksi menjalankan antrean (`php artisan queue:work`), penjelasan `QUEUE_CONNECTION=database`, dan perintah pengecekan job gagal (`php artisan queue:failed`, `php artisan queue:retry all`).

### 📁 Berkas Repo yang Diberikan ke AI Tika:
- `app/Http/Controllers/AssignmentController.php`
- `app/Http/Controllers/SubmissionController.php`
- `app/Models/User.php`
- `app/Models/Course.php`
- `app/Models/Assignment.php`
- `app/Models/Submission.php`
- `routes/web.php`
- `README.md`

### 💬 Prompt Siap Pakai untuk AI Tika:
```markdown
Konteks: Proyek KampusLMS Kelompok 06 (Laravel 12, PHP 8.4, MySQL). Saya Tika Mila Wahyuni (Backend & Security). Saya bertanggung jawab atas arsitektur Event, Queue, Listener, Notifikasi, dan keamanan endpoint-nya untuk Minggu 10 (Milestone M3).

Tolong implementasikan backend asinkron untuk 2 peristiwa sistem:
1. Dosen membuat/mempublikasikan tugas baru (AssignmentPublished) -> seluruh mahasiswa di mata kuliah tersebut menerima NewAssignmentNotification.
2. Dosen memberi nilai pada submission (GradeSubmitted) -> mahasiswa pemilik submission menerima GradeGivenNotification.

Ketentuan Keras Modul Minggu 10 & Aspek Keamanan:
1. Event:
   - app/Events/AssignmentPublished.php (membawa model Assignment).
   - app/Events/GradeSubmitted.php (membawa model Grade dan Submission).
2. Listener (Asinkron & Tahan Banting):
   - app/Listeners/SendAssignmentNotification.php (implements ShouldQueue, hindari query N+1 saat memuat mahasiswa, gunakan Notification::send($students, ...)).
   - app/Listeners/SendGradeNotification.php (implements ShouldQueue, kirim ke $submission->student).
   - Kedua listener wajib memiliki:
     * public int $tries = 3;
     * public int $backoff = 10;
     * method failed(\Throwable $exception): mencatat ke Log::error("Gagal mengirim notifikasi...", [...]).
3. Notification (Database & Payload Aman):
   - app/Notifications/NewAssignmentNotification.php (implements ShouldQueue, via database).
   - app/Notifications/GradeGivenNotification.php (implements ShouldQueue, via database).
   - ATURAN AUDIT KEAMANAN (Poin 8): Kolom `data` di tabel `notifications` TIDAK BOLEH memuat skor angka atau isi feedback dosen (mencegah kebocoran data sensitif). Payload hanya boleh memuat: 'assignment_id', 'course_name', 'title', 'message', dan 'url'.
4. Trigger Dispatch di Controller:
   - Tambahkan dispatch AssignmentPublished di AssignmentController@store saat status tugas dibuat 'active'.
   - Tambahkan dispatch GradeSubmitted di SubmissionController@grade setelah updateOrCreate Grade berhasil.
5. Controller Notifikasi & Mitigasi IDOR (app/Http/Controllers/NotificationController.php):
   - index(): list notifikasi pengguna yang sedang login (terurut terbaru).
   - unreadCount(): mengembalikan JSON ['unread_count' => $user->unreadNotifications()->count()] untuk polling.
   - markAsRead($id): PERIKSA KEPEMILIKAN (Anti-IDOR). Jangan gunakan Notification::find($id), melainkan:
     $notification = $request->user()->notifications()->findOrFail($id);
     $notification->markAsRead();
   - markAllAsRead(): tandai semua notifikasi milik $request->user() sudah dibaca.
6. Routing:
   - Daftarkan rute notifikasi di routes/web.php di dalam middleware 'auth'.
7. Dokumentasi Worker:
   - Tambahkan panduan eksekusi worker antrean di README.md: perintah 'php artisan queue:work', penjelasan QUEUE_CONNECTION=database, serta cara mengecek dan retry job gagal ('php artisan queue:failed' dan 'php artisan queue:retry all').

Berikan kode lengkap yang clean, terdokumentasi, dan aman sesuai standar keamanan Laravel 12.
```

---

## 2. Oktavia Nur Rahmadani
- **NIM:** 10241060  
- **Peran:** *Frontend & UX / Blade Components & Form Validation*  
- **Fokus Deliverable:** Poin 4, 5, dan Tampilan UI Poin 6-7  

### 📌 Rincian Tugas:
1. **Lonceng Notifikasi di Navbar (`resources/views/components/layout.blade.php`):**
   - Menambahkan icon lonceng (`material-symbols-outlined: 'notifications'`) di bar navigasi desktop dan mobile.
   - Badge counter berwarna merah/indigo menyala jika ada notifikasi belum dibaca.
   - Dropdown preview notifikasi ketika lonceng diklik (5 notifikasi terakhir, tombol tandai semua dibaca, dan link ke halaman semua notifikasi).
   - Auto-polling realtime: skrip JavaScript ringan yang setiap 30 detik memanggil endpoint `/notifications/unread-count` dan memperbarui counter lonceng tanpa reload halaman.
2. **Halaman Daftar Notifikasi (`resources/views/notifications/index.blade.php`):**
   - Tampilan berbasis card dengan tab filter: "Semua" dan "Belum Dibaca".
   - Indikator ikon pembeda antara tugas baru dan nilai keluar, nama mata kuliah, waktu relatif (`diffForHumans()`), dan tombol akses langsung ke tugas.
   - Aksi interaktif: tombol "Tandai Telah Dibaca" per notifikasi dan tombol "Tandai Semua Dibaca" (CSRF protected).
3. **Tampilan UI Rekap Nilai (`resources/views/grades/index.blade.php`):**
   - Tampilan Dosen: Tabel rekap nilai mahasiswa per mata kuliah (NIM, Nama, Nilai tugas 1..n, Nilai rata-rata).
   - Tampilan Mahasiswa: Tabel/kartu daftar tugas yang telah dinilai beserta feedback dari dosen.
   - Desain responsif, clean, dan selaras dengan tema portal EduKampus.

### 📁 Berkas Repo yang Diberikan ke AI Oktavia:
- `resources/views/components/layout.blade.php`
- `resources/views/submissions/show.blade.php`
- `resources/views/dashboard.blade.php`
- `routes/web.php`

### 💬 Prompt Siap Pakai untuk AI Oktavia:
```markdown
Konteks: Proyek KampusLMS Kelompok 06 (Laravel 12, Tailwind CSS, Blade). Saya Oktavia Nur Rahmadani (Frontend & Form Validation). Saya bertanggung jawab atas antarmuka Lonceng Notifikasi, Halaman Notifikasi, dan Halaman Rekap Nilai untuk Minggu 10 (Milestone M3).

Tolong buatkan komponen antarmuka pengguna yang estetik, responsif, dan interaktif:

1. Lonceng Notifikasi di Navbar (resources/views/components/layout.blade.php):
   - Tambahkan tombol ikon lonceng (material-symbols-outlined: 'notifications') di sebelah nama/profil pengguna pada navbar desktop dan mobile.
   - Tambahkan badge counter berwarna merah/indigo menyala jika ada notifikasi belum dibaca (ambil dari auth()->user()->unreadNotifications->count()).
   - Buat dropdown interaktif saat lonceng diklik:
     * Menampilkan 5 notifikasi terbaru.
     * Tanda pembeda visual antara yang sudah dibaca vs belum dibaca.
     * Tombol "Tandai Semua Dibaca" dan tautan "Lihat Semua Notifikasi".
   - Realtime Auto-Polling (Poin 4 Modul): Tambahkan JavaScript ringan yang setiap 30 detik memanggil endpoint '/notifications/unread-count' via fetch() dan mengupdate badge counter lonceng secara otomatis tanpa reload halaman.

2. Halaman Daftar Notifikasi (resources/views/notifications/index.blade.php):
   - Buat layout berbasis card dengan tab filter: "Semua" dan "Belum Dibaca".
   - Tampilkan badge ikon berbeda untuk tipe tugas baru vs nilai keluar, nama mata kuliah, teks pemberitahuan, waktu relatif (contoh: '5 menit yang lalu' via diffForHumans()), serta tombol langsung menuju tugas.
   - Sediakan tombol "Tandai Sudah Dibaca" per item (via form POST ber-CSRF) dan tombol "Tandai Semua Sudah Dibaca".

3. Halaman UI Rekap Nilai (resources/views/grades/index.blade.php):
   - Untuk Dosen: Tampilan tabel rekapitulasi nilai mahasiswa per mata kuliah (NIM, Nama Mahasiswa, kolom nilai masing-masing tugas, dan nilai rata-rata).
   - Untuk Mahasiswa: Tampilan kartu/tabel daftar seluruh tugas yang sudah dikerjakan, status penilaian, nilai yang diperoleh, dan catatan feedback dosen.
   - Desain modern bernuansa Indigo/Slate selaras dengan tema EduKampus.

Pastikan semua form memiliki token @csrf, tombol mudah diklik di smartphone, dan tidak ada layout yang pecah.
```

---

## 3. Shabrina Putri Az-Zahra
- **NIM:** 10241066  
- **Peran:** *Database & Testing (Model Aggregation, Seeder & Automated Tests)*  
- **Fokus Deliverable:** Poin 6, 7 (Logika Query Backend) & Automated Test Suite  

### 📌 Rincian Tugas:
1. **Controller Rekap Nilai (`app/Http/Controllers/GradeController.php`):**
   - Logika agregasi data rekap nilai per mata kuliah untuk dosen dan rekap nilai per mahasiswa untuk mahasiswa.
   - Mengoptimalkan performa query (Eager Loading dengan `with(...)`) untuk mencegah problem query N+1.
   - Menerapkan query scoping dan otorisasi ketat (`GradePolicy` / `Gate::authorize`).
2. **Seeder Tambahan (`database/seeders/NotificationAndGradeSeeder.php`):**
   - Menyiapkan data tugas dengan submission yang telah dinilai.
   - Menyiapkan contoh record di tabel `notifications` (dibaca dan belum dibaca) untuk kelancaran demo saat interview.
   - Mendaftarkannya di `DatabaseSeeder.php`.
3. **Automated Feature Tests (`tests/Feature/NotificationAndQueueTest.php`):**
   - Menguji event dispatch dan notifikasi saat dosen mempublikasikan tugas.
   - Menguji notifikasi penilaian hanya terkirim ke pemilik submission.
   - Menguji bahwa payload tabel notifikasi bersih dari data skor/feedback.
   - Menguji mitigasi IDOR: mahasiswa tidak bisa menandai notifikasi mahasiswa lain sebagai dibaca (status 403 atau 404).
   - Menguji query scoping rekap nilai sesuai hak akses peran.

### 📁 Berkas Repo yang Diberikan ke AI Shabrina:
- `app/Models/Grade.php`
- `app/Models/Submission.php`
- `app/Models/Course.php`
- `app/Policies/GradePolicy.php`
- `database/seeders/DatabaseSeeder.php`
- `tests/Feature/SubmissionTest.php`

### 💬 Prompt Siap Pakai untuk AI Shabrina:
```markdown
Konteks: Proyek KampusLMS Kelompok 06 (Laravel 12, PHPUnit / Pest). Saya Shabrina Putri Az-Zahra (Database & Testing). Saya bertanggung jawab atas Logika Query Rekap Nilai, Seeder, dan Automated Tests untuk Minggu 10 (Milestone M3).

Tolong buatkan Controller Rekap Nilai, Seeder data uji coba, dan Automated Test Suite lengkap:

1. Controller Rekap Nilai (app/Http/Controllers/GradeController.php):
   - Method index(Request $request):
     * Role Dosen: Ambil mata kuliah miliknya, lalu susun data mahasiswa yang terdaftar beserta submission & grade untuk setiap tugas di mata kuliah tersebut.
     * Role Mahasiswa: Ambil seluruh tugas di mata kuliah yang diikutinya beserta nilai submission miliknya sendiri (dilarang menampilkan nilai mahasiswa lain).
     * Role Admin: Dapat melihat seluruh mata kuliah dengan opsi filter.
   - Wajib optimasi query (Eager Loading): Gunakan with(['assignments', 'students.submissions.grade']) untuk mencegah problem query N+1.
   - Terapkan otorisasi ketat menggunakan Gate::authorize() atau GradePolicy.

2. Seeder Tambahan (database/seeders/NotificationAndGradeSeeder.php):
   - Buat seeder yang mengisi beberapa data tugas dengan submission yang sudah dinilai, serta beberapa record awal di tabel `notifications` (gabungan antara yang sudah dibaca dan yang belum dibaca) untuk memudahkan demo saat interview.
   - Panggil seeder ini di DatabaseSeeder.php.

3. Automated Feature Tests (tests/Feature/NotificationAndQueueTest.php):
   Buatkan pengujian menyeluruh untuk memenuhi kriteria penilaian Tugas 3:
   a. test_assignment_creation_dispatches_event_and_notifications(): Verifikasi saat Dosen membuat tugas berstatus 'active', event AssignmentPublished ter-dispatch dan notifikasi terkirim ke mahasiswa yang terdaftar.
   b. test_grading_submission_notifies_only_the_submission_owner(): Verifikasi saat Dosen memberi nilai, notifikasi HANYA dikirimkan ke mahasiswa pemilik submission tersebut.
   c. test_notification_payload_does_not_leak_sensitive_grade_data(): Verifikasi isi kolom `data` pada tabel `notifications` tidak memuat kunci 'score' atau 'feedback'.
   d. test_student_cannot_mark_other_students_notification_as_read(): Verifikasi mahasiswa A tidak bisa menandai notifikasi milik mahasiswa B (harus return 404 atau 403 Forbidden - uji mitigasi IDOR).
   e. test_grades_recap_is_properly_scoped_by_role(): Verifikasi mahasiswa tidak bisa mengintip rekap nilai mahasiswa lain.

Pastikan seluruh skenario test berjalan sukses (Green/Pass) saat dieksekusi dengan perintah 'php artisan test'.
```

---

## 🎓 Catatan Penting untuk Sesi Interview Tugas 3 (Checkpoint 10.4)

Pertanyaan-pertanyaan berikut sering ditanyakan oleh dosen saat sesi interview Milestone M3:

1. **Apa yang terjadi kalau queue worker mati saat notifikasi dikirim?**
   - *Jawaban:* Request web tetap berjalan sukses dan cepat (tidak crash), namun job akan tertimbun di tabel `jobs` dan notifikasi **tidak pernah sampai** ke tabel `notifications`. Ini disebut *silent failure*.
2. **Bagaimana cara mengetahui bahwa worker mati di server produksi?**
   - *Jawaban:* Menggunakan process monitor (seperti Supervisor atau systemd) untuk auto-restart worker, memonitor antrean job via monitoring tool (seperti Laravel Horizon atau Prometheus), dan mengecek log alert jika tumpukan antrean melebihi threshold tertentu.
3. **Kenapa nilai mahasiswa dilarang masuk payload notifikasi?**
   - *Jawaban:* Kolom `data` di tabel `notifications` tersimpan dalam format teks JSON biasa (unencrypted). Siapapun yang memiliki akses read tabel (seperti admin atau log viewer) bisa membaca nilai rahasia mahasiswa. Selain itu, notifikasi cukup mengarahkan pengguna ke halaman submission resmi yang telah diproteksi oleh `GradePolicy`.
4. **Tunjukkan kode yang mencegah mahasiswa menandai notifikasi orang lain sebagai sudah dibaca (IDOR)!**
   - *Jawaban:* Pada `NotificationController@markAsRead`, penelusuran record menggunakan relasi pengguna yang sedang login: `$request->user()->notifications()->findOrFail($id)->markAsRead();`. Jika ID bukan milik pengguna tersebut, sistem otomatis melempar `ModelNotFoundException` (HTTP 404).
