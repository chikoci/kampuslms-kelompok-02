<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        // 1. Akun Demo Administrator
        User::updateOrCreate(
            ['email' => 'admin@kampuslms.test'],
            [
                'name' => 'Administrator KampusLMS',
                'password' => $password,
                'role' => 'admin',
                'nim_nip' => null,
                'email_verified_at' => now(),
            ]
        );

        // 2. Akun Demo Dosen
        User::updateOrCreate(
            ['email' => 'dosen@kampuslms.test'],
            [
                'name' => 'Dr. Dosen Pengampu, M.Kom.',
                'password' => $password,
                'role' => 'dosen',
                'nim_nip' => '198501012010121001',
                'email_verified_at' => now(),
            ]
        );

        // 3. Akun Demo Mahasiswa
        User::updateOrCreate(
            ['email' => 'mahasiswa@kampuslms.test'],
            [
                'name' => 'Mahasiswa Uji Coba',
                'password' => $password,
                'role' => 'mahasiswa',
                'nim_nip' => '2201010001',
                'email_verified_at' => now(),
            ]
        );
    }
}
