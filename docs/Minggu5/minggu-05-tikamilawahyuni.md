# LAPORAN MINGGU 5

**Nama :** Tika Mila Wahyuni  
**NIM  :** 10241070  

---

# 1. READ — Pemetaan Route dan Titik Rawan IDOR

## 1.1 Daftar Route Aplikasi (`php artisan route:list --except-vendor`)

Berikut adalah daftar route utama di aplikasi KampusLMS yang menerima parameter (seperti `{course}`, `{assignment}`, `{submission}`, dan `{user}`):

```text
GET|HEAD   /mata-kuliah/{course} ............................ mata-kuliah.show › CourseController@show
GET|HEAD   /mata-kuliah/{course}/edit ....................... mata-kuliah.edit › CourseController@edit
PUT        /mata-kuliah/{course} .......................... mata-kuliah.update › CourseController@update
DELETE     /mata-kuliah/{course} ......................... mata-kuliah.destroy › CourseController@destroy
GET|HEAD   /mata-kuliah/{course}/tugas/{assignment} ........................... AssignmentController@show
POST       /mata-kuliah/{course}/tugas ............................. tugas.store › AssignmentController@store
GET|HEAD   /tugas/{assignment} ..................................... tugas.show › AssignmentController@show
PUT        /tugas/{assignment} ................................. tugas.update › AssignmentController@update
DELETE     /tugas/{assignment} ............................... tugas.destroy › AssignmentController@destroy
POST       /tugas/{assignment}/submit .......................... tugas.submit › AssignmentController@submit
GET|HEAD   /materi/{material}/download ....................... materi.download › MaterialController@download
DELETE     /materi/{material} ................................. materi.destroy › MaterialController@destroy
GET|HEAD   /submissions/{submission} ......................... submissions.show › SubmissionController@show
POST       /submissions/{submission}/grade ................. submissions.grade › SubmissionController@grade
GET|HEAD   /pengguna/{user} ...................................... pengguna.show › UserController@show
PUT        /pengguna/{user} .................................... pengguna.update › UserController@update
DELETE     /pengguna/{user} .................................. pengguna.destroy › UserController@destroy
```

---

## 1.2 Daftar Titik Rawan IDOR (*Insecure Direct Object Reference*)

IDOR itu kondisi di mana pengguna bisa mengintip atau mengubah data milik orang lain hanya dengan mengganti nomor ID pada URL browser (misalnya dari `/submissions/41` diubah jadi `/submissions/42`). 

Laravel memang otomatis mencarikan datanya lewat Route Model Binding, tapi Laravel **tidak otomatis mengecek apakah orang yang sedang membuka berhak melihat data itu atau tidak**.

Berikut adalah analisis titik rawan IDOR di aplikasi KampusLMS:

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

---

# 2. BREAK — Analisis Enam Kerusakan

Pengujian dilakukan dengan menulis prediksi lebih dulu, lalu menjalankannya di aplikasi untuk melihat apa yang sebenarnya terjadi.

---

### 1. IDOR Nyata pada Submission
* **Yang dicoba:** Login sebagai mahasiswa A yang punya tugas di `/submissions/41`. Lalu ganti angka di URL menjadi `/submissions/42` (tugas milik mahasiswa B).
* **Prediksi:** Kalau di controller cuma pakai `show(Submission $submission)` tanpa cek siapa yang login, data tugas mahasiswa B pasti langsung terbuka.
* **Hasil Pengamatan:** Benar terbuka. Jawaban tugas, file, dan nilai milik mahasiswa B langsung muncul tanpa dicegah sama sekali.
* **Kesimpulan:** Route Model Binding itu cuma bertugas mengecek "datanya ada atau tidak di database". Soal "boleh dibuka atau tidak", itu tanggung jawab kita untuk mengeceknya di controller menggunakan `abort_unless(...)`.

---

### 2. Nested Route Tanpa Scoping
* **Yang dicoba:** Buka URL `/courses/1/assignments/99`, padahal tugas ID 99 sebenarnya milik mata kuliah lain (ID 7).
* **Prediksi:** Halaman tugas 99 akan tetap terbuka normal karena Laravel mencari data course dan assignment secara terpisah.
* **Hasil Pengamatan:** Tugas 99 tetap terbuka di layar. Padahal aneh kalau di URL tertulis mata kuliah 1 tapi isinya tugas dari mata kuliah 7.
* **Kesimpulan:** Tanpa scoping, Laravel tidak memeriksa apakah tugas tersebut memang benar-benar milik mata kuliah yang ada di URL.

---

### 3. Mengaktifkan `Route::scopeBindings()`
* **Yang dicoba:** Rute nested dibungkus dengan `Route::scopeBindings()`, lalu coba buka lagi `/courses/1/assignments/99`.
* **Prediksi:** Karena tugas 99 bukan milik course 1, Laravel akan menolak dan memunculkan error 404 Not Found.
* **Hasil Pengamatan:** Muncul halaman 404 Not Found.
* **Kesimpulan:** `scopeBindings()` membuat Laravel otomatis mengecek hubungan relasi antara data induk (course) dan data anak (assignment). Kalau tidak cocok, langsung dianggap tidak ada (404).

---

