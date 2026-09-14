## Nama : Tika Mila Wahyuni
## NIM : 10241070

# READ

### 1. Gambar ulang ERD dari spesifikasi di papan/kertas, tanpa melihat dokumen.

Jawaban:

![alt text](ERD.jpeg)

### 2. Perilaku `onDelete` Setiap Foreign Key

#### `courses`
| FK | Perilaku | Alasan |
|----|----------|--------|
| `lecturer_id → users.id` | `restrictOnDelete` | Dosen tidak boleh dihapus selama masih mengampu mata kuliah. Mata kuliah adalah milik institusi, bukan milik dosen — jika dosen keluar, mata kuliahnya dialihkan ke dosen lain, bukan ikut dihapus. |

#### `course_user`
| FK | Perilaku | Alasan |
|----|----------|--------|
| `course_id → courses.id` | `cascadeOnDelete` | Jika mata kuliah dihapus, data enrollment tidak lagi relevan dan harus ikut terhapus. |
| `user_id → users.id` | `restrictOnDelete` | Mahasiswa tidak boleh dihapus selagi masih terdaftar di mata kuliah — perlu unenroll dulu. |

#### `materials`
| FK | Perilaku | Alasan |
|----|----------|--------|
| `course_id → courses.id` | `cascadeOnDelete` | Materi melekat pada mata kuliah. Jika mata kuliah dihapus, materi-materinya tidak berguna lagi. |
| `uploaded_by → users.id` | `restrictOnDelete` | Dosen pengunggah tidak boleh dihapus selama materinya masih ada, agar ada jejak pertanggungjawaban konten. |

#### `assignments`
| FK | Perilaku | Alasan |
|----|----------|--------|
| `course_id → courses.id` | `cascadeOnDelete` | Tugas melekat pada mata kuliah. Jika mata kuliah dihapus, tugasnya (beserta submission) ikut terhapus. |
| `created_by → users.id` | `restrictOnDelete` | Dosen pembuat tugas tidak boleh dihapus selama tugasnya masih aktif dan ada submission mahasiswa. |

#### `submissions`
| FK | Perilaku | Alasan |
|----|----------|--------|
| `assignment_id → assignments.id` | `cascadeOnDelete` | Submission tidak bisa berdiri tanpa tugas. Jika tugas dihapus, submission ikut terhapus. |
| `user_id → users.id` | `restrictOnDelete` | Submission adalah dokumen akademik — mahasiswa tidak boleh dihapus jika masih ada submission miliknya. |

#### `grades`
| FK | Perilaku | Alasan |
|----|----------|--------|
| `submission_id → submissions.id` | `cascadeOnDelete` | Nilai tidak bermakna tanpa submission yang dinilai. Jika submission dihapus, nilainya ikut terhapus. |
| `graded_by → users.id` | `restrictOnDelete` | Dosen penilai tidak boleh dihapus selama nilai yang ia berikan masih ada, demi pertanggungjawaban penilaian. |

---

### 3. Kalau Seorang Dosen Dihapus, Apa yang Terjadi pada Mata Kuliahnya?

**Jawaban:** Penghapusan ditolak database dengan error constraint violation, selama dosen masih menjadi `lecturer_id` di minimal satu mata kuliah.

Karena di migrasi kita tulis:

```php
$table->foreignId('lecturer_id')->constrained('users')->restrictOnDelete();
```

**Kenapa dirancang begitu?**

1. **Mata kuliah milik institusi, bukan milik dosen.** Jika dosen resign, mata kuliahnya tetap harus ada dan dialihkan ke dosen pengganti.
2. **Sejarah akademik tidak boleh hilang.** Nilai mahasiswa, materi, dan tugas yang pernah dibuat tidak boleh ikut lenyap.
3. **Alur yang benar:** admin ubah dulu `lecturer_id` ke dosen pengganti → baru hapus akun dosen lama.

Kalau pakai `cascadeOnDelete`, menghapus satu dosen bisa sekaligus menghapus semua mata kuliah, materi, tugas, dan submission mahasiswanya — tanpa peringatan. Itu jauh lebih berbahaya.

---

### 4. Kenapa `grades.submission_id` Bersifat UNIQUE, Bukan Sekadar Index Biasa?

**Jawaban:** Karena satu submission hanya boleh punya **tepat satu nilai** (relasi one-to-one).

**Perbedaan index biasa vs UNIQUE:**

