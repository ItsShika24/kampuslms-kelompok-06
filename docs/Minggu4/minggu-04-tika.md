## Nama : Tika Mila Wahyuni
## NIM  : 10241070

---

# READ — Penelusuran Siklus Form Gagal

Pengujian dilakukan dengan mencoba mengirimkan formulir penambahan mata kuliah dengan data yang sengaja dibuat tidak valid (misalnya nilai SKS diisi `99`).

### 1. Method apa yang menerima request? Di controller mana?
**Jawaban:**
Request POST formulir diterima oleh method `store(StoreCourseRequest $request)` di dalam `CourseController.php`

### 2. Di titik mana persisnya validasi terjadi — sebelum atau sesudah baris pertama method controller?
**Jawaban:**
Validasi terjadi sebelum baris pertama method controller dijalankan. Karena menggunakan Form Request (`StoreCourseRequest`), Laravel menjalankan proses validasi sebelum method `store()` dieksekusi. Jika validasi gagal, Laravel melempar `ValidationException` dan mengarahkan kembali ke halaman form, sehingga kode di dalam method `store()` tidak dijalankan.

### 3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?
**Jawaban:**
Laravel me-redirect pengguna kembali ke halaman form sebelumnya (`courses/create`). Yang menentukan tujuannya adalah mekanisme bawaan Laravel melalui URL referer HTTP request (`$request->headers->get('referer')`) atau history URL yang dicatat di session `url.intended` / `_previous.url`.

### 4. Dari mana `@error('sks')` mengambil pesannya?
**Jawaban:**
`@error('sks')` mengambil pesan error validasi untuk field sks dari error bag yang dibuat Laravel ketika validasi gagal.

### 5. Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai itu bertahan?
**Jawaban:**
Fungsi helper `old('sks')` mengambil nilai input dari flash session data request sebelumnya dengan key `_old_input.sks`. Nilai ini hanya bertahan selama **satu siklus request berikutnya (flash session)**. Begitu halaman hasil redirect selesai di render atau pengguna berpindah halaman lain, data `_old_input` langsung dibersihkan oleh framework.

### 6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel. Catat namanya.
**Jawaban:**
Nama cookie session aplikasi: `kampuslms_session` (dihasilkan dari konfigurasi `config/session.php` berdasarkan slug nama aplikasi).

---

# BREAK — Tujuh Kerusakan dan Hasil Pengamatan

| # | Skenario Kerusakan | Prediksi & Hasil Pengamatan | Analisis Keamanan & UX |
|---|---------------------|-----------------------------|------------------------|
| **1** | Hapus `@csrf` dari form, lalu submit form | Muncul halaman error **HTTP 419 Page Expired** | Tanpa token CSRF, aplikasi rentan terhadap serangan Cross-Site Request Forgery, di mana situs pihak ketiga yang berbahaya dapat memaksa browser korban mengirimkan aksi POST tanpa disadari. Token `@csrf` menjamin keaslian request berasal dari formulir sistem sendiri. |
| **2** | Ganti `$request->validated()` menjadi `$request->all()`, lalu kirim field liar lewat `curl` | Field liar yang tidak terdaftar di formulir ikut terbaca dan berpotensi disimpan | Celah Mass Assignment kembali terbuka. Penyerang dapat menyuntikkan data sensitif (seperti mengubah foreign key atau flag status). `$request->validated()` menjamin hanya atribut yang lolos aturan validasi Form Request yang boleh diproses. |
| **3** | Hapus validasi `exists:users,id` pada `lecturer_id`, kirim `lecturer_id=99999` | Muncul error unhandled 500 `QueryException`| Aturan `exists:users,id` mencegah database menerima integritas data yang rusak (orphan record) dan mengubah potensi fatal error 500 di database menjadi umpan balik pesan validasi yang ramah bagi pengguna di frontend. |
| **4** | Hapus validasi `in:...` pada `status`, kirim `status=superadmin` | Nilai liar masuk ke kolom status di database| Validasi `in:draft,active,archived` mengunci data pada domain nilai yang diizinkan (whitelisting), melindungi aplikasi dari manipulasi logika bisnis di luar alur resmi. |
| **5** | Hapus `->withQueryString()`, lakukan pencarian lalu klik halaman 2 | Parameter pencarian dan filter (`?q=...&status=...`) hilang, halaman 2 menampilkan seluruh data | Bug state klasik pada pagination. Pengguna kehilangan filter pencarian saat menjelajahi halaman selanjutnya. `withQueryString()` memastikan URL query string saat ini ikut dipertahankan di seluruh tautan pagination. |
| **6** | Ganti `return redirect()` menjadi `return view()` pada method `store`, lalu tekan F5 | Browser memunculkan dialog peringatan *"Confirm Form Resubmission"*, dan data baru tersimpan dua kali | Inilah alasan pentingnya pola **PRG (Post-Redirect-Get)**. Dengan melakukan redirect setelah POST, history browser berada pada posisi request GET, sehingga saat pengguna me-refresh halaman (F5), tidak terjadi pengiriman ulang form yang menduplikasi data. |
| **7** | Hapus `old(...)` dari semua input, lalu kirim form dengan satu field salah | Seluruh field yang sebelumnya sudah diisi dengan benar kembali kosong | Pengguna dipaksa mengetik ulang seluruh isian formulir dari awal hanya karena ada satu kesalahan kecil. Hal ini sangat merusak kenyamanan dan kepuasan pengguna (*poor user experience*). |


