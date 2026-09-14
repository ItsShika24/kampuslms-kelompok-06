<!DOCTYPE html>
<html>
<head>
    <title>Edit Pengguna</title>
</head>
<body>
    <h1>Edit Pengguna</h1>

    @if ($errors->any())
        <div>
            <strong>Terjadi kesalahan:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('pengguna.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div>
            <label>Nama</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}">
        </div>

        <div>
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}">
        </div>

        <div>
            <label>NIM/NIP</label>
            <input type="text" name="nim_nip" value="{{ old('nim_nip', $user->nim_nip) }}">
        </div>

        <div>
            <label>Role</label>
            <select name="role">
                <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>
                    Admin
                </option>
                <option value="dosen" {{ old('role', $user->role) == 'dosen' ? 'selected' : '' }}>
                    Dosen
                </option>
                <option value="mahasiswa" {{ old('role', $user->role) == 'mahasiswa' ? 'selected' : '' }}>
                    Mahasiswa
                </option>
            </select>
        </div>

        <button type="submit">Simpan Perubahan</button>
    </form>

    <a href="{{ route('pengguna.index') }}">Kembali</a>
</body>
</html>