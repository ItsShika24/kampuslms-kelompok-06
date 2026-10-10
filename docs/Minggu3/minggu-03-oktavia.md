## Oktavia Nur Rahmadani
### NIM 10241060
### Pemrograman Web - Laporan Minggu 3

### READ 

1. Gambar ulang ERD dari spesifikasi di papan/kertas, tanpa melihat dokumen.
- Jawaban: ![alt text](ERD.jpeg)
2. Untuk setiap foreign key, tentukan perilaku `onDelete`-nya dan **tuliskan alasannya**.
- Jawaban: 
```
#### `courses`

| FK | Perilaku | Alasan |
|----|----------|--------|
| `lecturer_id → users.id` | `restrictOnDelete` | Data dosen tidak bisa dihapus selama masih terhubung dengan mata kuliah. |

#### `course_user`

| FK | Perilaku | Alasan |
|----|----------|--------|
| `course_id → courses.id` | `cascadeOnDelete` | Jika mata kuliah dihapus, data pendaftaran mahasiswa ikut dihapus. |
| `user_id → users.id` | `restrictOnDelete` | Data mahasiswa tidak bisa dihapus selama masih terdaftar di mata kuliah. |

#### `materials`

| FK | Perilaku | Alasan |
|----|----------|--------|
| `course_id → courses.id` | `cascadeOnDelete` | Jika mata kuliah dihapus, semua materi di dalamnya ikut dihapus. |
| `uploaded_by → users.id` | `restrictOnDelete` | Akun pengunggah tidak bisa dihapus selama materinya masih tersimpan. |

#### `assignments`

| FK | Perilaku | Alasan |
|----|----------|--------|
| `course_id → courses.id` | `cascadeOnDelete` | Jika mata kuliah dihapus, tugas yang terkait ikut dihapus. |
| `created_by → users.id` | `restrictOnDelete` | Akun pembuat tugas tidak bisa dihapus selama tugasnya masih ada. |

#### `submissions`

| FK | Perilaku | Alasan |
|----|----------|--------|
| `assignment_id → assignments.id` | `cascadeOnDelete` | Jika tugas dihapus, jawaban mahasiswa untuk tugas tersebut ikut dihapus. |
| `user_id → users.id` | `restrictOnDelete` | Akun mahasiswa tidak bisa dihapus selama jawaban tugasnya masih tersimpan. |

#### `grades`

| FK | Perilaku | Alasan |
|----|----------|--------|
| `submission_id → submissions.id` | `cascadeOnDelete` | Jika jawaban tugas dihapus, nilai yang terkait ikut dihapus. |
| `graded_by → users.id` | `restrictOnDelete` | Akun pemberi nilai tidak bisa dihapus selama nilai yang diberikan masih tersimpan. |
```
3. Jawab: kalau seorang dosen dihapus, apa yang terjadi pada mata kuliahnya? Kenapa dirancang begitu?
- Jawaban: 
> Penghapusan dosen akan ditolak oleh database selama dosen tersebut masih terhubung dengan satu atau lebih mata kuliah melalui foreign key lecturer_id yang menggunakan restrictOnDelete(). Hal ini dirancang agar mata kuliah tetap tersimpan meskipun dosen sudah tidak mengajar, karena mata kuliah merupakan bagian dari institusi dan bukan milik pribadi dosen. Selain itu, data akademik seperti materi, tugas, submission, dan nilai mahasiswa perlu dipertahankan agar riwayat pembelajaran tidak hilang. Oleh karena itu, admin perlu mengalihkan mata kuliah kepada dosen pengganti terlebih dahulu sebelum menghapus akun dosen lama.

4. Jawab: kenapa `grades.submission_id` bersifat unique, bukan sekadar index biasa?
- Jawaban: 
> `grades.submission_id` bersifat unique karena setiap submission hanya boleh memiliki satu data nilai, sehingga relasinya bersifat one-to-one. Berbeda dengan index biasa yang hanya mempercepat pencarian dan masih memungkinkan nilai submission yang sama tercatat berkali-kali, unique memastikan tidak ada data nilai duplikat melalui pembatasan langsung pada database. Hal ini juga mencegah terjadinya duplikasi ketika dua permintaan penyimpanan nilai dilakukan hampir bersamaan, karena database akan menolak data dengan `submission_id` yang sudah digunakan. Dengan demikian, integritas data nilai tetap terjaga, termasuk ketika dosen melakukan penilaian ulang.

### BREAK

