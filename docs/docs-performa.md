# Laporan Optimisasi Performa KampusLMS

## 1. Tujuan

Pengukuran performa dilakukan untuk mengetahui kinerja beberapa halaman pada aplikasi KampusLMS serta melihat pengaruh optimisasi terhadap jumlah query, waktu eksekusi query, dan penggunaan memori.

## 2. Metode Pengujian

Pengujian dilakukan menggunakan Laravel Debugbar pada beberapa halaman dengan peran pengguna yang berbeda. Data yang diamati meliputi:

- **Jumlah query:** banyaknya query database yang dijalankan.
- **Waktu query:** total waktu yang dibutuhkan untuk menjalankan query database.
- **Penggunaan memori:** jumlah memori yang digunakan selama proses halaman.

Pengujian dilakukan pada versi sebelum dan sesudah optimisasi untuk halaman yang memiliki data pembanding. Halaman lainnya dicatat berdasarkan hasil pengukuran versi terbaru.

## 3. Hasil Pengukuran Performa

### 3.1 Hasil Pengukuran Versi Terbaru

Tabel berikut diurutkan dari halaman dengan waktu eksekusi query paling lama hingga paling cepat.

| Halaman | Jumlah Query | Waktu Query (ms) | Memori |
|---|---:|---:|---:|
| Dashboard Dosen | 14 | 61,11 | 3 MB |
| Dashboard Mahasiswa | 13 | 37,82 | 5 MB |
| Rekap Nilai (Dosen) | 11 | 38,57 | 4 MB |
| Daftar Mata Kuliah (Dosen) | 8 | 22,50 | 3 MB |

### 3.2 Perbandingan Sebelum dan Sesudah Optimisasi

| Halaman | Kondisi | Jumlah Query | Waktu Query (ms) | Memori |
|---|---|---:|---:|---:|
| Dashboard Mahasiswa | Sebelum | 12 | 56,53 | 4 MB |
| Dashboard Mahasiswa | Sesudah | 13 | 37,82 | 5 MB |
| Rekap Nilai (Dosen) | Sebelum | 11 | 49,15 | 4 MB |
| Rekap Nilai (Dosen) | Sesudah | 11 | 38,57 | 4 MB |

### 3.3 Analisis Hasil

Pada halaman Dashboard Mahasiswa, waktu eksekusi query turun dari 56,53 ms menjadi 37,82 ms. Jumlah query bertambah dari 12 menjadi 13 dan penggunaan memori naik dari 4 MB menjadi 5 MB. Meskipun jumlah query bertambah, waktu eksekusi query secara keseluruhan menjadi lebih singkat.

Pada halaman Rekap Nilai Dosen, jumlah query tetap sebanyak 11. Namun, waktu eksekusi query turun dari 49,15 ms menjadi 38,57 ms, sedangkan penggunaan memori tetap 4 MB.

Hasil tersebut menunjukkan adanya penurunan waktu eksekusi query pada kedua halaman yang dibandingkan. Namun, jumlah query dan penggunaan memori juga perlu diperhatikan karena performa tidak hanya ditentukan oleh satu metrik.

## 4. Optimisasi yang Dilakukan

### 4.1 Menggunakan `withCount()`

`withCount()` digunakan untuk menghitung jumlah data relasi langsung melalui query utama. Cara ini menghindari proses penghitungan berulang di dalam perulangan pada Blade.

Contohnya, jumlah mahasiswa pada setiap mata kuliah dapat dihitung menggunakan `withCount('students')`. Jumlah tugas aktif juga dapat dihitung menggunakan `withCount()` dengan kondisi tertentu.

### 4.2 Menggunakan Cache untuk Statistik Dashboard

Statistik pada Dashboard menggunakan `Cache::remember()` dengan masa penyimpanan selama 10 menit.

Cache digunakan untuk menyimpan data statistik yang tidak harus selalu dihitung ulang setiap kali halaman dibuka. Kunci cache juga dibedakan berdasarkan ID pengguna agar data statistik tidak tercampur antar pengguna.

### 4.3 Menggunakan Eager Loading

Eager loading digunakan untuk mengambil data relasi yang dibutuhkan melalui `with()`. Cara ini membantu mengurangi query berulang ketika data relasi diakses.

Contohnya, data dosen, mahasiswa, tugas, dan nilai dapat dimuat bersama data utamanya sesuai kebutuhan halaman.

Pada beberapa bagian, kolom yang diambil juga dibatasi agar aplikasi tidak mengambil seluruh kolom jika hanya beberapa kolom yang diperlukan.

