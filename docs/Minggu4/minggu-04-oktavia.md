## Oktavia Nur Rahmadani
### NIM 10241060
### Pemrograman Web - Laporan Minggu 4

### READ 
**1. Method apa yang menerima request? Di controller mana?**

**Jawaban:** Method yang menerima request adalah `store()` pada `CourseController`, yaitu `app/Http/Controllers/CourseController.php`. Method tersebut menerima `StoreCourseRequest $request` sebagai parameter untuk menangani penyimpanan data mata kuliah.

**2. Di titik mana persisnya validasi terjadi — sebelum atau sesudah baris pertama method controller?**

**Jawaban:** Validasi terjadi sebelum baris pertama method `store()` dijalankan. Hal ini karena `StoreCourseRequest` merupakan Form Request Laravel yang menjalankan validasi sebelum request diteruskan ke method controller. Aturan validasinya didefinisikan dalam method `rules()` pada `StoreCourseRequest.php.`

**3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?**

**Jawaban:** Jika validasi gagal, Laravel secara otomatis mengarahkan pengguna kembali ke halaman sebelumnya, biasanya halaman formulir tambah mata kuliah. Tujuan pengalihan ditentukan oleh mekanisme validasi Form Request Laravel, dengan tujuan bawaan berupa URL sebelumnya. Tujuan tersebut dapat disesuaikan melalui konfigurasi redirect pada Form Request.

**4. Dari mana @error('sks') mengambil pesannya?** 

**Jawaban:** `@error('sks')` mengambil pesan kesalahan dari kumpulan pesan validasi Laravel, yaitu `$errors`, yang tersedia pada tampilan Blade melalui session. Pesan tersebut berasal dari aturan validasi atribut sks yang gagal dipenuhi.

**5. Dari mana old('sks') mengambil nilainya? Berapa lama nilai itu bertahan?**

**Jawaban:** `old('sks')` mengambil nilai sks yang sebelumnya dikirim pengguna dan disimpan sementara sebagai flashed input di session ketika validasi gagal. Nilai tersebut tersedia untuk request berikutnya setelah pengalihan, sehingga pengguna tidak perlu mengisi ulang formulir dari awal. Nilai lama ini bersifat sementara, bukan penyimpanan permanen.

**6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel. Catat namanya.**

**Jawaban:** Nama cookie session aplikasi: `kampuslms_session` (dihasilkan dari `konfigurasi config/session.php` berdasarkan slug nama aplikasi).

### BREAK
| # | Skenario Kerusakan                                                                                        | Prediksi dan Hasil Pengamatan                                                                                                                                                           | Analisis Keamanan dan UX                                                                                                                                                                                                                                                           |
| - | --------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1 | Menghapus `@csrf` dari form, lalu mengirim formulir.                                                      | Muncul halaman error HTTP 419 Page Expired.                                                                                                                                             | Tanpa token CSRF, aplikasi lebih rentan terhadap serangan Cross-Site Request Forgery (CSRF), yaitu ketika situs berbahaya mencoba membuat browser korban mengirimkan request tanpa sepengetahuannya. Token `@csrf` membantu memastikan request memiliki token keamanan yang valid. |
| 2 | Mengganti `$request->validated()` menjadi `$request->all()`, lalu mengirim field tambahan melalui `curl`. | Field di luar formulir ikut terbaca dan berpotensi diproses atau disimpan, bergantung pada perlindungan model.                                                                          | Risiko Mass Assignment meningkat karena seluruh data request diteruskan ke proses penyimpanan. Penggunaan `$request->validated()` membatasi data pada atribut yang lolos validasi sehingga mengurangi risiko manipulasi atribut yang tidak diizinkan.                              |
| 3 | Menghapus validasi `exists:users,id` pada `lecturer_id`, lalu mengirim `lecturer_id=99999`.               | Validasi Laravel tidak lagi menolak ID tersebut. Jika foreign key database tetap aktif, penyimpanan dapat gagal dan menghasilkan error database.                                        | Aturan `exists:users,id` memastikan dosen yang dipilih benar-benar terdaftar. Validasi ini mencegah data dengan referensi dosen yang tidak valid sekaligus memberikan pesan kesalahan yang lebih mudah dipahami daripada error database.                                           |
| 4 | Menghapus validasi `in:...` pada `status`, lalu mengirim `status=superadmin`.                             | Nilai tidak valid dapat melewati validasi Laravel, tetapi penyimpanannya bergantung pada constraint database.                                                                           | Validasi `in:draft,active,archived` membatasi status pada nilai yang diizinkan. Pembatasan ini membantu mencegah manipulasi status yang dapat mengganggu logika bisnis aplikasi.                                                                                                   |
| 5 | Menghapus `->withQueryString()`, melakukan pencarian, lalu membuka halaman 2.                             | Parameter pencarian dan filter seperti `q` dan `status` tidak lagi dipertahankan pada tautan pagination.                                                                                | Pengguna kehilangan filter yang sedang digunakan sehingga hasil pencarian dapat berubah ketika berpindah halaman. `withQueryString()` mempertahankan parameter URL agar proses pagination tetap konsisten.                                                                         |
| 6 | Mengganti `return redirect()` menjadi `return view()` pada method `store`, lalu menekan F5.               | Browser dapat menampilkan peringatan Confirm Form Resubmission dan mengirim ulang formulir. Data berpotensi tersimpan kembali jika tidak ada validasi atau constraint yang mencegahnya. | Pola Post-Redirect-Get (PRG) mengarahkan pengguna ke request GET setelah proses POST berhasil. Dengan demikian, saat halaman dimuat ulang, browser tidak langsung mengulangi request penyimpanan sehingga risiko data ganda berkurang.                                             |
| 7 | Menghapus `old(...)` dari semua input, lalu mengirim formulir dengan satu field yang salah.               | Input yang sebelumnya sudah diisi dapat kembali kosong setelah validasi gagal dan halaman dimuat ulang.                                                                                 | Pengguna harus mengetik ulang data yang sudah benar hanya karena satu kesalahan. Fungsi `old(...)` membantu mempertahankan input sebelumnya sehingga formulir lebih nyaman digunakan dan pengalaman pengguna tetap baik.                                                           |

