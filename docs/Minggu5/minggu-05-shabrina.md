## Catatan Minggu 5 Pemrograman Web

### READ

**1. Jalankan `php artisan route:list --except-vendor`. Salin keluarannya ke catatan.**

Jawaban : 
```
  GET|HEAD        / ........................................................................ home › routes/web.php:22
  GET|HEAD        admin/courses ........................................ admin.courses.index › CourseController@index
  POST            admin/courses ........................................ admin.courses.store › CourseController@store
  GET|HEAD        admin/courses/create ............................... admin.courses.create › CourseController@create
  GET|HEAD        admin/courses/{course} ................................. admin.courses.show › CourseController@show
  PUT|PATCH       admin/courses/{course} ............................. admin.courses.update › CourseController@update
  DELETE          admin/courses/{course} ........................... admin.courses.destroy › CourseController@destroy
  GET|HEAD        admin/courses/{course}/edit ............................ admin.courses.edit › CourseController@edit
  GET|HEAD        admin/users .............................................. admin.users.index › UserController@index
  POST            admin/users .............................................. admin.users.store › UserController@store
  GET|HEAD        admin/users/create ..................................... admin.users.create › UserController@create
  GET|HEAD        admin/users/{user} ......................................... admin.users.show › UserController@show
  PUT|PATCH       admin/users/{user} ..................................... admin.users.update › UserController@update
  DELETE          admin/users/{user} ................................... admin.users.destroy › UserController@destroy
  GET|HEAD        admin/users/{user}/edit .................................... admin.users.edit › UserController@edit
  GET|HEAD        courses/{course}/assignments/{assignment} ............................... AssignmentController@show
  GET|HEAD        dashboard ................................................... dashboard › DashboardController@index
  GET|HEAD        dosen/assignments/{assignment} ................. dosen.assignments.show › AssignmentController@show
  PUT|PATCH       dosen/assignments/{assignment} ............. dosen.assignments.update › AssignmentController@update
  DELETE          dosen/assignments/{assignment} ........... dosen.assignments.destroy › AssignmentController@destroy
  GET|HEAD        dosen/assignments/{assignment}/edit ............ dosen.assignments.edit › AssignmentController@edit
  GET|HEAD        dosen/courses ........................................ dosen.courses.index › CourseController@index
  GET|HEAD        dosen/courses/{course} ................................. dosen.courses.show › CourseController@show
  PUT|PATCH       dosen/courses/{course} ............................. dosen.courses.update › CourseController@update
  GET|HEAD        dosen/courses/{course}/assignments ... dosen.courses.assignments.index › AssignmentController@index
  POST            dosen/courses/{course}/assignments ... dosen.courses.assignments.store › AssignmentController@store
  GET|HEAD        dosen/courses/{course}/assignments/create dosen.courses.assignments.create › AssignmentController@…
  GET|HEAD        dosen/courses/{course}/assignments/{assignment} dosen.courses.assignments.show.scoped › Assignment…
  GET|HEAD        dosen/courses/{course}/edit ............................ dosen.courses.edit › CourseController@edit
  GET|HEAD        dosen/courses/{course}/materials ......... dosen.courses.materials.index › MaterialController@index
  POST            dosen/courses/{course}/materials ......... dosen.courses.materials.store › MaterialController@store
  GET|HEAD        dosen/courses/{course}/materials/create dosen.courses.materials.create › MaterialController@create
  GET|HEAD        dosen/courses/{course}/materials/{material} dosen.courses.materials.show.scoped › MaterialControll…
  GET|HEAD        dosen/materials/{material} ......................... dosen.materials.show › MaterialController@show
  PUT|PATCH       dosen/materials/{material} ..................... dosen.materials.update › MaterialController@update
  DELETE          dosen/materials/{material} ................... dosen.materials.destroy › MaterialController@destroy
  GET|HEAD        dosen/materials/{material}/edit .................... dosen.materials.edit › MaterialController@edit
  GET|HEAD        login ................................................................... login › routes/web.php:36
  GET|HEAD        mahasiswa/assignments/{assignment} ......... mahasiswa.assignments.show › AssignmentController@show
  POST            mahasiswa/assignments/{assignment}/submit mahasiswa.assignments.submit › AssignmentController@subm…
  GET|HEAD        mahasiswa/courses ................................ mahasiswa.courses.index › CourseController@index
  GET|HEAD        mahasiswa/courses/{course} ......................... mahasiswa.courses.show › CourseController@show
  GET|HEAD        mahasiswa/courses/{course}/assignments/{assignment} mahasiswa.courses.assignments.show.scoped › As…
  GET|HEAD        mahasiswa/materials/{material}/download mahasiswa.materials.download › MaterialController@download
  GET|HEAD        mata-kuliah ............................................ mata-kuliah.index › CourseController@index
  POST            mata-kuliah ............................................ mata-kuliah.store › CourseController@store
  GET|HEAD        mata-kuliah/create ................................... mata-kuliah.create › CourseController@create
  GET|HEAD        mata-kuliah/{course} ..................................... mata-kuliah.show › CourseController@show
  PUT             mata-kuliah/{course} ................................. mata-kuliah.update › CourseController@update
  DELETE          mata-kuliah/{course} ............................... mata-kuliah.destroy › CourseController@destroy
  GET|HEAD        mata-kuliah/{course}/edit ................................ mata-kuliah.edit › CourseController@edit
  POST            mata-kuliah/{course}/materi ............................... materi.store › MaterialController@store
  GET|HEAD        mata-kuliah/{course}/materi/create ...................... materi.create › MaterialController@create
  POST            mata-kuliah/{course}/tugas ............................... tugas.store › AssignmentController@store
  GET|HEAD        mata-kuliah/{course}/tugas/create ...................... tugas.create › AssignmentController@create
  GET|HEAD        mata-kuliah/{course}/tugas/{assignment} ................................. AssignmentController@show
  DELETE          materi/{material} ..................................... materi.destroy › MaterialController@destroy
  GET|HEAD        materi/{material}/download .......................... materi.download › MaterialController@download
  GET|HEAD        pengguna .................................................... pengguna.index › UserController@index
  POST            pengguna .................................................... pengguna.store › UserController@store
  GET|HEAD        pengguna/create ........................................... pengguna.create › UserController@create
  GET|HEAD        pengguna/{user} ............................................... pengguna.show › UserController@show
  PUT             pengguna/{user} ........................................... pengguna.update › UserController@update
  DELETE          pengguna/{user} ......................................... pengguna.destroy › UserController@destroy
  GET|HEAD        pengguna/{user}/edit .......................................... pengguna.edit › UserController@edit
  GET|HEAD        pengumpulan-tugas .................................. pengumpulan.index › SubmissionController@index
  GET|HEAD        set-role/{role} ............................................ set-role › DashboardController@setRole
  GET|HEAD        submissions ........................................ submissions.index › SubmissionController@index
  GET|HEAD        submissions/{submission} ............................. submissions.show › SubmissionController@show
  POST            submissions/{submission}/grade ..................... submissions.grade › SubmissionController@grade
  GET|HEAD        tentang ............................................................... tentang › routes/web.php:28
  GET|HEAD        tugas/{assignment} ......................................... tugas.show › AssignmentController@show
  PUT             tugas/{assignment} ..................................... tugas.update › AssignmentController@update
  DELETE          tugas/{assignment} ................................... tugas.destroy › AssignmentController@destroy
  GET|HEAD        tugas/{assignment} ......................................... tugas.show › AssignmentController@show
  GET|HEAD        tugas/{assignment} ......................................... tugas.show › AssignmentController@show
  PUT             tugas/{assignment} ..................................... tugas.update › AssignmentController@update
  DELETE          tugas/{assignment} ................................... tugas.destroy › AssignmentController@destroy
  GET|HEAD        tugas/{assignment}/edit .................................... tugas.edit › AssignmentController@edit
  POST            tugas/{assignment}/submit .............................. tugas.submit › AssignmentController@submit
```


