## Nama: Tika Mila Wahyuni
## NIM: 10241070

# READ

### 1. Jalankan `php artisan install:api`. Baca perubahan yang terjadi di `bootstrap/app.php`.

Jawaban:

Perubahan yang terjadi pada berkas `bootstrap/app.php` adalah penambahan baris:

`api: __DIR__.'/../routes/api.php',`

di dalam fungsi `->withRouting(...)`.
Baris ini memberitahukan Laravel untuk memuat berkas rute API dan secara otomatis memberikan awalan (prefix) URL `/api` serta middleware kelompok api pada semua rute yang didefinisikan di dalamnya

### 2. Buat satu endpoint `GET /api/v1/courses` sederhana.

Jawaban:

Di dalam berkas `routes/api.php`, rutenya didefinisikan sebagai berikut:

```php
Route::get('/v1/courses', function () {
    return \App\Http\Resources\CourseResource::collection(
        \App\Models\Course::with('lecturer')->paginate(15)
    );
});
```


### 3. Bandingkan dengan `CourseController` versi web yang sudah ada. Tulis di catatan: apa yang **sama** dan apa yang **berbeda** di antara keduanya?

Jawaban:

- Persamaan:
1. **Sumber data**: keduanya mengambil data dari model Eloquent yang sama (`Course`).

2. **Query Eloquent**: Keduanya menggunakan eager loading with('lecturer') untuk mencegah N+1 Query serta pagination (paginate(15)).

3. **Logika Filter**: Keduanya memfilter data berdasarkan peran (role) pengguna (dosen melihat MK yang diampu, mahasiswa melihat MK yang diikuti).

- Perbedaan:

|Aspek|Web Controller (`CourseController.php`)|API Controller (`Api/CourseController.php`)|
|----|---------------------------------------------|----------------------------------------------|
|**Format Keluaran**| Mengembalikan tampilan HTML Blade via `return view(...)`|Mengembalikan data terstruktur JSON via `CourseResource::collection(...)`|
|**Autentikasi & State**| Menggunakan Session & Cookie browser (*Stateful*)|Menggunakan Bearer Token Sanctum (*Stateless*)|
|**Metode Form**| Memiliki method `create()` dan `edit()` untuk menampilkan halaman form|Tidak punya method `create()` dan `edit()` karena API hanya menyediakan data mentah|
|**Penyaringan Data**| Objek model dikirim langsung ke view Blade|Data disaring secara ketat melalui API Resource (Whitelist) agar kolom rahasia tidak bocor|


### 4. Panggil endpoint API tanpa header `Accept: application/json`. Lalu dengan header itu. Catat bedanya.

Jawaban:

- **Tanpa Header `Accept: application/json`:**
Jika terjadi kesalahan (misalnya token tidak valid/401 atau validasi gagal/422), Laravel mengira pemanggilnya adalah browser biasa, sehingga Laravel akan mencoba mengembalikan halaman HTML (misalnya redirect ke halaman login HTML `/login` atau menampilkan halaman error HTML Symfony).

- **Dengan Header `Accept: application/json`:**
Laravel mengenali bahwa pemanggilnya adalah aplikasi/klien API. Jika terjadi error autentikasi atau validasi, Laravel pasti merespons dalam bentuk format JSON murni (misalnya `{"message": "Unauthenticated."}` dengan status HTTP 401).

### 5. Jalankan `php artisan route:list --path=api`. Cocokkan dengan kontrak di spesifikasi.

Jawaban:

```php
 POST            api/v1/assignments ......................... Api\AssignmentController@store
  PUT|PATCH       api/v1/assignments/{assignment} ........... Api\AssignmentController@update
  DELETE          api/v1/assignments/{assignment} .......... Api\AssignmentController@destroy
  GET|HEAD        api/v1/assignments/{assignment}/submissions Api\AssignmentController@submi…
  POST            api/v1/assignments/{assignment}/submissions Api\SubmissionController@store
  POST            api/v1/auth/login ................................ Api\AuthController@login
  POST            api/v1/auth/logout .............................. Api\AuthController@logout
  GET|HEAD        api/v1/courses ................................. Api\CourseController@index
  GET|HEAD        api/v1/courses/{course} ......................... Api\CourseController@show
  GET|HEAD        api/v1/courses/{course}/assignments ...... Api\CourseController@assignments
  GET|HEAD        api/v1/courses/{course}/materials .......... Api\CourseController@materials
  GET|HEAD        api/v1/me ........................................... Api\AuthController@me
  GET|HEAD        api/v1/notifications ..................... Api\NotificationController@index
  POST            api/v1/notifications/{id}/read ............ Api\NotificationController@read
  PUT             api/v1/submissions/{submission}/grade ...... Api\SubmissionController@grade

                                                                          Showing [15] routes

```
Hasil eksekusi perintah `php artisan route:list --path=api` menampilkan 15 endpoint, cocok dengan kontrak API di spesifikasi.


