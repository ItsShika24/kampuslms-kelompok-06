# Dokumentasi REST API — KampusLMS (v1)

**SI2514024 Pemrograman Web | Proyek: KampusLMS | Laravel 12 Sanctum**

---

## Ringkasan Arsitektur API

- **Base URL:** `http://localhost/api/v1` (atau sesuai konfigurasi `APP_URL`)
- **Autentikasi:** Laravel Sanctum Bearer Token (`Authorization: Bearer <token>`)
- **Format Payload:** JSON (`Content-Type: application/json` & `Accept: application/json`)
- **Multipart:** Digunakan pada upload berkas submission (`POST /assignments/{id}/submissions`)
- **Rate Limiting:**
  - Login: **5 permintaan / menit** (`throttle:5,1`) untuk mencegah brute force dan user enumeration.
  - Endpoint terlindungi: **60 permintaan / menit** (`throttle:60,1`).

### Format Respons Standar

#### 1. Sukses Data Tunggal (Single Resource)
Status Code: `200 OK` atau `201 Created`
```json
{
  "data": {
    "id": 1,
    "code": "SI101",
    "name": "Pengantar Sistem Informasi"
  }
}
```

#### 2. Sukses Data Koleksi & Pagination (Collection Resource)
Status Code: `200 OK`
```json
{
  "data": [
    { "id": 1, "code": "SI101", "name": "Pengantar Sistem Informasi" }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "total": 47
  }
}
```

#### 3. Error Validasi Masukan
Status Code: `422 Unprocessable Content`
```json
{
  "message": "Data yang diberikan tidak valid.",
  "errors": {
    "score": [
      "The score field is required."
    ]
  }
}
```

#### 4. Error Hak Akses / Otorisasi
Status Code: `403 Forbidden`
```json
{
  "message": "Anda tidak memiliki akses ke sumber daya ini." }
```

#### 5. Error Belum Terautentikasi
Status Code: `401 Unauthorized`
```json
{
  "message": "Unauthenticated."
}
```

---

## Kredensial Akun Uji Coba (Seeder)

| Peran | Email | Kata Sandi | Deskripsi |
|---|---|---|---|
| **Admin** | `admin@kampuslms.test` | `password` | Pengelola sistem seluruh data |
| **Dosen A** | `dosen@kampuslms.test` | `password` | Dr. Ir. Bambang Hermanto (Pengampu SI101) |
| **Dosen B** | `siti.rahmawati@kampuslms.test` | `password` | Siti Rahmawati, S.Kom., M.Cs. (Pengampu SI201) |
| **Mahasiswa** | `mahasiswa@kampuslms.test` | `password` | Muhammad Rizky Pratama (Terdaftar di SI101 & SI201) |

---

## Daftar Endpoint Lengkap

### 1. Autentikasi

#### `POST /auth/login`
- **Akses:** Publik
- **Rate Limit:** 5 req/min
- **Deskripsi:** Melakukan autentikasi dan menerbitkan Sanctum Bearer token. Menggunakan pesan kesalahan seragam untuk mencegah *user enumeration*.
- **Parameter Body:**
  - `email` (string, required): Alamat email akun.
  - `password` (string, required): Kata sandi akun.
  - `device_name` (string, optional): Nama perangkat client (default: `api`).

**Contoh Request:**
```bash
curl -X POST http://localhost/api/v1/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "dosen@kampuslms.test",
    "password": "password",
    "device_name": "curl-client"
  }'
```

**Respons Sukses (200 OK):**
```json
{
  "token": "1|N40WzT9X...",
  "user": {
    "id": 2,
    "name": "Dr. Ir. Bambang Hermanto, M.Kom.",
    "email": "dosen@kampuslms.test",
    "role": "dosen",
    "nim_nip": "197508122003121001"
  }
}
```

**Respons Gagal (422 Unprocessable Content):**
```json
{
  "message": "Data yang diberikan tidak valid.",
  "errors": {
    "email": [
      "Email atau kata sandi salah."
    ]
  }
}
```

---

#### `POST /auth/logout`
- **Akses:** Terautentikasi (`auth:sanctum`)
- **Deskripsi:** Menghapus access token yang sedang aktif digunakan client.

**Contoh Request:**
```bash
curl -X POST http://localhost/api/v1/auth/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN>"
```

**Respons Sukses (200 OK):**
```json
{
  "message": "Berhasil logout."
}
```

---

#### `GET /me`
- **Akses:** Terautentikasi (`auth:sanctum`)
- **Deskripsi:** Mengambil informasi profil akun yang sedang login dan perannya.

