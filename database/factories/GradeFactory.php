<?php

namespace Database\Factories;

use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $feedbacks = [
            'Kerja bagus! Analisis kebutuhan dan diagram alur sangat mendalam dan terstruktur.',
            'Penjelasan use case diagram sudah tepat, namun skenario alternatif perlu diperjelas.',
            'Format penulisan dokumen dan standarisasi diagram sudah sangat baik. Lanjutkan!',
            'Perlu perbaikan pada normalisasi tabel database terutama aturan bentuk ketiga (3NF).',
            'Dokumentasi sangat rapi, metodologi yang digunakan relevan dengan permasalahan.',
            'Cukup baik, tetapi referensi pustaka masih kurang mutakhir. Perbanyak rujukan jurnal.',
            'Implementasi kueri SQL sudah berjalan baik dan efisien dalam penggunaan indeks.',
            'Struktur WBS proyek sistem informasi sudah komprehensif dan realistis.',
        ];

        return [
            'submission_id' => \App\Models\Submission::factory(),
            'graded_by'     => \App\Models\User::factory(),
            'score'         => fake()->randomFloat(2, 60, 100),
            'feedback'      => fake()->optional(0.7)->randomElement($feedbacks),
            'graded_at'     => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
