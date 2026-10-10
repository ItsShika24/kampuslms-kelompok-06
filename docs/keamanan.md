# DOKUMENTASI KEAMANAN & MITIGASI IDOR
## Proyek: KampusLMS (EduKampus) — Laravel 12
**Mata Kuliah:** SI2514024 Pemrograman Web  
**Milestone:** M2 (Tugas 2 — Minggu 7)  
**Kelompok:** Kelompok 06  

---

## 1. Konsep & Arsitektur Keamanan Aplikasi

Aplikasi web modern membutuhkan pemisahan tegas antara siapa yang menggunakan sistem dan apa yang diizinkan untuk dilakukan. Sebagian besar kerentanan pada aplikasi kampus berasal dari penggabungan atau salah paham mengenai kedua konsep ini.

### 1.1 Autentikasi vs Otorisasi

| Aspek | Autentikasi (*Authentication*) | Otorisasi (*Authorization*) |
|---|---|---|
| **Definisi** | Proses memverifikasi **siapa Anda** (*identitas* pengguna). | Proses memverifikasi **apa yang boleh Anda lakukan** (*hak akses* objek & aksi). |
| **Implementasi** | Form Login, Session Cookie, Token Sanctum, `Auth::attempt()`. | Laravel Policy (`CoursePolicy`, `SubmissionPolicy`), Gate, Middleware. |
| **Respon Gagal** | HTTP `401 Unauthorized` atau redirect ke halaman login. | HTTP `403 Forbidden` (*Akses Ditolak*). |
| **Lokasi Kode** | [`AuthController.php`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/AuthController.php) | [`app/Policies/`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies) & Controller |

> **Prinsip Utama:** Aplikasi yang memiliki login sempurna tetapi otorisasinya rapuh jauh lebih berbahaya daripada aplikasi tanpa login, karena memberikan rasa aman palsu.

---

### 1.2 Bahaya Anggapan `@can` di Blade adalah Pengaman

Banyak pengembang pemula mengira bahwa membungkus tombol di file Blade menggunakan `@can` sudah cukup untuk mengamankan fitur:

```blade
{{-- HANYA KENYAMANAN PENGGUNA (UX) --}}
@can('update', $course)
    <a href="{{ route('mata-kuliah.edit', $course->id) }}">Edit MK</a>
@endcan
```

* **Mengapa `@can` BUKAN pengaman?**  
  Directive `@can` di Blade **hanya menyembunyikan elemen visual (tombol/link)** dari peramban (*browser*). Jika controller tidak memvalidasi hak akses, penyerang cukup membuka DevTools, mengetik langsung URL `/mata-kuliah/1/edit`, atau mengirim perintah `curl -X PUT http://.../mata-kuliah/1` untuk memanipulasi data.
* **Pertahanan yang sesungguhnya:**  
  Setiap method di Controller wajib memanggil `Gate::authorize()` atau Form Request:
  ```php
  public function update(UpdateCourseRequest $request, Course $course)
  {
      Gate::authorize('update', $course); // Penegak keamanan server-side sesungguhnya
      $course->update($request->validated());
  }
  ```

---

### 1.3 Hashing Kata Sandi vs Enkripsi

Kata sandi pada model [`User.php`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Models/User.php) di-hash secara otomatis menggunakan *cast* bawaan Laravel:

```php
protected function casts(): array
{
    return [
        'password' => 'hashed',
    ];
}
```

* **Mengapa di-hash, bukan dienkripsi?**  
  * **Enkripsi** bersifat dua arah (*reversible*): teks sandi dapat dikembalikan ke teks asli jika memiliki kunci dekripsi. Jika server atau basis data bocor beserta kuncinya, semua kata sandi pengguna langsung terbongkar.
  * **Hash (Bcrypt/Argon2)** bersifat satu arah (*irreversible mathematical function*): tidak ada rumus matematika untuk membalikkan nilai hash kembali ke teks asli.
* **Konsekuensi untuk Fitur Lupa Kata Sandi:**  
  Sistem **tidak mungkin** mengirimkan kata sandi lama kepada pengguna karena server sendiri tidak mengetahuinya. Fitur reset sandi wajib mengirimkan tautan token verifikasi sekali pakai agar pengguna memasukkan kata sandi baru.

---

### 1.4 Proteksi Session Fixation

Saat pengguna berhasil login di [`AuthController.php`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/AuthController.php), aplikasi wajib memanggil:

```php
$request->session()->regenerate();
```

* **Bahaya jika dihilangkan:**  
  Penyerang dapat menanamkan ID session tertentu pada peramban korban (misal lewat link phishing). Jika ID session tidak diregenerasi setelah login berhasil, ID session yang sama akan naik tingkat menjadi session terotentikasi, sehingga penyerang otomatis memiliki akses penuh ke akun korban tanpa perlu mengetahui kata sandinya.
* **Saat Logout:**
  ```php
  Auth::logout();
  $request->session()->invalidate();
  $request->session()->regenerateToken();
  ```

---

## 2. Tabel Titik Rawan IDOR & Mekanisme Penutupannya

