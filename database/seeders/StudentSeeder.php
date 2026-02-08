<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\LazyCollection;
use Spatie\Permission\Models\Role;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = public_path('datasets/skasen.csv');

        LazyCollection::make(function () use ($csvPath) {
            $handle = fopen($csvPath, 'r');
            
            if ($handle === false) {
                return;
            }

            // Skip header
            fgetcsv($handle, 1000, ';');

            while (($row = fgetcsv($handle, 1000, ';')) !== false) {
                yield $row;
            }

            fclose($handle);
        })
        ->chunk(100)
        ->each(function ($chunk) {
            foreach ($chunk as $row) {
                // Ensure row has enough columns
                if (count($row) < 5) {
                    continue;
                }

                $nama = $row[0];
                $jk = $row[1];
                $nis = $row[2];
                $tempatLahir = $row[3];
                $tanggalLahir = $row[4];

                // Remove non-numeric characters from NIS just in case
                // $nis = preg_replace('/[^0-9]/', '', $nis);

                $user = User::updateOrCreate(
                    ['nis' => $nis],
                    [
                        'name' => $nama,
                        'email' => $nis . '@skasen.com',
                        'gender' => $jk,
                        'tempat_lahir' => $tempatLahir,
                        'tanggal_lahir' => $tanggalLahir,
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                    ]
                );

                $role = Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);
                $user->assignRole($role);
            }
        });
    }
}
