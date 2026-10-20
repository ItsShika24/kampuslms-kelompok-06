# KampusLMS — Kelompok 06 (EduKampus)

> **SI2514024 Pemrograman Web (3 SKS) — Semester Ganjil 2026/2027**  
> Proyek Berbasis Masalah: Sistem Manajemen Pembelajaran Kampus (*Learning Management System*)  
> **Teknologi:** Laravel 12 | PHP 8.4 | MySQL | Vite & TailwindCSS | Laravel Sanctum API  

---

## 👥 Tim Pengembang (Kelompok 06)

| No | Nama | NIM | Peran Utama | Fokus Kontribusi |
|:---:|---|:---:|---|---|
| 1 | **Raihandy Wijaya** | 10241064 | Fullstack & REST API | Arsitektur REST API Sanctum, Autentikasi & Routing, Integrasi Frontend-Backend |
| 2 | **Tika Mila Wahyuni** | 10241070 | Backend & Security | Kebijakan Otorisasi (5 Policy), Mitigasi IDOR, Query-Level Filtering |
| 3 | **Oktavia Nur Rahmadani** | 10241060 | Frontend & Form Validation | Layout Blade Components, Form Request Validation, Dashboard & UX |
| 4 | **Shabrina Putri Azzahra** | 10241066 | Database & Testing | Skema Migrasi & Relasi Eloquent, Factory & Seeder Realistis, Automated Tests |

---

## 🚀 Prasyarat Sistem

Pastikan lingkungan lokal Anda memenuhi spesifikasi berikut:
- **PHP** >= 8.2 (Disarankan PHP 8.4)
- **Composer** >= 2.x
- **Node.js** >= 18.x & **NPM**
- **MySQL** / **MariaDB** >= 8.0 / 10.4

---

## 🛠️ Panduan Instalasi & Menjalankan Proyek

Ikuti langkah-langkah berikut untuk menjalankan aplikasi secara lokal:

```bash
# 1. Clone repositori
git clone https://github.com/aidilsaputrakirsan/kampuslms-kelompok-06.git
cd kampuslms-kelompok-06

# 2. Pasang dependensi PHP (Composer)
composer install

# 3. Buat berkas konfigurasi lingkungan
cp .env.example .env

# 4. Generate Application Key
php artisan key:generate

# 5. Konfigurasi database di berkas .env
# Sesuaikan DB_DATABASE, DB_USERNAME, dan DB_PASSWORD sesuai konfigurasi lokal Anda
# Contoh:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=kampus_db
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Jalankan migrasi dan seeding data awal (Spesifikasi §4.4)
php artisan migrate:fresh --seed

# 7. Pasang dependensi JavaScript & bangun aset frontend
npm install
npm run build

# 8. Jalankan server pengembangan lokal
php artisan serve
```

Aplikasi dapat diakses melalui peramban di: **`http://127.0.0.1:8000`**

---

## 🔐 Kredensial Akun Demo (Spesifikasi §4.4)

Seluruh akun demo telah dibuat secara otomatis saat menjalankan `php artisan migrate:fresh --seed`:

| Peran | Alamat Email | Kata Sandi | Deskripsi Akses |
|---|---|---|---|
| **Administrator** | `admin@kampuslms.test` | `password` | Akses penuh: manajemen pengguna, manajemen seluruh mata kuliah, pengaturan sistem. |
| **Dosen** | `dosen@kampuslms.test` | `password` | Mengelola mata kuliah yang diampu, membuat/mengedit materi dan tugas, menilai submission. |
| **Mahasiswa** | `mahasiswa@kampuslms.test` | `password` | Melihat mata kuliah terdaftar, mengunduh materi, mengumpulkan tugas, melihat nilai & notifikasi. |

---

## 📌 Fitur Utama yang Telah Diimplementasikan (Minggu 1 – 8 / M1 & M2)

