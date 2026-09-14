<?php

namespace Database\Factories;

use App\Models\Assignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    /**
     * Contoh judul tugas Sistem Informasi.
     */
    protected static array $assignmentTitles = [
        'Analisis Kebutuhan Sistem Informasi dan Studi Kelayakan Proyek',
        'Pemodelan Proses Bisnis (BPMN) dan Diagram Alir Data (DFD)',
        'Perancangan Antarmuka Pengguna (UI/UX) dan Evaluasi Heuristik',
        'Normalisasi Basis Data dan Desain Skema Relasional 3NF',
        'Implementasi Query SQL Analitik dan Optimasi Indeks Database',
        'Penyusunan Jadwal Proyek TI Menggunakan Work Breakdown Structure',
        'Audit Tata Kelola Keamanan Informasi Berdasarkan ISO 27001',
        'Pengembangan RESTful API dan Integrasi Antar-Sistem',
        'Konfigurasi Modul Order-to-Cash pada Platform Sistem ERP',
        'Visualisasi Data Bisnis dan Pembuatan Executive Dashboard',
        'Dokumen Spesifikasi Kebutuhan Perangkat Lunak (SKPL / SRS)',
        'Pengujian Sistem: Blackbox Testing dan User Acceptance Test (UAT)',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id'    => \App\Models\Course::factory(),
            'created_by'   => \App\Models\User::factory(),
            'title'        => fake()->randomElement(static::$assignmentTitles),
            'instructions' => 'Selesaikan tugas sesuai dengan instruksi modul perkuliahan. Buat laporan analisis terstruktur dan kumpulkan dalam format dokumen PDF sebelum batas waktu yang telah ditentukan.',
            'due_at'       => fake()->dateTimeBetween('-1 month', '+1 month'),
            'max_score'    => 100,
            'allow_late'   => fake()->boolean(),
            'status'       => fake()->randomElement(['draft', 'active', 'closed']),
        ];
    }

    /**
     * State: tugas sudah lewat deadline (past due).
     */
    public function pastDeadline(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => fake()->dateTimeBetween('-3 months', '-1 day'),
            'status' => 'closed',
        ]);
    }

    /**
     * State: tugas aktif dan belum lewat deadline.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => fake()->dateTimeBetween('+1 day', '+1 month'),
            'status' => 'active',
        ]);
    }

    /**
     * State: tugas masih draft, belum dipublish.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => fake()->dateTimeBetween('+1 week', '+2 months'),
            'status' => 'draft',
        ]);
    }
}