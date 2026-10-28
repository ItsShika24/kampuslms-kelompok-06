## Catatan Minggu 10 Pemrograman Web

#### Nama : Shabrina Putri Az-Zahra
#### NIM : 10241066

---

### READ
Pada tahap ini, dilakukan penelusuran (tracing) terhadap siklus hidup pekerjaan asinkron (job) mulai dari pemicuan event, penyimpanan ke tabel antrean, eksekusi oleh worker, hingga terciptanya record notifikasi di basis data.

**1.1 Matikan worker. Buat satu tugas baru. Buka tabel `jobs`, apa isinya?**

* **Langkah Pengujian :** 
1. Pastikan proses worker `php artisan queue:work` dimatikan.
2. Login sebagai Dosen (`dosen@kampuslms.test`) lalu buatlah 1 tugas baru bestatus `active`.
3. Buka database MySQL dan jalankan query berikut:
```
SELECT id, queue, attempts, reserved_at, available_at, create_at FROM jobs;
```

* **Hasil Pengamatan :**
    - Tabel `jobs` bertambah 1 baris record baru
    - Kolom `queue` bernilai `defau    lt`
    - Kolom `attempts` bernilai `0` (job belum dicoba eksek    usi)
    - Kolom `reserved_at` bernilai `NULL` (belum ada proses worker yang mengunci/menahan     job)
    - Kolom `created_at` dan `available_at` memuat _unix timestamp_ saat tugas disimpan

* **Analisis Konseptual :**
Karena konfigurasi `.env` menggunakan `QUEUE_CONNECTION=database`, pemanggilan `AssignmentPublished::dispatch($assignment)` pada `AssignmentController@store` memicu listener `SendAssignmentNotification` yang mengimplementasikan `ShouldQueue`. Laravel tidak langsung memproses pengiriman notifikasi di siklus request HTTP web, melainkan mengemasnya menjadi baris antrean pada tabel `jobs`. Karena worker mati, pekerjaan ini berstatus _pending_ (tertahan).


---

**1.2 Baca kolom `payload`. Bisakah Anda mengenali nama Listener dan ID tugasnya?**

* **Langkah Pengujian :**
Periksa isi kolom `payload` pada tabel `jobs`:
```sql
SELECT payload FROM jobs LIMIT 1;
```

* **Hasil Pengamatan :**
Kolom `payload` memuat data serialisasi JSON antrean:
```json
{
  "uuid": "8e3c12f4-7e82-491a-963a-d68a992bb311",
  "displayName": "App\\Listeners\\SendAssignmentNotification",
  "job": "Illuminate\\Queue\\CallQueuedListener",
  "maxTries": 3,
  "backoff": 10,
  "data": {
    "class": "App\\Listeners\\SendAssignmentNotification",
    "method": "handle",
    "data": [
      "O:29:\"App\\Events\\AssignmentPublished\":1:{s:10:\"assignment\";O:45:\"Illuminate\\Contracts\\Database\\ModelIdentifier\":4:{s:5:\"class\";s:21:\"App\\Models\\Assignment\";s:2:\"id\";i:12;s:9:\"relations\";a:0:{}s:10:\"connection\";s:5:\"mysql\";}}"
    ]
  }
}
```

* **Analisis Identifikasi :**
  - **Nama Listener:** Dikenali secara jelas pada properti `displayName` dan `data.class` sebagai `App\Listeners\SendAssignmentNotification`.
  - **ID Tugas:** Terlihat pada string serialisasi PHP di dalam `data.data`, yaitu `s:21:"App\Models\Assignment";s:2:"id";i:12;` yang menandakan bahwa tugas yang diterbitkan memiliki **ID = 12**.
  - **Efisiensi `ModelIdentifier`:** Laravel menggunakan trait `SerializesModels` sehingga antrean hanya menyimpan nama class model dan primary key ID tugas. Saat worker memproses job, instance model terbaru akan diambil ulang dari database (*fresh query*).

---

**1.3 Jalankan `php artisan queue:work`, amati keluarannya. Periksa tabel `jobs` lagi.**

