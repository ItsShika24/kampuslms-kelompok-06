<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder khusus untuk akun demo di server produksi.
 *
 * Jalankan dengan:
 *   php artisan db:seed --class=DemoAccountSeeder --force
 *
 * Kata sandi produksi tercatat di docs/deployment.md (BUKAN di README.md).
 */
class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('DEMO_PASSWORD', 'ChangeMe@Prod2025!');

        $accounts = [
            [
                'email'   => 'admin@kampuslms.test',
                'name'    => 'Budi Santoso (Admin)',
                'role'    => 'admin',
                'nim_nip' => null,
            ],
            [
                'email'   => 'dosen@kampuslms.test',
                'name'    => 'Dr. Ir. Bambang Hermanto, M.Kom.',
                'role'    => 'dosen',
                'nim_nip' => '197508122003121001',
            ],
            [
                'email'   => '10241064@kampuslms.tes',
                'name'    => 'Raihandy Wijaya',
                'role'    => 'mahasiswa',
                'nim_nip' => '10241064',
            ],
        ];

        foreach ($accounts as $account) {
            $user = User::where('email', $account['email'])->first();

            if ($user) {
                // Update nama dan kata sandi akun yang sudah ada.
                $user->name = $account['name'];
                $user->password = Hash::make($password);
                $user->save();

                $this->command->info("Updated account & password for {$account['email']}");
            } else {
                // Buat akun baru jika belum ada (misalnya production fresh install).
                $user = new User();
                $user->name     = $account['name'];
                $user->email    = $account['email'];
                $user->password = Hash::make($password);
                $user->role     = $account['role'];
                $user->nim_nip  = $account['nim_nip'];
                $user->save();

                $this->command->info("Created account for {$account['email']}");
            }
        }

        $this->command->warn('⚠  Ingat: ganti DEMO_PASSWORD di .env produksi setelah deployment!');
    }
}
