# LAPORAN MINGGU 6 — REST API dan Integrasi

**Mata Kuliah:** SI2514024 — Pemrograman Web  
**Proyek:** KampusLMS (Kelompok 06)  
**Framework:** Laravel 12 + Laravel Sanctum  

---

## 1. READ — Analisis Web vs API & Arsitektur RESTful

Pada Minggu 6, backend KampusLMS diperluas dengan REST API berstandar industri dengan autentikasi berbasis Bearer Token (Laravel Sanctum). Perbedaan mendasar antara Controller Web (Blade) dan Controller API:

| Aspek | Web Controller (`routes/web.php`) | API Controller (`routes/api.php`) |
|---|---|---|
| **Identitas Pengguna** | Session & Cookie browser | Bearer Token (Sanctum) di header `Authorization` |
| **Proteksi CSRF** | Ya (`@csrf` token pada form) | Tidak (karena *stateless*, tidak mengandalkan cookie) |
| **Gagal Autentikasi** | Redirect ke route `/login` | Status `401 Unauthorized` dengan JSON payload |
| **Format Keluaran** | HTML Blade View | JSON terstruktur dibungkus `API Resource` |
| **Manajemen State** | Stateful (Session server-side) | Stateless (setiap request membawa token sendiri) |
| **Pola Respon POST** | Post-Redirect-Get (PRG) | Respon JSON langsung (`201 Created` / `200 OK`) |

---

## 2. BREAK — Analisis Kerusakan Keamanan & Praktik Buruk API

1. **Kebocoran Model Mentah (`return response()->json(User::all())`):**
   - *Pengamatan:* Seluruh kolom database termasuk hash password (`password`), token (`remember_token`), dan timestamp sensitif bocor ke JSON publik.
   - *Pencegahan:* Menggunakan `UserResource` yang bertindak sebagai **daftar putih (whitelist)** field yang diizinkan keluar saja.
2. **Endpoint Tanpa `auth:sanctum`:**
   - *Pengamatan:* Data privat mata kuliah dan submission dapat diakses oleh siapa saja tanpa autentikasi.
   - *Pencegahan:* Membungkus seluruh rute tertutup dalam middleware grup `auth:sanctum`.
3. **Penyatuan Pesan Error Login (User Enumeration):**
   - *Pengamatan:* Membedakan pesan "Email tidak terdaftar" dan "Password salah" memudahkan penyerang melakukan *user enumeration* (menebak daftar email pengguna valid di sistem).
   - *Pencegahan:* Mengembalikan satu pesan seragam: `"Email atau kata sandi salah."` dengan status 422.
4. **Brute Force Tanpa Rate Limiting:**
   - *Pengamatan:* Endpoint `/auth/login` tanpa batasan frekuensi memungkinkan ribuan percobaan tebak kata sandi per menit.
   - *Pencegahan:* Menerapkan `throttle:5,1` (maksimal 5 percobaan per menit per IP).
5. **N+1 Query pada Koleksi:**
   - *Pengamatan:* Mengambil koleksi tanpa eager loading memicu puluhan query tambahan untuk setiap dosen/relasi.
   - *Pencegahan:* Selalu memanggil `with('lecturer')` dan `withCount(['materials', 'assignments'])`.

---

## 3. BUILD — Rincian Implementasi Minggu 6

### 1. Instalasi dan Konfigurasi Sanctum di Laravel 12
- Menjalankan `php artisan install:api` yang secara otomatis:
  - Menginstal paket `laravel/sanctum`
  - Mendaftarkan rute API di `bootstrap/app.php` (`api: __DIR__.'/../routes/api.php'`)
  - Membuat migrasi tabel `personal_access_tokens`
- Menambahkan trait `Laravel\Sanctum\HasApiTokens` pada model `App\Models\User`.

### 2. Standarisasi Error Response di `bootstrap/app.php`
Untuk memenuhi Kontrak Bagian 5 Spesifikasi Proyek:
- Status 401: `{"message": "Unauthenticated."}`
- Status 403: `{"message": "Anda tidak memiliki akses ke sumber daya ini."}`
- Status 422: `{"message": "Data yang diberikan tidak valid.", "errors": { ... }}`
- Status 404: `{"message": "Data tidak ditemukan."}`

