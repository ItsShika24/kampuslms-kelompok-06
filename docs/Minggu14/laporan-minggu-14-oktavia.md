# Laporan Praktikum Minggu 14 — Debugging dan Testing
## Nama: Oktavia Nur Rahmadani
## NIM: 10241060
## Kelompok: 06 (EduKampus)
**Peran Utama:** Frontend & QA Lead  
**Repositori:** KampusLMS (Laravel 12)  
**Dokumen & Berkas Terkait:** [`tests/Feature/CourseWebTest.php`](../../tests/Feature/CourseWebTest.php) & [`tests/Feature/MaterialWebTest.php`](../../tests/Feature/MaterialWebTest.php)

---

## 1. Identitas & Posisi Rantai Eksekusi Tim

Dalam sprint pengujian otomatis Minggu 14, alur kerja pengujian dibagi secara berurutan dan terisolasi untuk menghindari konflik merger dan memastikan setiap lapisan aplikasi teruji secara komprehensif:

```
0. Tika (Security, Anti-IDOR, Auth Web Feature Test & docs/keamanan.md) [MERGED]
   └── 1. Raihandy (REST API Sanctum v1 Feature Test) [MERGED]
         └── 2. Oktavia (Anda — Web CRUD Course & Materials Test) [AKTIF]
               └── 3. Shabrina (Submissions, Queue/Notifications Test & docs/debugging.md)
```

Sebagai **Frontend & QA Lead**, tanggung jawab utama saya pada tahap ini adalah merancang dan mengimplementasikan rangkaian *Feature Test* untuk alur Web CRUD Mata Kuliah serta Manajemen Berkas & Tautan Materi Kuliah, memverifikasi Form Request, menguji isolasi storage privat dengan `Storage::fake()`, serta membuktikan ketahanan test terhadap fenomena *false-positive* (test menipu).

---

## 2. Tahap 1: READ — Verifikasi Sinkronisasi & Analisis Konteks Controller (30 Menit)

Sebelum melakukan penulisan kode test apa pun, dilakukan verifikasi integrasi dari pekerjaan anggota sebelumnya:

### 2.1 Verifikasi Prasyarat Hasil Kerja Raihandy (Urutan 1)
Pemeriksaan dilakukan langsung di branch `main` dengan memastikan Pull Request #58 milik Raihandy (`Raihandy-Wijaya-14`) telah di-merge:
```bash
git checkout main
git pull origin main
```
* **Bukti Integrasi:** Folder `tests/Feature/Api/` telah tersedia di branch `main` dengan 5 berkas pengujian API Sanctum (`AssignmentApiTest.php`, `AuthApiTest.php`, `CourseApiTest.php`, `NotificationApiTest.php`, dan `SubmissionApiTest.php`).
* **Pembuatan Branch Fitur:** Dibuat branch baru khusus untuk tugas Oktavia:
  ```bash
  git checkout -b test/w14-oktavia-web-crud-tests
  ```

### 2.2 Analisis Kontrak Controller & Form Request
Dilakukan pembacaan arsitektur logika aplikasi pada berkas-berkas berikut:
1. `app/Http/Controllers/CourseController.php`:
   * Menggunakan pola PRG (*Post/Redirect/Get*) yang mengarahkan hasil store, update, dan destroy ke route `mata-kuliah.index` dengan session flash message `success`.
   * Otorisasi ketat menggunakan `CoursePolicy` (`Gate::authorize('create', Course::class)`, `Gate::authorize('update', $course)`, dan `Gate::authorize('delete', $course)`).
   * Penghapusan MK dibungkus dalam `DB::transaction()` dengan pembersihan cascading berkas fisik di event model `Course::deleting`.
2. `app/Http/Controllers/MaterialController.php`:
   * Menyimpan berkas materi ke disk privat `'local'` (`storage/app/private/materials/{course_id}`) untuk mencegah akses URL publik langsung tanpa autentikasi.
   * Mendukung dua varian materi: tipe `file` (berkas fisik maks 20 MB) dan tipe `link` (tautan eksternal URL valid).
   * Event model `Material::deleting` otomatis menghapus berkas fisik di storage privat saat record database dihapus.