# BREAK

| # | Yang Dicoba | Prediksi & Hasil Pengamatan | Analisis Keamanan & Dampak |
|:---:|---|---|---|
| **1** | Kembalikan `response()->json(User::all())` di satu endpoint uji | Seluruh kolom tabel database pengguna tampil di JSON (`created_at`, `updated_at`, `email_verified_at`). Saat `$hidden` di model `User` dihapus sementara, **hash password (`password`) dan `remember_token` ikut bocor di layar.** | **Bahaya Model Mentah & Blacklist:** Mengembalikan model mentah mengirim semua kolom database. Properti `$hidden` bersifat *blacklist* (rawan bocor jika ada kolom sensitif baru yang lupa didaftarkan). **Solusi:** Wajib memakai **API Resource** yang bersifat *whitelist* (hanya kolom yang diizinkan yang keluar). |
| **2** | Hapus `auth:sanctum` dari grup route, lalu panggil tanpa token | Data privat (seperti daftar mata kuliah privat, materi, profil akun) langsung terbuka dan dapat diakses oleh siapa saja tanpa autentikasi (HTTP 200). | **Akses Tanpa Autentikasi (*Broken Authentication*):** Siapa pun atau bot luar dapat mengambil (*scraping*) data akademik kampus tanpa login. **Solusi:** Seluruh rute privat wajib dilindungi middleware `auth:sanctum`. |
| **3** | Panggil endpoint terlindungi dengan token yang sudah dihapus / logout | Server menolak permintaan dengan kode **HTTP 401 Unauthorized** dan pesan `{"message": "Unauthenticated."}`. | **Pencabutan Token (*Token Revocation*):** Menunjukkan mekanisme keamanan Sanctum bekerja dengan benar. Begitu pengguna logout (`tokens()->delete()`), token lama langsung mati seketika dan tidak bisa disalahgunakan lagi jika dicuri. |
| **4** | Login sebagai mahasiswa, panggil `POST /api/v1/assignments` | Permintaan ditolak dengan status **HTTP 403 Forbidden** (`{"message": "Anda tidak memiliki akses ke sumber daya ini."}`), bukan 401. | **Otorisasi Berbasis Peran (*Broken Access Control*):** Mahasiswa sudah terautentikasi sah (bukan 401), tetapi sistem memastikan mahasiswa tidak berhak membuat tugas (karena membuat tugas adalah wewenang Dosen/Admin). |
| **5** | Hapus *eager loading* (`with('lecturer')`), panggil daftar mata kuliah | Jumlah query database di query log melonjak drastis. Jika ada 15 mata kuliah, dieksekusi **1 query utama + 15 query terpisah** untuk setiap dosen (total 16 query). | **Masalah N+1 Query:** Memicu query berulang-ulang (*lazy loading*) yang sangat membebani server database dan membuat respon API lambat. **Solusi:** Selalu gunakan *eager loading* `with('lecturer')` dan `whenLoaded()` di Resource. |
| **6** | Hapus `throttle` dari rute login, jalankan 50 percobaan berturut-turut | Seluruh 50 percobaan login diproses terus-menerus oleh server tanpa ada jeda atau pemblokiran. | **Serangan Brute Force & DoS:** Bot dapat mencoba ratusan ribu kombinasi kata sandi per menit. Pengecekan `Hash::check()` yang berat juga dapat membuat CPU server mencapai 100% (*Denial of Service*). **Solusi:** Batasi dengan `throttle:5,1` agar percobaan ke-6 langsung diblokir (HTTP 429). |
| **7** | Buat pesan login berbeda untuk email salah vs password salah | Sistem membedakan respon: *"Email tidak terdaftar"* saat email salah, dan *"Password salah"* saat email valid tapi sandi salah. | **User Enumeration:** Penyerang dapat mengotomasi ribuan email untuk mencari tahu email dosen/mahasiswa mana saja yang terdaftar di kampus. **Solusi:** Gunakan satu pesan seragam: `"Email atau kata sandi salah."`. |

