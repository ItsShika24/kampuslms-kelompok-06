## Oktavia Nur Rahmadani
### NIM 10241060
### Pemrograman Web - Laporan Minggu 7

---
### READ
| Konsep | Mekanisme di KampusLMS | Alasan Keamanan |
|---|---|---|
| Autentikasi | `Auth::attempt($credentials)` | Memverifikasi email dan kata sandi pengguna. |
| Otorisasi | Laravel Policy dan `Gate::authorize()` | Membatasi akses sesuai hak pengguna. |
| Session Fixation | `$request->session()->regenerate()` | Mengganti ID sesi setelah login berhasil. |
| Logout | `Auth::logout()`, `invalidate()`, dan `regenerateToken()` | Mengakhiri sesi dan memperbarui token CSRF. |
| Password Hashing | `'password' => 'hashed'` | Melindungi kata sandi dengan hash satu arah. |
| User Enumeration | Pesan kesalahan login umum | Mencegah orang mengetahui keberadaan akun melalui pesan login. |

### BREAK
| # | Skenario Kerusakan | Dampak dan Risiko | Solusi di KampusLMS |
|---|---|---|---|
| 1 | Menghapus `session()->regenerate()` saat login | Meningkatkan risiko *session fixation*. | Regenerasi ID sesi setelah login berhasil. |
| 2 | Menghapus `Gate::authorize()` tetapi membiarkan `@can` di Blade | Tombol tersembunyi, tetapi URL masih bisa diakses langsung. | Terapkan otorisasi di controller; `@can` hanya mengatur tampilan. |
| 3 | Dosen mengakses mata kuliah dosen lain | Materi dan tugas dapat diubah tanpa izin (*IDOR*). | Gunakan `CoursePolicy` untuk memeriksa kepemilikan mata kuliah. |
| 4 | Mahasiswa membuka submission mahasiswa lain | Data dan berkas tugas dapat terbongkar (*IDOR*). | Gunakan `SubmissionPolicy` untuk membatasi akses pemilik. |
| 5 | Menggunakan `Course::paginate()` tanpa pembatasan | Data mata kuliah yang tidak relevan dapat terlihat oleh pengguna. | Terapkan pembatasan query berdasarkan peran dan hak akses. |
| 6 | Mengirim `role=admin` melalui form profil | Pengguna dapat memperoleh hak administrator tanpa izin (*privilege escalation*). | Batasi field yang dapat diisi dan lindungi atribut `role`. |
| 7 | Menghapus cast `'password' => 'hashed'` | Kata sandi berisiko tersimpan tanpa hashing. | Pertahankan cast `hashed` pada model `User`. |
| 8 | Cookie sesi tidak memakai flag keamanan | Cookie berisiko dicuri, terutama melalui serangan XSS. | Aktifkan `HttpOnly` dan `Secure` melalui konfigurasi cookie yang sesuai. |

### FIX
| No. | Perbaikan | Penjelasan |
|---|---|---|
| 1 | Otorisasi Controller | Menambahkan `Gate::authorize()` pada `CourseController` dan `SubmissionController` agar setiap akses diperiksa berdasarkan hak pengguna. |
| 2 | Perbaikan Policy | Memperbarui `CoursePolicy` untuk membatasi tindakan berdasarkan peran dan kepemilikan mata kuliah. |
| 3 | Penyaringan Mata Kuliah | Menyesuaikan query pada `CourseController@index` agar daftar mata kuliah sesuai dengan peran pengguna. |
| 4 | Perlindungan Data Profil | Menggunakan `$request->only(['name', 'email'])` agar pengguna tidak bisa mengubah peran melalui form profil. |
| 5 | Keamanan Sesi Login | Menambahkan `$request->session()->regenerate()` setelah login berhasil untuk mengurangi risiko *session fixation*. |
| 6 | Keamanan Pesan Login | Menggunakan pesan kesalahan yang sama untuk email tidak terdaftar maupun kata sandi yang salah. |
| 7 | Perlindungan Route Dosen | Menambahkan middleware `role:dosen` agar route dosen hanya dapat diakses oleh pengguna dengan peran yang sesuai. |
| 8 | Optimasi Policy | Menggunakan pemeriksaan awal dan `loadMissing('assignment.course')` untuk mengurangi query berulang saat memeriksa akses. |
| 9 | Penyesuaian Laravel 12 | Menggunakan `Gate::authorize()` dan mendaftarkan alias middleware `role` pada `bootstrap/app.php` agar sesuai dengan struktur Laravel 12. |