3. Form Requests:
   * `StoreCourseRequest` & `UpdateCourseRequest`: Memvalidasi field wajib `code` (unique), `name`, `sks` (between:1,6), `lecturer_id`, dan `status` (`in:draft,active,archived`).
   * `StoreMaterialRequest`: Memvalidasi aturan kondisional `required_if:type,file` dengan `max:20480` (20 MB), ekstensi berkas `mimes:pdf,pptx,ppt,docx,doc`, serta `required_if:type,link` dengan format `url`.

---

## 3. Tahap 2: BREAK — Simulasi Kegagalan & Uji Anti-Test Menipu (45 Menit)

Untuk menjamin bahwa test yang dibuat benar-benar menguji logika bisnis dan bukan *false-positive* yang selalu hijau tanpa arti, dilakukan empat eksperimen pengujian kritis:

| # | Skenario Percobaan | Tindakan yang Dilakukan | Hasil Pengamatan & Analisis Teknis |
|---|-------------------|-------------------------|------------------------------------|
| **1** | **Pelonggaran Validasi Form Request** | Mengubah aturan `'name' => ['required', ...]` menjadi `'nullable'` pada `StoreCourseRequest.php`. | **Test Langsung MERAH (FAIL)!**<br>PHPUnit melaporkan:<br>`FAILED: Tests\Feature\CourseWebTest::test_pembuatan_mata_kuliah_gagal_dan_menampilkan_error_validasi_jika_field_wajib_kosong`<br>`Session missing error: name`<br>`Failed asserting that false is true.`<br>Hal ini membuktikan bahwa assertion `assertSessionHasErrors(['name'])` aktif mengawal integritas form. |
| **2** | **Penghapusan `Storage::fake()`** | Menguji proses upload materi tanpa memanggil `Storage::fake('local')`. | Berkas dummy (`silabus.pdf`) fisik tersimpan secara permanen di direktori server `storage/app/private/materials/...`. Hal ini membuktikan bahwa tanpa `Storage::fake()`, disk server akan mengalami kebocoran kapasitas (*disk exhaustion*) akibat penumpukan file testing. |
| **3** | **Simulasi Upload Melebihi Kuota (25 MB)** | Mengirimkan file palsu berukuran 25.600 KB (25 MB) dengan batasan `max:20480`. | Request ditolak oleh Laravel Validator dengan error session pada key `file`, dan record tidak masuk ke database (`assertDatabaseMissing('materials')`). |
| **4** | **Simulasi File Ekstensi Berbahaya** | Mengirimkan berkas `malicious.sh` bertipe shell script. | Request ditolak karena melanggar aturan `mimes:pdf,pptx,ppt,docx,doc`, membuktikan pencegahan *Arbitrary File Upload*. |

---

## 4. Tahap 3: BUILD — Implementasi Rangkaian Feature Test (60 Menit)

Rangkaian pengujian diimplementasikan ke dalam 2 berkas terisolasi sesuai aturan batasan kerja (*Scope Lock*):

### 4.1 Feature Test CRUD Mata Kuliah ([`tests/Feature/CourseWebTest.php`](../../tests/Feature/CourseWebTest.php))
Terdiri atas **8 skenario pengujian (40 assertions)**:
1. `test_admin_dapat_melihat_halaman_daftar_seluruh_mata_kuliah_dan_pagination`: Menegaskan status 200, view `courses.index`, dan navigasi halaman pagination (15 item/halaman).
2. `test_admin_dapat_mengakses_halaman_formulir_tambah_mata_kuliah`: Memastikan formulir `mata-kuliah.create` dapat dirender oleh admin.
3. `test_admin_dapat_membuat_mata_kuliah_baru_dengan_form_request_valid`: Memastikan data valid tersimpan di database (`assertDatabaseHas`), redirect ke index, dan terdapat flash session sukses.
4. `test_pembuatan_mata_kuliah_gagal_dan_menampilkan_error_validasi_jika_field_wajib_kosong`: Menguji validasi form submission kosong (`assertSessionHasErrors(['name', 'code', 'sks', 'lecturer_id', 'status'])`).
5. `test_pembuatan_mata_kuliah_gagal_jika_kode_mata_kuliah_sudah_digunakan`: Menguji aturan integritas *unique code*.
6. `test_dosen_pengampu_dapat_memperbarui_deskripsi_mata_kuliah_miliknya`: Menguji operasi update dan verifikasi perubahan data pada tabel `courses`.
7. `test_dosen_pengampu_dapat_melihat_halaman_detail_mata_kuliah`: Memastikan halaman detail `courses.show` memuat identitas nama dan kode MK.
8. `test_admin_dapat_menghapus_mata_kuliah_dan_cascading_data_berjalan_aman`: Menguji atomisitas delete MK yang membersihkan record materi, tugas, dan submission, serta menghapus berkas fisik di storage privat.

