# Laporan Praktikum Minggu 14 — Debugging dan Testing
## Nama: Tika Mila Wahyuni
## NIM: 10241070
## Kelompok: 06 (EduKampus)
**Peran Utama:** Backend & Security Lead  
**Repositori:** KampusLMS (Laravel 12)  
**Dokumen Terkait:** [`docs/keamanan.md`](../keamanan.md), [`tests/Feature/AuthorizationWebTest.php`](../../tests/Feature/AuthorizationWebTest.php), [`tests/Feature/AuthWebTest.php`](../../tests/Feature/AuthWebTest.php)

---

## 1. Tahap 1: READ — Membaca Kegagalan, Log, dan Stack Trace (30 Menit)

Sesuai panduan Modul 14 Subbab 14.3, dilakukan eksplorasi sistematis terhadap diagnosis kegagalan aplikasi web dan pembacaan jejak kesalahan (*stack trace*):

### 1.1 Eksekusi Pengujian Otomatis Proyek
Perintah pengujian dijalankan melalui terminal:
```bash
php artisan test --filter=AuthorizationWebTest
php artisan test --filter=AuthWebTest
```
* **Hasil Pengamatan:**
  * Rangkaian pengujian otorisasi ([`AuthorizationWebTest.php`](../../tests/Feature/AuthorizationWebTest.php)): **12 passed (23 assertions)**.
  * Rangkaian pengujian autentikasi ([`AuthWebTest.php`](../../tests/Feature/AuthWebTest.php)): **7 passed (26 assertions)**.
  * Total suite keamanan & autentikasi: **19 passed (49 assertions)** berjalan dalam waktu ~8 detik tanpa ada kegagalan (*0 failed*).

### 1.2 Simulasi Test Sengaja Gagal & Pembacaan Baris Kode `app/`
Sebuah pengujian sengaja digagalkan pada `AuthorizationWebTest` dengan mengubah ekspektasi status respon dari `403` menjadi `200`:
```php
$responseB->assertStatus(200); // Seharusnya 403 Forbidden
```
* **Keluaran Kesalahan (*Output Error*):**
  ```text
  FAILED  Tests\Feature\AuthorizationWebTest > student cannot view another student submission prevents idor
  Expected response status code [200] but received 403.

  at tests/Feature/AuthorizationWebTest.php:48
     44▕         $responseA->assertStatus(200);
     45▕
     46▕         // IDOR Test: Student B attempts to access Student A's submission -> MUST RETURN 403
     47▕         $responseB = $this->actingAs($studentB)->get(route('submissions.show', $submissionA));
  ➜  48▕         $responseB->assertStatus(200);
  ```
* **Analisis Reflektif:**  
  Dalam mendiagnosis kegagalan test, baris pertama yang harus dibaca dari jejak kesalahan adalah **baris yang menunjuk langsung ke berkas di dalam `tests/` atau `app/`**, bukan tumpukan jejak vendor framework Laravel (`vendor/laravel/framework/...`). Dari baris 48 di atas, langsung terlihat bahwa sumber kegagalan adalah ketidakcocokan ekspektasi kontrak status kode HTTP.

### 1.3 Penelusuran Exception pada `storage/logs/laravel.log`
Pemeriksaan log dilakukan pada berkas `storage/logs/laravel.log`:
* **Temuan Kasus Exception:** Ditemukan exception riil `CommandNotFoundException` dan `PDOException: Connection Refused` saat koneksi port database lokal sempat terputus.
* **Cara Membaca Stack Trace yang Benar:**  
  Stack trace sering kali menghasilkan 50+ baris jejak pemanggilan fungsi internal Symfony dan Laravel kernel. Aturan baku yang diterapkan: **abaikan seluruh jejak `vendor/` dan cari baris pertama yang memuat path controller/model proyek** (misal `app/Http/Controllers/SubmissionController.php:61`). Di baris itulah logika aplikasi kelompok mulai menyimpang.

### 1.4 Pemantauan Kueri SQL dengan `DB::listen()`
Dipasang listener kueri database pada method `boot()` di `app/Providers/AppServiceProvider.php`:
```php
if (config('app.debug')) {
    \Illuminate\Support\Facades\DB::listen(function ($query) {
        \Illuminate\Support\Facades\Log::debug("[SQL Query] {$query->sql}", [
            'bindings' => $query->bindings,
            'time_ms'  => $query->time,
        ]);
    });
}
```
* **Hasil Pengamatan:** Saat membuka halaman detail mata kuliah dan daftar pengumpulan tugas, kueri yang dieksekusi tercatat rapi di log. Listener ini membuktikan apakah eager loading (`with('lecturer')`) berjalan efektif atau memicu N+1 query loop.

---

## 2. Tahap 2: BREAK — Tujuh Percobaan Simulasi Kegagalan & Test Menipu (45 Menit)