| | Index Biasa | UNIQUE |
|---|---|---|
| Fungsi | Mempercepat pencarian | Mempercepat + **menegakkan keunikan** |
| Boleh duplikat? | Ya | Tidak |
| Dijaga oleh | Kode aplikasi saja | Kode aplikasi + **database** |

**Kenapa tidak cukup dicek di aplikasi saja?**

Pengecekan di kode bisa gagal saat dua request datang hampir bersamaan (*race condition*). Misalnya dosen klik "Simpan Nilai" dua kali cepat kedua request bisa lolos pengecekan aplikasi sebelum salah satunya sempat tersimpan. Dengan `UNIQUE`, database yang langsung menolak insert kedua, tidak peduli seberapa cepat requestnya.

 `UNIQUE` pada `submission_id` memungkinkan kita pakai `updateOrCreate` dengan aman di endpoint `PUT /submissions/{id}/grade` tidak perlu khawatir membuat baris nilai duplikat saat dosen menilai ulang.

# BREAK


| No. | Yang Dicoba | Hipotesis / Yang Diamati | Analisis |
|---|---|---|---|
| **1** | Hapus `unique(['course_id', 'user_id'])` dari tabel `course_user`, lalu daftarkan mahasiswa yang sama ke satu mata kuliah sebanyak dua kali. | Data yang sama dapat masuk dua kali tanpa muncul pesan kesalahan. | Tanpa aturan `unique(['course_id', 'user_id'])`, database dapat menyimpan data yang sama lebih dari satu kali pada tabel `course_user`. Akibatnya, mahasiswa bisa tercatat dua kali pada mata kuliah yang sama. Hal ini dapat membuat jumlah peserta menjadi tidak sesuai dan memengaruhi data pada sistem. Oleh karena itu, aturan unik perlu diterapkan langsung pada database agar data ganda dapat dicegah. |
| **2** | Tambahkan `role` ke `$fillable` pada model `User`, lalu kirim data `role=admin` melalui form yang sebenarnya tidak memiliki pilihan role. | User biasa dapat menjadi admin hanya dengan mengirim nilai `role`. | Ketika `role` dimasukkan ke dalam `$fillable`, nilai tersebut dapat diisi melalui data yang dikirim dari luar. Jika controller menggunakan `$request->all()` atau tidak membatasi field yang boleh dikirim, pengguna dapat mengirim `role=admin` melalui Inspect Element, cURL, atau Postman. Akibatnya, pengguna biasa dapat memperoleh hak akses admin. |
| **3** | Ganti seluruh `$fillable` dengan `protected $guarded = [];`, lalu ulangi percobaan nomor 2. | Data apa pun yang dikirim dari luar dapat masuk ke database tanpa pembatasan. | Penggunaan `$guarded = []` membuat semua kolom pada model dapat diisi melalui data dari luar. Artinya, tidak ada lagi pembatasan terhadap field yang boleh diubah. Hal ini berbahaya karena pengguna dapat mengirim data yang seharusnya tidak boleh diubah, seperti `role`, status akun, atau data lainnya. |
| **4** | Kosongkan isi `down()` pada salah satu migration, lalu jalankan `php artisan migrate:refresh`. | Migration tidak dapat dikembalikan dengan benar sehingga proses gagal. | Method `down()` digunakan untuk mengembalikan perubahan yang dibuat oleh `up()`. Jika `down()` dikosongkan, Laravel tidak memiliki perintah untuk menghapus atau mengembalikan perubahan migration tersebut. Akibatnya, proses `migrate:refresh` dapat mengalami kegagalan karena struktur tabel sebelumnya tidak dapat dikembalikan dengan benar. |
| **5** | Ubah `restrictOnDelete` pada `lecturer_id` menjadi `cascadeOnDelete`, lalu hapus salah satu dosen. | Data yang berhubungan dengan dosen ikut terhapus. | `cascadeOnDelete` membuat data yang berhubungan dengan dosen ikut terhapus ketika dosen tersebut dihapus. Karena dosen memiliki hubungan dengan mata kuliah, penghapusan dosen dapat menyebabkan mata kuliah dan data lain yang terkait ikut terhapus. Hal ini berisiko menyebabkan kehilangan data penting. Penggunaan `restrictOnDelete` lebih aman karena dosen tidak dapat dihapus selama masih memiliki mata kuliah yang terkait. |