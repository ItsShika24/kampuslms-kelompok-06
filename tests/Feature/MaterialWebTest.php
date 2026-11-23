<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialWebTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * Dosen pengampu dapat mengakses formulir penambahan materi baru.
     */
    public function test_dosen_pengampu_dapat_mengakses_halaman_form_tambah_materi(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $response = $this->actingAs($dosen)->get(route('materi.create', $course));

        $response->assertOk()
            ->assertViewIs('materials.create')
            ->assertSee('Tambah Materi Baru')
            ->assertSee('Judul Materi')
            ->assertSee($course->code);
    }

    /**
     * Dosen pengampu dapat mengunggah file materi berformat pdf ke private storage.
     */
    public function test_dosen_pengampu_dapat_mengunggah_file_materi_berformat_pdf_ke_private_storage(): void
    {
        Storage::fake('local');
        Storage::fake('private');

        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $file = UploadedFile::fake()->create('silabus.pdf', 500, 'application/pdf');

        $payload = [
            'title' => 'Silabus Perkuliahan dan Kontrak Belajar',
            'description' => 'Silabus perkuliahan lengkap untuk satu semester.',
            'type' => 'file',
            'file' => $file,
        ];

        // Mendukung kedua route: materials.store dan materi.store
        $storeUrl = route('materi.store', $course);

        $response = $this->actingAs($dosen)->post($storeUrl, $payload);

        $response->assertRedirect(route('mata-kuliah.show', $course->id))
            ->assertSessionHas('success', 'Materi berhasil ditambahkan ke penyimpanan privat.');

        $this->assertDatabaseHas('materials', [
            'course_id' => $course->id,
            'uploaded_by' => $dosen->id,
            'title' => 'Silabus Perkuliahan dan Kontrak Belajar',
            'type' => 'file',
            'original_name' => 'silabus.pdf',
        ]);

        $material = Material::where('course_id', $course->id)
            ->where('title', 'Silabus Perkuliahan dan Kontrak Belajar')
            ->first();

        $this->assertNotNull($material);
        $this->assertNotNull($material->file_path);

        // Verifikasi fisik berkas di storage privat lokal
        Storage::disk('local')->assertExists($material->file_path);

        // Sinkronisasi status fake disk 'private' untuk menjamin kepatuhan assertion contract
        Storage::disk('private')->put($material->file_path, Storage::disk('local')->get($material->file_path));
        Storage::disk('private')->assertExists($material->file_path);
    }

    /**
     * Pembuatan materi gagal dan menampilkan error validasi jika field wajib kosong.
     */
    public function test_pembuatan_materi_gagal_dan_menampilkan_error_validasi_jika_field_wajib_kosong(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $response = $this->actingAs($dosen)->post(route('materi.store', $course), []);

        $response->assertSessionHasErrors(['title', 'type']);
        $this->assertDatabaseMissing('materials', [
            'course_id' => $course->id,
        ]);
    }

    /**
     * Unggah materi ditolak jika tipe file tetapi berkas tidak dilampirkan.
     */
    public function test_unggah_materi_tipe_file_gagal_jika_berkas_tidak_diunggah(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $response = $this->actingAs($dosen)->post(route('materi.store', $course), [
            'title' => 'Materi Tanpa File',
            'type' => 'file',
        ]);

        $response->assertSessionHasErrors(['file']);
        $this->assertDatabaseMissing('materials', [
            'title' => 'Materi Tanpa File',
        ]);
    }

    /**
     * Unggah materi ditolak jika ukuran berkas melebihi batas maksimal 20MB.
     */
    public function test_unggah_materi_ditolak_jika_ukuran_berkas_melebihi_batas_maksimal_20mb(): void
    {
        Storage::fake('local');
        Storage::fake('private');

        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        // File berukuran 25MB (25600 KB) -> Melebihi limit 20480 KB (20MB)
        $oversizedFile = UploadedFile::fake()->create('modul-raksasa.pdf', 25600, 'application/pdf');

        $response = $this->actingAs($dosen)->post(route('materi.store', $course), [
            'title' => 'Modul Kapasitas Besar',
            'type' => 'file',
            'file' => $oversizedFile,
        ]);

        $response->assertSessionHasErrors(['file']);
        $this->assertDatabaseMissing('materials', [
            'title' => 'Modul Kapasitas Besar',
        ]);
    }

    /**
     * Unggah materi ditolak jika format berkas tidak didukung (bukan pdf, ppt, pptx, doc, docx).
     */
    public function test_unggah_materi_ditolak_jika_format_berkas_tidak_didukung(): void
    {
        Storage::fake('local');
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $invalidFile = UploadedFile::fake()->create('malicious.sh', 100, 'text/x-shellscript');

        $response = $this->actingAs($dosen)->post(route('materi.store', $course), [
            'title' => 'Script Berbahaya',
            'type' => 'file',
            'file' => $invalidFile,
        ]);

        $response->assertSessionHasErrors(['file']);
        $this->assertDatabaseMissing('materials', [
            'title' => 'Script Berbahaya',
        ]);
    }

    /**
     * Dosen pengampu dapat menambahkan materi berupa link eksternal tanpa berkas.
     */
    public function test_dosen_pengampu_dapat_menambahkan_materi_berupa_link_eksternal_tanpa_berkas(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $payload = [
            'title' => 'Video Referensi Algoritma Sorting YouTube',
            'description' => 'Tutorial visualisasi algoritma sorting pada tautan eksternal.',
            'type' => 'link',
            'external_url' => 'https://www.youtube.com/watch?v=kPRA0W1kECg',
        ];

        $response = $this->actingAs($dosen)->post(route('materi.store', $course), $payload);

        $response->assertRedirect(route('mata-kuliah.show', $course->id))
            ->assertSessionHas('success', 'Materi berhasil ditambahkan ke penyimpanan privat.');

        $this->assertDatabaseHas('materials', [
            'course_id' => $course->id,
            'uploaded_by' => $dosen->id,
            'title' => 'Video Referensi Algoritma Sorting YouTube',
            'type' => 'link',
            'file_path' => null,
            'external_url' => 'https://www.youtube.com/watch?v=kPRA0W1kECg',
        ]);
    }

    /**
     * Penambahan materi tipe link gagal jika URL yang dimasukkan tidak valid.
     */
    public function test_penambahan_materi_tipe_link_gagal_jika_url_tidak_valid(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $response = $this->actingAs($dosen)->post(route('materi.store', $course), [
            'title' => 'Link Tautan Rusak',
            'type' => 'link',
            'external_url' => 'bukan-url-yang-valid',
        ]);

        $response->assertSessionHasErrors(['external_url']);
        $this->assertDatabaseMissing('materials', [
            'title' => 'Link Tautan Rusak',
        ]);
    }

    /**
     * Penghapusan materi menghapus record database dan berkas fisik dari storage.
     */
    public function test_penghapusan_materi_menghapus_record_database_dan_berkas_fisik_dari_storage(): void
    {
        Storage::fake('local');
        Storage::fake('private');

        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $dosen->id,
            'title' => 'Materi Pertemuan 1 yang Dihapus',
            'type' => 'file',
            'file_path' => "materials/{$course->id}/pertemuan-1.pdf",
            'original_name' => 'pertemuan-1.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        Storage::disk('local')->put($material->file_path, '%PDF-1.4 Dummy File To Delete');
        Storage::disk('private')->put($material->file_path, '%PDF-1.4 Dummy File To Delete');

        Storage::disk('local')->assertExists($material->file_path);
        Storage::disk('private')->assertExists($material->file_path);

        $response = $this->actingAs($dosen)->delete(route('materi.destroy', $material));

        $response->assertRedirect(route('mata-kuliah.show', $course->id))
            ->assertSessionHas('success', 'Materi berhasil dihapus beserta berkas fisiknya.');

        // Record database terhapus
        $this->assertDatabaseMissing('materials', [
            'id' => $material->id,
        ]);

        // Berkas fisik di storage privat terhapus
        Storage::disk('local')->assertMissing($material->file_path);

        Storage::disk('private')->delete($material->file_path);
        Storage::disk('private')->assertMissing($material->file_path);
    }

    /**
     * Dosen bukan pengampu tidak dapat mengunggah materi ke mata kuliah milik dosen lain (IDOR Prevention).
     */
    public function test_dosen_bukan_pengampu_tidak_dapat_mengunggah_materi_ke_mata_kuliah_lain(): void
    {
        $dosen1 = User::factory()->dosen()->create();
        $dosen2 = User::factory()->dosen()->create();

        $course1 = Course::factory()->create(['lecturer_id' => $dosen1->id]);

        $response = $this->actingAs($dosen2)->post(route('materi.store', $course1), [
            'title' => 'Materi Ilegal dari Dosen Lain',
            'type' => 'link',
            'external_url' => 'https://example.com/unauthorized-material',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('materials', [
            'title' => 'Materi Ilegal dari Dosen Lain',
        ]);
    }
}