Tujuh eksperimen dijalankan di branch lokal untuk menguji batas ketahanan sistem pengujian dan memahami fenomena *false-positive tests*:

| # | Skenario Percobaan | Yang Dilakukan | Hasil Pengamatan & Analisis Kritis |
|---|-------------------|----------------|-------------------------------------|
| **1** | **Test yang Menipu (*False Positive*)** | Menulis test yang hanya mengecek `assertStatus(200)`, lalu mengosongkan seluruh method controller-nya. | **Test TETAP HIJAU!** Meskipun controller tidak lagi mengembalikan data apa pun atau logikanya hilang, status HTTP tetap 200 selama view kosong dikembalikan. **Kesimpulan:** Test wajib memiliki *assertion* bermakna terhadap data di database (`assertDatabaseHas`) atau teks spesifik (`assertSee`). |
| **2** | **Pencabutan Otorisasi (`Gate::authorize`)** | Mengomentari baris `Gate::authorize('delete', $course)` pada `CourseController@destroy`. | **Test `test_non_admin_cannot_delete_course` langsung MERAH (FAIL)!** Test mendeteksi dosen/mahasiswa berhasil menghapus mata kuliah (menerima status 302/redirect bukannya 403). Hal ini membuktikan test otorisasi kelompok kami benar-benar mengunci keamanan. |
| **3** | **Penghapusan Trait Database Isolation** | Menghapus trait `DatabaseTransactions` / `RefreshDatabase`, lalu menjalankan test dua kali beruntun. | **Test kedua GAGAL!** Data yang dibuat pada eksekusi pertama (misal email `testuser@kampuslms.test`) masih tersisa di tabel `users`, memicu error SQL `Duplicate entry for key 'users_email_unique'`. |
| **4** | **Ketergantungan Data Seeder** | Menulis test yang bergantung pada ID hasil seeder tetap (`User::find(1)`). | Test berjalan di lokal tetapi **GAGAL di lingkungan CI/CD**, karena runner CI memulai database dari kondisi kosong (*clean slate*) di mana ID auto-increment tidak dijamin sama. Solusi: Wajib gunakan Model Factory (`User::factory()`). |
| **5** | **Penghapusan Fake Storage** | Menghapus `Storage::fake('private')` pada test unggah tugas atau materi. | Berkas dummy (seperti `jawaban.pdf` dan `silabus.pdf`) menumpuk di harddisk fisik `storage/app/private/`. Dalam jangka panjang, hal ini memicu kebocoran kapasitas disk server testing dan mengotori lingkungan kerja. |
| **6** | **Perusakan Migrasi `down()`** | Menghapus method `down()` pada salah satu berkas migrasi tabel pivot, lalu memanggil `RefreshDatabase`. | Perintah `php artisan migrate:rollback` gagal mengeksekusi *drop table*, menyebabkan siklus *refresh* database terhenti dengan error `Table already exists`. Migrasi wajib *reversible*. |
| **7** | **Tertinggalnya Fungsi `dd()`** | Menaruh `dd($request->all())` di dalam `AuthController@login`. | Test langsung terhenti (*aborted*), menghasilkan error parser HTML dump, dan seluruh 6 test berikutnya gagal dieksekusi. Inilah alasan mengapa `dd()` dan `dump()` dilarang keras masuk Pull Request. |

---

## 3. Tahap 3: BUILD — Penguatan Test Suite Keamanan & Dokumen Keamanan

Sebagai pemegang peran **Backend & Security Lead**, saya mengimplementasikan dan memverifikasi modul pengujian berikut:

### 3.1 Penambahan Test Proteksi Penghapusan Mata Kuliah
Pada [`tests/Feature/AuthorizationWebTest.php`](../../tests/Feature/AuthorizationWebTest.php), ditambahkan method pengujian baru:
```php
public function test_non_admin_cannot_delete_course(): void
{
    $dosen = User::factory()->dosen()->create();
    $student = User::factory()->mahasiswa()->create();
    $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

    // Dosen cannot delete course -> MUST RETURN 403
    $this->actingAs($dosen)->delete(route('mata-kuliah.destroy', $course))->assertStatus(403);

    // Student cannot delete course -> MUST RETURN 403
    $this->actingAs($student)->delete(route('mata-kuliah.destroy', $course))->assertStatus(403);

    // Course should still exist in database
    $this->assertDatabaseHas('courses', ['id' => $course->id]);
}
```

