## Catatan Minggu 4 Pemrograman Web

### READ

Pengujian ini dilakukan dengan mengirimkan formulir tambah mata kuliah yang datanya sengaja dibuat tidak valid (misalnya SKS diisi 99).

**1. Method apa yang menerima request? Di controller mana?**

Jawaban : Request POST dari formulir diterima oleh method `store(StoreCourseRequest $request)` di `CourseController.php`.


**2. Di titik mana persisnya validasi terjadi, sebelum atau sesudah baris pertama method controller?**

Jawaban : Validasi terjadi sebelum baris pertama method controller dijalankan. Karena memakai Form Request (`StoreCourseRequest`), Laravel memvalidasi data lebih dulu, baru menjalankan `store()`. Kalau validasi gagal, Laravel melempar `ValidationException` dan mengembalikan pengguna ke form. Jadi kode di dalam `store()` tidak pernah dijalankan.


**3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?**

Jawaban : Laravel mengembalikan pengguna ke halaman form sebelumnya (`courses/create`). Tujuan ini ditentukan oleh Form Request lewat method `getRedirectUrl()`, yang mengambil URL halaman sebelumnya dari header `Referer` atau dari session (`_previous.url`). Tujuan ini bisa diubah dengan properti `$redirect` atau `$redirectRoute` di Form Request.


**4. Dari mana `@error('sks')` mengambil pesannya?**

Jawaban : `@error('sks')` mengambil pesan error untuk field `sks` dari variabel `$errors` (error bag). Error bag ini dibuat Laravel saat validasi gagal dan disimpan sementara di session.


**5. Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai itu bertahan?**

Jawaban : `old('sks')` mengambil nilai input sebelumnya dari flash session dengan key `_old_input.sks`. Nilai ini hanya bertahan **satu request berikutnya**. Setelah halaman hasil redirect ditampilkan, Laravel langsung menghapusnya.


**6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel. Catat namanya.**

Jawaban : Nama cookie session aplikasi: `kampuslms_session`. Namanya dibuat dari konfigurasi `config/session.php` berdasarkan nama aplikasi.


---

### BREAK - 7 Kerusakan dan Hasil Pengamatan

| # | Kerusakan yang Dicoba | Prediksi dan Hasil Pengamatan | Analisis Keamanan dan UX |
|---|---|---|---|
| **1** | Hapus `@csrf` dari form, lalu submit. | Muncul error **HTTP 419 Page Expired**. | Tanpa token CSRF, situs jahat bisa memaksa browser korban mengirim aksi POST tanpa disadari (serangan *Cross-Site Request Forgery*). Token `@csrf` memastikan request benar-benar berasal dari form milik sistem kita. |
| **2** | Ganti `$request->validated()` jadi `$request->all()`, lalu kirim field tambahan lewat `curl`. | Field yang tidak ada di formulir ikut terbaca dan bisa ikut tersimpan. | Celah *mass assignment* terbuka lagi, sehingga penyerang bisa menyisipkan data sensitif seperti foreign key atau status. `$request->validated()` memastikan hanya field yang lolos aturan validasi yang diproses. |
| **3** | Hapus validasi `exists:users,id` pada `lecturer_id`, lalu kirim `lecturer_id=99999`. | Muncul error 500 (`QueryException`, foreign key constraint gagal). Kalau tidak ada constraint di database, muncul data yatim (*orphan*). | Aturan `exists` mencegah data rusak masuk ke database. Aturan ini juga mengubah error 500 yang membingungkan menjadi pesan validasi yang ramah bagi pengguna. |
| **4** | Hapus validasi `in:...` pada `status`, lalu kirim `status=superadmin`. | Kalau kolom bertipe string, nilai asal-asalan itu tersimpan. Kalau bertipe enum, database menolaknya dan muncul error 500. | `in:draft,active,archived` membatasi nilai hanya pada yang diizinkan (*whitelist*), sehingga alur bisnis tidak bisa dimanipulasi. |
| **5** | Hapus `->withQueryString()`, lalu cari data dan klik halaman 2. | Parameter pencarian dan filter (`?q=...&status=...`) hilang, sehingga halaman 2 menampilkan semua data. | Ini bug umum pada pagination: pengguna kehilangan filter saat pindah halaman. `withQueryString()` menjaga query string tetap ada di semua link pagination. |
| **6** | Ganti `return redirect()` jadi `return view()` di `store`, lalu tekan F5. | Browser menampilkan peringatan *"Confirm Form Resubmission"*. Kalau pengguna melanjutkan, data tersimpan dua kali. | Inilah alasan pola **PRG (Post-Redirect-Get)** dipakai. Setelah redirect, halaman terakhir adalah request GET, sehingga F5 tidak mengirim ulang form dan tidak menggandakan data. |
| **7** | Hapus `old(...)` dari semua input, lalu kirim form dengan satu field salah. | Semua field yang tadinya sudah benar ikut kosong lagi. | Pengguna harus mengetik ulang seluruh form hanya karena satu kesalahan kecil, sehingga pengalaman pengguna (UX) jadi buruk. |


