## Catatan Minggu 6 Pemrograman Web

### READ

**1. Jalankan `php artisan install:api`. Baca perubahan yang terjadi di `bootstrap/app.php`.**

Jawaban : Di `bootstrap/app.php` ditambahkan satu baris di dalam `->withRouting(...)`:

```php
api: __DIR__.'/../routes/api.php',
```

Baris ini menyuruh Laravel memuat file `routes/api.php`. Semua route di dalamnya otomatis mendapat awalan URL `/api` dan memakai middleware group `api`.


**2. Buat satu endpoint `GET /api/v1/courses` sederhana.**

Jawaban : Di `routes/api.php`, route-nya ditulis seperti ini:

```php
Route::get('/v1/courses', function () {
    return \App\Http\Resources\CourseResource::collection(
        \App\Models\Course::with('lecturer')->paginate(15)
    );
});
```


**3.  Bandingkan dengan `CourseController` versi web. Apa yang sama dan apa yang berbeda?**

Jawaban : 
#### Yang sama :
1. **Sumber data:** keduanya mengambil data dari model Eloquent yang sama (`Course`).
2. **Query:** keduanya memakai eager loading `with('lecturer')` untuk mencegah N+1 query, dan sama-sama memakai `paginate(15)`.
3. **Filter berdasarkan role:** dosen hanya melihat mata kuliah yang ia ampu, mahasiswa hanya melihat mata kuliah yang ia ikuti.

#### Yang berbeda : 
| Aspek | Web Controller (`CourseController.php`) | API Controller (`Api/CourseController.php`) |
|---|---|---|
| **Format keluaran** | HTML dari Blade lewat `return view(...)` | Data JSON lewat `CourseResource::collection(...)` |
| **Autentikasi** | Session dan cookie browser (*stateful*) | Bearer token Sanctum (*stateless*) |
| **Method form** | Punya `create()` dan `edit()` untuk menampilkan halaman form | Tidak punya `create()` dan `edit()` karena API hanya mengirim data |
| **Penyaringan data** | Objek model langsung dikirim ke view Blade | Data disaring lewat API Resource (*whitelist*) supaya kolom rahasia tidak bocor |


**4. Panggil endpoint API tanpa header `Accept: application/json`, lalu dengan header itu. Catat bedanya.**

Jawaban : 
- **Tanpa `Accept: application/json`:** Laravel mengira pemanggilnya browser biasa. Kalau terjadi error (misalnya token tidak valid atau validasi gagal), Laravel mengembalikan respons HTML, misalnya mengarahkan ke halaman `/login` atau menampilkan halaman error HTML.
- **Dengan `Accept: application/json`:** Laravel tahu pemanggilnya klien API. Kalau terjadi error autentikasi atau validasi, responsnya berupa JSON, misalnya `{"message": "Unauthenticated."}` dengan status HTTP 401.


**5. Jalankan `php artisan route:list --path=api`. Cocokkan dengan kontrak di spesifikasi.**