### 3.2 Pemutakhiran Tabel Pemetaan Pengujian di `docs/keamanan.md`
Tabel Titik Rawan IDOR pada [`docs/keamanan.md`](../keamanan.md) diperbarui dengan menambahkan kolom resmi **`Feature Test Penguji (M14)`**:
* Titik Rawan 1 (Detail Submission) $\rightarrow$ `test_student_cannot_view_another_student_submission_prevents_idor`
* Titik Rawan 2 (Penilaian Tugas) $\rightarrow$ `test_student_cannot_grade_submission`
* Titik Rawan 3 (Edit Mata Kuliah) $\rightarrow$ `test_dosen_cannot_edit_another_dosen_course`
* Titik Rawan 4 (Hapus Mata Kuliah) $\rightarrow$ `test_non_admin_cannot_delete_course`
* Titik Rawan 5 (Akses Tugas Draft) $\rightarrow$ `test_student_cannot_view_draft_assignment`
* Titik Rawan 6 (Pengumpulan Liar Unenrolled) $\rightarrow$ `test_unenrolled_student_cannot_submit_assignment_prevents_idor`
* Titik Rawan 7 (Unduh/Hapus Materi) $\rightarrow$ `test_unenrolled_student_cannot_download_course_material`
* Titik Rawan 8 (Scoping Daftar) $\rightarrow$ `test_dosen_cannot_access_other_lecturers_course`
* Titik Rawan 9 (Mass Assignment Role) $\rightarrow$ `test_role_is_not_mass_assignable_on_user`

---

## 4. Evaluasi Mandiri & Jawaban Checkpoint Minggu 14 (Subbab 14.4)

Berikut adalah jawaban reflektif atas 6 pertanyaan wajib evaluasi Checkpoint Minggu 14:

### 4.1 Peragaan Test Merah Saat Fitur Dihapus
* **Langkah Demonstrasi:** Saya membuka `CourseController.php:151`, menghapus baris `Gate::authorize('delete', $course);`, dan menjalankan `php artisan test --filter=test_non_admin_cannot_delete_course`. Test langsung merah (menerima 302 bukannya 403). Setelah baris otorisasi dikembalikan, test kembali hijau. Hal ini membuktikan test kami bermakna dan aktif mengawal logika keamanan.

### 4.2 Mengapa `assertStatus(200)` Sering Menipu?
* **Alasan Teknis:** Kode status `200 OK` hanya menunjukkan bahwa server web berhasil mengembalikan response HTTP tanpa unhandled exception. Status 200 **tidak menjamin** bahwa data yang ditampilkan benar, otorisasi diperiksa, atau logika bisnis bekerja. Halaman putih kosong atau halaman yang membocorkan data pengguna lain tetap bisa mengembalikan status 200.

### 4.3 Mengapa Test Jalur Ditolak (403) Jauh Lebih Krusial?
* **Alasan Teknis:** Test jalur sukses (*happy path*) hanya membuktikan bahwa fitur bekerja untuk skenario normal. Namun pada aplikasi kampus berbasis multi-peran, risiko terbesar adalah **kebocoran data dan pembobolan hak akses (IDOR/Privilege Escalation)**. Memastikan bahwa mahasiswa tidak bisa menilai tugasnya sendiri atau mengintip nilai temannya jauh lebih kritis bagi integritas akademik daripada sekadar memastikan tombol login berfungsi.

### 4.4 Apa Fungsi `RefreshDatabase` dan Mengapa Migrasi Harus Reversible?
* **Fungsi:** `RefreshDatabase` memastikan bahwa setiap pengujian berjalan dalam database yang terisolasi dan bersih, baik melalui transaksi rollback in-memory atau migrasi ulang otomatis.
* **Kebutuhan Reversible:** Jika migrasi tidak memiliki method `down()` yang valid, perintah rollback akan gagal, menyebabkan tabel tertinggal dan merusak eksekusi test-test berikutnya.

### 4.5 Studi Kasus Debugging (`docs/debugging.md`)
* **Kasus Helper `env()`:** Saat deployment ke produksi, seluruh konfigurasi di-cache menggunakan `php artisan config:cache`. Akibatnya fungsi `env('PRIVATE_DISK')` mengembalikan `null` di controller karena Laravel menonaktifkan pemuatan berkas `.env` setelah dicache. Kami mempersempit masalah dengan membaca log dan memeriksa `config/filesystems.php`, lalu mengganti seluruh pemanggilan `env()` langsung di controller menjadi `config('filesystems.disks.private')`.

### 4.6 Mengapa `dd()` Dilarang di PR Sedangkan `Log::info()` Boleh?
* **Alasan:** `dd()` (*Dump and Die*) menghentikan seketika eksekusi skrip PHP dan mencetak seluruh data memori mentah ke layar peramban. Jika lolos ke produksi, pengguna biasa akan melihat layar debug hitam yang membocorkan kredensial database dan merusak pengalaman pengguna. Sebaliknya, `Log::info()` menulis pesan diagnostik ke berkas log server di latar belakang tanpa mengganggu alur tampilan pengguna.