**2. Tandai setiap route yang menerima parameter model (`{course}`, `{assignment}`, dst).**

Jawaban : Berikut route yang punya parameter model (ditandai `{...}` di URL):

1. **`{course}` (Mata Kuliah):**
   - `GET /mata-kuliah/{course}`, `GET /admin/courses/{course}`, `GET /dosen/courses/{course}`, `GET /mahasiswa/courses/{course}`
   - `GET /mata-kuliah/{course}/edit`, `GET /admin/courses/{course}/edit`, `GET /dosen/courses/{course}/edit`
   - `PUT /mata-kuliah/{course}`, `PUT /admin/courses/{course}`, `PUT /dosen/courses/{course}`
   - `DELETE /mata-kuliah/{course}`, `DELETE /admin/courses/{course}`
   - `POST /mata-kuliah/{course}/tugas`, `POST /dosen/courses/{course}/assignments`
   - `POST /mata-kuliah/{course}/materi`, `POST /dosen/courses/{course}/materials`
2. **`{assignment}` (Tugas):**
   - `GET /tugas/{assignment}`, `GET /dosen/assignments/{assignment}`, `GET /mahasiswa/assignments/{assignment}`
   - `PUT /tugas/{assignment}`, `PUT /dosen/assignments/{assignment}`
   - `DELETE /tugas/{assignment}`, `DELETE /dosen/assignments/{assignment}`
   - `POST /tugas/{assignment}/submit`, `POST /mahasiswa/assignments/{assignment}/submit`
   - `GET /mata-kuliah/{course}/tugas/{assignment}` *(bersarang)*
