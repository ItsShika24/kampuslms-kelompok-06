<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki izin untuk membuat request ini.
     */
    public function authorize(): bool
    {
        // TODO: Minggu 7 diganti dengan pengecekan hak akses sungguhan (Policy)
        return true;
    }

    /**
     * Aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code'        => ['required', 'string', 'max:20', 'unique:courses,code'],
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'sks'         => ['required', 'integer', 'between:1,6'],
            'lecturer_id' => ['required', 'exists:users,id'],
            'status'      => ['required', 'in:draft,active,archived'],
        ];
    }

    /**
     * Pesan kustom untuk aturan validasi dalam bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required'        => 'Kode mata kuliah wajib diisi.',
            'code.unique'          => 'Kode mata kuliah ini sudah dipakai.',
            'code.max'             => 'Kode mata kuliah maksimal 20 karakter.',
            'name.required'        => 'Nama mata kuliah wajib diisi.',
            'name.max'             => 'Nama mata kuliah maksimal 150 karakter.',
            'sks.required'         => 'Jumlah SKS wajib diisi.',
            'sks.integer'          => 'Jumlah SKS harus berupa angka.',
            'sks.between'          => 'SKS harus antara 1 sampai 6.',
            'lecturer_id.required' => 'Dosen pengampu wajib dipilih.',
            'lecturer_id.exists'   => 'Dosen yang dipilih tidak valid atau tidak terdaftar.',
            'status.required'      => 'Status mata kuliah wajib dipilih.',
            'status.in'            => 'Status mata kuliah harus salah satu dari: draft, active, atau archived.',
        ];
    }
}