### 3. Implementasi 7 API Resource
1. `UserResource`: Whitelist `id`, `name`, `email`, `role`, `nim_nip`.
2. `CourseResource`: Data MK, dosen via `whenLoaded('lecturer')`, dan hitungan via `whenCounted`.
3. `MaterialResource`: Data berkas/materi perkuliahan dengan data uploader.
4. `AssignmentResource`: Data tugas, waktu tenggat, bobot, status, relasi course/creator.
5. `SubmissionResource`: Data pengumpulan, berkas, status `is_late`, mahasiswa, nilai.
6. `GradeResource`: Nilai, feedback, grader dosen, waktu penilaian.
7. `NotificationResource`: Notifikasi pengguna beserta status dibaca.

### 4. Implementasi 15 Endpoint Lengkap Sesuai Kontrak Spesifikasi Bagian 5
Prefix: `/api/v1`

| Method | Endpoint | Akses | Keterangan & Kontrak Khusus |
|---|---|---|---|
| `POST` | `/auth/login` | Publik (`throttle:5,1`) | Mengembalikan Bearer Token & data User |
| `POST` | `/auth/logout` | Auth | Menghapus token aktif |
| `GET` | `/me` | Auth | Mengambil profil dan role |
| `GET` | `/courses` | Auth | Dosen: MK yang diajar; Mahasiswa: MK yang diikuti |
| `GET` | `/courses/{id}` | Auth + Scope | Detail MK + jumlah materi & tugas |
| `GET` | `/courses/{id}/materials` | Auth + Scope | Daftar materi mata kuliah |
| `GET` | `/courses/{id}/assignments` | Auth + Scope | Mendukung `?status=` dan `?page=` |
| `POST` | `/assignments` | Dosen (Pengampu) | Status 201 Created |
| `PUT/PATCH` | `/assignments/{id}` | Dosen (Pemilik) | Status 200 OK |
| `DELETE` | `/assignments/{id}` | Dosen (Pemilik) | Status 204 No Content |
| `GET` | `/assignments/{id}/submissions` | Dosen (Pemilik) | Daftar submission mahasiswa |
| `POST` | `/assignments/{id}/submissions` | Mahasiswa (Terdaftar) | Multipart, tolak submission ganda & telat ilegal |
| `PUT` | `/submissions/{id}/grade` | Dosen (Pemilik) | `updateOrCreate`: **201** saat pertama dibuat, **200** saat diperbarui |
| `GET` | `/notifications` | Auth | Daftar notifikasi pengguna |
| `POST` | `/notifications/{id}/read` | Auth | Tandai notifikasi sebagai terbaca |

### 5. Pengujian & Artefak
1. **Automated Feature Tests:** 4 file pengujian baru (`AuthApiTest`, `CourseApiTest`, `AssignmentApiTest`, `SubmissionApiTest`, `NotificationApiTest`) dengan total **36 test cases** dan **96 assertions**, seluruhnya **PASS (100% Hijau)**.
2. **Skrip Uji Otorisasi Bash:** `scripts/test-api.sh` yang menguji 4 kondisi otorisasi (tanpa token $\rightarrow$ 401, token mahasiswa ke dosen $\rightarrow$ 403, dosen A ke dosen B $\rightarrow$ 403, token valid $\rightarrow$ 200/201).
3. **Dokumentasi API Lengkap:** `docs/api.md` yang merinci parameter, contoh `curl`, format response sukses, dan respon error.

---

## 4. CHECKPOINT MINGGU 6 — Tanya Jawab Konseptual

### 1. Kenapa mengembalikan model mentah berbahaya? Peragakan kebocorannya.
**Jawaban:**
Mengembalikan model Eloquent mentah secara langsung via `response()->json($user)` mengekspos seluruh atribut kolom tabel database yang bersangkutan. Pada tabel `users`, kolom sensitif seperti hash password (`password`), token sesi (`remember_token`), email verified timestamp, atau kolom internal yang belum dirilis akan ikut terkirim ke client.
Meskipun ada properti `$hidden` di model, properti tersebut rentan terlewat atau tidak sengaja terbongkar ketika developer memanggil relasi dinamis. Dengan **API Resource**, sistem menerapkan pendekatan **Whitelist** (*daftar putih*): hanya field yang secara eksplisit dicantumkan di `toArray()` yang akan keluar ke respons JSON.

### 2. Apa beda 401 dan 403? Tunjukkan di API Anda satu contoh masing-masing.
**Jawaban:**
- **`401 Unauthorized` (Sebenarnya Unauthenticated):** Pengguna **belum membuktikan identitasnya** (tidak mengirim token Bearer atau token sudah kadaluarsa/tidak valid).
  - *Contoh di API:* Memanggil `GET /api/v1/me` tanpa header `Authorization: Bearer <token>` menghasilkan respon `401 Unauthorized`.