3. **`{material}` (Materi):**
   - `GET /materi/{material}/download`, `GET /mahasiswa/materials/{material}/download`
   - `DELETE /materi/{material}`, `DELETE /dosen/materials/{material}`
4. **`{submission}` (Pengumpulan Tugas):**
   - `GET /submissions/{submission}`
   - `POST /submissions/{submission}/grade`
5. **`{user}` (Pengguna):**
   - `GET /pengguna/{user}`, `GET /admin/users/{user}`
   - `PUT /pengguna/{user}`, `PUT /admin/users/{user}`
   - `DELETE /pengguna/{user}`, `DELETE /admin/users/{user}`


**3.  Untuk setiap route bertanda: siapa yang seharusnya boleh mengakses, dan apa yang saat ini mencegah orang lain?**

Jawaban : 

#### Siapa yang seharusnya boleh mengakses:

- **Submission:** hanya mahasiswa pemilik submission itu, dosen pengampu mata kuliahnya, dan admin.
- **Mata kuliah (edit, tambah tugas, tambah materi):** hanya dosen pengampu mata kuliah itu dan admin.
- **Tugas (edit/hapus):** hanya dosen pengampu yang membuat tugas itu dan admin.
- **Mengumpulkan tugas:** hanya mahasiswa yang terdaftar di mata kuliah tersebut.
- **Download materi:** mahasiswa yang terdaftar di mata kuliah tersebut, dosen pengampu, dan admin.
- **Pengguna:** hanya admin (atau pengguna yang melihat profilnya sendiri).

#### Apa yang saat ini mencegah orang lain?

Awalnya belum ada apa-apa. Route Model Binding di Laravel hanya mengecek apakah datanya ada di database. Laravel tidak mengecek apakah orang yang login berhak membuka data itu. Akibatnya, siapa pun yang mengganti angka ID di URL bisa melihat atau mengedit data orang lain. Celah ini disebut **IDOR** (*Insecure Direct Object Reference*).

Karena itu, di Minggu 5 ini kita memasang pencegahan Lapis 1, yaitu middleware peran dan pengecekan kepemilikan manual (`abort_unless`) di controller.


**4. Daftar Titik Rawan IDOR**