# FIX - w04

### 1. Validasi Hanya di Frontend (StoreCourseRequest)
- **Lokasi:** `app/Http/Requests/StoreCourseRequest.php`
- **Dampak Sudut Pandang Pengguna:** Jika ID dosen tidak ada di DB, data tetap tersimpan sehingga saat halaman detail dibuka aplikasi crash (null pointer error pada relasi dosen).
- **Dampak Sudut Pandang Penyerang:** Penyerang dapat mem-bypass browser via curl/Postman untuk menyuntikkan ID dosen fiktif (`lecturer_id=99999`) atau status liar (`status=superadmin`), merusak integritas database.
- **Perbaikan:** Menambahkan `'exists:users,id'` pada `lecturer_id` dan `'in:draft,active,archived'` pada `status`.
### 2. Unique pada Update Menolak Dirinya Sendiri (UpdateCourseRequest)
- **Lokasi:** `app/Http/Requests/UpdateCourseRequest.php`
- **Dampak Sudut Pandang Pengguna:** Pengguna tidak bisa mengedit data MK jika tidak mengubah kodenya karena form selalu error "Kode mata kuliah ini sudah dipakai".
- **Dampak Sudut Pandang Penyerang:** Menjadi Denial-of-Service fungsional bagi operator dan berpotensi memaksa operator merusak konsistensi kode kurikulum resmi agar form bisa disimpan.
- **Perbaikan:** Menggunakan `Rule::unique('courses', 'code')->ignore($this->course)` agar baris mata kuliah yang sedang diedit dikecualikan dari pengecekan unik.
### 3. Filter Disimpan di Session (CourseController@index)
- **Lokasi:** `app/Http/Controllers/CourseController.php`
- **Dampak Sudut Pandang Pengguna:** Pengguna tidak bisa membagikan/bookmark URL pencarian (URL tetap polos), dua tab browser saling menimpa filter pencarian, dan tombol Back tidak bekerja semestinya.
- **Dampak Sudut Pandang Penyerang:** Memungkinkan session pollution/tampering untuk mendistorsi tampilan filter pengguna secara diam-diam dan memboroskan memori session server.
- **Perbaikan:** Mengambil parameter pencarian dan status langsung dari `$request->query(...)` tanpa menyimpannya ke session.
### 4. Pagination Kehilangan Query String (CourseController@index)
- **Lokasi:** `app/Http/Controllers/CourseController.php`
- **Dampak Sudut Pandang Pengguna:** Saat berpindah ke halaman 2 pencarian, parameter filter hilang sehingga halaman 2 menampilkan seluruh data umum dan membingungkan pengguna.
- **Dampak Sudut Pandang Penyerang:** Mengaburkan hasil audit data dan navigasi record, membuat pengguna rentan melewatkan data penting.
- **Perbaikan:** Menambahkan `->withQueryString()` pada pemanggilan pagination.
### 5. Store Tanpa Redirect / Pelanggaran Pola PRG (CourseController@store)
- **Lokasi:** `app/Http/Controllers/CourseController.php`
- **Dampak Sudut Pandang Pengguna:** Setelah simpan, menekan F5/Refresh memunculkan dialog form resubmission yang jika ditekan OK akan menduplikasi data mata kuliah yang sama.
- **Dampak Sudut Pandang Penyerang:** Penyerang dapat melakukan form resubmission flooding untuk membuat duplikat record secara massal hanya dengan me-refresh browser.
- **Perbaikan:** Mengembalikan `redirect()->route('courses.index')->with('success', ...)` sesuai standar pola Post-Redirect-Get (PRG).
### 6. Form Edit Tanpa @csrf (edit.blade.php)
- **Lokasi:** `resources/views/courses/edit.blade.php`
- **Dampak Sudut Pandang Pengguna:** Pengguna sah yang menekan tombol submit edit akan langsung menerima error HTTP 419 Page Expired sehingga tidak dapat memperbarui data.
- **Dampak Sudut Pandang Penyerang:** Jika proteksi CSRF diabaikan, penyerang dapat membuat situs phising/jebakan untuk memalsukan request PUT atas nama admin/dosen yang sedang login guna mengubah data MK tanpa izin.
- **Perbaikan:** Menambahkan direktif `@csrf` di dalam tag `<form>` edit.
