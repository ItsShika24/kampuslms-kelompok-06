## Nama: Tika Mila Wahyuni
## NIM: 10241070

# LAPORAN PRAKTIKUM MINGGU 9
### Pengelolaan Unggahan Berkas, Penyimpanan Privat, dan Data Dinamis

---

## 1. READ — Bedah Pengelolaan Berkas Privat vs Publik di Laravel 12

Pada Minggu 9, arsitektur KampusLMS ditingkatkan untuk mendukung penanganan berkas fisik (materi perkuliahan dan pengumpulan tugas) dengan memisahkan tegas antara berkas publik dan berkas privat:

| Aspek | Berkas Publik (`storage/app/public`) | Berkas Privat (`storage/app/private` / `local`) |
|---|---|---|
| **Contoh di KampusLMS** | Foto profil pengguna, logo instansi, favicon | Berkas materi kuliah, berkas jawaban/tugas mahasiswa |
| **Metode Akses** | Akses langsung peramban via symlink `public/storage/...` | Melalui Controller terlindungi + `Gate::authorize()` via Policy |
| **Pengecekan Otorisasi** | Tidak ada (siapa saja yang tahu URL dapat mengunduh) | Wajib lolos Policy (Dosen pengampu, Mahasiswa terdaftar, Admin) |
| **Keamanan Nama Berkas** | Sering memakai nama asli atau hash publik | Wajib nama acak di disk fisik, nama asli di kolom `original_name` |
| **Risiko Kebocoran** | Sangat tinggi jika dipakai untuk tugas/materi berbayar | Nol jika controller menerapkan otorisasi ketat |

---

## 2. BREAK — Analisis 8 Kerusakan Berkas & Keamanan (Modul 9.3)

| # | Skenario Kerusakan yang Diuji | Pengamatan Dampak & Risiko | Solusi & Pencegahan di KampusLMS |
|---|---|---|---|
| 1 | Memindahkan submission ke `storage/app/public` dan akses URL langsung | Tugas seluruh kelas dapat diunduh siapa saja dari internet tanpa login. | Simpan mutlak di disk `'local'` (privat) di bawah `storage/app/private/`. |
| 2 | Menghapus `Gate::authorize()` dari endpoint download submission | Mahasiswa A mengganti ID submission di URL dan dapat mengintip jawaban teman (*IDOR pada File Download*). | Wajib memanggil `Gate::authorize('view', $submission)` sebelum memproses unduhan. |
| 3 | Menyimpan berkas dengan `storeAs()` memakai nama asli pengguna | Dua mahasiswa mengunggah `tugas.pdf` ke folder tugas yang sama; berkas mahasiswa pertama tertimpa dan hilang. | Gunakan `$file->store(...)` dengan nama unik acak; simpan nama asli di database. |
| 4 | Validasi berkas hanya memakai `extensions:pdf` tanpa `mimes:pdf` | Penyerang mengunggah skrip PHP berbahaya yang diberi nama `shell.pdf` dan lolos validasi. | Wajib menggunakan aturan `mimes:pdf,...` yang memverifikasi *magic bytes* isi berkas. |
| 5 | Mengisi aturan validasi `max:5` dengan asumsi satuan Megabyte | Berkas ukuran 1 MB tertolak karena satuan aturan `max` di Laravel adalah Kilobyte (5 KB). | Gunakan konversi KB yang benar (`max:5120` = 5 MB, `max:20480` = 20 MB). |
| 6 | Nilai `upload_max_filesize` di `php.ini` bernilai 2M | Unggahan berkas 3 MB langsung ditolak web server sebelum request mencapai validasi Laravel. | Pastikan `upload_max_filesize` dan `post_max_size` pada PHP server lebih besar dari validasi aplikasi. |
| 7 | Menggunakan `Submission::create` alih-alih `updateOrCreate` | Mahasiswa yang mengumpulkan ulang tugas memicu error duplicate entry composite key. | Gunakan `updateOrCreate(['assignment_id' => ..., 'user_id' => ...])`. |
| 8 | Menghapus record tugas/submission langsung dari basis data | Baris database terhapus tetapi berkas fisik tertinggal menjadi berkas yatim (*orphan files*). | Pasang lifecycle hook `booted() deleting` pada model Eloquent dan pembersihan cascade di controller. |

---

## 3. FIX - w09

Pada latihan branch `w09`:
1. **Pemisahan Disk Storage:** Mengubah konfigurasi storage materi dan submission dari `public` ke `local` (privat).
2. **Penutupan IDOR Unduhan:** Menambahkan otorisasi `Gate::authorize('view', $submission)` dan `Gate::authorize('download', $material)` pada method unduh di controller.
3. **Penamaan Berkas Acak:** Mengganti metode penyimpanan yang memakai nama pengguna asli menjadi `$file->store(...)` acak di direktori bersarang.
4. **Validasi Magic Bytes:** Mengganti aturan validasi dari pengecekan ekstensi dangkal menjadi `mimes:pdf,doc,docx` dengan batasan `max` dalam kilobyte.
5. **Pembersihan Berkas Fisik:** Menambahkan pemanggilan `Storage::disk('local')->delete()` saat record dihapus agar tidak meninggalkan sisa sampah di media penyimpanan.

---

## 4. BUILD — Kontribusi & Implementasi Tika Mila Wahyuni

Pada pengerjaan BUILD Minggu 9, saya fokus pada **Arsitektur Penyimpanan Privat, Controller Unduhan Terotorisasi, dan Manajemen Siklus Hidup Berkas Fisik**:

1. **Refaktor `MaterialController.php` ke Disk Privat:**
   - Mengubah method `store()` agar berkas materi disimpan pada disk `'local'` di path `materials/{course_id}`.
   - Mengubah method `download()` agar memverifikasi otorisasi `Gate::authorize('download', $material)` dan mengalirkan berkas melalui `Storage::disk('local')->download()`.
2. **Implementasi Unduh Submission Terproteksi di `SubmissionController.php`:**
   - Menambahkan method `download(Submission $submission)`.
   - Mengamankan akses via `Gate::authorize('view', $submission)` sehingga hanya pemilik jawaban, dosen pengampu, atau admin yang dapat mengunduh.
3. **Routing Unduhan di `routes/web.php`:**
   - Mendaftarkan route: `GET /submissions/{submission}/download` dengan name `submissions.download`.
4. **Lifecycle Hooks Pembersihan Berkas Fisik:**
   - Menambahkan event `deleting` pada method `booted()` di [`Material.php`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Models/Material.php) dan [`Submission.php`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Models/Submission.php) untuk menghapus berkas fisik secara otomatis saat row model dihapus.
   - Menambahkan accessor `formatted_file_size` agar ukuran berkas ditampilkan dalam format ramah manusia (B, KB, MB).
5. **Penanganan Kasus Cascade Delete:**
   - Pada [`CourseController@destroy`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/CourseController.php) dan [`AssignmentController@destroy`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/AssignmentController.php), menambahkan iterasi pembersihan berkas fisik materi dan tugas sebelum memicu cascade delete basis data, mencegah terjadinya *orphan files*.