### 4.4 Lazy Loading pada Gambar

Untuk gambar yang berada di bagian bawah halaman dan belum langsung terlihat oleh pengguna, dapat digunakan atribut `loading="lazy"`.

Contoh:

```html
<img src="gambar.jpg" alt="Gambar" loading="lazy">
```

Lazy loading membantu menunda pemuatan gambar sampai gambar mendekati area tampilan pengguna. Namun, gambar utama yang langsung terlihat saat halaman dibuka sebaiknya tidak menggunakan lazy loading agar tetap dapat dimuat dengan cepat.

## 5. Audit N+1 Query dan Pemeriksaan EXPLAIN

### 5.1 Menemukan Query N+1

Audit dilakukan pada halaman dengan performa terburuk berdasarkan hasil pengukuran, yaitu Dashboard Dosen.

Langkah pemeriksaan:

1. Buka halaman Dashboard Dosen.
2. Buka Laravel Debugbar, kemudian pilih tab **Queries**.
3. Periksa daftar query yang dijalankan.
4. Temukan query yang berulang dengan pola sama, tetapi menggunakan nilai parameter yang berbeda.
5. Jika query tersebut dijalankan berulang untuk setiap data dalam suatu perulangan, hal tersebut dapat menjadi indikasi masalah N+1 query.
6. Catat query yang berulang sebagai bukti hasil audit.

**Hasil temuan query N+1:**

- Nama halaman: Dashboard Dosen
- Query yang berulang: [Isi berdasarkan hasil Debugbar]
- Jumlah pengulangan: [Isi jumlah pengulangan]
- Bagian kode yang berkaitan: [Isi controller atau Blade yang menyebabkan query]

### 5.2 Pemeriksaan Query Menggunakan EXPLAIN

Setelah menemukan query yang terlambat atau berulang, salin salah satu query tersebut dan jalankan melalui database dengan menambahkan perintah `EXPLAIN` di bagian awal.

Contoh format:

```sql
EXPLAIN SELECT * FROM nama_tabel WHERE kolom = 'nilai';
```

Hasil pemeriksaan perlu memperhatikan dua kolom berikut:

- **type:** menunjukkan jenis akses database untuk membaca data. Nilai seperti `ALL` menandakan pemeriksaan seluruh baris tabel, sedangkan `ref` atau `const` menunjukkan jenis pencarian yang lebih spesifik.
- **rows:** menunjukkan perkiraan jumlah baris yang perlu diperiksa oleh database. Semakin banyak baris yang harus diperiksa, semakin besar kemungkinan query membutuhkan waktu lebih lama.

**Hasil pemeriksaan EXPLAIN:**

| Informasi | Hasil |
|---|---|
| Query yang diperiksa | [Tempel query dari Debugbar] |
| Nilai `type` | [Isi hasil EXPLAIN] |
| Nilai `rows` | [Isi hasil EXPLAIN] |
| Analisis | [Jelaskan berdasarkan hasil pemeriksaan] |

## 6. Alasan Data Tertentu Menggunakan Cache

Statistik pada Dashboard dapat menggunakan cache karena data tersebut berupa ringkasan, seperti jumlah mata kuliah, jumlah mahasiswa, atau jumlah tugas. Data ringkasan tidak selalu harus dihitung ulang setiap kali halaman dibuka.

Sebaliknya, data nilai dan pengumpulan tugas sebaiknya tidak menggunakan cache yang terlalu lama karena dapat berubah sewaktu-waktu. Dosen dapat memberikan atau mengubah nilai, sedangkan mahasiswa dapat mengirimkan tugas. Jika data tersebut menggunakan cache yang belum diperbarui, pengguna bisa melihat informasi yang sudah tidak sesuai dengan kondisi terbaru.

Selain itu, data nilai dan pengumpulan tugas berkaitan dengan hak akses pengguna. Karena itu, data harus tetap diambil dan diperiksa berdasarkan pengguna yang sedang mengaksesnya.

## 7. Kesimpulan

Berdasarkan hasil pengukuran, optimisasi pada KampusLMS menunjukkan penurunan waktu eksekusi query pada Dashboard Mahasiswa dan Rekap Nilai Dosen. Penggunaan `withCount()`, eager loading, dan cache pada statistik dashboard membantu mengurangi pekerjaan database yang tidak perlu. Meskipun demikian, pemeriksaan query berulang melalui Debugbar dan analisis menggunakan `EXPLAIN` tetap diperlukan untuk menemukan bagian aplikasi yang masih berpotensi mengalami masalah N+1 atau proses pencarian data yang kurang efisien.