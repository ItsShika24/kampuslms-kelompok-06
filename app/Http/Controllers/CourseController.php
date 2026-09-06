<?php

namespace App\Http\Controllers;

class CourseController extends Controller
{
    // Menampilkan daftar seluruh mata kuliah.
    public function index()
    {
        $courses = [
            [
                'id' => 1,
                'code' => 'SI101',
                'name' => 'Pemrograman Web',
                'sks' => 3,
                'semester' => 5,
                'dosen' => 'Dr. Budi Santoso, M.Kom.',
            ],
            [
                'id' => 2,
                'code' => 'SI102',
                'name' => 'Basis Data',
                'sks' => 3,
                'semester' => 5,
                'dosen' => 'Siti Rahmawati, M.Kom.',
            ],
            [
                'id' => 3,
                'code' => 'SI103',
                'name' => 'Analisis dan Perancangan Sistem',
                'sks' => 3,
                'semester' => 5,
                'dosen' => 'Andi Pratama, M.Kom.',
            ],
        ];

        // Mengirim data mata kuliah ke halaman daftar.
        return view('courses.index', compact('courses'));
    }

    // Menampilkan detail satu mata kuliah berdasarkan ID.
    public function show($mataKuliah)
    {
        $courses = [
            [
                'id' => 1,
                'code' => 'SI101',
                'name' => 'Pemrograman Web',
                'sks' => 3,
                'semester' => 5,
                'dosen' => 'Dr. Budi Santoso, M.Kom.',
            ],
            [
                'id' => 2,
                'code' => 'SI102',
                'name' => 'Basis Data',
                'sks' => 3,
                'semester' => 5,
                'dosen' => 'Siti Rahmawati, M.Kom.',
            ],
            [
                'id' => 3,
                'code' => 'SI103',
                'name' => 'Analisis dan Perancangan Sistem',
                'sks' => 3,
                'semester' => 5,
                'dosen' => 'Andi Pratama, M.Kom.',
            ],
        ];

        // Mencari satu mata kuliah berdasarkan ID dari URL.
        $course = collect($courses)->firstWhere('id', (int) $mataKuliah);

        return view('courses.show', compact('course'));
    }
}