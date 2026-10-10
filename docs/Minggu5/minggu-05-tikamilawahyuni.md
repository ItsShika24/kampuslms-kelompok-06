# LAPORAN MINGGU 5 

**Nama :** Tika Mila Wahyuni  
**NIM  :** 10241070  

---

# 1. READ
## 1. Jalankan php artisan `route:list --except-vendor`. Salin keluarannya ke catatan.
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
## 2. Tandai setiap route yang menerima parameter model (`{course}`, `{assignment}`, dst).

Berikut adalah daftar rute yang menerima parameter model (ditandai dengan `{...}` pada URL):
1. **Parameter `{course}` (Mata Kuliah):**
   - `GET /mata-kuliah/{course}` & `GET /admin/courses/{course}` & `GET /dosen/courses/{course}` & `GET /mahasiswa/courses/{course}`
   - `GET /mata-kuliah/{course}/edit` & `GET /admin/courses/{course}/edit` & `GET /dosen/courses/{course}/edit`
   - `PUT /mata-kuliah/{course}` & `PUT /admin/courses/{course}` & `PUT /dosen/courses/{course}`
   - `DELETE /mata-kuliah/{course}` & `DELETE /admin/courses/{course}`
   - `POST /mata-kuliah/{course}/tugas` & `POST /dosen/courses/{course}/assignments`
   - `POST /mata-kuliah/{course}/materi` & `POST /dosen/courses/{course}/materials`
2. **Parameter `{assignment}` (Tugas):**
   - `GET /tugas/{assignment}` & `GET /dosen/assignments/{assignment}` & `GET /mahasiswa/assignments/{assignment}`
   - `PUT /tugas/{assignment}` & `PUT /dosen/assignments/{assignment}`
   - `DELETE /tugas/{assignment}` & `DELETE /dosen/assignments/{assignment}`
   - `POST /tugas/{assignment}/submit` & `POST /mahasiswa/assignments/{assignment}/submit`
   - `GET /mata-kuliah/{course}/tugas/{assignment}` *(nested)*
3. **Parameter `{material}` (Materi):**
   - `GET /materi/{material}/download` & `GET /mahasiswa/materials/{material}/download`
   - `DELETE /materi/{material}` & `DELETE /dosen/materials/{material}`
4. **Parameter `{submission}` (Pengumpulan Tugas):**
   - `GET /submissions/{submission}`
   - `POST /submissions/{submission}/grade`
5. **Parameter `{user}` (Pengguna):**
   - `GET /pengguna/{user}` & `GET /admin/users/{user}`
   - `PUT /pengguna/{user}` & `PUT /admin/users/{user}`
   - `DELETE /pengguna/{user}` & `DELETE /admin/users/{user}`
---

## 3. Untuk setiap route bertanda, jawab: siapa saja yang seharusnya boleh mengaksesnya, dan apa yang saat ini mencegah orang lain? Kemungkinan besar jawabannya "belum ada apa-apa" — itu wajar, dan itulah pekerjaan minggu ini dan minggu 7.

* **Siapa yang seharusnya boleh mengakses:**
  - **Submission:** Hanya mahasiswa pemilik tugas tersebut, dosen pengampu mata kuliah terkait, dan admin.
  - **Mata Kuliah (Edit/Hapus/Tambah Tugas & Materi):** Hanya dosen pengampu mata kuliah tersebut dan admin.
  - **Tugas (Edit/Hapus):** Hanya dosen pengampu yang membuat tugas tersebut dan admin.
  - **Pengumpulan Tugas (Submit) & Download Materi:** Hanya mahasiswa yang benar-benar terdaftar di kelas mata kuliah tersebut.
  - **Pengguna:** Hanya admin (atau pengguna yang bersangkutan melihat profilnya sendiri).
* **Apa yang saat ini mencegah orang lain?**
  - **Jawabannya: Awalnya belum ada apa-apa**  
    Secara bawaan, Route Model Binding Laravel hanya mengecek apakah datanya ada atau tidak di database. Laravel sama sekali tidak mengecek apakah yang sedang login berhak membuka data itu atau bukan. Akibatnya, siapa saja yang mengganti angka ID di URL bisa langsung melihat atau mengedit data orang lain (celah IDOR).  
  - Oleh karena itu, di Minggu 5 ini kita mulai memasang pencegahan Lapis 1 menggunakan middleware peran dan pengecekan manual kepemilikan (`abort_unless`) di controller.
---

## 4. Buat tabel di `docs/minggu-05-<nama>.md` berjudul "Daftar Titik Rawan IDOR". 