### 4.2 Feature Test Manajemen Materi Kuliah ([`tests/Feature/MaterialWebTest.php`](../../tests/Feature/MaterialWebTest.php))
Terdiri atas **10 skenario pengujian (43 assertions)**:
1. `test_dosen_pengampu_dapat_mengakses_halaman_form_tambah_materi`: Memastikan form penambahan materi dapat diakses oleh dosen pengampu terkait.
2. `test_dosen_pengampu_dapat_mengunggah_file_materi_berformat_pdf_ke_private_storage`: Memverifikasi unggah berkas PDF dengan `Storage::fake()`, memastikan record tersimpan, dan memvalidasi `Storage::disk('local')->assertExists($material->file_path)`.
3. `test_pembuatan_materi_gagal_dan_menampilkan_error_validasi_jika_field_wajib_kosong`: Memverifikasi session error saat judul dan tipe materi tidak diisi.
4. `test_unggah_materi_tipe_file_gagal_jika_berkas_tidak_diunggah`: Menguji aturan `required_if:type,file`.
5. `test_unggah_materi_ditolak_jika_ukuran_berkas_melebihi_batas_maksimal_20mb`: Menguji penolakan berkas 25 MB.
6. `test_unggah_materi_ditolak_jika_format_berkas_tidak_didukung`: Menguji penolakan berkas berekstensi ilegal (`.sh`).
7. `test_dosen_pengampu_dapat_menambahkan_materi_berupa_link_eksternal_tanpa_berkas`: Menguji penambahan materi tautan video YouTube dengan kolom `file_path = null` dan `external_url` terisi.
8. `test_penambahan_materi_tipe_link_gagal_jika_url_tidak_valid`: Menguji aturan validasi format URL.
9. `test_penghapusan_materi_menghapus_record_database_dan_berkas_fisik_dari_storage`: Menguji penghapusan materi yang memicu pembersihan berkas fisik secara otomatis di storage (`Storage::assertMissing`).
10. `test_dosen_bukan_pengampu_tidak_dapat_mengunggah_materi_ke_mata_kuliah_lain`: Menguji otorisasi `MaterialPolicy` untuk mencegah serangan IDOR (menghasilkan status HTTP 403 Forbidden).

---

## 5. Hasil Eksekusi Pengujian Otomatis

Seluruh pengujian dijalankan melalui terminal menggunakan PHPUnit:

```bash
./vendor/bin/phpunit --filter=CourseWebTest
./vendor/bin/phpunit --filter=MaterialWebTest
```

**Keluaran Terminal (*Terminal Output*):**
```text
PHPUnit 11.5.56 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.5.10
Configuration: C:\laragon\www\Proweb\Oktavia Nur\kampuslms-kelompok-06\phpunit.xml

........                                                            8 / 8 (100%)
Time: 00:00.665, Memory: 46.00 MB
OK (8 tests, 40 assertions)

PHPUnit 11.5.56 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.5.10
Configuration: C:\laragon\www\Proweb\Oktavia Nur\kampuslms-kelompok-06\phpunit.xml

..........                                                        10 / 10 (100%)
Time: 00:00.654, Memory: 46.00 MB
OK (10 tests, 43 assertions)
```

**Uji Regresi Keseluruhan (*Full Regression Check*):**  
Suite pengujian anggota lain (`AuthWebTest`, `AuthorizationWebTest`, `FileUploadSecurityTest`, dan `CourseApiTest`) turut dieksekusi dan seluruhnya **100% HIJAU (PASS)** dengan total 56 test tanpa kegagalan.


