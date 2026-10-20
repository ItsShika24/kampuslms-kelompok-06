<?php

namespace App\Http\Requests;

use App\Models\Course;
use App\Models\Material;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $course instanceof Course
            && $this->user()?->can('create', [Material::class, $course]) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:file,link,text'],
            'file' => ['required_if:type,file', 'file', 'mimes:pdf,pptx,ppt,docx,doc', 'max:20480'],
            'external_url' => ['required_if:type,link', 'nullable', 'url'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul materi wajib diisi.',
            'title.string' => 'Judul materi harus berupa teks.',
            'title.max' => 'Judul materi maksimal 255 karakter.',
            'description.string' => 'Deskripsi materi harus berupa teks.',
            'type.required' => 'Tipe materi wajib dipilih.',
            'type.in' => 'Tipe materi harus berupa berkas, tautan, atau teks.',
            'file.required_if' => 'Berkas materi wajib diunggah.',
            'file.file' => 'Materi harus berupa berkas.',
            'file.mimes' => 'Format materi harus PDF, PPT, PPTX, DOC, atau DOCX.',
            'file.max' => 'Ukuran maksimal berkas materi adalah 20 MB.',
            'external_url.required_if' => 'URL materi wajib diisi.',
            'external_url.url' => 'URL materi harus berupa alamat URL yang valid.',
        ];
    }
}
