<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * Memenuhi spec §4.4:
     *  - 1 admin, 3 dosen, 30 mahasiswa (semua nama orang Indonesia)
     *  - 5 MK Program Studi Sistem Informasi, tiap MK ≥ 15 mahasiswa
     *  - 3 tugas per MK (campuran pastDeadline / active / draft)
     *  - ≥ 100 submission, ~60% dinilai
     *  - 3 akun demo wajib
     */
    public function run(): void
    {
        // ── 1. AKUN DEMO WAJIB & PENGGUNA ──────────────────────────────────
        // Menggunakan firstOrCreate agar migrate:refresh --seed tidak error duplicate.

        $admin = User::firstOrCreate(
            ['email' => 'admin@kampuslms.test'],
            [
                'name'              => 'Budi Santoso (Admin)',
                'role'              => 'admin',
                'nim_nip'           => null,
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 3 Dosen dengan nama orang Indonesia dan gelar akademik
        $lecturersData = [
            [
                'name'    => 'Dr. Ir. Bambang Hermanto, M.Kom.',
                'email'   => 'dosen@kampuslms.test', // Akun demo dosen
                'nim_nip' => '197508122003121001',
            ],
            [
                'name'    => 'Siti Rahmawati, S.Kom., M.Cs.',
                'email'   => 'siti.rahmawati@kampuslms.test',
                'nim_nip' => '198203152008122002',
            ],
            [
                'name'    => 'Ahmad Fauzi, S.T., M.T.',
                'email'   => 'ahmad.fauzi@kampuslms.test',
                'nim_nip' => '198711202015041003',
            ],
        ];

        $dosenList = collect();
        foreach ($lecturersData as $lec) {
            $lecturer = User::firstOrCreate(
                ['email' => $lec['email']],
                [
                    'name'              => $lec['name'],
                    'role'              => 'dosen',
                    'nim_nip'           => $lec['nim_nip'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $dosenList->push($lecturer);
        }

        // Akun demo mahasiswa
        $demoStudent = User::firstOrCreate(
            ['email' => 'mahasiswa@kampuslms.test'],
            [
                'name'              => 'Muhammad Rizky Pratama',
                'role'              => 'mahasiswa',
                'nim_nip'           => '20210801001',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 29 Mahasiswa lainnya (total 30 mahasiswa) dengan nama orang Indonesia
        $otherStudents = User::factory()->mahasiswa()->count(29)->create();
        $allStudents = collect([$demoStudent])->merge($otherStudents);

        // ── 2. MATA KULIAH SISTEM INFORMASI ───────────────────────────────
        $siCoursesData = [
            [
                'code'        => 'SI101',
                'name'        => 'Pengantar Sistem Informasi',
                'sks'         => 3,
                'lecturer_id' => $dosenList[0]->id,
                'status'      => 'active',
                'description' => 'Membahas konsep dasar sistem informasi, peranan teknologi informasi dalam keunggulan kompetitif organisasi bisnis, komponen sistem komputer, dan strategi transformasi digital enterprise.',
                'assignments' => [
                    [
                        'title'        => 'Tugas 1: Studi Kasus Peranan SI dalam Industri Ritel',
                        'instructions' => 'Analisis implementasi sistem informasi pada salah satu ritel modern di Indonesia. Jelaskan bagaimana TI memberikan keunggulan kompetitif. Unggah berkas dalam format PDF.',
                        'state'        => 'pastDeadline',
                    ],
                    [
                        'title'        => 'Tugas 2: Evaluasi Model Bisnis E-Commerce Nasional',
                        'instructions' => 'Pilihlah salah satu platform e-commerce di Indonesia. Lakukan evaluasi arsitektur proses bisnis dan berikan analisis SWOT terhadap sistem informasi mereka.',
                        'state'        => 'active',
                    ],
                    [
                        'title'        => 'Tugas 3: Usulan Inovasi SI Sektor Pelayanan Publik',
                        'instructions' => 'Buat proposal ringkas perancangan inovasi sistem informasi untuk mengatasi kendala pelayanan publik di instansi pemerintah daerah setempat.',
                        'state'        => 'draft',
                    ],
                ],
            ],
            [
                'code'        => 'SI201',
                'name'        => 'Analisis dan Perancangan Sistem Informasi',
                'sks'         => 4,
                'lecturer_id' => $dosenList[1]->id,
                'status'      => 'active',
                'description' => 'Metodologi analisis dan perancangan sistem berbasis UML (Unified Modeling Language), pemodelan use case, activity diagram, sequence diagram, perancangan antarmuka pengguna (UI/UX), serta spesifikasi kebutuhan perangkat lunak.',
                'assignments' => [
                    [
                        'title'        => 'Tugas 1: Penyusunan Dokumen Spesifikasi Kebutuhan Perangkat Lunak (SKPL)',
                        'instructions' => 'Susun dokumen SKPL / SRS sesuai standar IEEE 830 berdasarkan studi kasus yang telah ditentukan. Identifikasi kebutuhan fungsional dan non-fungsional sistem secara terstruktur.',
                        'state'        => 'pastDeadline',
                    ],
                    [
                        'title'        => 'Tugas 2: Pemodelan UML: Use Case Diagram dan Skenario Naratif',
                        'instructions' => 'Gambarkan Use Case Diagram sistem dan tuliskan Use Case Narrative untuk minimal 3 proses utama. Sertakan pula Activity Diagram untuk alur proses kritis.',
                        'state'        => 'active',
                    ],
                    [
                        'title'        => 'Tugas 3: Desain Prototipe UI/UX Interaktif High-Fidelity',
                        'instructions' => 'Rancang antarmuka sistem (UI/UX) interaktif menggunakan alat prototyping seperti Figma. Sertakan tautan prototipe yang dapat dicoba pada dokumen PDF.',
                        'state'        => 'draft',
                    ],
                ],
            ],
            [
                'code'        => 'SI202',
                'name'        => 'Sistem Manajemen Basis Data',
                'sks'         => 3,
                'lecturer_id' => $dosenList[2]->id,
                'status'      => 'active',
                'description' => 'Konsep dasar basis data relasional, pemodelan Entity-Relationship Diagram (ERD), normalisasi data (1NF sampai 3NF), perancangan skema relasional, manipulasi data menggunakan Structured Query Language (SQL), indexing, dan administrasi database.',
                'assignments' => [
                    [
                        'title'        => 'Tugas 1: Pemodelan Konseptual ERD dan Normalisasi Basis Data Hingga 3NF',
                        'instructions' => 'Buat Entity Relationship Diagram (ERD) lengkap dengan entitas, atribut kunci, dan kardinalitas. Lakukan proses normalisasi bertahap dari unnormalized data hingga 3NF.',
                        'state'        => 'pastDeadline',
                    ],
                    [
                        'title'        => 'Tugas 2: Implementasi Skrip DDL dan Kueri SQL Lanjut (Join & Subquery)',
                        'instructions' => 'Tuliskan perintah DDL untuk pembuatan tabel dan relasi foreign key, lalu selesaikan 10 skenario kueri SQL menggunakan multi-table JOIN, GROUP BY, dan subqueries.',
                        'state'        => 'active',
                    ],
                    [
                        'title'        => 'Tugas 3: Optimasi Performa Query Menggunakan Indexing dan Stored Procedure',
                        'instructions' => 'Lakukan benchmarking kecepatan kueri sebelum dan sesudah penambahan indeks B-Tree. Rancang minimal dua Stored Procedure untuk transaksi data penting.',
                        'state'        => 'draft',
                    ],
                ],
            ],
            [
                'code'        => 'SI301',
                'name'        => 'Manajemen Proyek Sistem Informasi',
                'sks'         => 3,
                'lecturer_id' => $dosenList[0]->id,
                'status'      => 'active',
                'description' => 'Prinsip manajemen proyek TI berbasis kerangka kerja PMBOK dan Agile/Scrum. Topik mencakup perencanaan proyek, Work Breakdown Structure (WBS), estimasi jadwal dan anggaran, manajemen risiko sistem, serta kepemimpinan tim pengembang.',
                'assignments' => [
                    [
                        'title'        => 'Tugas 1: Penyusunan Project Charter dan Analisis Matriks Stakeholder',
                        'instructions' => 'Susun dokumen Project Charter lengkap dengan tujuan bisnis proyek, kriteria sukses, batasan anggaran, serta matriks pemangku kepentingan (power/interest grid).',
                        'state'        => 'pastDeadline',
                    ],
                    [
                        'title'        => 'Tugas 2: Pembuatan Work Breakdown Structure (WBS) dan Gantt Chart',
                        'instructions' => 'Rinci seluruh aktivitas proyek perangkat lunak ke dalam struktur hierarki WBS level 3. Hitung Critical Path dan sajikan jadwal dalam bentuk Gantt Chart.',
                        'state'        => 'active',
                    ],
                    [
                        'title'        => 'Tugas 3: Penyusunan Risk Register dan Perencanaan Mitigasi Risiko TI',
                        'instructions' => 'Identifikasi 8 risiko potensial pada proyek implementasi sistem informasi. Nilai skor risiko dan tentukan tindakan pencegahan serta contingency plan.',
                        'state'        => 'draft',
                    ],
                ],
            ],
            [
                'code'        => 'SI302',
                'name'        => 'Enterprise Resource Planning (ERP)',
                'sks'         => 3,
                'lecturer_id' => $dosenList[1]->id,
                'status'      => 'active',
                'description' => 'Konsep dan implementasi sistem enterprise terintegrasi yang mencakup siklus rantai pasok (supply chain), modul pengadaan, penjualan, akuntansi finansial, serta simulasi proses bisnis korporasi.',
                'assignments' => [
                    [
                        'title'        => 'Tugas 1: Analisis Alur Proses Bisnis Procure-to-Pay (P2P)',
                        'instructions' => 'Petakan alur bisnis Procure-to-Pay end-to-end mulai dari Purchase Requisition, PO, Goods Receipt, hingga Vendor Invoice. Jelaskan integrasi antar-modul ERP.',
                        'state'        => 'pastDeadline',
                    ],
                    [
                        'title'        => 'Tugas 2: Simulasi Konfigurasi Modul Sales dan Distribusi ERP',
                        'instructions' => 'Lakukan input master data pelanggan dan produk pada sistem ERP. Jalankan simulasi transaksi Order-to-Cash dan dokumentasikan setiap langkahnya.',
                        'state'        => 'active',
                    ],
                    [
                        'title'        => 'Tugas 3: Analisis Dashboard Kinerja Operasional dan Finansial ERP',
                        'instructions' => 'Rancang format laporan analitik eksekutif berbasis data modul penjualan dan persediaan pada sistem ERP untuk mendukung pengambilan keputusan manajemen.',
                        'state'        => 'draft',
                    ],
                ],
            ],
        ];

        $courses = collect();
        $assignmentConfigs = collect();

        foreach ($siCoursesData as $cData) {
            $assignmentsInfo = $cData['assignments'];
            unset($cData['assignments']);

            $course = Course::firstOrCreate(
                ['code' => $cData['code']],
                $cData
            );
            $courses->push($course);

            $assignmentConfigs->put($course->id, $assignmentsInfo);
        }

        // ── 3. ENROLL MAHASISWA (≥ 15 per MK) ────────────────────────────
        foreach ($courses as $course) {
            // Pastikan mahasiswa demo selalu ter-enroll di setiap MK
            // Ditambah 14 mahasiswa lain secara acak -> total 15 mahasiswa
            $otherRandom = $otherStudents->random(14)->pluck('id');
            $enrolled = collect([$demoStudent->id])->merge($otherRandom)->unique()->values();

            $course->students()->sync(
                $enrolled->mapWithKeys(fn ($id) => [
                    $id => ['enrolled_at' => now()->subDays(rand(10, 60))],
                ])->all()
            );
        }

        // ── 4. TUGAS (3 per MK, campuran status) ─────────────────────────
        foreach ($courses as $course) {
            $assignments = $assignmentConfigs->get($course->id);

            foreach ($assignments as $task) {
                $factoryState = $task['state'];

                Assignment::factory()->{$factoryState}()->create([
                    'course_id'    => $course->id,
                    'created_by'   => $course->lecturer_id,
                    'title'        => $task['title'],
                    'instructions' => $task['instructions'],
                ]);
            }
        }

        // ── 5. SUBMISSION (≥ 100, merata per assignment) ─────────────────
        $allAssignments = Assignment::with('course.students')->get();
        $createdSubmissions = collect();

        foreach ($allAssignments as $assignment) {
            $students = $assignment->course->students;

            foreach ($students as $student) {
                // Hindari duplikat (unique constraint assignment_id + user_id)
                $alreadySubmitted = $createdSubmissions
                    ->where('assignment_id', $assignment->id)
                    ->where('user_id', $student->id)
                    ->isNotEmpty();

                if ($alreadySubmitted) {
                    continue;
                }

                $submission = Submission::factory()->create([
                    'assignment_id' => $assignment->id,
                    'user_id'       => $student->id,
                ]);

                $createdSubmissions->push([
                    'id'            => $submission->id,
                    'assignment_id' => $assignment->id,
                    'user_id'       => $student->id,
                    'created_by'    => $assignment->created_by,
                ]);
            }
        }

        // ── 6. NILAI (~60% submission dinilai) ───────────────────────────
        $allSubmissions = Submission::with('assignment')->get();

        $toGrade = $allSubmissions->random(
            (int) floor($allSubmissions->count() * 0.6)
        );

        foreach ($toGrade as $submission) {
            Grade::factory()->create([
                'submission_id' => $submission->id,
                'graded_by'     => $submission->assignment->created_by,
                'graded_at'     => now()->subDays(rand(1, 30)),
            ]);
        }
    }
}