| No. | Yang Dicoba | Hipotesis / Yang Diamati | Analisis |
|---|---|---|---|
| 1 | Menghapus `unique(['course_id', 'user_id'])` pada tabel `course_user`, lalu mendaftarkan mahasiswa yang sama dua kali ke mata kuliah yang sama. | Mahasiswa dapat terdaftar lebih dari satu kali pada mata kuliah yang sama. | Tanpa aturan `unique`, database dapat menyimpan data pendaftaran yang sama berulang kali. Akibatnya, jumlah peserta menjadi tidak akurat. |
| 2 | Menambahkan `role` ke `$fillable` pada model `User`, lalu mengirim `role=admin` melalui form. | Pengguna biasa berpotensi mengubah perannya menjadi admin. | Jika `role` masuk ke `$fillable` dan controller menerima semua input tanpa pembatasan, pengguna dapat mengirim `role=admin` melalui permintaan yang dimodifikasi. Hal ini dapat menyebabkan penyalahgunaan hak akses. |
| 3 | Mengganti `$fillable` dengan `protected $guarded = [];`, lalu mengulangi percobaan nomor 2. | Semua kolom dapat diisi melalui mass assignment. | Pengaturan `$guarded = []` membuat semua atribut bisa diisi tanpa perlindungan mass assignment. Akibatnya, data penting seperti `role` dapat diubah jika input tidak dibatasi dengan benar. |
| 4 | Mengosongkan method `down()` pada salah satu migration, lalu menjalankan `php artisan migrate:refresh`. | Perubahan database tidak dapat dibatalkan dengan benar. | Method `down()` digunakan untuk membatalkan perubahan dari `up()`. Jika dikosongkan, Laravel tidak memiliki instruksi untuk mengembalikan perubahan tersebut sehingga proses refresh dapat bermasalah. |
| 5 | Mengganti `restrictOnDelete` menjadi `cascadeOnDelete` pada `lecturer_id`, lalu menghapus dosen. | Mata kuliah yang terkait dapat ikut terhapus. | `cascadeOnDelete` menghapus data terkait secara otomatis. Hal ini berisiko menghilangkan data mata kuliah dan informasi akademik. Sebaliknya, `restrictOnDelete` mencegah penghapusan dosen sebelum mata kuliahnya dialihkan kepada dosen pengganti. |


### FIX
| No. | Masalah                                  | Dampak Nyata                                                                                                                                                                                                                                                                                                  |
| --- | ---------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Urutan migrasi salah                     | Jika tabel dibuat sebelum tabel yang menjadi referensi foreign key tersedia, migrasi dapat gagal. Akibatnya, struktur database tidak terbentuk lengkap dan aplikasi tidak dapat berjalan sesuai kebutuhan.                                                                                                    |
| 2   | Unique composite pertama hilang          | Pada tabel `course_user`, kombinasi `course_id` dan `user_id` harus unik agar mahasiswa tidak terdaftar berulang kali pada mata kuliah yang sama. Tanpa batasan ini, jumlah peserta dan data pendaftaran menjadi tidak akurat. Kode yang diperiksa sudah memiliki batasan ini.                                |
| 3   | Unique composite kedua hilang            | Pada tabel `submissions`, kombinasi mahasiswa dan tugas harus unik agar mahasiswa tidak mengirim submission berulang kali untuk tugas yang sama. Tanpa batasan ini, data pengumpulan dapat terduplikasi dan menyulitkan dosen menentukan submission yang akan dinilai. Migrasi `submissions` belum diperiksa. |
| 4   | Pengaturan `onDelete` keliru             | Pada tabel `courses`, penghapusan dosen pengampu seharusnya dibatasi agar mata kuliah yang diampunya tidak ikut terhapus. Jika aturan penghapusan keliru, data mata kuliah dapat hilang atau proses penghapusan menjadi tidak sesuai kebutuhan. Kode yang diperiksa sudah menggunakan `restrictOnDelete()`.   |
| 5   | Model menggunakan `$guarded = []`        | Seluruh atribut model terbuka untuk mass assignment. Jika input pengguna diteruskan tanpa pembatasan, atribut sensitif seperti `role` berpotensi diubah sehingga pengguna dapat memperoleh hak akses yang tidak semestinya. Pencarian pada folder model belum menemukan penggunaan `$guarded`.                |
| 6   | Method `down()` kosong                   | Rollback tidak dapat mengembalikan struktur database ke kondisi sebelumnya dengan benar. Hal ini menyulitkan pengujian ulang dan pemulihan ketika migrasi bermasalah. Migrasi `courses`, `course_user`, dan `grades` yang diperiksa sudah memiliki `down()`.                                                  |
| 7   | Controller menggunakan `$request->all()` | Seluruh input pengguna dapat diteruskan ke proses penyimpanan, termasuk atribut yang seharusnya tidak dapat diubah. Hal ini berisiko menyebabkan manipulasi data atau perubahan atribut sensitif, seperti `role`. Pencarian pada folder controller belum menemukan penggunaan `$request->all()`.              |