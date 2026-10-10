## Oktavia Nur Rahmadani
### NIM 10241060
### Pemrograman Web - Laporan Minggu 5

---
### READ
**1. Jalankan php artisan route:list --except-vendor. Salin keluarannya ke catatan.**

**Jawaban:**
```
  GET|HEAD        / .......................................................... home › routes/web.php:22
  GET|HEAD        admin/courses .......................... admin.courses.index › CourseController@index
  POST            admin/courses .......................... admin.courses.store › CourseController@store
  GET|HEAD        admin/courses/create ................. admin.courses.create › CourseController@create
  GET|HEAD        admin/courses/{course} ................... admin.courses.show › CourseController@show
  PUT|PATCH       admin/courses/{course} ............... admin.courses.update › CourseController@update
  DELETE          admin/courses/{course} ............. admin.courses.destroy › CourseController@destroy
  GET|HEAD        admin/courses/{course}/edit .............. admin.courses.edit › CourseController@edit
  GET|HEAD        admin/users ................................ admin.users.index › UserController@index
  POST            admin/users ................................ admin.users.store › UserController@store
  GET|HEAD        admin/users/create ....................... admin.users.create › UserController@create
  GET|HEAD        admin/users/{user} ........................... admin.users.show › UserController@show
  PUT|PATCH       admin/users/{user} ....................... admin.users.update › UserController@update
  DELETE          admin/users/{user} ..................... admin.users.destroy › UserController@destroy
  GET|HEAD        admin/users/{user}/edit ...................... admin.users.edit › UserController@edit
  POST            api/v1/assignments ................................... Api\AssignmentController@store
  PUT|PATCH       api/v1/assignments/{assignment} ..................... Api\AssignmentController@update
  DELETE          api/v1/assignments/{assignment} .................... Api\AssignmentController@destroy
  GET|HEAD        api/v1/assignments/{assignment}/submissions .... Api\AssignmentController@submissions
  POST            api/v1/assignments/{assignment}/submissions .......... Api\SubmissionController@store
  POST            api/v1/auth/login .......................................... Api\AuthController@login
  POST            api/v1/auth/logout ........................................ Api\AuthController@logout
  GET|HEAD        api/v1/courses ........................................... Api\CourseController@index
  GET|HEAD        api/v1/courses/{course} ................................... Api\CourseController@show
  GET|HEAD        api/v1/courses/{course}/assignments ................ Api\CourseController@assignments
  GET|HEAD        api/v1/courses/{course}/materials .................... Api\CourseController@materials
  GET|HEAD        api/v1/me ..................................................... Api\AuthController@me
  GET|HEAD        api/v1/notifications ............................... Api\NotificationController@index
  POST            api/v1/notifications/{id}/read ...................... Api\NotificationController@read
  PUT             api/v1/submissions/{submission}/grade ................ Api\SubmissionController@grade
  GET|HEAD        courses/{course}/assignments/{assignment} ................. AssignmentController@show
  GET|HEAD        dashboard ..................................... dashboard › DashboardController@index
  GET|HEAD        dosen/assignments/{assignment} ... dosen.assignments.show › AssignmentController@show
  PUT|PATCH       dosen/assignments/{assignment} dosen.assignments.update › AssignmentController@update
  DELETE          dosen/assignments/{assignment} dosen.assignments.destroy › AssignmentController@dest…
  GET|HEAD        dosen/assignments/{assignment}/edit dosen.assignments.edit › AssignmentController@ed…
  GET|HEAD        dosen/courses .......................... dosen.courses.index › CourseController@index
  GET|HEAD        dosen/courses/{course} ................... dosen.courses.show › CourseController@show
  PUT|PATCH       dosen/courses/{course} ............... dosen.courses.update › CourseController@update
  GET|HEAD        dosen/courses/{course}/assignments dosen.courses.assignments.index › AssignmentContr…
  POST            dosen/courses/{course}/assignments dosen.courses.assignments.store › AssignmentContr…
  GET|HEAD        dosen/courses/{course}/assignments/create dosen.courses.assignments.create › Assignm…
  GET|HEAD        dosen/courses/{course}/assignments/{assignment} dosen.courses.assignments.show.scope…
  GET|HEAD        dosen/courses/{course}/edit .............. dosen.courses.edit › CourseController@edit
  GET|HEAD        dosen/courses/{course}/materials dosen.courses.materials.index › MaterialController@…
  POST            dosen/courses/{course}/materials dosen.courses.materials.store › MaterialController@…
  GET|HEAD        dosen/courses/{course}/materials/create dosen.courses.materials.create › MaterialCon…
  GET|HEAD        dosen/courses/{course}/materials/{material} dosen.courses.materials.show.scoped › Ma…
  GET|HEAD        dosen/materials/{material} ........... dosen.materials.show › MaterialController@show
  PUT|PATCH       dosen/materials/{material} ....... dosen.materials.update › MaterialController@update
  DELETE          dosen/materials/{material} ..... dosen.materials.destroy › MaterialController@destroy
  GET|HEAD        dosen/materials/{material}/edit ...... dosen.materials.edit › MaterialController@edit
  GET|HEAD        login ..................................................... login › routes/web.php:36
  GET|HEAD        mahasiswa/assignments/{assignment} mahasiswa.assignments.show › AssignmentController…
  POST            mahasiswa/assignments/{assignment}/submit mahasiswa.assignments.submit › AssignmentC…
  GET|HEAD        mahasiswa/courses .................. mahasiswa.courses.index › CourseController@index
  GET|HEAD        mahasiswa/courses/{course} ........... mahasiswa.courses.show › CourseController@show
  GET|HEAD        mahasiswa/courses/{course}/assignments/{assignment} mahasiswa.courses.assignments.sh…
  GET|HEAD        mahasiswa/materials/{material}/download mahasiswa.materials.download › MaterialContr…
  GET|HEAD        mata-kuliah .............................. mata-kuliah.index › CourseController@index
  POST            mata-kuliah .............................. mata-kuliah.store › CourseController@store
  GET|HEAD        mata-kuliah/create ..................... mata-kuliah.create › CourseController@create
  GET|HEAD        mata-kuliah/{course} ....................... mata-kuliah.show › CourseController@show
  PUT             mata-kuliah/{course} ................... mata-kuliah.update › CourseController@update
  DELETE          mata-kuliah/{course} ................. mata-kuliah.destroy › CourseController@destroy
  GET|HEAD        mata-kuliah/{course}/edit .................. mata-kuliah.edit › CourseController@edit
  POST            mata-kuliah/{course}/materi ................. materi.store › MaterialController@store
  GET|HEAD        mata-kuliah/{course}/materi/create ........ materi.create › MaterialController@create
  POST            mata-kuliah/{course}/tugas ................. tugas.store › AssignmentController@store
  GET|HEAD        mata-kuliah/{course}/tugas/create ........ tugas.create › AssignmentController@create
  GET|HEAD        mata-kuliah/{course}/tugas/{assignment} ................... AssignmentController@show
  DELETE          materi/{material} ....................... materi.destroy › MaterialController@destroy
  GET|HEAD        materi/{material}/download ............ materi.download › MaterialController@download
  GET|HEAD        pengguna ...................................... pengguna.index › UserController@index
  POST            pengguna ...................................... pengguna.store › UserController@store
  GET|HEAD        pengguna/create ............................. pengguna.create › UserController@create
  GET|HEAD        pengguna/{user} ................................. pengguna.show › UserController@show
  PUT             pengguna/{user} ............................. pengguna.update › UserController@update
  DELETE          pengguna/{user} ........................... pengguna.destroy › UserController@destroy
  GET|HEAD        pengguna/{user}/edit ............................ pengguna.edit › UserController@edit
  GET|HEAD        pengumpulan-tugas .................... pengumpulan.index › SubmissionController@index
  GET|HEAD        set-role/{role} .............................. set-role › DashboardController@setRole
  GET|HEAD        submissions .......................... submissions.index › SubmissionController@index
  GET|HEAD        submissions/{submission} ............... submissions.show › SubmissionController@show
  POST            submissions/{submission}/grade ....... submissions.grade › SubmissionController@grade
  GET|HEAD        tentang ................................................. tentang › routes/web.php:28
  GET|HEAD        tugas/{assignment} ........................... tugas.show › AssignmentController@show
  PUT             tugas/{assignment} ....................... tugas.update › AssignmentController@update
  DELETE          tugas/{assignment} ..................... tugas.destroy › AssignmentController@destroy
  GET|HEAD        tugas/{assignment}/edit ...................... tugas.edit › AssignmentController@edit
  POST            tugas/{assignment}/submit ................ tugas.submit › AssignmentController@submit
  ```