1. **Autentikasi & Manajemen Sesi:**
   - Multi-role login (Admin, Dosen, Mahasiswa) dengan dashboard yang disesuaikan untuk tiap peran.
   - Proteksi *Session Fixation* menggunakan `$request->session()->regenerate()`.
   - Kata sandi di-hash secara aman menggunakan Bcrypt (`password` => `hashed` casting).

2. **Otorisasi & Keamanan Berlapis (Anti-IDOR):**
   - 5 Laravel Policy lengkap: [`CoursePolicy`](app/Policies/CoursePolicy.php), [`MaterialPolicy`](app/Policies/MaterialPolicy.php), [`AssignmentPolicy`](app/Policies/AssignmentPolicy.php), [`SubmissionPolicy`](app/Policies/SubmissionPolicy.php), [`GradePolicy`](app/Policies/GradePolicy.php).
   - Otorisasi diterapkan di level Controller (`Gate::authorize()`) dan UI Blade (`@can`).
   - Query-level scoping (mencegah kebocoran data pada endpoint daftar/index).
   - Seluruh mitigasi IDOR dipetakan secara rinci di [`docs/keamanan.md`](docs/keamanan.md).

3. **Routing & Model Binding:**
   - Route grouping terstruktur berdasarkan peran (`/admin`, `/dosen`, `/mahasiswa`) dengan middleware alias `role`.
   - Nested resources dengan `->shallow()` dan `Route::scopeBindings()` untuk relasi mata kuliah, tugas, dan materi.
   - Halaman error kustom [`403 Forbidden`](resources/views/errors/403.blade.php) dan [`404 Not Found`](resources/views/errors/404.blade.php).

4. **Validasi Server-Side & Pengelolaan State:**
   - Validasi ketat menggunakan dedicated Form Request (`StoreCourseRequest`, `UpdateCourseRequest`, `StoreUserRequest`, `UpdateUserRequest`).
   - Mencegah *Mass Assignment* dengan menjaga kolom `role` di luar `$fillable`.
   - Penerapan pola PRG (*Post-Redirect-Get*) dan Flash Message untuk interaksi pengguna.
   - Pagination 15 item/halaman dengan pencarian dan filter status yang mempertahankan query string.

5. **RESTful API v1 (Laravel Sanctum):**
   - Endpoint API terproteksi token Sanctum di `/api/v1/`.
   - Transformasi response menggunakan API Resources (tidak membocorkan model mentah).
   - Rate limiting: 60 permintaan/menit umum, 5 percobaan/menit untuk login.
   - Dokumentasi lengkap endpoint dan contoh curl di [`docs/api.md`](docs/api.md).

---

## 🧪 Pengujian Otomatis

Aplikasi dilengkapi dengan pengujian unit/fitur PHPUnit serta skrip pengujian berbasis terminal:

```bash
# Menjalankan seluruh test suite PHPUnit
php artisan test

# Menjalankan pengujian otorisasi dan mitigasi IDOR saja
php artisan test --filter=AuthorizationWebTest

# Menjalankan skrip uji otorisasi via curl (Linux / Git Bash)
bash scripts/test-authz.sh

# Menjalankan skrip uji REST API via curl
bash scripts/test-api.sh
```

---

## 📂 Struktur Berkas & Dokumentasi

- [`docs/keamanan.md`](docs/keamanan.md) : Dokumentasi arsitektur keamanan, matriks titik rawan IDOR, dan panduan jawaban interview UTS.
- [`docs/api.md`](docs/api.md) : Kontrak dan dokumentasi teknis RESTful API v1.
- [`scripts/`](scripts/) : Skrip pengujian otomatis cURL (`test-api.sh` & `test-authz.sh`).
- [`docs/Minggu1/`](docs/Minggu1/) s/d [`docs/Minggu7/`](docs/Minggu7/) : Laporan berkala pelaksanaan praktikum READ-BREAK-FIX tiap anggota.