* **Langkah Pengujian :**
Jalankan proses worker pada terminal:
```bash
php artisan queue:work
```

* **Hasil Pengamatan :**
  - Terminal worker mencatat log eksekusi:
    ```text
    2026-10-27 12:10:05 App\Listeners\SendAssignmentNotification ........ RUNNING
    2026-10-27 12:10:05 App\Listeners\SendAssignmentNotification ....... 14.20ms DONE
    ```
  - Pemeriksaan ulang isi tabel `jobs`:
    ```sql
    SELECT COUNT(*) FROM jobs;
    ```
    Hasil query bernilai `0` (kosong).

* **Analisis Konseptual :**
Worker mengambil job berstatus `available`, mengeksekusi method `handle()` pada listener untuk mengirim notifikasi, dan segera menghapus record dari tabel `jobs` setelah berhasil diselesaikan tanpa exception (*idempotent*).

---

**1.4 Periksa tabel `notifications`. Berapa baris yang muncul? Cocok dengan jumlah mahasiswa?**

* **Langkah Pengujian :**
Periksa data pada tabel `notifications`:
```sql
SELECT id, type, notifiable_type, notifiable_id, read_at, created_at FROM notifications;
```

* **Hasil Pengamatan :**
  - Terbentuk **20 baris notifikasi baru** secara serentak.
  - Nilai kolom `type` adalah `App\Notifications\NewAssignmentNotification`.
  - Nilai kolom `notifiable_type` adalah `App\Models\User`.
  - Kolom `notifiable_id` berisi masing-masing ID mahasiswa yang terdaftar di kelas.
  - Kolom `read_at` bernilai `NULL` (status belum dibaca).

* **Verifikasi Jumlah Data :**
Jumlah baris notifikasi (20 baris) **cocok 100%** dengan jumlah record mahasiswa terdaftar pada tabel pivot `course_user` untuk mata kuliah tersebut (`SELECT COUNT(*) FROM course_user WHERE course_id = 1;`). Ini membuktikan pemanggilan batch `Notification::send($students, ...)` berhasil mendistribusikan notifikasi ke seluruh peserta kelas tanpa memicu masalah query N+1.

---

**1.5 Baca kolom `data` pada satu notifikasi. Adakah yang tidak seharusnya tersimpan di situ?**

* **Langkah Pengujian :**
Ambil salah satu kolom payload data notifikasi:
```sql
SELECT data FROM notifications WHERE type = 'App\\Notifications\\NewAssignmentNotification' LIMIT 1;
```

* **Hasil Pengamatan Isi Payload :**
```json
{
  "assignment_id": 12,
  "course_name": "Pemrograman Web",
  "title": "Tugas 3 — Implementasi Queue & Notifikasi",
  "due_at": "05 Nov 2026, 23:59",
  "type": "assignment_published",
  "message": "Tugas baru 'Tugas 3 — Implementasi Queue & Notifikasi' telah diterbitkan pada mata kuliah Pemrograman Web.",
  "url": "http://127.0.0.1:8000/mahasiswa/tugas/12"
}
```

* **Audit Keamanan Data (Modul Minggu 10 Poin 8) :**
  - **Apakah terdapat data sensitif?** **Tidak ada.**
  - Kolom `data` tersimpan dalam format teks JSON biasa (*unencrypted*). Tidak ada password, token, maupun data privat.
  - Begitu pula pada `GradeGivenNotification`, payload data diaudit secara ketat agar **tidak menyimpan nilai angka (`score`) atau umpan balik dosen (`feedback`)**. Mahasiswa diarahkan untuk membuka halaman nilai resmi yang terproteksi oleh Policy.

---

### BREAK

Berikut adalah hasil pengamatan pengujian 8 skenario kerusakan sistem berdasarkan panduan modul 10.3:

| # | Skenario yang Diuji | Hasil Pengujian & Pengamatan | Dampak Risiko & Pencegahan di KampusLMS |
|---|---|---|---|
| 1 | Set `QUEUE_CONNECTION=database`, jangan jalankan worker, lalu buat tugas baru | Tugas tersimpan di database dan request berhasil redirect, namun mahasiswa tidak menerima notifikasi apa pun. | **Kegagalan Senyap (*Silent Failure*).** Tidak ada pesan error yang muncul. Mitigasi: Gunakan process manager (Supervisor) di server produksi untuk memastikan worker selalu aktif. |
| 2 | Hapus `implements ShouldQueue` dari Listener, buat tugas untuk 40 mahasiswa | Waktu respons web melambat signifikan (>1.200 ms) karena eksekusi dilakukan langsung saat request HTTP. | Blocking I/O. Jika terjadi kegagalan koneksi pihak ketiga (misal email), request web bisa error 500. Solusi: Listener wajib mengimplementasikan `ShouldQueue`. |
| 3 | Ganti `QUEUE_CONNECTION=sync` di `.env`, lalu ulangi pengujian nomor 2 | Eksekusi kembali sinkron, tabel `jobs` tetap kosong, dan respons pembuatan tugas tetap terasa lambat. | Driver `sync` meniadakan manfaat antrean. Hanya dipakai untuk keperluan unit testing. |
| 4 | Buat Notification melempar exception sengaja (`throw new \Exception(...)`), jalankan worker | Worker mencatat status `FAIL`, mencoba ulang 3 kali (`$tries = 3`), lalu memindahkan record ke tabel `failed_jobs`. | Mencegah infinite loop kegagalan. Job gagal tercatat permanen di `failed_jobs` dan dapat dipantau lewat `php artisan queue:failed`. |
| 5 | Hentikan paksa worker (`Ctrl+C`) di tengah pemrosesan job | Job tetap aman tersimpan di tabel `jobs` dan terkunci pada `reserved_at`. Setelah batas waktu habis, job dapat diambil worker lain. | Antrean database bersifat *fail-safe*. Pekerjaan yang terhenti tidak akan hilang di udara. |
| 6 | Memasukkan nilai angka (`score`) dan feedback dosen ke payload JSON notifikasi | Data nilai tersimpan mentah di tabel `notifications` dan dapat dibaca siapa saja yang memiliki akses database. | **Kebocoran Data Privasi (*Data Leakage*).** Nilai tidak boleh disimpan di payload notifikasi unencrypted, melainkan harus diakses via halaman terlindungi Policy. |
| 7 | Mengirim request `POST /notifications/{id}/read` dengan ID notifikasi milik orang lain | Sistem menolak akses dengan error `404 Not Found` / `ModelNotFoundException`. | **Celah IDOR pada Notifikasi.** Dicegah dengan *query scoping* relasi user: `$request->user()->notifications()->findOrFail($id)->markAsRead()`. |
| 8 | Mengganti channel Reverb broadcasting dari `PrivateChannel` ke `Channel` publik | Klien luar dapat mendengarkan (*listen*) channel tanpa autentikasi token dan menyadap notifikasi pengguna lain. | **Penyadapan Realtime.** Broadcast notifikasi wajib menggunakan `PrivateChannel` dengan otorisasi di `routes/channels.php`. |

---

### FIX

Pada latihan perbaikan repositori cacat (`kampuslms-broken` branch `w10`):
1. **Penerapan Asinkron Antrean:** Menambahkan `implements ShouldQueue` pada class listener agar proses pengiriman berjalan di antrean latar belakang.
2. **Pembersihan Masalah N+1:** Mengganti perulangan kirim notifikasi satu per satu dengan batch method `Notification::send($students, ...)`.
3. **Pembersihan Payload Sensitif:** Menghapus kolom `score` dan `feedback` dari data payload notifikasi penilaian untuk mencegah kebocoran data nilai.
4. **Penutupan Celah IDOR Notifikasi:** Mengubah query pencarian notifikasi pada controller dari pencarian global `DatabaseNotification::findOrFail($id)` menjadi relasi milik pengguna yang sedang login (`$request->user()->notifications()->findOrFail($id)`).
5. **Konfigurasi Ketahanan Job:** Menambahkan batasan `$tries = 3;`, jeda `$backoff = 10;`, dan method `failed()` untuk mencatat log error audit.
6. **Migrasi `failed_jobs`:** Menjalankan migrasi basis data untuk memastikan tabel penampung pekerjaan gagal tersedia di sistem.
7. **Pengamanan Channel Realtime:** Mengubah instansiasi broadcast channel dari `Channel` publik menjadi `PrivateChannel` terotorisasi.

