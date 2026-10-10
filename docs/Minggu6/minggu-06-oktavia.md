## Oktavia Nur Rahmadani
### NIM 10241060
### Pemrograman Web - Laporan Minggu 6

---
### READ
**1. Jalankan php artisan install:api. Baca perubahan yang terjadi di bootstrap/app.php.**

**Jawaban:** Perintah php artisan install:api digunakan untuk menambahkan dukungan API pada Laravel, termasuk integrasi Laravel Sanctum. Pada proses ini, terminal menunjukkan bahwa berkas routes/api.php sudah tersedia sehingga Laravel tidak membuat berkas baru. Konfigurasi pada bootstrap/app.php perlu diperiksa untuk memastikan routing API sudah terdaftar melalui baris berikut di dalam withRouting(...):
```
api: __DIR__.'/../routes/api.php',
```
Konfigurasi tersebut membuat Laravel memuat route dari routes/api.php dengan prefix /api dan middleware grup API.

**2. Buat satu endpoint GET /api/v1/courses sederhana.**

**Jawaban:** Endpoint dibuat pada berkas routes/api.php untuk menyediakan daftar mata kuliah dalam format JSON. Jika CourseResource sudah tersedia, endpoint dapat ditulis seperti berikut:
```
use App\Models\Course;
use App\Http\Resources\CourseResource;
use Illuminate\Support\Facades\Route;

Route::get('/v1/courses', function () {
    return CourseResource::collection(
        Course::with('lecturer')->paginate(15)
    );
});
```
Endpoint tersebut mengambil data mata kuliah beserta relasi dosen menggunakan with('lecturer'), kemudian membatasi hasil menjadi 15 data per halaman melalui paginate(15). Penggunaan CourseResource membantu mengatur struktur data yang dikirimkan kepada klien API.

**3. Bandingkan dengan CourseController versi web yang sudah ada. Tulis di catatan: apa yang sama dan apa yang berbeda di antara keduanya?**

**Jawaban:**
- Persamaan:
1. Keduanya dapat menggunakan model Course sebagai sumber data dari database.
2. Keduanya dapat memanfaatkan Eloquent untuk mengambil data dan relasi antarmodel.
3. Keduanya dapat menerapkan pagination dan penyaringan data sesuai kebutuhan aplikasi.
- Perbedaan:

| Aspek | `CourseController` Web | Endpoint API |
|---|---|---|
| Format respons | Mengembalikan halaman HTML melalui Blade. | Mengembalikan data dalam format JSON melalui `CourseResource`. |
| Tujuan | Melayani interaksi pengguna melalui halaman web. | Menyediakan data untuk aplikasi atau klien lain. |
| Autentikasi | Umumnya menggunakan session dan cookie. | Dapat menggunakan token, seperti Sanctum, jika dikonfigurasi. |
| Pengelolaan tampilan | Dapat menyediakan halaman formulir tambah dan edit. | Berfokus pada pertukaran data, bukan tampilan formulir. |
| Penyajian data | Data dikirim ke view untuk ditampilkan. | `CourseResource` mengatur atribut yang disertakan dalam respons JSON. |

**4. Panggil endpoint API tanpa header Accept: application/json. Lalu dengan header itu. Catat bedanya.**

**Jawaban:**
- Tanpa header `Accept: application/json:` Laravel dapat mengembalikan respons HTML jika terjadi kesalahan, bergantung pada jenis kesalahan dan konfigurasi aplikasi.

- Dengan header `Accept: application/json:` Laravel akan memprioritaskan respons JSON, terutama ketika terjadi kesalahan validasi atau autentikasi.

**5. Jalankan php artisan route:list --path=api. Cocokkan dengan kontrak di spesifikasi.**