**2. Tandai setiap route yang menerima parameter model ({course}, {assignment}, dst).**

**Jawaban:** Route yang memiliki parameter model seperti `{course}`, `{assignment}`, `{submission}`, `{material}`, dan `{user}` perlu diperiksa karena ID pada URL dapat diganti oleh pengguna. Setiap route harus memastikan bahwa pengguna memiliki izin untuk mengakses atau mengubah objek tersebut, bukan hanya memastikan bahwa objeknya tersedia di database.

**3. Untuk setiap route bertanda, jawab: siapa saja yang seharusnya boleh mengaksesnya, dan apa yang saat ini mencegah orang lain? Kemungkinan besar jawabannya "belum ada apa-apa" — itu wajar, dan itulah pekerjaan minggu ini dan minggu 7.**

**Jawaban:**
| Route                        | Siapa yang seharusnya boleh mengakses?                         | Apa yang saat ini mencegah orang lain?                                                                                |
| ---------------------------- | -------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| `GET /courses/{course}`      | Pengguna yang memiliki hak untuk melihat mata kuliah tersebut. | Perlu diperiksa apakah hanya menggunakan middleware `auth` atau sudah ada pemeriksaan hak akses terhadap mata kuliah. |
| `GET /courses/{course}/edit` | Dosen pemilik mata kuliah atau admin yang berwenang.           | Perlu diperiksa apakah controller memeriksa kepemilikan mata kuliah sebelum menampilkan halaman edit.                 |
| `PUT /courses/{course}`      | Dosen pemilik mata kuliah atau admin yang berwenang.           | Perlu diperiksa apakah terdapat pemeriksaan hak akses sebelum data diperbarui.                                        |
| `DELETE /courses/{course}`   | Dosen pemilik mata kuliah atau admin yang berwenang.           | Perlu diperiksa apakah terdapat pemeriksaan hak akses sebelum data dihapus.                                           |

