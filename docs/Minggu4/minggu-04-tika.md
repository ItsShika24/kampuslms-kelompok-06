## Nama : Tika Mila Wahyuni
## NIM  : 10241070

---

# READ — Penelusuran Siklus Form Gagal

Pengujian dilakukan dengan mencoba mengirimkan formulir penambahan mata kuliah dengan data yang sengaja dibuat tidak valid (misalnya nilai SKS diisi `99` atau dikosongkan).

### 1. Method apa yang menerima request? Di controller mana?
**Jawaban:**
Request POST formulir diterima oleh method `store(StoreCourseRequest $request)` di dalam [CourseController.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/CourseController.php).

### 2. Di titik mana persisnya validasi terjadi — sebelum atau sesudah baris pertama method controller?
**Jawaban:**
Validasi terjadi **sebelum** baris pertama method controller dijalankan. Karena kita menggunakan Form Request (`StoreCourseRequest`), Laravel secara otomatis mengeksekusi validasi melalui lifecycle pipeline service container sebelum parameter method di-resolve. Jika validasi gagal, Laravel langsung melempar `Illuminate\Validation\ValidationException` dan memicu redirect response, sehingga kode di dalam badan method `store()` sama sekali tidak pernah disentuh.

### 3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?
**Jawaban:**
Laravel me-redirect pengguna kembali ke halaman form sebelumnya (`courses/create`). Yang menentukan tujuannya adalah mekanisme bawaan Laravel melalui URL referer HTTP request (`$request->headers->get('referer')`) atau history URL yang dicatat di session `url.intended` / `_previous.url`.

### 4. Dari mana `@error('sks')` mengambil pesannya?
**Jawaban:**
Directive `@error('sks')` mengambil pesan validasi dari objek session message bag (`$errors` / `ViewErrorBag`) yang secara otomatis disimpan di flash session saat validasi gagal, kemudian disuntikkan ke seluruh view Blade oleh middleware `Illuminate\View\Middleware\ShareErrorsFromSession`.

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

---

# BUILD — Implementasi Fitur Layak Pakai

Berikut adalah implementasi teknis yang telah diselesaikan di repositori:

1. **Form Request Terpisah**:
   - `app/Http/Requests/StoreCourseRequest.php`: Validasi penambahan mata kuliah baru lengkap dengan pesan berbahasa Indonesia yang jelas.
   - `app/Http/Requests/UpdateCourseRequest.php`: Validasi pembaruan mata kuliah dengan penanganan keunikan kode menggunakan `Rule::unique('courses', 'code')->ignore($courseId)` agar tidak menolak kode milik dirinya sendiri saat edit.
   - `app/Http/Requests/StoreUserRequest.php` & `app/Http/Requests/UpdateUserRequest.php`: Validasi modul pengguna dengan aturan email, NIM/NIP unik, role valid, serta password minimal 8 karakter.
2. **Penerapan Pola PRG & `$request->validated()`**:
   - Refactor `CourseController` dan `UserController` pada method `store` dan `update` agar hanya menggunakan `$request->validated()` dan merespons dengan `redirect()->route(...)->with('success', ...)`.
3. **Global Flash Messages di Layout**:
   - Menambahkan banner notifikasi sukses dan error terpusat di [layout.blade.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/resources/views/components/layout.blade.php) menggunakan styling Tailwind CSS yang serasi dan dilengkapi tombol tutup (Alpine.js).
4. **Pencarian, Filter, dan Pagination dengan State Terjaga**:
   - Daftar Mata Kuliah ([courses/index.blade.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/resources/views/courses/index.blade.php)) dilengkapi form pencarian kata kunci (`q`), dropdown filter status (`draft`, `active`, `archived`), tombol reset filter, dan pagination 15 item per halaman yang menggunakan `->withQueryString()`.
   - Daftar Pengguna ([users/index.blade.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/resources/views/users/index.blade.php)) dilengkapi form pencarian nama/email/NIM, filter role, dan pagination dengan query string.
5. **Konfirmasi Penghapusan Aman**:
   - Penghapusan data mata kuliah dan pengguna wajib menggunakan method HTTP `DELETE` dengan perlindungan `@csrf` dan konfirmasi sebelum dieksekusi.

---

# CHECKPOINT — Pemahaman Konsep Minggu 4

### 1. Kenapa validasi di JavaScript tidak dianggap keamanan? Peragakan cara melewatinya.
**Jawaban:**
JavaScript berjalan sepenuhnya di browser client (lingkungan yang dikendalikan pengguna). Pengguna dapat menonaktifkan JavaScript, memodifikasi script lewat Developer Tools Console, menghapus atribut `required`/`maxlength` di HTML Elements, atau mengirimkan request langsung ke endpoint server menggunakan tool seperti `curl` atau Postman:
```bash
curl -X POST http://127.0.0.1:8000/mata-kuliah \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "code=IF9999&name=Hacker&sks=999"
```
Karena itu, validasi frontend hanyalah kenyamanan UX, sedangkan pertahanan keamanan yang mutlak harus berada di backend (server-side).