---

### FIX 

**Branch `w04` pada repo `kampuslms-broken` berisi modul mata kuliah dengan 6 masalah: validasi hanya di frontend, `unique` pada update yang menolak dirinya sendiri, filter disimpan di session, pagination kehilangan query string, `store` tanpa redirect, dan satu form tanpa `@csrf`.**

---

**1. Validasi hanya di frontend**

- Masalah : `CourseController@store` dan `@update` hanya mengandalkan atribut `required` di HTML dan pengecekan JavaScript. Tidak ada validasi di server, dan data dari request langsung disimpan.

- Dampak : Bagi pengguna, kesalahan input yang lolos dari JavaScript menghasilkan data rusak atau error 500. Bagi penyerang, validasi frontend bisa dilewati lewat DevTools atau `curl`, sehingga data sampah seperti `sks=99` atau `status=superadmin` bisa masuk ke database.

- Perbaikan : Dibuat `StoreCourseRequest` dan `UpdateCourseRequest` berisi aturan `required`, `max`, `between:1,6`, `exists:users,id`, dan `in:draft,active,archived`, lengkap dengan pesan bahasa Indonesia. Controller kini memakai `$request->validated()`. Setelah perbaikan, `curl` dengan `sks=99` ditolak dengan redirect 302 beserta error validasi.


**2. `unique` pada update menolak dirinya sendiri**

- Masalah : Aturan `unique:courses,code` ditulis polos pada `UpdateCourseRequest`.

- Dampak : Mengedit mata kuliah tanpa mengubah kodenya selalu gagal dengan pesan "Kode mata kuliah ini sudah dipakai", karena kodenya dianggap bentrok dengan baris dirinya sendiri. Ini bug fungsional, bukan celah keamanan.

- Perbaikan : Aturan diganti menjadi `Rule::unique('courses', 'code')->ignore($this->route('course'))`. Setelah itu edit tanpa mengubah kode berhasil, sedangkan memakai kode milik mata kuliah lain tetap ditolak.


**3. Filter disimpan di session**

- Masalah : `CourseController@index` menyimpan pencarian dan filter status lewat `session(['q' => ..., 'status' => ...])`, lalu membacanya kembali dari session.

- Dampak : Tautan hasil pencarian tidak bisa dibagikan, tombol back membuat filter tidak sesuai dengan URL, dan membuka dua tab membuat filter di satu tab menimpa tab lainnya.

- Perbaikan : Filter dibaca langsung dari query string (`$request->q`, `$request->status`) memakai `->when($request->filled(...))`, dan semua kode session untuk filter dihapus. Bagian `orWhere` pencarian dibungkus closure agar tidak melewati filter status.


**4. Pagination kehilangan query string**

- Masalah : `paginate(15)` dipanggil tanpa `->withQueryString()`.

- Dampak : Setelah mencari "basis data" lalu klik halaman 2, parameter `?q=...&status=...` hilang dan halaman 2 menampilkan semua data tanpa filter.

- Perbaikan : Ditambahkan `->withQueryString()` setelah `paginate(15)`. Tautan halaman 2 kini tetap membawa `q` dan `status`.


**5. `store` tanpa redirect**

- Masalah : `store` mengembalikan `return view('courses.index', ...)` setelah `Course::create()`.

- Dampak : Menekan F5 setelah menyimpan memunculkan "Confirm Form Resubmission", dan jika dilanjutkan, data tersimpan dua kali.

- Perbaikan : Diganti dengan pola PRG: `return redirect()->route('courses.show', $course)->with('success', 'Mata kuliah berhasil ditambahkan.')`. Pesan sukses ditampilkan lewat flash session di layout, dan F5 tidak lagi menggandakan data.


**6. Satu form tanpa `@csrf`**

- Masalah : Salah satu form (misalnya form edit atau hapus) tidak memiliki `@csrf`.

- Dampak : Bagi pengguna, form selalu gagal dengan error 419 Page Expired. Bagi penyerang, kalau token tidak diwajibkan, situs jahat bisa mengirim POST atas nama korban yang sedang login.

- Perbaikan : Ditambahkan `@csrf` (dan `@method('PUT')` atau `@method('DELETE')` bila perlu) pada form tersebut. Setelah itu form berhasil dikirim, dan request tanpa token ditolak dengan 419.