**Contoh Request:**
```bash
curl -X GET http://localhost/api/v1/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN>"
```

**Respons Sukses (200 OK):**
```json
{
  "data": {
    "id": 2,
    "name": "Dr. Ir. Bambang Hermanto, M.Kom.",
    "email": "dosen@kampuslms.test",
    "role": "dosen",
    "nim_nip": "197508122003121001"
  }
}
```

---

### 2. Modul Mata Kuliah (Courses)

#### `GET /courses`
- **Akses:** Terautentikasi (`auth:sanctum`)
- **Deskripsi:** Mengembalikan daftar mata kuliah yang relevan:
  - Dosen: Mata kuliah yang diampu (`taughtCourses`).
  - Mahasiswa: Mata kuliah yang diikuti (`courses`).
  - Admin: Seluruh mata kuliah di sistem.
  - Sudah menerapkan **eager loading** dosen dan hitungan materi/tugas untuk mencegah query N+1.

**Contoh Request:**
```bash
curl -X GET http://localhost/api/v1/courses \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN>"
```

**Respons Sukses (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "code": "SI101",
      "name": "Pengantar Sistem Informasi",
      "description": "Membahas konsep dasar sistem informasi...",
      "sks": 3,
      "status": "active",
      "lecturer": {
        "id": 2,
        "name": "Dr. Ir. Bambang Hermanto, M.Kom.",
        "email": "dosen@kampuslms.test",
        "role": "dosen",
        "nim_nip": "197508122003121001"
      },
      "counts": {
        "materials": 4,
        "assignments": 3
      },
      "created_at": "2026-09-14T09:00:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

---

#### `GET /courses/{id}`
- **Akses:** Terautentikasi + Scope
- **Deskripsi:** Detail mata kuliah beserta eager loading data dosen dan total materi & tugas. Dosen hanya dapat membuka MK yang diampunya, mahasiswa hanya MK yang diikutinya.

**Contoh Request:**
```bash
curl -X GET http://localhost/api/v1/courses/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN>"
```

**Respons Sukses (200 OK):**
```json
{
  "data": {
    "id": 1,
    "code": "SI101",
    "name": "Pengantar Sistem Informasi",
    "description": "Membahas konsep dasar sistem informasi...",
    "sks": 3,
    "status": "active",
    "lecturer": {
      "id": 2,
      "name": "Dr. Ir. Bambang Hermanto, M.Kom.",
      "email": "dosen@kampuslms.test",
      "role": "dosen",
      "nim_nip": "197508122003121001"
    },
    "counts": {
      "materials": 4,
      "assignments": 3
    },
    "created_at": "2026-09-14T09:00:00+00:00"
  }
}
```

**Respons Gagal (403 Forbidden):**
```json
{
  "message": "Anda tidak memiliki akses ke sumber daya ini."
}
```

---

#### `GET /courses/{id}/materials`
- **Akses:** Terautentikasi + Scope
- **Deskripsi:** Mengambil seluruh materi pembelajaran untuk mata kuliah tertentu.

**Contoh Request:**
```bash
curl -X GET http://localhost/api/v1/courses/1/materials \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN>"
```

**Respons Sukses (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "week_number": 1,
      "title": "Materi Pertemuan 1: Pengantar SI",
      "description": "Slide pengantar konsep enterprise information system",
      "type": "file",
      "file_path": "materials/pertemuan_1.pdf",
      "original_name": "pertemuan_1.pdf",
      "file_size": 204800,
      "mime_type": "application/pdf",
      "external_url": null,
      "uploader": {
        "id": 2,
        "name": "Dr. Ir. Bambang Hermanto, M.Kom.",
        "email": "dosen@kampuslms.test",
        "role": "dosen",
        "nim_nip": "197508122003121001"
      },
      "created_at": "2026-09-14T10:00:00+00:00"
    }
  ]
}
```

---

#### `GET /courses/{id}/assignments`
- **Akses:** Terautentikasi + Scope
- **Query Parameter:**
  - `status` (string, optional): Filter status (`published`, `active`, `draft`). Khusus mahasiswa hanya diperkenankan melihat status yang aktif/terpublikasi.
  - `page` (integer, optional): Nomor halaman pagination.

**Contoh Request:**
```bash
curl -X GET "http://localhost/api/v1/courses/1/assignments?status=published&page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN>"
```

**Respons Sukses (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "created_by": 2,
      "week_number": 1,
      "title": "Tugas 1: Studi Kasus Peranan SI",
      "instructions": "Analisis implementasi sistem informasi pada ritel modern.",
      "due_at": "2026-10-15T23:59:00+00:00",
      "max_score": 100,
      "allow_late": true,
      "status": "published",
      "course": null,
      "creator": {
        "id": 2,
        "name": "Dr. Ir. Bambang Hermanto, M.Kom.",
        "email": "dosen@kampuslms.test",
        "role": "dosen",
        "nim_nip": "197508122003121001"
      },
      "submissions_count": 18,
      "created_at": "2026-09-14T11:00:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

---

### 3. Modul Tugas (Assignments)

#### `POST /assignments`
- **Akses:** Dosen pengampu mata kuliah atau Admin
- **Deskripsi:** Membuat penugasan baru. Dosen hanya dapat membuat tugas untuk MK yang diampunya.
- **Parameter Body:**
  - `course_id` (integer, required)
  - `title` (string, required, max:255)
  - `instructions` (string, required)
  - `due_at` (datetime string, required)
  - `max_score` (numeric, optional, default: 100)
  - `allow_late` (boolean, optional, default: true)
  - `status` (string, required: `draft` atau `published`)
  - `week_number` (integer, optional, between: 1-16)

**Contoh Request:**
```bash
curl -X POST http://localhost/api/v1/assignments \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN_DOSEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "course_id": 1,
    "title": "Tugas Analisis Data Flow Diagram (DFD)",
    "instructions": "Rancang diagram konteks dan DFD Level 0 s/d Level 1.",
    "due_at": "2026-10-20 23:59:00",
    "max_score": 100,
    "allow_late": true,
    "status": "published",
    "week_number": 6
  }'