| Route / URL | Parameter | Siapa yang Boleh Akses? | Bahayanya Kalau Tidak Dicek | Cara Mencegahnya (Minggu 5) |
|---|---|---|---|---|
| `GET /submissions/{submission}` | `{submission}` | Mahasiswa pemilik tugas, Dosen pengampu, dan Admin | Mahasiswa lain bisa melihat jawaban, file tugas, dan nilai teman sekelasnya cuma dengan ganti angka ID di URL. | Cek pemilik: `abort_unless($submission->user_id === auth()->id() \|\| auth()->user()->role === 'admin' \|\| $submission->assignment->course->lecturer_id === auth()->id(), 403);` |
| `POST /submissions/{submission}/grade` | `{submission}` | Dosen pengampu mata kuliah & Admin | Dosen A bisa memberi atau mengubah nilai mahasiswa di mata kuliah yang diampu Dosen B. | Pastikan dosen yang login adalah pengampu mata kuliah tugas tersebut. |
| `GET /mata-kuliah/{course}/edit`<br>`PUT /mata-kuliah/{course}` | `{course}` | Dosen pengampu mata kuliah & Admin | Dosen lain bisa mengedit nama, deskripsi, atau SKS mata kuliah milik dosen lain. | `abort_unless(auth()->user()->role === 'admin' \|\| (auth()->user()->role === 'dosen' && $course->lecturer_id === auth()->id()), 403);` |
| `DELETE /mata-kuliah/{course}` | `{course}` | Khusus Admin | Dosen atau user lain bisa menghapus mata kuliah dari database. | Kunci rute agar hanya bisa diakses role admin (`role:admin`). |
| `POST /mata-kuliah/{course}/tugas`<br>`POST /mata-kuliah/{course}/materi` | `{course}` | Dosen pengampu & Admin | Dosen lain bisa menambahkan tugas atau materi sembarangan ke mata kuliah orang lain. | Cek apakah `lecturer_id` mata kuliah cocok dengan ID dosen yang sedang login. |
| `PUT/DELETE /tugas/{assignment}`<br>`DELETE /materi/{material}` | `{assignment}`<br>`{material}` | Dosen pengampu & Admin | Dosen luar bisa menghapus tugas/materi atau mengganti deadline tugas kelas lain. | Cek relasi kepemilikan dosen pengampu sebelum update/delete. |
| `POST /tugas/{assignment}/submit` | `{assignment}` | Mahasiswa yang mengambil mata kuliah tersebut | Mahasiswa dari luar kelas bisa ikut mengirim tugas ke mata kuliah yang tidak diambilnya. | Cek apakah mahasiswa terdaftar di mata kuliah terkait sebelum simpan jawaban. |
| `GET /materi/{material}/download` | `{material}` | Mahasiswa kelas, Dosen pengampu, & Admin | Mahasiswa luar kelas bisa mengunduh file materi atau soal ujian kelas lain. | Cek apakah mahasiswa yang login memang terdaftar di kelas tersebut. |
| `GET/PUT/DELETE /pengguna/{user}` | `{user}` | Admin (atau user melihat profil sendiri) | User biasa bisa mengedit data user lain atau mengganti rolenya sendiri menjadi admin. | Batasi rute pengguna menggunakan middleware `role:admin`. |


# 2. BREAK

| # | Yang dicoba | Hasil Pengamatan |
|---|-------------|------------------------|
| 1 | Login sebagai mahasiswa A. Buka submission milik mahasiswa B dengan mengubah angka di URL | Saat ID di URL diubah (dari `/submissions/2101` ke `210`), muncul error 403 Forbidden karena sistem menolak akses. Tanpa pengaman `abort_unless`, berkas jawaban dan nilai mahasiswa lain akan langsung terbuka (celah IDOR). |
| 2 | Buka `/courses/1/assignments/99` di mana tugas 99 milik mata kuliah lain | Jika tanpa scoping, halaman tugas tetap terbuka normal menampilkan tugas orang lain. Laravel mengecek ID mata kuliah dan ID tugas secara terpisah tanpa memastikan apakah tugas tersebut memang milik mata kuliah tersebut. |
| 3 | Aktifkan `Route::scopeBindings()`, ulangi nomor 2 | `Route::scopeBindings()` sudah diaktifkan di routes/web.php, saat URL silang (/mata-kuliah/1/tugas/4) dibuka, peramban langsung menampilkan 404 Not Found. Laravel otomatis mengecek relasi di database dan menolak tugas yang bukan milik mata kuliah tersebut. |
| 4 | Daftarkan middleware di `app/Http/Kernel.php` seperti tutorial lama | Berkas `app/Http/Kernel.php` tidak ditemukan di proyek karena sudah ditiadakan di Laravel 12. Pendaftaran alias middleware (seperti `role`) sekarang dipusatkan di berkas `bootstrap/app.php` menggunakan fungsi `->withMiddleware()`. |
| 5 | Pasang `role:admin` pada grup, lalu akses sebagai dosen | Saat login sebagai dosen dan mencoba membuka halaman admin (`/admin/users`), sistem langsung mencegat dengan pesan error **403**. Middleware bekerja efektif sebagai penjaga gerbang untuk menolak peran yang tidak sesuai. |
| 6 | Sebagai dosen A, edit mata kuliah milik dosen B (keduanya lolos `role:dosen`) | Tanpa verifikasi pemilik di controller, Dosen A berhasil mengedit mata kuliah Dosen B karena keduanya sama-sama lolos middleware `role:dosen`. Ini membuktikan **middleware saja tidak cukup**; middleware hanya mengecek peran pengguna, sedangkan pengecekan kepemilikan data spesifik harus dilakukan di controller (melalui `abort_unless`) atau policy di minggu 7 nanti.|