**Bukti Pengukuran Waktu Respons (Sebelum vs Sesudah):**
- *Sebelum Perbaikan (Sinkron / Tanpa `ShouldQueue` untuk 40 Mahasiswa):* Waktu respons HTTP mencapai **~1.380 ms**.
- *Sesudah Perbaikan (Asinkron via Database Queue):* Waktu respons HTTP turun drastis menjadi **~38 ms** (responsif dan non-blocking).

---

### BUILD

Sebagai pemegang peran **Database & Testing**, kontribusi saya pada Milestone M3 Minggu 10 berfokus pada **Logika Query Rekap Nilai Bebas N+1, Seeder Data Uji Coba, dan Automated Test Suite**:

**1. Logika Agregasi & Query Rekap Nilai Bebas N+1 (`GradeController.php`)**
- Membangun method `index()` pada `app/Http/Controllers/GradeController.php` dengan otorisasi ketat berbasis role menggunakan `Gate::authorize('viewAny', Grade::class)`.
- Mengoptimalkan performa kueri menggunakan eager loading bersarang:
  ```php
  $relations = [
      'assignments' => fn (HasMany $query) => $query->orderBy('due_at'),
      'assignments.submissions' => function (HasMany $query) use ($user): void {
          $query->with(['student', 'grade']);
          if ($user->role === 'mahasiswa') {
              $query->where('user_id', $user->id);
          }
      },
  ];
  if ($user->role !== 'mahasiswa') {
      $relations['students'] = fn (BelongsToMany $query) => $query->orderBy('name');
  }
  ```
- Menerapkan pembatasan data (*query scoping*) otomatis: Dosen hanya dapat melihat rekap kelas yang diampunya, mahasiswa hanya dapat melihat nilainya sendiri, dan admin dapat melihat seluruh kelas.

**2. Seeder Tambahan (`NotificationAndGradeSeeder.php`)**
- Menyiapkan data tugas, submission, dan grade otomatis pada mata kuliah `SI101` untuk kebutuhan demonstrasi wawancara.
- Mengisi record awal di tabel `notifications` dengan kombinasi status terbaca (`read_at !== null`) dan belum terbaca (`read_at === null`).
- Memastikan struktur data payload pada seeder mematuhi aturan audit keamanan (bebas dari kunci `score` dan `feedback`).

**3. Automated Feature Test Suite (`tests/Feature/NotificationAndQueueTest.php`)**
Membangun 5 skenario pengujian komprehensif untuk memvalidasi pemenuhan deliverable Minggu 10:
- `test_assignment_creation_dispatches_event_and_notifies_enrolled_students`: Memastikan saat tugas aktif dibuat, event ter-dispatch dan seluruh mahasiswa kelas menerima notifikasi.
- `test_grading_submission_notifies_only_the_submission_owner`: Memastikan notifikasi penilaian hanya dikirimkan kepada mahasiswa pemilik tugas, bukan mahasiswa lain.
- `test_student_cannot_mark_other_students_notification_as_read`: Memvalidasi mitigasi celah IDOR bahwa mahasiswa A tidak dapat menandai notifikasi mahasiswa B (mengembalikan status 404).
- `test_notification_payload_does_not_leak_sensitive_grade_data`: Memastikan kolom `data` pada tabel notifikasi tidak membocorkan data nilai (`score` dan `feedback`).
- `test_grades_recap_is_properly_scoped_by_role`: Memastikan akses rekapitulasi nilai terisolasi secara sah berdasarkan hak akses peran masing-masing pengguna.
