<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Memastikan auth guard tersinkronisasi jika menggunakan demo switcher.
     */
    protected function syncAuthUser(): ?User
    {
        if (! auth()->check() && session()->has('demo_role')) {
            $demoUser = User::where('role', session('demo_role'))->first();
            if ($demoUser) {
                auth()->login($demoUser);
            }
        }

        return auth()->user();
    }

    // Menampilkan daftar seluruh pengguna dengan pencarian, filter, dan pagination (Khusus Admin).
    public function index(Request $request)
    {
        $user = $this->syncAuthUser();
        abort_unless($user && $user->role === 'admin', 403, 'Akses Ditolak: Hanya administrator yang dapat mengelola pengguna.');

        $users = User::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->q . '%')
                      ->orWhere('email', 'like', '%' . $request->q . '%')
                      ->orWhere('nim_nip', 'like', '%' . $request->q . '%');
                });
            })
            ->when($request->filled('role'), fn ($query) =>
                $query->where('role', $request->role))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    // Menampilkan detail satu pengguna.
    public function show(User $user)
    {
        $currentUser = $this->syncAuthUser();
        abort_unless(
            $currentUser && ($currentUser->role === 'admin' || $currentUser->id === $user->id),
            403,
            'Akses Ditolak: Anda tidak memiliki wewenang untuk melihat profil ini.'
        );

        return view('users.show', compact('user'));
    }

    // Menampilkan form untuk menambah pengguna (Khusus Admin).
    public function create()
    {
        $currentUser = $this->syncAuthUser();
        abort_unless($currentUser && $currentUser->role === 'admin', 403, 'Akses Ditolak: Hanya administrator yang dapat mendaftarkan pengguna baru.');

        return view('users.create');
    }

    // Menyimpan pengguna baru ke database menggunakan Form Request dan pola PRG.
    public function store(StoreUserRequest $request)
    {
        $currentUser = $this->syncAuthUser();
        abort_unless($currentUser && $currentUser->role === 'admin', 403, 'Akses Ditolak: Hanya administrator yang dapat mendaftarkan pengguna baru.');

        $validated = $request->validated();

        $user = new User([
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

    // Menampilkan form untuk mengedit pengguna (Khusus Admin).
    public function edit(User $user)
    {
        $currentUser = $this->syncAuthUser();
        abort_unless($currentUser && $currentUser->role === 'admin', 403, 'Akses Ditolak: Hanya administrator yang dapat mengubah data pengguna.');

        return view('users.edit', compact('user'));
    }

    // Memperbarui data pengguna menggunakan Form Request dan pola PRG.
    public function update(UpdateUserRequest $request, User $user)
    {
        $currentUser = $this->syncAuthUser();
        abort_unless($currentUser && $currentUser->role === 'admin', 403, 'Akses Ditolak: Hanya administrator yang dapat mengubah data pengguna.');

        $validated = $request->validated();

        $user->fill([
            'name'    => $validated['name'],
            'email'   => $validated['email'],
            'nim_nip' => $validated['nim_nip'] ?? null,
        ]);
        $user->role = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    // Menghapus pengguna (soft delete karena User menggunakan SoftDeletes).
    public function destroy(User $user)
    {
        $currentUser = $this->syncAuthUser();
        abort_unless($currentUser && $currentUser->role === 'admin', 403, 'Akses Ditolak: Hanya administrator yang dapat menghapus pengguna.');

        $user->delete();

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }
}