```

**Respons Sukses (201 Created):**
```json
{
  "data": {
    "id": 16,
    "course_id": 1,
    "created_by": 2,
    "week_number": 6,
    "title": "Tugas Analisis Data Flow Diagram (DFD)",
    "instructions": "Rancang diagram konteks dan DFD Level 0 s/d Level 1.",
    "due_at": "2026-10-20T23:59:00+00:00",
    "max_score": 100,
    "allow_late": true,
    "status": "published",
    "course": {
      "id": 1,
      "code": "SI101",
      "name": "Pengantar Sistem Informasi"
    },
    "creator": {
      "id": 2,
      "name": "Dr. Ir. Bambang Hermanto, M.Kom.",
      "email": "dosen@kampuslms.test",
      "role": "dosen",
      "nim_nip": "197508122003121001"
    },
    "submissions_count": null,
    "created_at": "2026-10-04T12:00:00+00:00"
  }
}
```

---

#### `PUT/PATCH /assignments/{id}`
- **Akses:** Dosen pemilik tugas atau Admin
- **Deskripsi:** Memperbarui data tugas yang ada.

**Contoh Request:**
```bash
curl -X PUT http://localhost/api/v1/assignments/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN_DOSEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Tugas 1: Studi Kasus Peranan SI (Revisi)"
  }'
```

**Respons Sukses (200 OK):**
```json
{
  "data": {
    "id": 1,
    "title": "Tugas 1: Studi Kasus Peranan SI (Revisi)",
    "status": "published"
  }
}
```

---

#### `DELETE /assignments/{id}`
- **Akses:** Dosen pemilik tugas atau Admin
- **Deskripsi:** Menghapus tugas yang ditentukan.

**Contoh Request:**
```bash
curl -X DELETE http://localhost/api/v1/assignments/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN_DOSEN>"
```

**Respons Sukses (204 No Content):**
*(Badan respons kosong sesuai standar HTTP RESTful)*

---

#### `GET /assignments/{id}/submissions`
- **Akses:** Dosen pemilik tugas atau Admin
- **Deskripsi:** Mengambil seluruh berkas submission yang dikumpulkan mahasiswa, lengkap dengan identitas mahasiswa dan data nilai (jika sudah dinilai).

**Contoh Request:**
```bash
curl -X GET http://localhost/api/v1/assignments/1/submissions \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN_DOSEN>"
```

**Respons Sukses (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "assignment_id": 1,
      "user_id": 5,
      "file_path": "submissions/laporan_tugas.pdf",
      "original_name": "laporan_tugas.pdf",
      "file_size": 1048576,
      "note": "Catatan tugas",
      "submitted_at": "2026-09-20T14:30:00+00:00",
      "is_late": false,
      "student": {
        "id": 5,
        "name": "Muhammad Rizky Pratama",
        "email": "mahasiswa@kampuslms.test",
        "role": "mahasiswa",
        "nim_nip": "20210801001"
      },
      "grade": {
        "id": 1,
        "score": 88,
        "feedback": "Pengerjaan sangat sistematis."
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 2,
    "total": 18
  }
}
```

---

### 4. Modul Pengumpulan & Penilaian (Submissions & Grading)