**IDOR (*Insecure Direct Object Reference*)** adalah kerentanan di mana pengguna dapat mengakses atau memanipulasi objek database milik orang lain hanya dengan mengganti parameter identitas (seperti ID) di URL atau request body.

Berikut adalah tabel matriks seluruh titik rawan di KampusLMS dan pembuktian mekanisme penutupannya:

| No | Titik Rawan / Endpoint | Skenario Celah IDOR | Bahaya Keamanan | Mekanisme Penutupan | File & Lokasi Kode | Status Uji |
|:---:|---|---|---|---|---|:---:|
| **1** | **Detail Pengumpulan**<br>`GET /submissions/{submission}` | Mahasiswa A membuka tugasnya di ID `41`, lalu mengganti angka di URL menjadi `42`, `43`, dst. | Mahasiswa dapat mengintip jawaban, file, dan nilai seluruh angkatan. | **Scoped Authorization di Policy:**<br>`$submission->user_id === $user->id`<br>atau Dosen pengampu MK terkait / Admin. | [`SubmissionPolicy.php:26-38`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies/SubmissionPolicy.php#L26-L38)<br>Dipanggil di [`SubmissionController.php:61`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/SubmissionController.php#L61) | **PASS (403)** |
| **2** | **Penilaian Tugas**<br>`POST /submissions/{submission}/grade` | Mahasiswa mengirimkan request POST penilaian ke submission milik temannya atau dirinya sendiri. | Mahasiswa dapat mengubah nilainya sendiri menjadi 100. | **Policy Restriction:**<br>Hanya Admin dan Dosen pengampu dari mata kuliah submission tersebut yang diizinkan. | [`SubmissionPolicy.php:57-69`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies/SubmissionPolicy.php#L57-L69)<br>Dipanggil di [`SubmissionController.php:77`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/SubmissionController.php#L77) | **PASS (403)** |
| **3** | **Edit Mata Kuliah**<br>`GET /mata-kuliah/{course}/edit`<br>`PUT /mata-kuliah/{course}` | Dosen A (pengampu SI101) mengganti URL ID untuk mengedit mata kuliah SI201 milik Dosen B. | Dosen dapat mengubah deskripsi, SKS, atau silabus mata kuliah dosen lain. | **CoursePolicy Ownership Check:**<br>`$user->role === 'admin' \|\| $course->lecturer_id === $user->id` | [`CoursePolicy.php:48-51`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies/CoursePolicy.php#L48-L51)<br>Dipanggil di [`CourseController.php:124,138`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/CourseController.php#L124) | **PASS (403)** |
| **4** | **Hapus Mata Kuliah**<br>`DELETE /mata-kuliah/{course}` | Dosen mengirim request DELETE ke salah satu mata kuliah. | Terhapusnya mata kuliah dan relasi tugas secara sepihak. | **Strict Admin Authorization:**<br>Hanya pengguna dengan `role === 'admin'` yang diizinkan menghapus. | [`CoursePolicy.php:59-62`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies/CoursePolicy.php#L59-L62)<br>Dipanggil di [`CourseController.php:151`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/CourseController.php#L151) | **PASS (403)** |
| **5** | **Akses Tugas Berstatus Draft**<br>`GET /tugas/{assignment}` | Mahasiswa menebak ID tugas masa depan yang masih berstatus `draft`. | Mahasiswa mencuri soal tugas/ujian sebelum waktu rilis resmi. | **Status Filtering pada Policy:**<br>Mahasiswa ditolak jika `$assignment->status === 'draft'`. | [`AssignmentPolicy.php:32-39`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies/AssignmentPolicy.php#L32-L39)<br>Dipanggil di [`AssignmentController.php:112`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/AssignmentController.php#L112) | **PASS (403)** |
| **6** | **Pengumpulan Tugas Liar**<br>`POST /tugas/{assignment}/submit` | Mahasiswa yang tidak mengambil MK mencoba mengirim submission ke tugas MK tersebut. | Terjadinya data yatim dan polusi database akademik. | **Enrollment Verification:**<br>Memeriksa bahwa mahasiswa terdaftar aktif pada MK tugas bersangkutan. | [`AssignmentPolicy.php:79-89`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies/AssignmentPolicy.php#L79-L89)<br>Dipanggil di [`AssignmentController.php:143`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/AssignmentController.php#L143) | **PASS (403)** |
| **7** | **Unduh & Kelola Materi**<br>`GET /materi/{material}/download`<br>`DELETE /materi/{material}` | Mahasiswa dari luar kelas mengunduh materi berbayar/internal, atau mencoba menghapusnya. | Kebocoran hak cipta materi pembelajaran dan vandalisme data. | **MaterialPolicy Access Control:**<br>Unduh hanya jika terdaftar di MK. Hapus hanya jika Admin atau Dosen pengampu. | [`MaterialPolicy.php:33-40,65-72`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies/MaterialPolicy.php#L33-L40)<br>Dipanggil di [`MaterialController.php:104,138`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/MaterialController.php#L104) | **PASS (403)** |
| **8** | **Kebocoran Tingkat Daftar**<br>`GET /submissions`<br>`GET /mata-kuliah` | Mahasiswa memanggil endpoint daftar tanpa filter dan melihat data seluruh angkatan. | Pelanggaran privasi akademik skala massal (*Mass Data Scraping*). | **Query-Level Scoping:**<br>Data disaring langsung di query SQL berdasarkan peran (`$user->courses()`, dsb). | [`CourseController.php:33-46`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/CourseController.php#L33-L46)<br>[`SubmissionController.php:33-37`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/SubmissionController.php#L33-L37) | **PASS (200)** |
| **9** | **Eskalasi Hak Akses Profil**<br>`PUT /pengguna/{user}` | Pengguna biasa mengirim payload `role=admin` saat memperbarui profil mereka. | Eskalasi peran (*Privilege Escalation*) menjadi administrator. | **Form Request Authorization:**<br>`StoreUserRequest` & `UpdateUserRequest` mewajibkan `$user->role === 'admin'`. | [`UpdateUserRequest.php:15`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Requests/UpdateUserRequest.php#L15)<br>Diproteksi di [`UserController.php`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/UserController.php) | **PASS (403)** |

---

## 3. Pembuktian Uji Tembus Otomatis

Aplikasi dilengkapi dengan kumpulan pengujian otomatis (*automated test suite*) yang membuktikan tidak ada celah keamanan maupun IDOR yang tersisa:

```bash
# Menjalankan seluruh pengujian otorisasi dan penutupan IDOR
php artisan test --filter=AuthorizationWebTest
```

**Hasil Pengujian:**
```text
   PASS  Tests\Feature\AuthorizationWebTest
  ✓ student cannot view another student submission prevents idor (0.42s)
  ✓ dosen cannot edit another dosen course                       (0.08s)
  ✓ student cannot access create course page                     (0.06s)
  ✓ student cannot grade submission                              (0.07s)
  ✓ student cannot view draft assignment                         (0.07s)
  ✓ non admin cannot access user management                      (0.08s)

  Tests:    6 passed (9 assertions)
```

Skrip bash otomatis juga disediakan pada [`scripts/test-authz.sh`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/scripts/test-authz.sh) yang dapat dijalankan langsung di terminal Linux/Git Bash untuk mendemonstrasikan respon HTTP `403 Forbidden` pada setiap percobaan penembusan.

---

## 4. Panduan Jawaban Evaluasi / Interview Dosen (Checkpoint M2)

Berikut ringkasan jawaban untuk pertanyaan wawancara individu yang tercantum pada spesifikasi Modul 7.4:

1. **Apa beda autentikasi dan otorisasi? Tunjukkan contohnya di kode Anda!**  
   * **Jawaban:** Autentikasi adalah pembuktian identitas (*siapa Anda*), ditangani oleh `AuthController::login()` dengan memeriksa hash sandi dan membuat session. Otorisasi adalah pemeriksaan wewenang (*apa yang boleh Anda lakukan*), ditangani oleh Policy seperti `SubmissionPolicy::view()` yang memastikan hanya pemilik submission, dosen pengampu, atau admin yang boleh membaca data tersebut.
2. **Tunjukkan Policy yang Anda tulis dan jelaskan tiap barisnya!**  
   * **Jawaban:** Buka [`SubmissionPolicy.php`](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies/SubmissionPolicy.php). Baris 26-38 memeriksa `if ($user->role === 'admin') return true;`. Baris berikutnya memeriksa relasi dosen pengampu `$submission->assignment->course->lecturer_id === $user->id`. Jika mahasiswa, diperiksa kesamaan ID `$submission->user_id === $user->id`. Jika tidak cocok, sistem me-return `false` yang menghasilkan respon `403 Forbidden`.
3. **Kenapa `@can` di Blade tidak cukup? Peragakan dengan mengakses URL langsung!**  
   * **Jawaban:** Karena `@can` hanya memanipulasi HTML di frontend (menyembunyikan tombol). Penyerang tetap bisa mengirim request HTTP langsung menggunakan `curl` atau mengubah URL browser. Tanpa `Gate::authorize()` di controller, data tetap dapat dimanipulasi.
4. **Kenapa kata sandi di-hash, bukan dienkripsi? Apa konsekuensinya untuk fitur lupa password?**  
   * **Jawaban:** Hash adalah fungsi matematis satu arah sehingga tidak dapat didekripsi kembali bahkan jika database bocor. Konsekuensinya, server tidak tahu kata sandi asli pengguna, sehingga fitur lupa sandi wajib membuat tautan token reset baru, bukan mengirimkan kata sandi lama.
5. **Apa fungsi `session()->regenerate()` saat login?**  
   * **Jawaban:** Mencegah serangan *Session Fixation* dengan cara membuang ID session lama dan menerbitkan ID session baru yang unik segera setelah pengguna terotentikasi.
6. **Kenapa daftar mata kuliah tidak boleh diambil semua lalu disaring di view?**  
   * **Jawaban:** Mengambil semua data dengan `Course::all()` lalu memfilternya di Blade membocorkan data ke memori PHP, boros performa (*N+1 issue*), merusak pagination (*halaman 1 bisa kosong padahal ada data di halaman 2*), dan berisiko bocor saat view diubah atau data di-inspect.