- **`403 Forbidden`:** Pengguna **sudah terautentikasi (server tahu siapa Anda)**, tetapi pengguna **tidak memiliki hak/wewenang** untuk mengakses sumber daya tersebut.
  - *Contoh di API:* Mahasiswa yang sudah login mencoba menilai tugas di `PUT /api/v1/submissions/1/grade`, atau Dosen A mencoba melihat detail MK milik Dosen B di `GET /api/v1/courses/2`. Responnya adalah `403 Forbidden` (`{"message": "Anda tidak memiliki akses ke sumber daya ini."}`).

### 3. Kenapa `routes/api.php` tidak ada secara default di Laravel 12? Bagaimana mengaktifkannya?
**Jawaban:**
Laravel 12 mengadopsi arsitektur *lean skeleton* untuk menjaga aplikasi seringan mungkin sejak awal. Banyak aplikasi web tradisional (Blade/Livewire) tidak memerlukan REST API terpisah. Untuk mengaktifkannya, jalankan perintah:
```bash
php artisan install:api
```
Perintah ini akan memasang paket Laravel Sanctum, menerbitkan berkas migrasi tabel token, membuat berkas rute `routes/api.php`, dan secara otomatis mendaftarkan rute API dengan prefix `/api` di dalam closure `->withRouting(...)` pada berkas `bootstrap/app.php`.

### 4. Apa fungsi `whenLoaded()`? Apa yang terjadi tanpanya?
**Jawaban:**
`whenLoaded('relation')` pada API Resource berfungsi untuk **hanya menyertakan data relasi jika relasi tersebut telah di-*eager load*** pada query controller sebelumnya (misal melalui `with('lecturer')`).
Tanpa `whenLoaded()` (misalnya langsung memanggil `new UserResource($this->lecturer)`), Eloquent akan mengeksekusi query database baru secara *lazy loading* setiap kali satu baris data diproses dalam perulangan resource collection. Pada 50 data mata kuliah, ini akan memicu 50 query tambahan ke tabel `users` (**Masalah N+1 Query**), yang secara signifikan memperlambat performa respons API.

### 5. Kenapa pesan gagal login tidak boleh membedakan email salah dan password salah?
**Jawaban:**
Jika sistem membedakan pesan (misal: *"Email tidak terdaftar"* vs *"Password salah"*), penyerang dapat memanfaatkan perbedaan tersebut untuk melakukan teknik **User Enumeration**. Penyerang bisa mengotomasi ribuan alamat surel dan langsung mengetahui surel mana saja yang terdaftar di sistem KampusLMS.
Dengan menyeragamkan pesan kesalahan menjadi `"Email atau kata sandi salah."`, penyerang tidak mendapatkan petunjuk apakah email tersebut valid atau tidak di basis data.

### 6. Kenapa endpoint login wajib di-*throttle*? Berapa nilai yang Anda pakai dan mengapa?
**Jawaban:**
Endpoint login merupakan target utama serangan *brute force* dan *credential stuffing* (kamus kata sandi). Tanpa *rate limiting*, bot penyerang dapat mencoba ratusan ribu kombinasi kata sandi dalam hitungan menit yang dapat membebani CPU server (*Denial of Service*) dan membobol akun pengguna.
Nilai yang diterapkan di KampusLMS adalah **`throttle:5,1`** (maksimal **5 percobaan per 1 menit** per alamat IP). Batasan ini memberikan toleransi yang cukup bagi manusia normal yang salah mengetik kata sandi 1–3 kali, namun langsung memblokir secara instan bot yang mencoba serangan otomatis.

---

## 5. PENDAFTARAN JALUR FRONTEND (MINGGU 7–16)

Sesuai ketentuan Bagian 6 Spesifikasi Proyek KampusLMS:
- **Jalur yang Dipilih Kelompok 06:** **(b) Blade + Livewire 3** *(atau sesuai arahan tim: Blade terstruktur dengan komponen interaktif dinamis)*.
- **Alasan:** Memanfaatkan fondasi Blade yang sudah kokoh pada Minggu 1–5, dengan penambahan reaktivitas modern komponen Livewire 3 untuk fitur real-time seperti submission, filter dinamis, dan grading tanpa kompleksitas *build tooling* SPA terpisah. Seluruh logika tetap konsisten dengan REST API kontrak Bagian 5 yang telah dibangun.