**Jawaban:**
```
  POST            api/v1/assignments ..................................................... Api\AssignmentController@store
  PUT|PATCH       api/v1/assignments/{assignment} ....................................... Api\AssignmentController@update
  DELETE          api/v1/assignments/{assignment} ...................................... Api\AssignmentController@destroy
  GET|HEAD        api/v1/assignments/{assignment}/submissions ...................... Api\AssignmentController@submissions
  POST            api/v1/assignments/{assignment}/submissions ............................ Api\SubmissionController@store
  POST            api/v1/auth/login ............................................................ Api\AuthController@login
  POST            api/v1/auth/logout .......................................................... Api\AuthController@logout
  GET|HEAD        api/v1/courses ............................................................. Api\CourseController@index
  GET|HEAD        api/v1/courses/{course} ..................................................... Api\CourseController@show
  GET|HEAD        api/v1/courses/{course}/assignments .................................. Api\CourseController@assignments
  GET|HEAD        api/v1/courses/{course}/materials ...................................... Api\CourseController@materials
  GET|HEAD        api/v1/me ....................................................................... Api\AuthController@me
  GET|HEAD        api/v1/notifications ................................................. Api\NotificationController@index
  POST            api/v1/notifications/{id}/read ........................................ Api\NotificationController@read
  PUT             api/v1/submissions/{submission}/grade .................................. Api\SubmissionController@grade

                                                                                                      Showing [15] routes
```
### BREAK
| No. | Pengujian | Hasil Pengamatan | Analisis Keamanan dan Dampak |
|---|---|---|---|
| 1 | Mengembalikan `response()->json(User::all())` pada endpoint pengujian. | Respons JSON dapat menampilkan banyak kolom milik pengguna. Jika perlindungan `$hidden` dihapus, atribut sensitif seperti hash kata sandi dan `remember_token` berpotensi ikut terkirim. | Mengirim model secara langsung berisiko membocorkan data yang tidak diperlukan. Penggunaan API Resource dengan pendekatan *whitelist* membatasi atribut yang boleh ditampilkan. |
| 2 | Menghapus middleware `auth:sanctum`, lalu mengakses endpoint tanpa token. | Jika tidak ada perlindungan akses lain, endpoint privat dapat diakses tanpa autentikasi. | Kondisi ini memungkinkan pihak yang tidak berwenang mengambil data. Endpoint privat harus dilindungi middleware autentikasi dan pemeriksaan otorisasi yang sesuai. |
| 3 | Mengakses endpoint terlindungi menggunakan token yang sudah tidak berlaku atau dicabut. | Permintaan seharusnya ditolak dengan status `401 Unauthorized` jika token tidak lagi valid. | Pencabutan token membantu mencegah penggunaan ulang token yang sudah tidak diizinkan. Saat logout, token yang digunakan harus dicabut sesuai mekanisme autentikasi aplikasi. |
| 4 | Login sebagai mahasiswa, lalu mencoba mengakses `POST /api/v1/assignments`. | Jika endpoint hanya boleh digunakan dosen atau admin, permintaan mahasiswa yang sudah login seharusnya ditolak dengan `403 Forbidden`. | Autentikasi tidak otomatis memberikan semua hak akses. Pemeriksaan peran diperlukan agar mahasiswa tidak dapat menjalankan tindakan yang bukan kewenangannya. |
| 5 | Menghapus eager loading `with('lecturer')` dari query daftar mata kuliah. | Jika relasi dosen diakses untuk setiap mata kuliah, jumlah query dapat meningkat. Contohnya, 15 mata kuliah bisa menghasilkan 1 query utama dan hingga 15 query tambahan. | Masalah N+1 dapat memperlambat respons dan membebani database. Eager loading melalui `with('lecturer')` membantu mengurangi query berulang, sedangkan `whenLoaded()` pada Resource mengatur penyertaan relasi yang sudah dimuat. |
| 6 | Menghapus pembatasan `throttle` pada route login dan melakukan percobaan login berulang. | Permintaan dapat terus diproses selama tidak ada pembatasan lain yang aktif. | Percobaan login berlebihan meningkatkan risiko *brute force* dan membebani server. Rate limiting, misalnya `throttle:5,1`, dapat membatasi frekuensi permintaan sesuai konfigurasi aplikasi. |
| 7 | Membuat pesan berbeda untuk email yang tidak terdaftar dan kata sandi yang salah. | Respons yang berbeda dapat mengungkap apakah suatu alamat email terdaftar. | Kondisi ini disebut *user enumeration*, yaitu upaya mengidentifikasi akun yang valid. Gunakan pesan generik seperti `Email atau kata sandi salah.` agar informasi akun tidak mudah ditebak. |

