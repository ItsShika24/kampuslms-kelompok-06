<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Menampilkan daftar seluruh pengguna.
    public function index()
    {
        $users = User::all();

        return view('users.index', compact('users'));
    }

    // Menampilkan detail satu pengguna.
    public function show(User $user)
    {
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
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'nim_nip'  => ['nullable', 'string', 'max:255', 'unique:users,nim_nip'],
            'role'     => ['required', 'in:admin,dosen,mahasiswa'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        // 'role' tidak ada di $fillable — diisi eksplisit agar tidak bisa
        // di-mass-assign oleh input user dari luar.
        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'nim_nip'  => $validated['nim_nip'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        $user->role = $validated['role'];
        $user->save();

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    // Menampilkan form untuk mengedit pengguna.
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    // Memperbarui data pengguna.
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'nim_nip' => ['nullable', 'string', 'max:255', 'unique:users,nim_nip,' . $user->id],
            'role'    => ['required', 'in:admin,dosen,mahasiswa'],
        ]);

        // Update field yang ada di $fillable secara mass-assign.
        $user->update([
            'name'    => $validated['name'],
            'email'   => $validated['email'],
            'nim_nip' => $validated['nim_nip'] ?? null,
        ]);

        // 'role' diisi eksplisit karena tidak ada di $fillable.
        $user->role = $validated['role'];
        $user->save();

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    // Menghapus pengguna (soft delete karena User menggunakan SoftDeletes).
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }
}