<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseWebTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * Admin dapat melihat halaman daftar seluruh mata kuliah dan pagination.
     */
    public function test_admin_dapat_melihat_halaman_daftar_seluruh_mata_kuliah_dan_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = User::factory()->dosen()->create();

        // Buat 16 mata kuliah untuk memicu pagination (15 per halaman)
        $courses = Course::factory()->count(16)->create([
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);

        $firstCourse = $courses->first();

        // Buka halaman index
        $response = $this->actingAs($admin)->get(route('mata-kuliah.index'));

        $response->assertOk()
            ->assertViewIs('courses.index')
            ->assertSee($firstCourse->name)
            ->assertSee('Daftar Mata Kuliah');

        // Buka halaman 2 pagination
        $responsePage2 = $this->actingAs($admin)->get(route('mata-kuliah.index', ['page' => 2]));
        $responsePage2->assertOk();
    }

    /**
     * Admin dapat mengakses halaman formulir penambahan mata kuliah baru.
     */
    public function test_admin_dapat_mengakses_halaman_formulir_tambah_mata_kuliah(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('mata-kuliah.create'));

        $response->assertOk()
            ->assertViewIs('courses.create')
            ->assertSee('Tambah Mata Kuliah')
            ->assertSee('Kode Mata Kuliah')
            ->assertSee('Nama Mata Kuliah');
    }

    /**
     * Admin dapat membuat mata kuliah baru dengan form request valid.
     */
    public function test_admin_dapat_membuat_mata_kuliah_baru_dengan_form_request_valid(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = User::factory()->dosen()->create();

        $payload = [
            'code' => 'CS101',
            'name' => 'Algoritma dan Pemrograman',
            'description' => 'Mata kuliah dasar pemrograman dan algoritma komputasi.',
            'sks' => 3,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ];

        $response = $this->actingAs($admin)->post(route('mata-kuliah.store'), $payload);

        $response->assertRedirect(route('mata-kuliah.index'))
            ->assertSessionHas('success', 'Mata kuliah berhasil ditambahkan.');

        $this->assertDatabaseHas('courses', [
            'code' => 'CS101',
            'name' => 'Algoritma dan Pemrograman',
            'sks' => 3,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);
    }

    /**
     * Pembuatan mata kuliah gagal dan menampilkan error validasi jika field wajib kosong.
     */
    public function test_pembuatan_mata_kuliah_gagal_dan_menampilkan_error_validasi_jika_field_wajib_kosong(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('mata-kuliah.store'), []);

        $response->assertSessionHasErrors(['name', 'code', 'sks', 'lecturer_id', 'status']);
        $this->assertDatabaseMissing('courses', [
            'name' => '',
            'code' => '',
        ]);
    }

    /**
     * Pembuatan mata kuliah gagal jika kode mata kuliah sudah digunakan (unique constraint).
     */
    public function test_pembuatan_mata_kuliah_gagal_jika_kode_mata_kuliah_sudah_digunakan(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = User::factory()->dosen()->create();

        $existingCourse = Course::factory()->create([
            'code' => 'UNIQUE101',
            'lecturer_id' => $dosen->id,
        ]);

        $payload = [
            'code' => 'UNIQUE101',
            'name' => 'Duplikat Kode Mata Kuliah',
            'description' => 'Mata kuliah dengan kode yang sudah ada.',
            'sks' => 3,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ];

        $response = $this->actingAs($admin)->post(route('mata-kuliah.store'), $payload);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * Dosen pengampu dapat memperbarui deskripsi mata kuliah miliknya.
     */
    public function test_dosen_pengampu_dapat_memperbarui_deskripsi_mata_kuliah_miliknya(): void
    {
        $dosenPengampu = User::factory()->dosen()->create();
        $uniqueCode = 'UPD-' . strtoupper(substr(uniqid(), -5));
        $course = Course::factory()->create([
            'lecturer_id' => $dosenPengampu->id,
            'code' => $uniqueCode,
            'name' => 'Manajemen Proyek SI',
            'description' => 'Deskripsi lama perkuliahan.',
            'sks' => 3,
            'status' => 'active',
        ]);

        $payload = [
            'code' => $course->code,
            'name' => $course->name,
            'description' => 'Deskripsi baru setelah diperbarui oleh dosen pengampu resmi.',
            'sks' => $course->sks,
            'lecturer_id' => $dosenPengampu->id,
            'status' => 'active',
        ];

        $response = $this->actingAs($dosenPengampu)->put(route('mata-kuliah.update', $course), $payload);

        $response->assertRedirect(route('mata-kuliah.index'))
            ->assertSessionHas('success', 'Mata kuliah berhasil diperbarui.');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'description' => 'Deskripsi baru setelah diperbarui oleh dosen pengampu resmi.',
        ]);
    }

    /**
     * Dosen pengampu dapat melihat halaman detail mata kuliah miliknya.
     */
    public function test_dosen_pengampu_dapat_melihat_halaman_detail_mata_kuliah(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        $response = $this->actingAs($dosen)->get(route('mata-kuliah.show', $course));

        $response->assertOk()
            ->assertViewIs('courses.show')
            ->assertSee($course->name)
            ->assertSee($course->code);
    }

    /**
     * Admin dapat menghapus mata kuliah dan cascading data berjalan aman.
     */
    public function test_admin_dapat_menghapus_mata_kuliah_dan_cascading_data_berjalan_aman(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        // Buat materi berfile fisik
        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $dosen->id,
            'title' => 'Materi Kuliah Cascading Test',
            'type' => 'file',
            'file_path' => "materials/{$course->id}/cascade-test.pdf",
            'original_name' => 'cascade-test.pdf',
        ]);
        Storage::disk('local')->put($material->file_path, '%PDF-1.4 Cascading Test');

        // Buat tugas dan submission
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
        ]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => "submissions/{$assignment->id}/cascade-sub.pdf",
        ]);
        Storage::disk('local')->put($submission->file_path, '%PDF-1.4 Cascading Submission');

        // Admin menghapus mata kuliah
        $response = $this->actingAs($admin)->delete(route('mata-kuliah.destroy', $course));

        $response->assertRedirect(route('mata-kuliah.index'))
            ->assertSessionHas('success', 'Mata kuliah berhasil dihapus.');

        // Verifikasi database cascading
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);

        // Verifikasi berkas fisik terhapus dari storage
        Storage::disk('local')->assertMissing("materials/{$course->id}/cascade-test.pdf");
        Storage::disk('local')->assertMissing("submissions/{$assignment->id}/cascade-sub.pdf");
    }
}