Jawaban : 
```
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

Perintah ini menampilkan 15 endpoint, dan semuanya cocok dengan kontrak API di spesifikasi.


---

# BREAK

| # | Yang Dicoba | Prediksi dan Hasil Pengamatan | Analisis Keamanan dan Dampak |
|:---:|---|---|---|
| **1** | Kembalikan `response()->json(User::all())` di satu endpoint uji. | Semua kolom tabel pengguna tampil di JSON (`created_at`, `updated_at`, `email_verified_at`). Saat `$hidden` di model `User` dihapus sementara, **hash password dan `remember_token` ikut bocor.** | Mengembalikan model mentah berarti mengirim semua kolom database. `$hidden` bersifat *blacklist*, jadi rawan bocor kalau ada kolom sensitif baru yang lupa didaftarkan. **Solusi:** pakai **API Resource** yang bersifat *whitelist* (hanya kolom yang diizinkan yang keluar). |
| **2** | Hapus `auth:sanctum` dari grup route, lalu panggil tanpa token. | Data privat (daftar mata kuliah, materi, profil akun) terbuka untuk siapa saja dengan status HTTP 200. | Siapa pun, termasuk bot, bisa mengambil data akademik kampus tanpa login (*broken authentication*). **Solusi:** semua route privat wajib dilindungi `auth:sanctum`. |
| **3** | Panggil endpoint terlindungi dengan token yang sudah dihapus (setelah logout). | Server menolak dengan **HTTP 401 Unauthorized** dan pesan `{"message": "Unauthenticated."}`. | Ini menunjukkan pencabutan token (*token revocation*) berjalan benar. Setelah logout, token lama langsung tidak berlaku, jadi tidak bisa disalahgunakan kalau sempat dicuri. |
| **4** | Login sebagai mahasiswa, lalu panggil `POST /api/v1/assignments`. | Ditolak dengan **HTTP 403 Forbidden** (`{"message": "Anda tidak memiliki akses ke sumber daya ini."}`), bukan 401. | Mahasiswa sudah terautentikasi (makanya bukan 401), tetapi tidak berhak membuat tugas karena itu wewenang dosen atau admin (*broken access control* kalau tidak dicek). |
| **5** | Hapus eager loading (`with('lecturer')`), lalu panggil daftar mata kuliah. | Jumlah query di query log melonjak. Untuk 15 mata kuliah, ada **1 query utama + 15 query terpisah** untuk dosen (total 16 query). | Ini masalah **N+1 query**: query berulang membebani database dan membuat API lambat. **Solusi:** selalu pakai eager loading `with('lecturer')` dan `whenLoaded()` di Resource. |
| **6** | Hapus `throttle` dari route login, lalu coba login 50 kali berturut-turut. | Semua 50 percobaan diproses tanpa jeda atau pemblokiran. | Rawan serangan *brute force*: bot bisa mencoba sangat banyak kombinasi password. `Hash::check()` juga berat, jadi CPU server bisa penuh (*denial of service*). **Solusi:** batasi dengan `throttle:5,1` supaya percobaan ke-6 diblokir (HTTP 429). |
| **7** | Buat pesan login berbeda untuk email salah dan password salah. | Sistem membedakan respons: "Email tidak terdaftar" saat email salah, dan "Password salah" saat email benar tapi password salah. | Ini membuka celah ***user enumeration***: penyerang bisa mengotomasi pengecekan ribuan email untuk tahu siapa saja yang terdaftar. **Solusi:** pakai satu pesan yang sama, yaitu "Email atau kata sandi salah." |


---

### FIX

**Branch `w06` pada repo `kampuslms-broken` berisi 8 masalah: model mentah dikembalikan pada dua endpoint, satu endpoint tanpa `auth:sanctum`, status code salah pada `store` dan `destroy`, 403 dikembalikan sebagai 401, login tanpa throttle, pesan login membocorkan keberadaan email, dan N+1 pada endpoint daftar.**

---

**1. Model mentah dikembalikan pada dua endpoint**

- Masalah : Dua endpoint (misalnya `GET /api/v1/courses` dan `GET /api/v1/me`) mengembalikan `response()->json(Course::all())` atau `response()->json($user)`.

- Dampak : Seluruh kolom tabel ikut keluar, termasuk yang tidak seharusnya dilihat klien. `$hidden` hanyalah daftar hitam, jadi kolom sensitif baru yang lupa didaftarkan langsung bocor. Format response juga tidak sesuai kontrak.

- Perbaikan : Dibuat `CourseResource` dan `UserResource` yang bersifat daftar putih (hanya kolom yang disebutkan yang keluar), lalu controller mengembalikan `CourseResource::collection(...)` dan `new UserResource($user)`.


**2. Satu endpoint tanpa `auth:sanctum`**

- Masalah : Salah satu route di `routes/api.php` berada di luar grup `Route::middleware('auth:sanctum')`.

- Dampak : Siapa pun, termasuk bot, bisa mengambil data akademik tanpa login dan mendapat HTTP 200.

- Perbaikan : Route dipindah ke dalam grup `auth:sanctum`. Setelah itu panggilan tanpa token mengembalikan 401 `{"message": "Unauthenticated."}`.


**3. Status code salah pada `store`**

- Masalah : `store` mengembalikan 200 setelah membuat data.

- Dampak : Klien tidak bisa membedakan "berhasil membuat" dari "berhasil mengambil", sehingga penanganan hasilnya bisa salah. Ini bukan celah keamanan.

- Perbaikan : Diubah menjadi `return (new CourseResource($course))->response()->setStatusCode(201)`, sehingga mengembalikan 201 beserta objek yang dibuat.


**4. Status code salah pada `destroy`**

- Masalah : `destroy` mengembalikan 200 dengan body JSON setelah menghapus data.

- Dampak : Klien menunggu isi response padahal tidak ada yang perlu dikembalikan, dan kontrak API dilanggar.

- Perbaikan : Diubah menjadi `return response()->noContent()`, yang mengembalikan 204 tanpa isi.


**5. 403 dikembalikan sebagai 401**

- Masalah : Pengguna yang sudah login tetapi tidak berhak (misalnya mahasiswa memanggil `POST /api/v1/assignments`) menerima 401.

- Dampak : Klien mengira token kedaluwarsa lalu memaksa pengguna login ulang, padahal masalahnya hak akses. Kode yang salah juga menyulitkan pemantauan dan audit akses.

- Perbaikan : Pengecekan peran diganti dengan `abort(403, ...)` atau `Gate::authorize()`. Sekarang tanpa token mengembalikan 401, sedangkan token mahasiswa ke endpoint dosen mengembalikan 403.


**6. Login tanpa throttle**

- Masalah : `POST /api/v1/auth/login` tidak memiliki pembatasan percobaan.

- Dampak : Penyerang bisa mencoba banyak kombinasi password tanpa hambatan (brute force). `Hash::check()` yang berat juga bisa membebani CPU server (denial of service).

- Perbaikan : Ditambahkan `->middleware('throttle:5,1')` pada route login. Pada percobaan ke-6 dalam satu menit, server mengembalikan 429.


**7. Pesan login membocorkan keberadaan email**

- Masalah : Login mengembalikan "Email tidak terdaftar" untuk email salah dan "Password salah" untuk password salah.

- Dampak : Penyerang bisa mengotomasi pengecekan ribuan email untuk mengetahui siapa saja yang terdaftar (user enumeration).

- Perbaikan : Keduanya diganti dengan satu pesan lewat `ValidationException::withMessages(['email' => ['Email atau kata sandi salah.']])`.


**8. N+1 pada endpoint daftar**

- Masalah : Endpoint daftar mata kuliah memanggil `Course::paginate(15)` tanpa eager loading, sedangkan Resource mengakses data dosen untuk tiap baris.

- Dampak : Setiap baris memicu query tambahan untuk dosen, sehingga database terbebani dan respons API menjadi lambat.

- Perbaikan : Diganti menjadi `Course::with('lecturer')->withCount(['materials', 'assignments'])->paginate(15)`, dan di Resource relasi ditulis dengan `whenLoaded('lecturer')`. Jumlah query di query log turun menjadi konstan, tidak lagi bertambah per baris.