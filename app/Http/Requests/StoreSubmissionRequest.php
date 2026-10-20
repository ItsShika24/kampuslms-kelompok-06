<?php

namespace App\Http\Requests;

use App\Models\Assignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('assignment');

        return $assignment instanceof Assignment
            && $this->user()?->can('submit', $assignment) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,zip', 'max:10240'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Berkas pengumpulan tugas wajib diunggah.',
            'file.file' => 'Pengumpulan tugas harus berupa berkas.',
            'file.mimes' => 'Format berkas harus PDF, DOC, DOCX, atau ZIP.',
            'file.max' => 'Ukuran maksimal berkas adalah 10 MB.',
            'note.string' => 'Catatan harus berupa teks.',
            'note.max' => 'Catatan maksimal 2.000 karakter.',
        ];
    }
}