### FIX
**1. Daftar Perbaikan**

| No. | Masalah | Perbaikan |
|---|---|---|
| 1 | Pengembalian model mentah pada `AuthController@me` dan `UserController@index`. | Menggunakan `UserResource` untuk membatasi data yang dikirimkan serta memastikan `password` dan `remember_token` tidak muncul dalam respons. |
| 2 | Endpoint `GET /v1/users` tidak dilindungi autentikasi. | Menambahkan middleware `auth:sanctum` agar data pengguna hanya dapat diakses oleh pengguna yang terautentikasi. |
| 3 | Status respons pada `CourseController@store` tidak sesuai. | Menggunakan status `201 Created` ketika data mata kuliah berhasil dibuat. |
| 4 | Status respons pada `CourseController@destroy` tidak sesuai. | Menggunakan status `204 No Content` setelah data berhasil dihapus tanpa mengirimkan isi respons. |
| 5 | Penolakan akses pada `UserController@show` menggunakan status `401`. | Mengubahnya menjadi `403 Forbidden` ketika pengguna sudah login tetapi tidak memiliki izin mengakses data. |
| 6 | Route login tidak memiliki pembatasan percobaan. | Menambahkan `throttle:5,1` pada route login untuk membatasi percobaan dan mengurangi risiko brute force. |
| 7 | Pesan kesalahan login membedakan email yang tidak terdaftar dan kata sandi yang salah. | Menggunakan satu pesan kesalahan umum agar informasi keberadaan akun tidak mudah diketahui pihak lain. |
| 8 | Endpoint daftar mata kuliah mengalami N+1 query. | Menambahkan eager loading `with('lecturer')` pada query mata kuliah agar pengambilan data relasi lebih efisien. |

**2. Bukti Pengujian cURL**
- Sebelum perbaikan:

```bash
curl -i http://localhost:8000/api/v1/users
```

Jika endpoint sebelumnya terbuka, respons dapat berupa `200 OK` tanpa autentikasi.

- Sesudah perbaikan:

```bash
curl -i http://localhost:8000/api/v1/users
```

Respons yang diharapkan:

```http
HTTP/1.1 401 Unauthorized
```

Dengan token yang valid:

```bash
curl -i -H "Accept: application/json" -H "Authorization: Bearer TOKEN_VALID" http://localhost:8000/api/v1/users
```

Respons yang diharapkan adalah `200 OK`, dengan data yang sudah dibatasi oleh `UserResource` dan tanpa atribut sensitif seperti `password` maupun `remember_token`.

- Pencegahan user enumeration

Uji login menggunakan email yang tidak terdaftar:

```bash
curl -i -X POST http://localhost:8000/api/v1/auth/login -H "Accept: application/json" -H "Content-Type: application/json" -d "{\"email\":\"dummy@test.com\",\"password\":\"wrong\"}"
```

Setelah perbaikan, pesan kesalahan tidak boleh mengungkap apakah email terdaftar. Gunakan pesan umum, misalnya `Kredensial yang diberikan tidak cocok dengan data kami.`

- Pengujian rate limiting

Lakukan percobaan login berulang menggunakan endpoint yang sama. Setelah melewati batas yang dikonfigurasi, respons yang diharapkan adalah:

```http
HTTP/1.1 429 Too Many Requests
```

Jika menggunakan `throttle:5,1`, permintaan berlebih dapat dibatasi berdasarkan konfigurasi limiter dan mekanisme penghitungannya.

- Pengujian otorisasi pengguna

```bash
curl -i -H "Accept: application/json" -H "Authorization: Bearer TOKEN_MHS" http://localhost:8000/api/v1/users/2
```

Jika mahasiswa sudah terautentikasi tetapi tidak berhak melihat data pengguna tersebut, respons yang diharapkan adalah `403 Forbidden`.

- Pengujian status respons mata kuliah
```
- `POST /api/v1/courses` — diharapkan menghasilkan `201 Created` setelah data berhasil dibuat.
- `DELETE /api/v1/courses/{id}` — diharapkan menghasilkan `204 No Content` setelah data berhasil dihapus.
```