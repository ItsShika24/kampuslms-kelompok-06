## Catatan Minggu 5 Pemrograman Web

### FIX

**Branch `w05` pada repo `kampuslms-broken` berisi 7 masalah: satu route di luar grup `auth`, dua IDOR pada submission dan material, nested route tanpa `scopeBindings`, middleware didaftarkan di berkas yang salah, nama route bentrok antar peran, dan satu route destruktif yang memakai `GET`.**

---

**1. Halaman `/profile` bisa dibuka tanpa login**

- Masalah : Route `GET /profile` di `routes/web.php` berada di luar grup `Route::middleware('auth')`.

- Dampak  : Pengguna yang belum login tetap bisa membuka halaman profil hanya dengan mengetik URL-nya.

- Perbaikan   : Route dipindah ke dalam grup auth. Saat logout, `/profile` kini diarahkan ke `/login`.


**2. IDOR pada Submission dan Material**

2a. `SubmissionController@show`
- Masalah   : `show(Submission $submission)` langsung menampilkan data tanpa memeriksa pemiliknya. Route model binding hanya memastikan datanya ada, bukan bahwa pengguna berhak melihatnya.

- Dampak    : Mahasiswa bisa membuka submission mahasiswa lain hanya dengan mengganti angka ID di URL, sehingga kerahasiaan data akademik bocor.

- Perbaikan :Ditambahkan ` abort_unless(...)`  yang hanya mengizinkan pemilik, admin, atau dosen pengampu course-nya. Sebelumnya curl dari sesi mahasiswa A ke submission B mengembalikan 200, sesudahnya 403, sedangkan akses ke submission sendiri tetap 200.

2b. `MaterialController` (show, edit, update, destroy)
- Masalah   : Controller tidak memeriksa bahwa material milik course di URL, dan tidak memeriksa bahwa dosen yang mengakses adalah pengampu course tersebut. `Middleware role:dosen` hanya membuktikan pengguna itu dosen, bukan bahwa data itu miliknya.

- Dampak    : Dosen dapat membuka, mengubah, atau menghapus material milik course dosen lain dengan cara menebak ID.

- Perbaikan : Ditambahkan method `authorizeMaterial()` yang memastikan material milik course di URL (404 jika tidak) dan pengguna adalah admin atau dosen pengampu (403 jika bukan), dipanggil di setiap method yang menerima course atau material.


**3. Nested route material tanpa `scopeBindings()`**

- Masalah   : `Route::resource('courses.materials', ...)` pada grup dosen tidak dibatasi relasi parent-child, jadi Laravel hanya mencari material berdasarkan ID tanpa memeriksa course-nya.

- Dampak    : Dosen bisa menjangkau material milik course lain lewat manipulasi ID di URL.

- Perbaikan : Ditambahkan `->scoped()`, yang bekerja seperti `scopeBindings()` untuk resource. Hasilnya dicek lewat `php artisan route:list --name=materials`. Untuk route shallow, ditambah pemeriksaan kepemilikan di controller.


**4. Alias middleware `role` didaftarkan di berkas yang salah `(Kernel.php)`.**

- Masalah   : Alias `role` untuk `EnsureUserHasRole` didaftarkan di `app/Http/Kernel.php` lewat `$routeMiddleware`, padahal Laravel 12 tidak lagi memakai `Kernel.php` untuk konfigurasi middleware. Pendaftaran middleware seharusnya di `bootstrap/app.php`.

- Dampak    : Alias `role` tidak dikenali Laravel, sehingga setiap route yang memakai `role:...` menghasilkan error 500 `"Target class [role] does not exist"`. Akibatnya fitur pembatasan akses berbasis peran tidak berfungsi.

- Perbaikan : File `Kernel.php` dihapus, dan alias didaftarkan di `bootstrap/app.php` lewat `$middleware->alias()` di dalam `withMiddleware()`. Setelah itu mahasiswa yang membuka route dosen mendapat 403, sedangkan dosen tetap bisa mengaksesnya.


**5. Nama route bentrok `(courses.index)`**

- Masalah   : Grup admin, dosen, dan mahasiswa sama-sama mendaftarkan  `Route::resource('courses', ...) ` tanpa name prefix, sehingga nama seperti  `courses.index ` terdefinisi berulang.

- Dampak    : Laravel hanya mengenali satu nama, sehingga route `('courses.index') ` selalu mengarah ke satu URL saja dan navigasi berbasis peran salah arah.

- Perbaikan : Ditambahkan `->name('admin.')`, `->name('dosen.')`, dan `->name('mahasiswa.')` pada tiap grup, dan pemanggilan `route()` di view ikut disesuaikan.


**6. Hapus semua submission memakai GET**

- Masalah   : `Route::get('/submissions/destroy-all', ...)` menjalankan `Submission::truncate()` lewat method GET.

- Dampak    : Semua data submission bisa terhapus hanya dengan membuka URL, misalnya lewat preload browser, bot, atau link preview, dan tidak ada perlindungan CSRF.

- Perbaikan : Diubah menjadi  `Route::delete(...) `, dan tombol di view menjadi form  `POST ` dengan  `@csrf ` dan  `@method('DELETE')`. `route:list` menampilkan method `DELETE`.