### 2. Apa yang dikembalikan `$request->validated()` dan kenapa lebih aman daripada `$request->all()`?
**Jawaban:**
`$request->validated()` mengembalikan array yang **hanya berisi pasangan key-value yang telah didefinisikan dalam aturan `rules()` dan lolos pengujian validasi**. Field liar atau parameter tambahan yang dikirim penyerang otomatis dibuang. Sebaliknya, `$request->all()` mengembalikan seluruh data mentah dari payload request, sehingga sangat rentan memicu celah mass assignment jika dimasukkan langsung ke `Model::create()` atau `Model::update()`.

### 3. Jelaskan pola PRG. Apa yang terjadi kalau `store` mengembalikan view?
**Jawaban:**
Pola PRG (*Post → Redirect → Get*) adalah konvensi arsitektur web di mana setiap request non-idempotent (`POST`/`PUT`/`DELETE`) yang berhasil diproses harus diakhiri dengan response redirect (HTTP 302/303) ke URL tujuan yang diakses menggunakan method `GET`.
Jika `store` langsung mengembalikan `return view()`, URL di browser tetap berada pada posisi POST. Apabila pengguna menekan tombol reload/F5 atau tombol back-forward, browser akan mengirim ulang seluruh data formulir dan memicu duplikasi data di database serta menampilkan peringatan "Confirm Form Resubmission".

### 4. Kenapa filter pencarian sebaiknya di query string, bukan session? Beri satu skenario yang rusak kalau dipindah ke session.
**Jawaban:**
Filter dan pencarian adalah representasi state tampilan data saat itu. Jika disimpan di **Query String** (`?q=algoritma&status=active`):
- URL bersifat shareable (bisa di-bookmark atau dibagikan ke orang lain).
- Tombol *Back* dan *Forward* browser bekerja secara wajar.
- Mendukung multi-tab tanpa saling bentrok.

**Skenario yang rusak jika disimpan di Session:**
Seorang admin membuka Tab 1 untuk mencari mata kuliah "Basis Data". Kemudian admin membuka Tab 2 di browser yang sama untuk mencari mata kuliah "Pemrograman Web". Karena session bersifat global per sesi browser, filter "Pemrograman Web" dari Tab 2 akan menimpa filter di session. Saat admin kembali ke Tab 1 dan berpindah ke halaman 2, data yang muncul di Tab 1 tiba-tiba berubah menjadi mata kuliah "Pemrograman Web". Hal ini sangat membingungkan dan merusak konsistensi kerja pengguna.

### 5. Apa fungsi `@csrf`? Serangan apa yang dicegahnya, dan bagaimana serangan itu bekerja?
**Jawaban:**
Directive `@csrf` menyisipkan hidden input berisi token kriptografis unik yang dibuat untuk sesi pengguna saat ini. Directive ini mencegah serangan **CSRF (Cross-Site Request Forgery)**.
Pada serangan CSRF, seorang penyerang memancing korban (yang sedang aktif login di LMS) untuk mengunjungi situs jebakan milik penyerang. Di situs jebakan tersebut terdapat script atau form tersembunyi yang otomatis mengirimkan POST request ke `http://kampuslms.test/mata-kuliah/1` dengan method `DELETE`. Karena korban memiliki cookie session aktif, browser akan menyertakan cookie tersebut secara otomatis. Tanpa verifikasi token CSRF, server akan mengira permintaan penghapusan tersebut sah dari korban dan mengeksekusinya. Token CSRF mencegah hal ini karena situs pihak ketiga tidak bisa membaca token rahasia yang hanya diketahui oleh domain asli aplikasi.

### 6. Kenapa aturan `unique` pada update perlu `ignore()`?
**Jawaban:**
Saat memperbarui record (misalnya mengedit deskripsi atau nama mata kuliah tanpa mengubah kodenya), aturan validasi `unique:courses,code` secara default akan memeriksa apakah kode tersebut sudah ada di tabel `courses`. Database akan menemukan kode tersebut (yang sebenarnya milik record yang sedang diedit itu sendiri) dan menganggapnya sebagai duplikasi, sehingga proses update gagal. Dengan menambahkan `Rule::unique('courses', 'code')->ignore($courseId)`, Laravel diperintahkan untuk mengabaikan baris dengan ID record yang sedang diubah dalam pemeriksaan keunikan.