# FIX - w05

### 1. Route di Luar Grup Auth
- **Lokasi:** `routes/web.php`
- **Risiko:** Route `/profile` berada di luar middleware `auth`, memungkinkan pengguna tanpa login (tamu) mengakses halaman profil dan membocorkan data.
- **Perbaikan:** Memindahkan route `/profile` ke dalam `Route::middleware('auth')->group(...)`.
### 2. IDOR pada Submission (SubmissionController@show)
- **Lokasi:** `app/Http/Controllers/SubmissionController.php`
- **Risiko:** Route model binding hanya memastikan data ada, tetapi tidak memverifikasi kepemilikan. Mahasiswa B bisa melihat tugas dan nilai Mahasiswa A hanya dengan mengubah ID di URL (`/mahasiswa/submissions/1`).
- **Perbaikan:** Menambahkan otorisasi kepemilikan: `abort_if($submission->user_id !== auth()->id() && auth()->user()->role !== 'admin', 403)`.
### 3. IDOR pada Material & Nested Route tanpa scopeBindings
- **Lokasi:** `app/Http/Controllers/MaterialController.php` & `routes/web.php`
- **Risiko:** 
  1. Tanpa `scopeBindings()`, URL `/dosen/courses/1/materials/99` dapat diakses meskipun material 99 milik mata kuliah lain.
  2. Dosen yang bukan pengampu bisa melihat, mengedit, dan menghapus materi mata kuliah dosen lain.
- **Perbaikan:** Menambahkan `->scopeBindings()` pada grup routing dosen dan menambahkan proteksi `abort_if($course->lecturer_id !== auth()->id(), 403)` pada `MaterialController`.
### 4. Middleware Didaftarkan di Berkas yang Salah
- **Lokasi:** `app/Http/Kernel.php` & `bootstrap/app.php`
- **Risiko:** Di Laravel 12 berkas `app/Http/Kernel.php` sudah tidak digunakan. Pendaftaran alias middleware di sana menyebabkan error fatal: `Target class [role] does not exist`.
- **Perbaikan:** Menghapus `app/Http/Kernel.php` dan mendaftarkan alias `'role'` di `bootstrap/app.php` via `$middleware->alias(...)`.
### 5. Nama Route Bentrok Antar Peran
- **Lokasi:** `routes/web.php`
- **Risiko:** Grup admin, dosen, dan mahasiswa sama-sama mendefinisikan `courses.index`. Route mahasiswa menimpa route admin dan dosen, menyebabkan admin/dosen terlempar ke URL mahasiswa dan terkena error 403 saat memanggil `route('courses.index')`.
- **Perbaikan:** Menambahkan name prefix pada masing-masing grup: `->name('admin.')`, `->name('dosen.')`, dan `->name('mahasiswa.')`.
### 6. Route Destruktif Menggunakan GET
- **Lokasi:** `routes/web.php`
- **Risiko:** Route pengosongan data (`truncate()`) menggunakan method `GET`. Browser prefetching, crawler bot, atau tag gambar tersembunyi (`<img src="...">`) dapat memicu penghapusan seluruh data submission tanpa sengaja (CSRF via GET).
- **Perbaikan:** Mengubah method route menjadi `Route::delete('/submissions/destroy-all')`.

## Bukti Pengujian cURL (IDOR Submission)

Pengujian dilakukan dengan akun **Mahasiswa B** (User ID: 2) yang mencoba mengakses submission milik **Mahasiswa A** (Submission ID: 1, User ID: 1).
### Sebelum Perbaikan:
Request berhasil mengambil data mahasiswa lain (Celah IDOR Terbuka):

```bash
curl -i -b cookies_mhs_b.txt http://localhost:8000/mahasiswa/submissions/1
```

**Respons:**

```
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8

<!-- Halaman terbuka: Detail submission milik Mahasiswa A ditampilkan -->
```

**Sesudah Perbaikan:**
Request ditolak karena sistem memvalidasi kepemilikan (Celah IDOR Tertutup):

```bash
curl -i -b cookies_mhs_b.txt http://localhost:8000/mahasiswa/submissions/1
```

**Respons:**

```
HTTP/1.1 403 Forbidden
Content-Type: text/html; charset=UTF-8

{
    "message": "Anda tidak berhak melihat submission ini."
}
```


