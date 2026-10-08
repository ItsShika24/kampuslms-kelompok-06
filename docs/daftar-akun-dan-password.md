# DAFTAR AKUN DAN KATA SANDI (KREDENSIAL PENGUJIAN)

**Proyek:** KampusLMS — Sistem Manajemen Pembelajaran Kampus  
**Kelompok:** Kelompok 06 (Kelas Pemrograman Web SI2514024)  

---

## 1. Akun Demo Utama (Wajib untuk Penguji & Dosen)

Tiga akun demo berikut selalu tersedia untuk keperluan evaluasi, demonstrasi fitur, dan pengujian otomatis:

| Peran (*Role*) | Nama Lengkap | Alamat Email | Kata Sandi (*Password*) | Keterangan Akses |
|---|---|---|---|---|
| **Administrator** | Budi Santoso (Admin) | `admin@kampuslms.test` | `password` | Akses penuh CRUD Pengguna, Mata Kuliah, dan Pengaturan Sistem |
| **Dosen** | Dr. Ir. Bambang Hermanto, M.Kom. | `dosen@kampuslms.test` | `password` | Pengampu mata kuliah, membuat materi & tugas, menilai mahasiswa |
| **Mahasiswa** | Muhammad Rizky Pratama | `mahasiswa@kampuslms.test` | `password` | Mengikuti mata kuliah, mengunduh materi, mengumpulkan tugas |

> 🔑 **Kata Sandi Standar Pengembangan (Local):** `password` (semua huruf kecil).

---

## 2. Daftar Lengkap Seluruh Akun Dosen (3 Dosen)

Sistem memiliki 3 dosen pengampu dengan mata kuliah yang berbeda untuk pengujian otorisasi antar-dosen (*horizontal privilege isolation*):

| # | Nama Lengkap | NIP | Alamat Email | Kata Sandi |
|:---:|---|---|---|---|
| 1 | Dr. Ir. Bambang Hermanto, M.Kom. | `197508122003121001` | `dosen@kampuslms.test` | `password` |
| 2 | Siti Rahmawati, S.Kom., M.Cs. | `198203152008122002` | `siti.rahmawati@kampuslms.test` | `password` |
| 3 | Ahmad Fauzi, S.T., M.T. | `198711202015041003` | `ahmad.fauzi@kampuslms.test` | `password` |

---

## 3. Daftar Lengkap 30 Mahasiswa (Database Seeder)

Seluruh 30 mahasiswa di bawah ini terdaftar aktif di database lokal dan ter-enroll pada 5 mata kuliah Program Studi Sistem Informasi:

| # | Nama Mahasiswa | NIM | Alamat Email | Kata Sandi |
|:---:|---|---|---|---|
| 1 | **Muhammad Rizky Pratama** *(Demo)* | `20210801001` | `mahasiswa@kampuslms.test` | `password` |
| 2 | Ade Puti Nurdiyanti | `202208014808` | `laras94@example.com` | `password` |
| 3 | Ani Maryati S.Sos | `202208014965` | `artanto.usada@example.net` | `password` |
| 4 | Cinthia Uchita Suryatmi S.I.Kom | `202208016522` | `ana26@example.com` | `password` |
| 5 | Citra Wastuti | `202208014164` | `rahmi47@example.com` | `password` |
| 6 | Diah Yunita Anggraini | `202208018031` | `mustofa.marwata@example.com` | `password` |
| 7 | Edi Elvin Gunawan | `202208019000` | `ylailasari@example.org` | `password` |
| 8 | Eva Halimah | `202208018186` | `lintang60@example.com` | `password` |
| 9 | Jaga Iswahyudi | `202208017208` | `amelia.mandala@example.com` | `password` |
| 10 | Jasmin Hasanah M.Farm | `202208011840` | `pangestu.sari@example.org` | `password` |
| 11 | Jasmin Hassanah | `202208013664` | `belinda.pertiwi@example.com` | `password` |
| 12 | Jelita Ellis Uyainah | `202208016298` | `firmansyah.hani@example.org` | `password` |
| 13 | Jumari Mangunsong | `202208019322` | `pangestu.cawisono@example.com` | `password` |
| 14 | Karja Permadi S.Psi | `202208010208` | `azalea.saefullah@example.net` | `password` |
| 15 | Laila Uchita Wulandari | `202208014668` | `nzulkarnain@example.net` | `password` |
| 16 | Lintang Yuniar | `202208010120` | `nainggolan.nurul@example.com` | `password` |
| 17 | Maya Pertiwi | `202208016403` | `pranowo.balijan@example.org` | `password` |
| 18 | Najib Sihombing M.Farm | `202208016750` | `kani33@example.net` | `password` |
| 19 | Nova Wastuti S.E. | `202208011721` | `ihsan.nasyiah@example.net` | `password` |
| 20 | Opan Hardiansyah S.Farm | `202208018241` | `astuti.laras@example.org` | `password` |
| 21 | Padmi Endah Prastuti M.Kom. | `202208013364` | `umar.firmansyah@example.com` | `password` |
| 22 | Pangeran Arta Utama | `202208016915` | `umi78@example.net` | `password` |
| 23 | Raden Maras Habibi M.Pd | `202208013861` | `winarsih.dewi@example.org` | `password` |
| 24 | Rika Gawati Hartati | `202208012430` | `bpangestu@example.net` | `password` |
| 25 | Salimah Zalindra Yolanda | `202208019804` | `chakim@example.org` | `password` |
| 26 | Shania Susanti | `202208017635` | `lala.suartini@example.org` | `password` |
| 27 | Tedi Sitompul | `202208014718` | `ybudiyanto@example.org` | `password` |
| 28 | Tira Padmasari | `202208019286` | `zmandasari@example.net` | `password` |
| 29 | Wardaya Ardianto | `202208010667` | `bagiya45@example.org` | `password` |
| 30 | Yulia Padmasari | `202208013054` | `lkusumo@example.net` | `password` |

---

## 4. Cara Penggunaan Akun

### A. Melalui Halaman Web Browser (LMS Web)
1. Buka URL: **`http://127.0.0.1:8000/login`**
2. Masukkan alamat email salah satu akun di atas (misal `dosen@kampuslms.test`).
3. Masukkan kata sandi: **`password`**.
4. Klik **Masuk ke Akun**. Sistem akan meregenerasi session dan mengarahkan ke dashboard peran terkait.

### B. Melalui REST API (Postman / Curl)
Endpoint Login API:
```bash
POST http://127.0.0.1:8000/api/v1/auth/login
Content-Type: application/json
Accept: application/json

{
  "email": "dosen@kampuslms.test",
  "password": "password"
}
```
Respons akan mengembalikan Bearer Token untuk disertakan pada header `Authorization: Bearer <token>`.
