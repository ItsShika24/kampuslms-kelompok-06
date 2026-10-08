<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        $this->syncAuthUser();
        Gate::authorize('viewAny', User::class);

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
        $this->syncAuthUser();
        Gate::authorize('view', $user);

        return view('users.show', compact('user'));
    }

    // Menampilkan form untuk menambah pengguna (Khusus Admin).
    public function create()
    {
        $this->syncAuthUser();
        Gate::authorize('create', User::class);

        return view('users.create');
    }

    // Menyimpan pengguna baru ke database menggunakan Form Request dan pola PRG.
    public function store(StoreUserRequest $request)
    {
        $this->syncAuthUser();
        Gate::authorize('create', User::class);

        $validated = $request->validated();

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'nim_nip'  => $validated['nim_nip'] ?? null,
            'role'     => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    // Menampilkan form untuk mengedit pengguna (Khusus Admin).
    public function edit(User $user)
    {
        $this->syncAuthUser();
        Gate::authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    // Memperbarui data pengguna menggunakan Form Request dan pola PRG.
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->syncAuthUser();
        Gate::authorize('update', $user);

        $validated = $request->validated();

        $userData = [
            'name'    => $validated['name'],
            'email'   => $validated['email'],
            'nim_nip' => $validated['nim_nip'] ?? null,
            'role'    => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    // Menghapus pengguna (soft delete karena User menggunakan SoftDeletes).
    public function destroy(User $user)
    {
        $this->syncAuthUser();
        Gate::authorize('delete', $user);

        $user->delete();

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }
}