**4. Buat tabel di docs/minggu-05-<nama>.md berjudul "Daftar Titik Rawan IDOR". Tabel ini akan Anda pakai lagi di minggu 7 dan saat interview.**

**Jawaban:**
| No. | Route atau Objek | Potensi Titik Rawan IDOR | Hak Akses yang Seharusnya | Pemeriksaan yang Perlu Dilakukan |
|---|---|---|---|---|
| 1 | Mata kuliah (`{course}`) | Pengguna mencoba melihat atau mengubah mata kuliah milik pengguna lain dengan mengganti ID pada URL. | Dosen pemilik mata kuliah atau admin yang berwenang. | Periksa apakah controller memvalidasi kepemilikan mata kuliah sebelum menampilkan, mengubah, atau menghapus data. |
| 2 | Tugas (`{assignment}`) | Pengguna mencoba mengakses tugas yang bukan bagian dari mata kuliah yang berhak diaksesnya. | Pengguna yang memiliki hak akses terhadap mata kuliah dan tugas tersebut. | Periksa otorisasi serta kesesuaian relasi antara tugas dan mata kuliah pada URL. |
| 3 | Pengumpulan tugas (`{submission}`) | Mahasiswa mencoba melihat atau mengubah pengumpulan tugas milik mahasiswa lain dengan mengganti ID. | Mahasiswa pemilik pengumpulan atau dosen yang berwenang menilainya. | Periksa apakah controller membatasi akses berdasarkan pemilik pengumpulan dan peran pengguna. |
| 4 | Materi (`{material}`) | Pengguna mencoba membuka atau mengubah materi dari mata kuliah yang tidak boleh diaksesnya. | Pengguna yang berhak mengakses materi tersebut; perubahan hanya untuk pihak yang berwenang. | Periksa hak akses terhadap materi dan relasinya dengan mata kuliah terkait. |
| 5 | Nilai atau data penilaian, jika menggunakan parameter model | Pengguna mencoba melihat atau mengubah nilai milik mahasiswa lain. | Mahasiswa hanya boleh melihat nilai sendiri, sedangkan dosen yang berwenang boleh mengelola nilai sesuai tanggung jawabnya. | Periksa otorisasi berdasarkan mahasiswa, pengumpulan tugas, dan dosen yang berwenang. |

