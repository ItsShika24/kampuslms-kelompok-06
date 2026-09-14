<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    /**
     * Daftar referensi mata kuliah Program Studi Sistem Informasi.
     */
    protected static array $siCourses = [
        [
            'code' => 'SI101',
            'name' => 'Pengantar Sistem Informasi',
            'sks' => 3,
            'description' => 'Membahas konsep dasar sistem informasi, peranan teknologi informasi dalam organisasi bisnis, komponen sistem, dan transformasi digital.',
        ],
        [
            'code' => 'SI201',
            'name' => 'Analisis dan Perancangan Sistem Informasi',
            'sks' => 4,
            'description' => 'Prinsip dan metodologi rekayasa kebutuhan sistem, perancangan diagram UML, pemodelan use case, dan perancangan antarmuka pengguna.',
        ],
        [
            'code' => 'SI202',
            'name' => 'Sistem Manajemen Basis Data',
            'sks' => 3,
            'description' => 'Perancangan konseptual ERD, normalisasi database, query SQL terstruktur, indexing, dan administrasi basis data relasional enterprise.',
        ],
        [
            'code' => 'SI203',
            'name' => 'Pemrograman Berbasis Web',
            'sks' => 3,
            'description' => 'Pengembangan aplikasi web modern full-stack menggunakan arsitektur MVC, RESTful API, framework Laravel, dan antarmuka responsif.',
        ],
        [
            'code' => 'SI301',
            'name' => 'Manajemen Proyek Sistem Informasi',
            'sks' => 3,
            'description' => 'Metodologi manajemen proyek berbasis Agile dan PMBOK, perencanaan jadwal, estimasi anggaran, mitigasi risiko, serta pengelolaan tim pengembang.',
        ],
        [
            'code' => 'SI302',
            'name' => 'Enterprise Resource Planning (ERP)',
            'sks' => 3,
            'description' => 'Integrasi proses bisnis enterprise (pengadaan, penjualan, inventaris, akuntansi), konfigurasi modul sistem ERP, dan studi implementasi industri.',
        ],
        [
            'code' => 'SI303',
            'name' => 'Tata Kelola dan Audit Sistem Informasi',
            'sks' => 3,
            'description' => 'Penerapan kerangka kerja COBIT, ITIL, standar ISO 27001, pelaksanaan audit kontrol internal, dan kepatuhan sistem informasi korporat.',
        ],
        [
            'code' => 'SI304',
            'name' => 'Interaksi Manusia dan Komputer',
            'sks' => 3,
            'description' => 'Prinsip User Experience (UX), User Interface (UI), usability testing, perancangan wireframe interaktif, dan evaluasi heuristik antarmuka.',
        ],
        [
            'code' => 'SI401',
            'name' => 'Kecerdasan Bisnis dan Analitika Data',
            'sks' => 3,
            'description' => 'Data warehousing, proses ETL (Extract, Transform, Load), visualisasi data analitik, dan penerapan machine learning untuk keputusan bisnis.',
        ],
        [
            'code' => 'SI402',
            'name' => 'Keamanan Sistem Informasi & Kriptografi',
            'sks' => 3,
            'description' => 'Prinsip keamanan informasi CIA triad, autentikasi dan otorisasi, enkripsi data, penanganan insiden keamanan siber, dan vulnerability assessment.',
        ],
        [
            'code' => 'SI403',
            'name' => 'Arsitektur Enterprise',
            'sks' => 3,
            'description' => 'Penyusunan kerangka kerja TOGAF, alignment strategi bisnis dan strategi TI, serta blueprint arsitektur aplikasi dan teknologi organisasi.',
        ],
        [
            'code' => 'SI404',
            'name' => 'E-Business dan Inovasi Digital',
            'sks' => 3,
            'description' => 'Model bisnis digital B2B dan B2C, arsitektur platform e-commerce, sistem pembayaran digital, dan strategi pemasaran digital.',
        ],
    ];

    public function definition(): array
    {
        $course = fake()->randomElement(static::$siCourses);

        return [
            'code' => fake()->unique()->numerify('SI###'),
            'name' => $course['name'],
            'description' => $course['description'],
            'sks' => $course['sks'],
            'lecturer_id' => null,
            'status' => fake()->randomElement(['draft', 'active', 'archived']),
        ];
    }
}