### FIX

**1. Validasi Hanya di Frontend (`StoreCourseRequest`)**
- Lokasi: `app/Http/Requests/StoreCourseRequest.php`
- Dampak bagi Pengguna: Data dengan ID dosen yang tidak terdaftar atau status yang tidak sesuai dapat menimbulkan masalah saat data ditampilkan atau diproses oleh aplikasi.
- Dampak bagi Penyerang: Request dapat dikirim melalui `curl` atau Postman tanpa mengikuti validasi browser, misalnya dengan memasukkan `lecturer_id=99999` atau `status=superadmin`. Hal ini berisiko merusak integritas data.
- Perbaikan: Tambahkan aturan `'exists:users,id'` untuk `lecturer_id` dan `'in:draft,active,archived'` untuk `status` agar server hanya menerima nilai yang valid.

**2. Validasi `unique` pada Update Menolak Kode Milik Sendiri (`UpdateCourseRequest`)**

- Lokasi: `app/Http/Requests/UpdateCourseRequest.php`
- Dampak bagi Pengguna: Pengguna tidak dapat menyimpan perubahan pada mata kuliah jika kodenya tidak diubah karena dianggap sudah digunakan.
- Dampak bagi Penyerang: Kesalahan ini dapat menghambat pekerjaan operator dan mengganggu pengelolaan data mata kuliah karena pembaruan sederhana selalu gagal.
- Perbaikan: Gunakan `Rule::unique('courses', 'code')->ignore($this->course)` agar kode milik mata kuliah yang sedang diedit tidak dianggap sebagai duplikasi, tetapi kode milik mata kuliah lain tetap ditolak.

**3. Filter Pencarian Disimpan dalam Session (`CourseController@index`)**

- Lokasi: `app/Http/Controllers/CourseController.php`
- Dampak bagi Pengguna: URL pencarian tidak dapat dibagikan atau disimpan sebagai bookmark. Filter pada satu tab juga dapat memengaruhi tab lain, sedangkan tombol Back bisa memberikan hasil yang tidak sesuai harapan.
- Dampak bagi Penyerang: Pengelolaan filter melalui session berpotensi menimbulkan kondisi pencarian yang tidak konsisten dan membingungkan pengguna. Namun, perubahan filter di session saja tidak otomatis memberikan akses ke data yang tidak diizinkan.
- Perbaikan: Ambil kata pencarian dan status langsung dari `$request->query(...)` sehingga filter dikelola melalui parameter URL, bukan disimpan dalam session.

**4. Pagination Tidak Mempertahankan Query String (`CourseController@index`)**

- Lokasi: `app/Http/Controllers/CourseController.php`
- Dampak bagi Pengguna: Ketika berpindah ke halaman berikutnya, parameter pencarian dan filter hilang sehingga hasil yang ditampilkan tidak lagi sesuai dengan pencarian sebelumnya.
- Dampak bagi Penyerang: Ketidakkonsistenan hasil dapat menyulitkan pemeriksaan dan penelusuran data, sehingga pengguna berisiko melewatkan informasi yang relevan.
- Perbaikan: Tambahkan `->withQueryString()` setelah `paginate()` agar parameter pencarian dan filter tetap terbawa saat berpindah halaman.

**5. Method `store` Tidak Melakukan Redirect (Pola PRG)**

- Lokasi: `app/Http/Controllers/CourseController.php`, method `store()`
- Dampak bagi Pengguna: Setelah data disimpan, menekan F5 dapat memunculkan peringatan pengiriman ulang formulir. Jika dikonfirmasi, request penyimpanan dapat dikirim kembali.
- Dampak bagi Penyerang: Request berulang dapat membebani aplikasi dan berpotensi menghasilkan data duplikat apabila tidak ada validasi atau constraint yang mencegahnya.
- Perbaikan: Setelah penyimpanan berhasil, arahkan pengguna menggunakan `redirect()->route('courses.index')->with('success', ...)`. Pola Post-Redirect-Get (PRG) mencegah refresh halaman langsung mengulangi request POST.

**6. Form Edit Tidak Memiliki `@csrf` (`edit.blade.php`)**

- Lokasi: `resources/views/courses/edit.blade.php`
- Dampak bagi Pengguna: Saat formulir edit dikirim tanpa token CSRF, Laravel dapat menolak request dan menampilkan error HTTP 419 Page Expired, sehingga perubahan tidak tersimpan.
- Dampak bagi Penyerang: Tanpa perlindungan CSRF yang memadai, penyerang dapat mencoba memanfaatkan sesi admin atau dosen yang sedang login untuk mengirim perubahan data tanpa sepengetahuan pemilik sesi.
- Perbaikan: Tambahkan direktif `@csrf` di dalam tag `<form>` pada halaman edit agar token keamanan disertakan dalam request.