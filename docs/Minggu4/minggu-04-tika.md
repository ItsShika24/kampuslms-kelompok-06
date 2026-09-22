## Nama : Tika Mila Wahyuni
## NIM  : 10241070

---

# READ — Penelusuran Siklus Form Gagal

Pengujian dilakukan dengan mencoba mengirimkan formulir penambahan mata kuliah dengan data yang sengaja dibuat tidak valid (misalnya nilai SKS diisi 99).

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
Fungsi helper `old('sks')` mengambil nilai input dari flash session data request sebelumnya dengan key `_old_input.sks`. Nilai ini hanya bertahan selama **satu siklus request berikutnya (flash session)**. Begitu halaman hasil redirect selesai di-render atau pengguna berpindah halaman lain, data `_old_input` langsung dibersihkan oleh framework.

### 6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel. Catat namanya.
**Jawaban:**
Nama cookie session aplikasi: `kampuslms_session` (dihasilkan dari konfigurasi `config/session.php` berdasarkan slug nama aplikasi).

---

# BREAK — Tujuh Kerusakan dan Hasil Pengamatan

| # | Skenario Kerusakan | Prediksi & Hasil Pengamatan | Analisis Keamanan & UX |
|---|---------------------|-----------------------------|------------------------|
| **1** | Hapus `@csrf` dari form, lalu submit form | Muncul halaman error **HTTP 419 Page Expired** | Tanpa token CSRF, aplikasi rentan terhadap serangan Cross-Site Request Forgery, di mana situs pihak ketiga yang berbahaya dapat memaksa browser korban mengirimkan aksi POST tanpa disadari. Token `@csrf` menjamin keaslian request berasal dari formulir sistem sendiri. |
| **2** | Ganti `$request->validated()` menjadi `$request->all()`, lalu kirim field liar lewat `curl` | Field liar yang tidak terdaftar di formulir ikut terbaca dan berpotensi disimpan | Celah Mass Assignment kembali terbuka. Penyerang dapat menyuntikkan data sensitif (seperti mengubah foreign key atau flag status). `$request->validated()` menjamin hanya atribut yang lolos aturan validasi Form Request yang boleh diproses. |
| **3** | Hapus validasi `exists:users,id` pada `lecturer_id`, kirim `lecturer_id=99999` | Muncul error unhandled 500 `QueryException` (foreign key constraint fails) atau timbul data yatim jika tanpa constraint DB | Aturan `exists:users,id` mencegah database menerima integritas data yang rusak (orphan record) dan mengubah potensi fatal error 500 di database menjadi umpan balik pesan validasi yang ramah bagi pengguna di frontend. |
| **4** | Hapus validasi `in:...` pada `status`, kirim `status=superadmin` | Nilai liar masuk ke kolom status di database atau ditolak mentah-mentah jika menggunakan native DB enum | Validasi `in:draft,active,archived` mengunci data pada domain nilai yang diizinkan (whitelisting), melindungi aplikasi dari manipulasi logika bisnis di luar alur resmi. |
| **5** | Hapus `->withQueryString()`, lakukan pencarian lalu klik halaman 2 | Parameter pencarian dan filter (`?q=...&status=...`) hilang, halaman 2 menampilkan seluruh data | Bug state klasik pada pagination. Pengguna kehilangan filter pencarian saat menjelajahi halaman selanjutnya. `withQueryString()` memastikan URL query string saat ini ikut dipertahankan di seluruh tautan pagination. |
| **6** | Ganti `return redirect()` menjadi `return view()` pada method `store`, lalu tekan F5 | Browser memunculkan dialog peringatan *"Confirm Form Resubmission"*, dan data baru tersimpan dua kali | Inilah alasan pentingnya pola **PRG (Post-Redirect-Get)**. Dengan melakukan redirect setelah POST, history browser berada pada posisi request GET, sehingga saat pengguna me-refresh halaman (F5), tidak terjadi pengiriman ulang form yang menduplikasi data. |
| **7** | Hapus `old(...)` dari semua input, lalu kirim form dengan satu field salah | Seluruh field yang sebelumnya sudah diisi dengan benar kembali kosong | Pengguna dipaksa mengetik ulang seluruh isian formulir dari awal hanya karena ada satu kesalahan kecil. Hal ini sangat merusak kenyamanan dan kepuasan pengguna (*poor user experience*). |
