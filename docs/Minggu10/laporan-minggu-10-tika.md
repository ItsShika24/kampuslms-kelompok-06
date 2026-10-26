# Laporan Praktikum Minggu 10 — Realtime, Event, Queue, dan Notifikasi
**Mata Kuliah:** SI2514024 Pemrograman Web (Semester Ganjil 2026/2027)  
**Proyek:** KampusLMS (Kelompok 06 — EduKampus)  
**Nama Penyusun:** Tika Mila Wahyuni  
**NIM:** 10241070  
**Peran:** Backend Architecture & Security (Event, Queue, Listener, Notifikasi & Anti-IDOR)  
**Milestone:** **M3 — Tugas 3 (Bobot 7% + Asesmen / Interview Kelompok)**  

---

## 📑 Daftar Isi
1. [Tahap 1: READ — Penelusuran Siklus Hidup Job Antrean](#1-tahap-1-read--penelusuran-siklus-hidup-job-antrean)
2. [Tahap 2: BREAK — Tabel Eksperimen & Analisis 8 Kerusakan](#2-tahap-2-break--tabel-eksperimen--analisis-8-kerusakan)
3. [Tahap 3: CHECKPOINT — Jawaban Analitis Pertanyaan Interview Minggu 10](#3-tahap-3-checkpoint--jawaban-analitis-pertanyaan-interview-minggu-10)
4. [Kesimpulan & Catatan Keamanan](#4-kesimpulan--catatan-keamanan)

---

## 1. Tahap 1: READ — Penelusuran Siklus Hidup Job Antrean

Pada tahap ini, dilakukan penelusuran (*tracing*) terhadap siklus hidup sebuah pekerjaan asinkron (*job*) mulai dari pemicuan event, penyimpanan ke tabel antrean, pemrosesan oleh worker, hingga tersimpan di tabel notifikasi.

### 1.1 Matikan worker. Buat satu tugas baru. Buka tabel `jobs` — apa isinya?
* **Langkah Uji:**
  1. Pastikan terminal worker (`php artisan queue:work`) dimatikan.
  2. Login sebagai Dosen (`dosen@kampuslms.test`), lalu buat sebuah tugas baru pada mata kuliah yang diampu.
  3. Buka basis data MySQL dan periksa tabel `jobs`:
     ```sql
     SELECT id, queue, attempts, reserved_at, available_at, created_at FROM jobs;
     ```
* **Hasil Pengamatan:**
  - Terdapat **1 baris record baru** di tabel `jobs`.
  - Kolom `queue` bernilai `default`.
  - Kolom `attempts` bernilai `0` (menandakan job belum pernah dieksekusi).
  - Kolom `reserved_at` bernilai `NULL` (karena belum ada worker yang mengambil dan mengunci job tersebut).
  - Kolom `created_at` dan `available_at` memuat stempel waktu unix (*unix timestamp*) saat tugas disimpan.
* **Analisis Konseptual:**
  Karena konfigurasi `.env` menggunakan `QUEUE_CONNECTION=database`, pemanggilan `AssignmentPublished::dispatch($assignment)` di controller memicu listener `SendAssignmentNotification` yang mengimplementasikan `ShouldQueue`. Laravel tidak langsung mengeksekusi pengiriman notifikasi saat request HTTP berlangsung, melainkan menyimpannya ke tabel `jobs`. Karena worker dimatikan, pekerjaan ini tetap tertahan dalam antrean (*pending*).

---

### 1.2 Baca kolom `payload`. Bisakah Anda mengenali nama Listener dan ID tugasnya?
* **Langkah Uji:**
  Periksa isi kolom `payload` pada tabel `jobs`:
  ```sql
  SELECT payload FROM jobs LIMIT 1;
  ```
* **Hasil Pengamatan Isi JSON Payload:**
  ```json
  {
    "uuid": "8e3c12f4-7e82-491a-963a-d68a992bb311",
    "displayName": "App\\Listeners\\SendAssignmentNotification",
    "job": "Illuminate\\Queue\\CallQueuedListener",
    "maxTries": 3,
    "backoff": 10,
    "timeout": null,
    "data": {
      "class": "App\\Listeners\\SendAssignmentNotification",
      "method": "handle",
      "data": [
        "O:29:\"App\\Events\\AssignmentPublished\":1:{s:10:\"assignment\";O:45:\"Illuminate\\Contracts\\Database\\ModelIdentifier\":4:{s:5:\"class\";s:21:\"App\\Models\\Assignment\";s:2:\"id\";i:12;s:9:\"relations\";a:0:{}s:10:\"connection\";s:5:\"mysql\";}}"
      ]
    }
  }
  ```
* **Analisis Identifikasi:**
  - **Nama Listener:** Sangat mudah dikenali pada properti `displayName` dan `data.class`, yaitu `App\Listeners\SendAssignmentNotification`.
  - **Identitas Tugas (ID Assignment):** Dapat dikenali pada string serialisasi PHP di dalam properti `data.data`. Laravel menggunakan `ModelIdentifier` berkat trait `SerializesModels` pada event:
    `s:21:"App\Models\Assignment";s:2:"id";i:12;`
    Artinya, tugas yang dipublikasikan memiliki **ID = 12**.
  - **Mengapa disimpan sebagai `ModelIdentifier` bukan data mentah?**
    Ini adalah fitur efisiensi memori Laravel. Queue hanya menyimpan nama class model dan ID primary key. Saat job dieksekusi nanti oleh worker, Laravel akan mengambil instance model terbaru dari database (*fresh query*).

---

### 1.3 Jalankan `php artisan queue:work`, amati keluarannya. Periksa tabel `jobs` lagi.
* **Langkah Uji:**
  Jalankan perintah worker di terminal:
  ```bash
  php artisan queue:work
  ```
* **Hasil Pengamatan Terminal Worker:**
  ```text
  2026-10-27 00:35:12 App\Listeners\SendAssignmentNotification ........ RUNNING
  2026-10-27 00:35:12 App\Listeners\SendAssignmentNotification ....... 12.45ms DONE
  ```
* **Pemeriksaan Tabel `jobs` Pasca-Eksekusi:**
  ```sql
  SELECT COUNT(*) FROM jobs;
  -- Hasil: 0 baris (KOSONG)
  ```
* **Analisis Konseptual:**
  Ketika worker aktif, proses daemon worker secara aktif melakukan polling pada tabel `jobs`. Worker mengambil job yang statusnya `available`, mengubah `reserved_at`, mengeksekusi method `handle()`, lalu setelah berhasil tanpa melempar exception, worker **otomatis menghapus job dari tabel `jobs`**. Ini menjamin pekerjaan yang sudah selesai tidak akan diproses ulang (*idempotent*).

---

### 1.4 Periksa tabel `notifications`. Berapa baris yang muncul? Cocok dengan jumlah mahasiswa?
* **Langkah Uji:**
  Periksa tabel `notifications` di database:
  ```sql
  SELECT id, type, notifiable_type, notifiable_id, read_at, created_at FROM notifications;
  ```
* **Hasil Pengamatan:**
  - Terdapat **20 baris notifikasi baru** yang dibuat secara serentak.
  - Nilai kolom `type` adalah `App\Notifications\NewAssignmentNotification`.
  - Kolom `notifiable_type` adalah `App\Models\User`.
  - Kolom `notifiable_id` berisi `id` dari masing-masing mahasiswa yang terdaftar di mata kuliah tersebut.
  - Kolom `read_at` bernilai `NULL` (status belum dibaca).
* **Verifikasi Jumlah Mahasiswa:**
  Jumlah baris record notifikasi (**20 baris**) **persis cocok 100%** dengan jumlah record mahasiswa terdaftar pada tabel pivot `course_user` untuk mata kuliah bersangkutan:
  ```sql
  SELECT COUNT(*) FROM course_user WHERE course_id = 1; -- Hasil: 20
  ```
  Ini membuktikan method `Notification::send($students, new NewAssignmentNotification($assignment))` berhasil mengirimkan notifikasi secara massal kepada seluruh peserta kelas yang berhak.

---

### 1.5 Baca kolom `data` pada satu notifikasi. Adakah yang tidak seharusnya tersimpan di situ?
* **Langkah Uji:**
  Ambil satu data notifikasi dan format payload JSON-nya:
  ```sql
  SELECT data FROM notifications WHERE type = 'App\\Notifications\\NewAssignmentNotification' LIMIT 1;
  ```
* **Hasil Pengamatan Isi Kolom `data`:**
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
* **Audit Keamanan Data (Poin 8 Modul Minggu 10):**
  - **Apakah ada data sensitif?** **TIDAK ADA.**
  - **Penjelasan:** Kolom `data` pada tabel `notifications` bersifat *unencrypted* (teks terbuka di basis data). Tidak ada kata sandi, token otentikasi, maupun data privat yang tersimpan di sini.
  - Pada notifikasi penilaian (`GradeGivenNotification`), kolom `data` juga **diaudit ketat** agar **TIDAK menyimpan nilai angka (`score`) maupun catatan evaluasi dosen (`feedback`)**. Mahasiswa diarahkan untuk melihat nilai melalui URL detail submission yang terproteksi oleh `Gate::authorize()` dan `SubmissionPolicy`.

---

## 2. Tahap 2: BREAK — Tabel Eksperimen & Analisis 8 Kerusakan

Sesuai instruksi modul praktikum, berikut adalah catatan pelaksanaan 8 skenario kerusakan sistem, prediksi sebelum mencoba, hasil pengamatan aktual, dan cara mitigasi keamanannya.

| # | Percobaan Kerusakan (*Action*) | Prediksi Sebelum Mencoba | Hasil Pengamatan Aktual | Bahaya / Pelajaran Utama & Mitigasi |
|:---:|---|---|---|---|
| **1** | Set `QUEUE_CONNECTION=database`, jangan jalankan worker, lalu dosen membuat tugas baru | Web dosen tetap merespons sukses (HTTP 302 redirect), namun mahasiswa tidak menerima notifikasi apa pun. | **Gagal total tanpa satu pun pesan error (*Silent Failure*).** Tugas tersimpan di DB, tabel `jobs` bertambah, tetapi tabel `notifications` kosong dan badge lonceng mahasiswa tidak bertambah. | **Paling berbahaya di produksi:** Aplikasi terlihat sehat dan tidak ada log error, tetapi fitur background mati total. Mitigasi: Gunakan process monitor seperti *Supervisor* atau *systemd* di server produksi untuk memastikan worker selalu hidup. |
| **2** | Hapus `implements ShouldQueue` dari class Listener (`SendAssignmentNotification`), buat tugas untuk 40 mahasiswa | Waktu respon saat dosen menekan tombol simpan akan terasa lambat karena pengiriman notifikasi dieksekusi dalam siklus web request. | Waktu respons HTTP melonjak tajam (dari ~35ms menjadi >1.200ms). Web browser dosen berputar lama sebelum halaman redirect muncul. | Pekerjaan berat menyandera pengguna (*blocking I/O*). Jika saluran pengiriman memakai email (SMTP) dan ada 1 email gagal, seluruh request HTTP bisa crash 500 dan tugas gagal tersimpan. Solusi: Wajib pasang `implements ShouldQueue`. |
| **3** | Ganti `QUEUE_CONNECTION=sync` di `.env`, lalu ulangi percobaan nomor 2 | Perilaku sama persis seperti nomor 2: eksekusi kembali sinkron, tabel `jobs` tidak pernah terisi. | Tabel `jobs` selalu kosong. Job langsung dieksekusi di thread PHP yang sama saat request berlangsung. Waktu respon dosen tetap lambat. | Driver `sync` meniadakan manfaat antrean. Hanya berguna untuk pengujian unit lokal (*testing environment*), bukan untuk lingkungan staging/production. |
| **4** | Buat class Notification melempar exception sengaja (`throw new \Exception('Simulasi error worker');`), lalu jalankan worker | Worker akan gagal memproses job, mencoba ulang beberapa kali, lalu memindahkannya ke tabel `failed_jobs`. | Terminal worker mencatat log `FAIL`. Job dicoba ulang sebanyak 3 kali dengan jeda delay 10 detik (`$backoff`). Setelah 3 kali gagal, method `failed()` mencatat log error ke `laravel.log` dan job dicatat permanen di tabel `failed_jobs`. | Mencegah loop kegagalan tak hingga (*infinite loop*) yang membebani CPU server. Admin dapat memeriksa daftar kegagalan lewat `php artisan queue:failed` dan memicu retry via `php artisan queue:retry all`. |
| **5** | Hentikan paksa worker (`Ctrl+C` / kill process) tepat saat job sedang diproses (*processing*) | Job yang sedang berjalan tidak akan hilang dari database. | Record job tetap tersimpan di tabel `jobs`. Kolom `reserved_at` terisi timestamp penguncian. Setelah timeout `retry_after` (default 90 detik) habis, worker baru dapat mengambil ulang job tersebut. | Antrean database Laravel bersifat *fail-safe*. Job yang belum selesai diproses tidak akan hilang di udara, melainkan dikembalikan ke antrean untuk dieksekusi ulang. |
| **6** | Menyimpan nilai angka (`score`) dan feedback dosen ke dalam payload JSON notifikasi, lalu periksa database | Nilai mahasiswa tersimpan sebagai teks terbuka di tabel `notifications` dan dapat diakses tanpa otorisasi. | Terjadi **kebocoran data privasi (*Data Leakage*)**. Siapa pun yang memiliki akses baca ke tabel notifikasi (atau lewat celah query) dapat melihat riwayat nilai seluruh mahasiswa tanpa melewati aturan otorisasi Policy. | **Pelanggaran Prinsip Privasi Akademik:** Payload notifikasi tidak boleh menyimpan data nilai. Mahasiswa harus diarahkan membuka halaman submission yang diproteksi `SubmissionPolicy` untuk melihat nilainya secara sah. |
| **7** | Mengirim request `POST /notifications/{id}/read` menggunakan ID notifikasi milik mahasiswa lain | Jika controller tidak memeriksa kepemilikan, notifikasi milik orang lain akan ditandai terbaca (IDOR). | **Eksperimen Kode Cacat:** Mahasiswa A berhasil menandai notifikasi Mahasiswa B.<br>**Eksperimen Kode Aman (KampusLMS):** Muncul error `404 Not Found` / `ModelNotFoundException` karena query dibatasi oleh `$request->user()->notifications()->findOrFail($id)`. | **Celah IDOR (Insecure Direct Object Reference) pada Notifikasi:** Penyerang bisa memanipulasi status baca notifikasi pengguna lain. Mitigasinya adalah melakukan *query scoping* langsung dari relasi objek pengguna yang sedang login. |
| **8** | Mengganti channel Reverb broadcasting dari `PrivateChannel` menjadi `Channel` publik biasa | Siapa pun dapat berlangganan ke channel tersebut dan membaca broadcast notifikasi pengguna lain secara realtime. | Klien luar yang terhubung ke WebSocket dapat melakukan `Echo.channel('users.1').listen(...)` tanpa perlu mengirim token autentikasi, menyadap isi notifikasi pengguna ID 1 secara realtime. | **Celah IDOR pada Arsitektur Realtime:** Broadcast notifikasi pribadi wajib menggunakan `PrivateChannel` dengan otorisasi channel di `routes/channels.php` (`$user->id === (int) $id`). |

---

## 3. Tahap 3: CHECKPOINT — Jawaban Analitis Pertanyaan Interview Minggu 10

Pertanyaan-pertanyaan berikut merupakan instrumen asesmen lisan (*interview*) Milestone M3 yang menguji pemahaman konseptual dan pertanggungjawaban kode:

### ❓ 1. Apa yang terjadi kalau queue worker mati saat notifikasi dikirim? Peragakan!
* **Jawaban:**  
  Jika worker mati saat event notifikasi dipicu oleh dosen:
  1. Web server dosen tetap merespons dengan cepat dan sukses (HTTP 302 / redirect).
  2. Data tugas berhasil tersimpan secara normal di tabel `assignments`.
  3. Job pengiriman notifikasi berhasil dibuat dan tersimpan aman di tabel `jobs`.
  4. Namun, **tidak ada satu pun notifikasi yang terkirim ke mahasiswa** dan badge lonceng mahasiswa tidak bertambah, karena tidak ada proses daemon worker yang mengambil dan menjalankan job tersebut.
* **Peragaan:**
  - Matikan terminal `php artisan queue:work`.
  - Buat tugas baru melalui dashboard dosen.
  - Tunjukkan di MySQL bahwa tabel `jobs` bertambah 1 baris record.
  - Buka akun mahasiswa di peramban lain: lonceng notifikasi tetap `0` (tidak ada notifikasi baru).
  - Jalankan kembali `php artisan queue:work` di terminal: terminal langsung mencetak log `Processing` dan `Processed`.
  - Refresh peramban mahasiswa: notifikasi tugas baru langsung muncul di lonceng.

---

### ❓ 2. Kenapa kegagalan itu tidak menghasilkan pesan error? Bagaimana Anda akan mengetahuinya di server produksi?
* **Jawaban:**
  * **Mengapa tidak ada error?**  
    Karena dari sudut pandang siklus request HTTP, tugas web server telah selesai dengan sempurna ketika job berhasil dimasukkan (*dispatched*) ke dalam antrean database. Menyimpan job ke tabel `jobs` adalah query `INSERT` yang sukses, sehingga tidak ada exception yang dilempar ke pengguna. Worker berjalan di luar siklus request web sebagai proses CLI terpisah, sehingga matinya worker tidak memengaruhi respons HTTP browser.
  * **Cara Mendeteksi di Server Produksi:**
    1. **Monitoring Jumlah Antrean (Queue Queue Metrics):** Menggunakan alat pemantau seperti *Laravel Horizon* (jika menggunakan Redis) atau menjalankan query monitoring tabel `jobs`:
       ```bash
       php artisan queue:monitor database:default --max=50
       ```
       Jika jumlah job yang tertahan (*pending jobs*) melebihi ambang batas wajar, sistem monitoring (misal Prometheus, Grafana, atau Datadog) akan mengirimkan peringatan (alert) ke tim DevOps.
    2. **Process Supervisor Health Check:** Menggunakan daemon supervisor (*systemd* atau *Supervisor*) yang dikonfigurasi dengan `autorestart=true`. Jika proses worker mati mendadak, supervisor akan otomatis menghidupkannya kembali dan mencatat statusnya ke log sistem (`/var/log/supervisor/`).
    3. **Tabel `failed_jobs`:** Memeriksa apakah terjadi penumpukan pekerjaan gagal menggunakan perintah:
       ```bash
       php artisan queue:failed
       ```

---

### ❓ 3. Apa beda `QUEUE_CONNECTION=sync` dan `database`? Kapan masing-masing dipakai?
* **Jawaban:**
  * **`QUEUE_CONNECTION=sync` (Sinkron):**
    - Pekerjaan dieksekusi **saat itu juga** di dalam thread proses PHP web request yang sama.
    - Pengguna (dosen) harus menunggu sampai seluruh notifikasi selesai dikirim sebelum halaman browser selesai dimuat.
    - Tidak memerlukan worker CLI (`php artisan queue:work`) dan tidak memakai tabel database `jobs`.
    - **Kapan dipakai:** Hanya saat pengembangan lokal (*local testing*), debugging sederhana, atau eksekusi *Automated Feature Tests* agar pengujian berjalan deterministik dalam satu transaksi.
  * **`QUEUE_CONNECTION=database` (Asinkron):**
    - Pekerjaan diserialisasi menjadi teks JSON dan disimpan ke tabel basis data `jobs`.
    - Web request pengguna langsung selesai seketika (*non-blocking*).
    - Memerlukan proses latar belakang (*queue worker*) yang berjalan secara kontinu untuk membaca dan memproses pekerjaan.
    - **Kapan dipakai:** Di lingkungan *staging* dan *production* untuk segala tugas yang memakan waktu I/O atau jaringan (seperti kirim notifikasi massal, kirim email SMTP, pemrosesan gambar/dokumen, kompresi berkas).

---

### ❓ 4. Tunjukkan Listener yang Anda tulis. Apa akibatnya kalau `ShouldQueue` dihapus?
* **Kode Listener ([SendAssignmentNotification.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Listeners/SendAssignmentNotification.php)):**
  ```php
  namespace App\Listeners;

  use App\Events\AssignmentPublished;
  use App\Notifications\NewAssignmentNotification;
  use Illuminate\Contracts\Queue\ShouldQueue;
  use Illuminate\Queue\InteractsWithQueue;
  use Illuminate\Support\Facades\Log;
  use Illuminate\Support\Facades\Notification;

  class SendAssignmentNotification implements ShouldQueue
  {
      use InteractsWithQueue;

      public int $tries = 3;
      public int $backoff = 10;

      public function handle(AssignmentPublished $event): void
      {
          $assignment = $event->assignment;
          $course = $assignment->course;

          if (! $course) {
              return;
          }

          // Bebas N+1: Ambil seluruh mahasiswa terdaftar sekaligus
          $students = $course->students()->get();

          if ($students->isNotEmpty()) {
              Notification::send($students, new NewAssignmentNotification($assignment));
          }
      }

      public function failed(\Throwable $exception): void
      {
          Log::error('SendAssignmentNotification gagal diproses di worker queue', [
              'error' => $exception->getMessage(),
              'file'  => $exception->getFile(),
              'line'  => $exception->getLine(),
          ]);
      }
  }
  ```
* **Akibat jika `implements ShouldQueue` Dihapus:**
  Class listener berubah status dari *Queued Listener* menjadi *Synchronous Listener*. Laravel akan langsung mengeksekusi method `handle()` di tengah-tengah pemanggilan controller dosen. Bila mata kuliah memiliki 40 mahasiswa, sistem akan melakukan iterasi pengiriman notifikasi secara langsung sebelum mengembalikan respon HTTP. Dosen akan mengalami *latency* tinggi (web terasa membeku/hang), dan jika terjadi error fatal di tengah pengiriman, data pembuatan tugas bisa gagal ter-commit.

---

### ❓ 5. Kenapa nilai mahasiswa tidak boleh masuk payload notifikasi?
* **Jawaban:**
  1. **Kolom Penyimpanan Tidak Terenkripsi:** Pada tabel database `notifications`, kolom `data` berformat teks biasa (`text` / `json`) yang disimpan tanpa enkripsi *at-rest*. Siapa pun yang memiliki hak akses membaca database (staf admin database, developer pengelola backup, atau jika ada celah kebocoran SQL injection) dapat membaca seluruh riwayat nilai mahasiswa.
  2. **Pelanggaran Prinsip Akses Berbasis Otorisasi (Bypass Policy):** Di aplikasi KampusLMS, nilai tugas dilindungi ketat oleh [GradePolicy.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies/GradePolicy.php) dan `Gate::authorize('view', $submission)`. Jika nilai dimasukkan ke dalam teks notifikasi, nilai tersebut menjadi data statis yang bebas dibaca tanpa melewati verifikasi policy.
  3. **Integritas Saat Terjadi Ralat Nilai:** Jika dosen meralat nilai tugas dari `75` menjadi `90`, payload notifikasi lama yang memuat angka `75` akan tetap tertinggal dan menimbulkan kebingungan (*data inconsistency*).
  * **Solusi Arsitektural:** Payload notifikasi hanya memuat ID tugas, ID submission, nama mata kuliah, dan pesan informatif. Mahasiswa diarahkan untuk membuka tautan resmi submission yang selalu mengambil data nilai terkini secara aman dari database setelah lolos otorisasi.

---

### ❓ 6. Tunjukkan apa yang mencegah mahasiswa menandai notifikasi orang lain sebagai sudah dibaca!
* **Kode Pengaman pada [NotificationController.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/NotificationController.php):**
  ```php
  public function markAsRead(Request $request, string $id)
  {
      $user = $this->syncAuthUser();
      if (! $user) {
          abort(401);
      }

      // KUNCI KEAMANAN ANTI-IDOR:
      // Query scoping dilakukan dari relasi objek pengguna yang login ($user->notifications())
      // BUKAN menggunakan model statis DatabaseNotification::findOrFail($id)
      $notification = $user->notifications()->findOrFail($id);
      
      $notification->markAsRead();

      if ($request->wantsJson()) {
          return response()->json([
              'success' => true,
              'message' => 'Notifikasi berhasil ditandai sebagai sudah dibaca.',
          ]);
      }

      return back()->with('success', 'Notifikasi berhasil ditandai sebagai sudah dibaca.');
  }
  ```
* **Mekanisme Pencegahan:**
  Penyerang tidak bisa mengeksploitasi IDOR karena query SQL yang dihasilkan oleh Eloquent adalah:
  ```sql
  SELECT * FROM notifications 
  WHERE notifiable_type = 'App\Models\User' 
    AND notifiable_id = [ID_USER_LOGIN] 
    AND id = '[ID_NOTIFIKASI]' 
  LIMIT 1;
  ```
  Jika Mahasiswa A mencoba mengirimkan ID notifikasi milik Mahasiswa B, database akan mengembalikan hasil kosong (karena `notifiable_id` tidak cocok), dan Laravel secara otomatis melempar `ModelNotFoundException` yang menghasilkan respon **`404 Not Found`**. Akses manipulasi data orang lain ditolak 100%.

---

### ❓ 7. Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?
* **Jawaban:**
  * **Bagian Kode:** Method `handle()` pada listener [SendAssignmentNotification.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Listeners/SendAssignmentNotification.php).
  * **Kode Awal yang Dihasilkan AI:**
    ```php
    public function handle(AssignmentPublished $event): void
    {
        // Kode bawaan saran AI lama:
        foreach ($event->assignment->course->students as $student) {
            $student->notify(new NewAssignmentNotification($event->assignment));
        }
    }
    ```
  * **Apa yang Saya Ubah:**
    Saya mengubah perulangan `foreach` tersebut menjadi pemanggilan massal:
    ```php
    public function handle(AssignmentPublished $event): void
    {
        $assignment = $event->assignment;
        $course = $assignment->course;

        if (! $course) {
            return;
        }

        // PERBAIKAN: Gunakan Facade Notification::send pada koleksi mahasiswa
        $students = $course->students()->get();

        if ($students->isNotEmpty()) {
            Notification::send($students, new NewAssignmentNotification($assignment));
        }
    }
    ```
  * **Alasan Perubahan:**
    Kode awal dari AI melakukan query loop satu per satu yang memicu masalah **N+1 Query** dan membuat insert database berulang kali sebanyak jumlah mahasiswa. Dengan menggantinya menggunakan Facade `Notification::send($students, ...)`, Laravel mengeksekusi pengiriman secara kolektif (*bulk processing*) yang jauh lebih hemat memori dan cepat. Selain itu, saya menambahkan pengecekan `$students->isNotEmpty()` untuk mencegah dispatch job kosong jika kelas belum memiliki peserta.

---

## 4. Kesimpulan & Catatan Keamanan

1. **Arsitektur Asinkron Andal:**  
   Pemisahan pemicu (*Event*) dan pemroses (*Listener ShouldQueue*) berhasil menjaga performa web dosen tetap cepat dan stabil tanpa terhambat beban pengiriman notifikasi.
2. **Resiliensi Antrean:**  
   Penerapan `$tries = 3`, `$backoff = 10`, dan pencatatan log error otomatis via method `failed()` menjamin kegagalan pengiriman tidak hilang tanpa jejak dan siap ditangani ulang.
3. **Standar Keamanan Tinggi:**  
   Aplikasi bebas dari celah kebocoran nilai pada payload notifikasi dan terlindungi dari serangan manipulasi notifikasi orang lain (*Anti-IDOR*). Seluruh kriteria Milestone M3 pada modul Minggu 10 telah dipenuhi secara menyeluruh.

---
*Laporan ini disusun secara mandiri sebagai dokumentasi pertanggungjawaban kode dan bukti kepenulisan tugas praktikum.*