### 4. Pendaftaran Middleware di `app/Http/Kernel.php`
* **Yang dicoba:** Mencari file `app/Http/Kernel.php` untuk mendaftarkan middleware seperti tutorial di internet.
* **Prediksi:** File tidak akan ditemukan karena struktur Laravel 12 sudah berbeda dengan Laravel versi lama.
* **Hasil Pengamatan:** File `Kernel.php` memang tidak ada sama sekali di folder `app/Http/`.
* **Kesimpulan:** Di Laravel 12, pendaftaran middleware sekarang dipusatkan di file `bootstrap/app.php` lewat fungsi `->withMiddleware()`. Jangan bingung kalau banyak tutorial lama masih menyuruh edit file Kernel.

---

### 5. Akun Dosen Mencoba Masuk ke Halaman Admin
* **Yang dicoba:** Login menggunakan akun dosen, lalu coba buka rute khusus admin seperti `/admin/users`.
* **Prediksi:** Dosen akan langsung ditolak dan muncul pesan error 403 Forbidden.
* **Hasil Pengamatan:** Muncul halaman error 403 Forbidden. Dosen tidak bisa masuk ke halaman admin.
* **Kesimpulan:** Middleware bertindak seperti satpam di depan pintu masuk. Siapa pun yang rolenya tidak sesuai langsung dicegat sebelum bisa masuk ke halaman tersebut.

---

### 6. Dosen A Mengedit Mata Kuliah Milik Dosen B
* **Yang dicoba:** Login sebagai Dosen A, lalu buka link edit mata kuliah kepunyaan Dosen B (`/mata-kuliah/{id_dosen_b}/edit`).
* **Prediksi:** Karena Dosen A dan Dosen B sama-sama punya peran dosen, middleware `role:dosen` akan meloloskan mereka berdua. Kalau di controller tidak dicek pemiliknya, Dosen A pasti bisa mengedit mata kuliah Dosen B.
* **Hasil Pengamatan:** Terbukti berhasil diedit. Dosen A bisa mengubah nama dan data mata kuliah milik Dosen B.
* **Kesimpulan Penting:** **Middleware saja tidak cukup!**  
  Middleware cuma mengecek *"Kamu dosen atau bukan?"*. Tapi middleware tidak tahu *"Mata kuliah ini punya kamu atau bukan?"*. Jadi kita tetap wajib mengecek kepemilikan data di controller (atau menggunakan Policy di Minggu 7 nanti).

---

# 3. CHECKPOINT — Pertanyaan dan Pemahaman Minggu 5

### 1. Apa itu IDOR? Berikan contohnya di aplikasi dan cara memperbaikinya.
**Jawaban:**  
IDOR (*Insecure Direct Object Reference*) adalah celah keamanan di mana pengguna bisa melihat atau mengubah data milik orang lain hanya dengan mengganti nomor ID di URL.  
* **Contoh:** Mahasiswa membuka tugasnya di `/submissions/41`, lalu mengganti angka di URL jadi `/submissions/42` dan bisa melihat tugas serta nilai teman sekelasnya.  
* **Cara perbaikinya:** Tambahkan pengecekan kepemilikan di controller sebelum menampilkan data:
  ```php
  abort_unless(
      $submission->user_id === auth()->id() 
      || auth()->user()->role === 'admin' 
      || $submission->assignment->course->lecturer_id === auth()->id(), 
      403
  );
  ```

---

### 2. Kenapa mengganti angka ID menjadi UUID BUKAN solusi untuk IDOR?
**Jawaban:**  
Karena UUID cuma membuat kodenya jadi panjang dan susah ditebak, tapi pintunya tetap tidak dikunci. Kalau orang lain berhasil mendapatkan link UUID tersebut (misal dikirim lewat chat atau inspect browser), datanya tetap bisa dibuka. Solusi aslinya adalah mengunci akses di controller dengan mengecek siapa yang sedang login.

---

### 3. Route Model Binding menjamin apa, dan TIDAK menjamin apa?
**Jawaban:**  
* **Menjamin:** Datanya ada di database. Kalau datanya ada, langsung diubah jadi objek model; kalau tidak ada, langsung keluar error 404.  
* **TIDAK Menjamin:** Apakah orang yang membuka rute tersebut berhak melihat/mengedit data itu atau tidak.

---

### 4. Apa fungsi `Route::scopeBindings()`? Beri contoh URL yang lolos tanpa itu.
**Jawaban:**  
Fungsinya untuk memastikan data anak di URL memang benar-benar bagian dari data induknya.  
* **Contoh yang lolos tanpa scoping:** `/courses/1/assignments/99` (di mana tugas 99 sebenarnya milik mata kuliah 7). Tanpa scoping, tugas 99 tetap kebuka. Kalau pakai `scopeBindings()`, Laravel otomatis menolak dengan error 404 karena tugas 99 bukan milik mata kuliah 1.

---

### 5. Di file mana middleware didaftarkan pada Laravel 12? Kenapa berbeda dengan kebanyakan tutorial?
**Jawaban:**  
Didaftarkan di file `bootstrap/app.php`. Berbeda karena di Laravel 11 dan 12 struktur foldernya dirampingkan, sehingga file `app/Http/Kernel.php` yang biasa dipakai di Laravel versi lama sudah dihapus.

---

### 6. Kenapa middleware `role:dosen` tidak cukup untuk mencegah Dosen A mengedit mata kuliah Dosen B?
**Jawaban:**  
Karena middleware hanya mengecek jabatan/role penggunanya. Selama Dosen A rolenya adalah `dosen`, middleware menganggap dia boleh lewat. Middleware tidak tahu mata kuliah nomor sekian itu milik siapa. Supaya aman, kita harus menambahkan pengecekan kepemilikan di controllernya.
