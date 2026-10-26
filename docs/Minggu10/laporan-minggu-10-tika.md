## Nama: Tika Mila Wahyuni
## NIM: 10241070

## Laporan Praktikum Minggu 10 — Realtime, Event, Queue, dan Notifikasi


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