#### `POST /assignments/{id}/submissions`
- **Akses:** Mahasiswa (terdaftar pada mata kuliah tugas tersebut)
- **Content-Type:** `multipart/form-data`
- **Deskripsi:** Mengunggah berkas pengumpulan tugas mahasiswa. Menolak submission ganda (1 mahasiswa 1 submission aktif) dan menolak pengumpulan terlambat jika `allow_late == false`.
- **Form Data:**
  - `file` (file, required, maks 10MB)
  - `note` (string, optional)

**Contoh Request:**
```bash
curl -X POST http://localhost/api/v1/assignments/1/submissions \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN_MAHASISWA>" \
  -F "file=@/path/to/laporan.pdf" \
  -F "note=Tugas pengantar SI pertemuan 1"
```

**Respons Sukses (201 Created):**
```json
{
  "data": {
    "id": 101,
    "assignment_id": 1,
    "user_id": 5,
    "file_path": "submissions/abcxyz123.pdf",
    "original_name": "laporan.pdf",
    "file_size": 524288,
    "note": "Tugas pengantar SI pertemuan 1",
    "submitted_at": "2026-10-04T12:30:00+00:00",
    "is_late": false,
    "student": {
      "id": 5,
      "name": "Muhammad Rizky Pratama",
      "email": "mahasiswa@kampuslms.test",
      "role": "mahasiswa",
      "nim_nip": "20210801001"
    },
    "grade": null
  }
}
```

**Respons Gagal (422 Unprocessable Content):**
```json
{
  "message": "Data yang diberikan tidak valid.",
  "errors": {
    "file": [
      "Anda sudah mengumpulkan tugas ini."
    ]
  }
}
```

---

#### `PUT /submissions/{id}/grade`
- **Akses:** Dosen pemilik tugas atau Admin
- **Deskripsi:** Memberikan atau memperbarui penilaian tugas mahasiswa (*upsert* via `updateOrCreate`). Mengembalikan **201** saat nilai dibuat pertama kali, dan **200** saat nilai diperbarui.
- **Parameter Body:**
  - `score` (numeric, required, min: 0, maks: max_score tugas)
  - `feedback` (string, optional, maks: 2000)

**Contoh Request:**
```bash
curl -X PUT http://localhost/api/v1/submissions/1/grade \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN_DOSEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "score": 90.5,
    "feedback": "Analisis kasus dan referensi sudah sangat baik."
  }'
```

**Respons Nilai Baru (201 Created) / Nilai Diperbarui (200 OK):**
```json
{
  "data": {
    "id": 51,
    "submission_id": 1,
    "graded_by": 2,
    "score": 90.5,
    "feedback": "Analisis kasus dan referensi sudah sangat baik.",
    "graded_at": "2026-10-04T12:45:00+00:00",
    "grader": {
      "id": 2,
      "name": "Dr. Ir. Bambang Hermanto, M.Kom.",
      "email": "dosen@kampuslms.test",
      "role": "dosen",
      "nim_nip": "197508122003121001"
    }
  }
}
```

---

### 5. Modul Notifikasi

#### `GET /notifications`
- **Akses:** Terautentikasi (`auth:sanctum`)
- **Deskripsi:** Mengambil daftar notifikasi milik pengguna yang sedang login.

**Contoh Request:**
```bash
curl -X GET http://localhost/api/v1/notifications \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN>"
```

**Respons Sukses (200 OK):**
```json
{
  "data": [
    {
      "id": "99d912e0-3d41-497e-8c90-e895dab87e22",
      "type": "App\\Notifications\\AssignmentCreated",
      "data": {
        "message": "Tugas baru telah ditambahkan pada SI101"
      },
      "read_at": null,
      "created_at": "2026-10-04T10:00:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

---

#### `POST /notifications/{id}/read`
- **Akses:** Terautentikasi (`auth:sanctum`)
- **Deskripsi:** Menandai satu notifikasi milik pengguna sebagai sudah dibaca (`read_at` terisi).

**Contoh Request:**
```bash
curl -X POST http://localhost/api/v1/notifications/99d912e0-3d41-497e-8c90-e895dab87e22/read \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN>"
```

**Respons Sukses (200 OK):**
```json
{
  "data": {
    "id": "99d912e0-3d41-497e-8c90-e895dab87e22",
    "type": "App\\Notifications\\AssignmentCreated",
    "data": {
      "message": "Tugas baru telah ditambahkan pada SI101"
    },
    "read_at": "2026-10-04T12:50:00+00:00",
    "created_at": "2026-10-04T10:00:00+00:00"
  }
}
```
