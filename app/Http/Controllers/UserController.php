<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Menampilkan daftar seluruh pengguna.
    public function index()
    {
        $users = User::all();

        return view('users.index', compact('users'));
    }

    // Menampilkan detail satu pengguna.
    public function show($pengguna)
    {
        $user = User::findOrFail($pengguna);

        return view('users.show', compact('user'));
    }

    // Menampilkan form untuk menambah pengguna.
    public function create()
    {
        return view('users.create');
    }

    // Menyimpan pengguna baru ke database.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nim_nip' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:admin,dosen,mahasiswa'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create($validated);

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    // Menampilkan form untuk mengedit pengguna.
    public function edit($pengguna)
    {
        $user = User::findOrFail($pengguna);

        return view('users.edit', compact('user'));
    }

    // Memperbarui data pengguna.
    public function update(Request $request, $pengguna)
    {
        $user = User::findOrFail($pengguna);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'nim_nip' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:admin,dosen,mahasiswa'],
        ]);

        $user->update($validated);

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    // Menghapus pengguna.
    public function destroy($pengguna)
    {
        $user = User::findOrFail($pengguna);

        $user->delete();

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }
}