#### Bukti Pengujian cURL
#### 1. Bukti IDOR Submission (`SubmissionController@show`)
Sebelum:
```bash
curl -i -b cookie_mhs_b.txt http://localhost:8000/mahasiswa/submissions/1
# Output: HTTP/1.1 200 OK (Mahasiswa B bisa melihat submission Mahasiswa A)
```

Sesudah:

```bash
curl -i -b cookie_mhs_b.txt http://localhost:8000/mahasiswa/submissions/1
# Output: HTTP/1.1 403 Forbidden
```

#### 2. Bukti Proteksi Mass Assignment Role Profil

Sebelum:

Mengirim parameter role=admin mengubah peran pengguna di database menjadi admin. 

Sesudah:

```bash
curl -i -X PUT http://localhost:8000/profile \
  -b cookie_user.txt \
  -H "X-CSRF-TOKEN: <TOKEN>" \
  -d "name=Hacker&email=hacker@test.com&role=admin"
# Output: HTTP/1.1 302 Found (Nilai role di database diabaikan dan tetap peran semula)
```

#### 3. Bukti Proteksi Route Dosen dari Mahasiswa

Sebelum:

```bash
curl -i -b cookie_mahasiswa.txt http://localhost:8000/dosen/courses/create
# Output: HTTP/1.1 200 OK (Mahasiswa bisa mengakses modul pembuatan MK dosen)
```

Sesudah:

```bash
curl -i -b cookie_mahasiswa.txt http://localhost:8000/dosen/courses/create
# Output: HTTP/1.1 403 Forbidden
```
### BUILD 
#### Langkah 1: Sistem Autentikasi
- Controller: `AuthController.php` menangani login dengan regenerasi sesi dan pesan kesalahan umum.
- Tampilan: `resources/views/auth/login.blade.php` menggunakan desain modern bertema gelap serta pintasan akun demo untuk Admin, Dosen, dan Mahasiswa.
- Rute dan Middleware: `/login` menggunakan middleware `guest`, sedangkan `/logout` dilindungi middleware `auth`.

#### Langkah 2: Pembuatan Lima Policy
Lima Policy digunakan untuk mengatur hak akses berdasarkan peran dan kepemilikan data. Laravel 12 mendukung pendaftaran Policy secara otomatis (*auto-discovery*).

1. `CoursePolicy.php` — mengatur akses mata kuliah.
2. `MaterialPolicy.php` — mengatur akses dan pengelolaan materi.
3. `AssignmentPolicy.php` — mengatur akses tugas, termasuk tugas draft.
4. `SubmissionPolicy.php` — melindungi data pengumpulan tugas dari akses tanpa izin.
5. `GradePolicy.php` — membatasi pemberian nilai kepada dosen pengampu atau admin.

#### Langkah 3: Otorisasi Controller dan Penyaringan Data
`Gate::authorize()` diterapkan pada `CourseController`, `SubmissionController`, `AssignmentController`, dan `MaterialController` untuk memastikan setiap permintaan diperiksa di sisi server.

Penyaringan mata kuliah juga disesuaikan dengan peran pengguna: admin dapat melihat seluruh mata kuliah, dosen melihat mata kuliah yang diampu, dan mahasiswa melihat mata kuliah yang diikuti. Relasi dosen dimuat menggunakan `with('lecturer')` untuk mengurangi query berulang.

#### Langkah 4: Penerapan `@can` pada Blade
Direktif `@can` digunakan untuk menampilkan tombol dan formulir sesuai hak akses pengguna.

- `courses/index.blade.php` — membatasi tampilan tombol tambah mata kuliah.
- `courses/show.blade.php` — mengatur tampilan tombol edit mata kuliah, tambah materi, dan tambah tugas.
- `submissions/show.blade.php` — membatasi tampilan formulir penilaian tugas.

Direktif `@can` mengatur tampilan antarmuka, sedangkan `Gate::authorize()` memastikan akses tetap terlindungi meskipun URL dibuka secara langsung.

#### Langkah 5: Dokumentasi dan Pengujian Otomatis
- Dokumentasi keamanan: `docs/keamanan.md` berisi sembilan skenario IDOR beserta panduan evaluasi keamanan.
- Skrip pengujian: `scripts/test-authz.sh` digunakan untuk menguji 26 skenario otorisasi Web dan API melalui cURL.