Jawaban : 
| Route / URL | Parameter | Siapa yang Boleh Akses? | Bahayanya Kalau Tidak Dicek | Cara Mencegahnya (Minggu 5) |
|---|---|---|---|---|
| `GET /submissions/{submission}` | `{submission}` | Mahasiswa pemilik submission, dosen pengampu, dan admin | Mahasiswa lain bisa melihat jawaban, file, dan nilai temannya hanya dengan mengganti angka ID di URL. | `abort_unless($submission->user_id === auth()->id() \|\| auth()->user()->role === 'admin' \|\| $submission->assignment->course->lecturer_id === auth()->id(), 403);` |
| `POST /submissions/{submission}/grade` | `{submission}` | Dosen pengampu dan admin | Dosen A bisa memberi atau mengubah nilai mahasiswa di mata kuliah milik Dosen B. | Pastikan dosen yang login adalah pengampu mata kuliah dari tugas tersebut. |
| `GET /mata-kuliah/{course}/edit`<br>`PUT /mata-kuliah/{course}` | `{course}` | Dosen pengampu dan admin | Dosen lain bisa mengubah nama, deskripsi, atau SKS mata kuliah milik dosen lain. | `abort_unless(auth()->user()->role === 'admin' \|\| (auth()->user()->role === 'dosen' && $course->lecturer_id === auth()->id()), 403);` |
| `DELETE /mata-kuliah/{course}` | `{course}` | Khusus admin | Dosen atau pengguna lain bisa menghapus mata kuliah dari database. | Batasi route hanya untuk role admin (`role:admin`). |
| `POST /mata-kuliah/{course}/tugas`<br>`POST /mata-kuliah/{course}/materi` | `{course}` | Dosen pengampu dan admin | Dosen lain bisa menambahkan tugas atau materi ke mata kuliah orang lain. | Cek apakah `lecturer_id` mata kuliah sama dengan ID dosen yang login. |
| `PUT/DELETE /tugas/{assignment}`<br>`DELETE /materi/{material}` | `{assignment}`<br>`{material}` | Dosen pengampu dan admin | Dosen lain bisa menghapus tugas/materi atau mengubah deadline kelas lain. | Cek kepemilikan dosen pengampu sebelum update atau hapus. |
| `POST /tugas/{assignment}/submit` | `{assignment}` | Mahasiswa yang mengambil mata kuliah tersebut | Mahasiswa dari luar kelas bisa mengirim tugas ke mata kuliah yang tidak ia ambil. | Cek apakah mahasiswa terdaftar di mata kuliah itu sebelum menyimpan jawaban. |
| `GET /materi/{material}/download` | `{material}` | Mahasiswa kelas, dosen pengampu, dan admin | Mahasiswa luar kelas bisa mengunduh materi atau soal ujian kelas lain. | Cek apakah mahasiswa yang login terdaftar di kelas tersebut. |
| `GET/PUT/DELETE /pengguna/{user}` | `{user}` | Admin (atau pengguna yang melihat profil sendiri) | Pengguna biasa bisa mengubah data pengguna lain atau mengubah rolenya sendiri menjadi admin. | Batasi route pengguna dengan middleware `role:admin`. |

---

###  BREAK

| # | Yang Dicoba | Hasil Pengamatan |
|---|---|---|
| 1 | Login sebagai mahasiswa A, lalu buka submission milik mahasiswa B dengan mengubah angka di URL. | Saat ID di URL diganti (dari `/submissions/2101` ke `/submissions/210`), muncul error **403 Forbidden** karena akses ditolak. Tanpa `abort_unless`, jawaban dan nilai mahasiswa lain akan terbuka (celah IDOR). |
| 2 | Buka `/courses/1/assignments/99`, padahal tugas 99 milik mata kuliah lain. | Tanpa *scoping*, halaman tugas tetap terbuka dan menampilkan tugas milik mata kuliah lain. Laravel mengecek ID mata kuliah dan ID tugas secara terpisah, tanpa memastikan tugas itu benar-benar milik mata kuliah tersebut. |
| 3 | Aktifkan `Route::scopeBindings()`, lalu ulangi nomor 2. | `Route::scopeBindings()` sudah aktif di `routes/web.php`. Saat URL yang tidak cocok (`/mata-kuliah/1/tugas/4`) dibuka, browser menampilkan **404 Not Found**. Laravel mengecek relasinya di database dan menolak tugas yang bukan milik mata kuliah itu. |
| 4 | Daftarkan middleware di `app/Http/Kernel.php` seperti di tutorial lama. | File `app/Http/Kernel.php` tidak ada di proyek ini karena sudah dihapus di Laravel versi baru. Alias middleware (seperti `role`) sekarang didaftarkan di `bootstrap/app.php` lewat `->withMiddleware()`. |
| 5 | Pasang `role:admin` pada grup route, lalu akses sebagai dosen. | Saat login sebagai dosen dan membuka `/admin/users`, muncul error **403**. Middleware berhasil menolak peran yang tidak sesuai. |
| 6 | Sebagai dosen A, edit mata kuliah milik dosen B (keduanya lolos `role:dosen`). | Tanpa pengecekan pemilik di controller, Dosen A berhasil mengedit mata kuliah Dosen B. Ini membuktikan **middleware saja tidak cukup**. Middleware hanya mengecek peran, sedangkan kepemilikan data harus dicek di controller (lewat `abort_unless`) atau lewat policy di Minggu 7. |

---

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