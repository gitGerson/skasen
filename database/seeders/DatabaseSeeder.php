<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Aspirasi;
use App\Models\Kategori;
use App\Models\Tujuan;
use App\Models\User;
use Faker\Factory as Faker;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faker = Faker::create();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $siswaRole = Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

        $tujuan = Tujuan::firstOrCreate(['name' => 'Bimbingan Konseling']);

        $kategoriIds = collect([
            'Ide',
            'Keluhan',
            'Saran',
            'Aduan',
        ])->map(fn (string $name) => Kategori::firstOrCreate(['name' => $name])->id);

        $admin = User::forceCreate([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'nis' => '000000000001',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $admin->syncRoles([$adminRole]);

        $users = collect();

        for ($i = 1; $i <= 10; $i++) {
            $user = User::forceCreate([
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'nis' => $faker->unique()->numerify('############'),
                'password' => 'password',
                'email_verified_at' => now(),
            ]);
            $user->syncRoles([$siswaRole]);

            $users->push($user);
        }

        for ($i = 1; $i <= 10; $i++) {
            Aspirasi::create([
                'user_id' => $users->random()->id,
                'tujuan_id' => $tujuan->id,
                'kategori_id' => $kategoriIds->random(),
                'keterangan' => $faker->paragraph(),
                'image_path' => null,
                'is_anonymous' => $faker->boolean(30),
                'status' => $faker->randomElement([
                    'Belum Ditindaklanjuti',
                    'Sedang Ditindaklanjuti',
                    'Selesai',
                ]),
            ]);
        }
    }
}
