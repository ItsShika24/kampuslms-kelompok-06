<?php

namespace Database\Factories;

use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filenames = [
            'Laporan_Tugas_Sistem_Informasi.pdf',
            'Tugas_Analisis_Perancangan.pdf',
            'Desain_ERD_dan_Query_SQL.pdf',
            'Dokumen_SKPL_Kelompok.pdf',
            'Konfigurasi_Modul_ERP.pdf',
            'Laporan_WBS_dan_Jadwal_Proyek.pdf',
            'Evaluasi_UI_UX_Prototype.pdf',
            'Analisis_Studi_Kasus_Bisnis.pdf',
        ];

        $notes = [
            'Mohon izin mengumpulkan tugas, terima kasih banyak Bapak/Ibu dosen.',
            'Tugas telah diselesaikan sesuai panduan modul perkuliahan.',
            'Berikut berkas laporan tugas yang telah disusun bersama kelompok.',
            'Tugas analisis sistem telah diperiksa dan disesuaikan.',
            'Mohon maaf atas keterlambatannya, terima kasih.',
        ];

        return [
            'assignment_id' => \App\Models\Assignment::factory(),
            'user_id'       => \App\Models\User::factory(),
            'file_path'     => 'submissions/' . fake()->uuid() . '.pdf',
            'original_name' => fake()->randomElement($filenames),
            'file_size'     => fake()->numberBetween(50000, 2500000),
            'note'          => fake()->optional(0.6)->randomElement($notes),
            'submitted_at'  => fake()->dateTimeBetween('-1 month', 'now'),
            'is_late'       => fake()->boolean(20),
        ];
    }
}