### BREAK
| # | Yang Dicoba                                                                                                   | Hasil Pengamatan                                                                                                                                                                                                                                                                                                                                        |
| - | ------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1 | Login sebagai mahasiswa A, kemudian akses submission mahasiswa B dengan mengganti ID pada URL.                | Setelah ID submission diubah, sistem menampilkan 403 Forbidden karena akses ditolak. Pemeriksaan kepemilikan seperti `abort_unless` mencegah mahasiswa membuka berkas jawaban dan nilai milik mahasiswa lain. Tanpa perlindungan tersebut, aplikasi berisiko mengalami celah IDOR.                                                                      |
| 2 | Buka `/courses/1/assignments/99`, padahal tugas 99 terhubung dengan mata kuliah lain.                         | Tanpa scoping, tugas berpotensi tetap terbuka karena ID mata kuliah dan ID tugas diperiksa secara terpisah tanpa memastikan hubungan keduanya. Akibatnya, pengguna dapat mengakses tugas yang bukan bagian dari mata kuliah tersebut.                                                                                                                   |
| 3 | Aktifkan `Route::scopeBindings()`, lalu ulangi pengujian nomor 2.                                             | Setelah scoping diterapkan, akses ke tugas yang tidak berhubungan dengan mata kuliah pada URL menghasilkan 404 Not Found. Laravel memastikan bahwa objek anak, yaitu tugas, memang terhubung dengan objek induknya, yaitu mata kuliah.                                                                                                                  |
| 4 | Coba daftarkan middleware melalui `app/Http/Kernel.php` mengikuti tutorial Laravel versi lama.                | Berkas `app/Http/Kernel.php` tidak tersedia pada struktur proyek Laravel 12 ini. Pendaftaran alias middleware, seperti `role`, dilakukan melalui `bootstrap/app.php` menggunakan `->withMiddleware()`. Hal ini menunjukkan adanya perubahan struktur konfigurasi pada versi Laravel yang lebih baru.                                                    |
| 5 | Terapkan `role:admin` pada grup route, lalu akses halaman admin menggunakan akun dosen.                       | Sistem menolak akses dengan 403 Forbidden karena akun dosen tidak memiliki role admin. Middleware berfungsi membatasi akses berdasarkan peran sehingga route khusus admin tidak dapat dibuka oleh pengguna dengan role yang berbeda.                                                                                                                    |
| 6 | Login sebagai dosen A, lalu coba mengedit mata kuliah milik dosen B, meskipun keduanya memiliki role `dosen`. | Jika controller hanya memeriksa role tanpa memvalidasi kepemilikan data, dosen A masih dapat mengubah mata kuliah dosen B. Hal ini membuktikan bahwa middleware role saja tidak cukup. Pemeriksaan kepemilikan perlu dilakukan melalui `abort_unless`, policy, atau mekanisme otorisasi lain agar dosen hanya dapat mengubah data sesuai kewenangannya. |

### FIX

**1. Daftar Masalah dan Perbaikan**

| No. | Masalah | Lokasi | Risiko | Perbaikan |
|---|---|---|---|---|
| 1 | Route profil tidak dilindungi autentikasi | `routes/web.php` | Pengguna yang belum login berpotensi membuka halaman profil. | Memasukkan route `/profile` ke dalam grup middleware `auth`. |
| 2 | IDOR pada submission | `SubmissionController.php` | Mahasiswa dapat mencoba membuka submission milik mahasiswa lain dengan mengganti ID pada URL. | Memvalidasi kepemilikan submission sebelum data ditampilkan. Pengguna yang tidak berhak ditolak dengan respons `403 Forbidden`, kecuali admin yang memang memiliki izin. |
| 3 | IDOR pada material dan nested route | `MaterialController.php`, `routes/web.php` | Dosen dapat mencoba mengakses materi milik dosen lain atau material yang tidak sesuai dengan mata kuliah pada URL. | Menambahkan `scopeBindings()` pada nested route dan pemeriksaan kepemilikan mata kuliah di controller. |
| 4 | Pendaftaran alias middleware tidak sesuai | `bootstrap/app.php` dan konfigurasi middleware | Alias `role` tidak dikenali sehingga aplikasi dapat mengalami error `Target class [role] does not exist`. | Mendaftarkan alias `role` melalui konfigurasi middleware di `bootstrap/app.php` sesuai struktur Laravel yang digunakan. |
| 5 | Nama route sama pada beberapa peran | `routes/web.php` | Pemanggilan nama seperti `courses.index` dapat mengarah ke route yang tidak sesuai dengan peran pengguna. | Memberikan awalan nama yang berbeda, misalnya `admin.`, `dosen.`, dan `mahasiswa.`. |
| 6 | Penghapusan data menggunakan GET | `routes/web.php` dan form terkait | Permintaan penghapusan dapat terpicu tanpa sengaja melalui tautan atau pemuatan otomatis. | Mengganti method menjadi `DELETE`, menggunakan perlindungan CSRF, dan memastikan hanya pengguna berwenang yang dapat menjalankannya. |

**2. Bukti Pengujian IDOR pada Submission**
Pengujian dilakukan untuk memastikan mahasiswa tidak dapat melihat submission milik mahasiswa lain dengan mengubah ID pada alamat URL.

- Sebelum perbaikan

Jalankan perintah berikut menggunakan cookie sesi login Mahasiswa B:

```bash
curl -i -b cookies_mhs_b.txt http://localhost:8000/mahasiswa/submissions/1
```

Jika celah IDOR masih ada, sistem mungkin memberikan respons `200 OK` dan menampilkan submission milik Mahasiswa A.

- Sesudah perbaikan

Jalankan kembali perintah yang sama setelah pemeriksaan kepemilikan diterapkan:

```bash
curl -i -b cookies_mhs_b.txt http://localhost:8000/mahasiswa/submissions/1
```

Respons yang diharapkan:

```http
HTTP/1.1 403 Forbidden
```

Respons tersebut menunjukkan bahwa akses ditolak karena Mahasiswa B tidak memiliki izin untuk melihat submission Mahasiswa A.


