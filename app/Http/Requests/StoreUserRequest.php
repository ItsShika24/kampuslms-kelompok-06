<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'nim_nip'  => ['nullable', 'string', 'max:50', 'unique:users,nim_nip'],
            'role'     => ['required', 'in:admin,dosen,mahasiswa'],
            'password' => ['required', 'string', 'min:8'],
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
            'name.required'     => 'Nama pengguna wajib diisi.',
            'name.max'          => 'Nama pengguna maksimal 255 karakter.',
            'email.required'    => 'Alamat email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email ini sudah terdaftar di sistem.',
            'nim_nip.unique'    => 'NIM/NIP ini sudah terdaftar.',
            'nim_nip.max'       => 'NIM/NIP maksimal 50 karakter.',
            'role.required'     => 'Role pengguna wajib dipilih.',
            'role.in'           => 'Role pengguna harus salah satu dari: admin, dosen, atau mahasiswa.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal harus 8 karakter.',
        ];
    }
}
