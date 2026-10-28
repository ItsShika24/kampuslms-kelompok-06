## Catatan Minggu 9 Pemrograman Web

#### Nama : Shabrina Putri Az-Zahra
#### NIM : 10241066

---

### READ

Materi dan submission harus disimpan dengan aman. Berkas yang disimpan di folder publik dapat dibuka lewat URL, sedangkan berkas privat hanya dapat diunduh melalui aplikasi setelah hak akses diperiksa.

| Aspek | Berkas Publik (`storage/app/public`) | Berkas Privat (`storage/app/private`) |
|---|---|---|
| **Contoh penggunaan** | Berkas yang memang boleh dilihat semua orang | Materi kuliah dan submission mahasiswa |
| **Cara membuka** | Langsung melalui URL setelah `storage:link` dibuat | Melalui halaman atau route aplikasi |
| **Siapa yang dapat mengakses** | Siapa pun yang mengetahui URL | Pengguna yang memiliki izin |
| **Penyimpanan di KampusLMS** | Tidak dipakai untuk materi dan submission | Dipakai pada disk `local` |

Nama asli file disimpan agar dapat ditampilkan di aplikasi. File fisiknya memakai nama acak agar nama dari pengguna tidak menjadi nama penyimpanan.

---

### BREAK 

Pengujian saya berfokus pada materi kuliah, berbeda dari pengujian unduh submission.

| # | Skenario yang Diuji | Hasil Pengujian | Pencegahan di KampusLMS |
|---|---|---|---|
| 1 | Dosen menambahkan materi berupa tautan yang benar tanpa mengunggah file. | Tautan berhasil disimpan sebagai materi. | Tautan boleh digunakan sebagai pilihan selain mengunggah file. |
| 2 | Dosen mengisi tautan dengan alamat yang tidak valid. | Materi ditolak dan tidak disimpan. | Alamat tautan diperiksa sebelum data disimpan. |
| 3 | Dosen mengunggah file materi yang ukurannya lebih dari 20 MB. | File ditolak karena melewati batas ukuran. | Ukuran file materi dibatasi maksimal 20 MB. |
| 4 | Mahasiswa yang tidak terdaftar di mata kuliah mencoba mengunduh materi. | Akses ditolak dengan status `403 Forbidden`. | Hanya pengguna yang berhak yang dapat mengunduh materi. |

---

### FIX 

Pada latihan branch `w09`:
1. **Pemisahan Disk Storage:** Mengubah konfigurasi storage materi dan submission dari `public` ke `local` (privat).
2. **Penutupan IDOR Unduhan:** Menambahkan otorisasi `Gate::authorize('view', $submission)` dan `Gate::authorize('download', $material)` pada method unduh di controller.
3. **Penamaan Berkas Acak:** Mengganti metode penyimpanan yang memakai nama pengguna asli menjadi `$file->store(...)` acak di direktori bersarang.
4. **Validasi Magic Bytes:** Mengganti aturan validasi dari pengecekan ekstensi dangkal menjadi `mimes:pdf,doc,docx` dengan batasan `max` dalam kilobyte.
5. **Pembersihan Berkas Fisik:** Menambahkan pemanggilan `Storage::disk('local')->delete()` saat record dihapus agar tidak meninggalkan sisa sampah di media penyimpanan.

---

### BUILD 

Sebagai bagian Database, Validasi & Testing, saya mengerjakan validasi upload dan pengujian keamanan berkas di KampusLMS.

**1. Validasi pengumpulan tugas**

Membuat StoreSubmissionRequest untuk memastikan berkas wajib diunggah, formatnya sesuai, dan ukurannya tidak lebih dari 10 MB. Catatan pengumpulan boleh dikosongkan.


**2. Validasi materi kuliah**

Membuat StoreMaterialRequest untuk memeriksa berkas materi maksimal 20 MB atau memastikan tautan materi yang dimasukkan valid.


**3. Pengujian berkas dan akses**

Membuat tes untuk memastikan file dengan isi yang tidak sesuai ditolak, mahasiswa tidak dapat mengunduh submission milik mahasiswa lain, dan pengguna tanpa login tidak dapat mengakses unduhan.


**4. Pengujian materi**

Menguji penyimpanan materi berupa tautan, penolakan tautan yang tidak valid, penolakan file yang melebihi batas 20 MB, serta penolakan akses materi bagi mahasiswa yang tidak terdaftar di kelas.

**5. Pengujian penghapusan dan pengumpulan ulang**

Memastikan file lama terhapus saat submission diperbarui, serta file fisik ikut dibersihkan saat data materi atau submission dihapus.

Pengujian tersedia di tests/Feature/FileUploadSecurityTest.php. Hasil pengujian terakhir: 13 tes lulus dengan 42 pemeriksaan.