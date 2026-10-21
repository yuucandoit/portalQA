<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Chandra Maulana',
                'email' => 'chandra@gmail.com',
                'password' => Hash::make('password'),
                'status' => 'Active',
            ],
             [
                'name' => 'Syahril Prasetyo',
                'email' => 'syahril@gmail.com',
                'password' => Hash::make('password'),
                'status' => 'Active',
            ],
            [
                'name' => 'Abdul Alem',
                'email' => 'alem@gmail.com',
                'password' => Hash::make('password'),
                'status' => 'Active',
            ],
            [
                'name' => 'Luthfi Rachman',
                'email' => 'luthfi@gmail.com',
                'password' => Hash::make('password'),
                'status' => 'Active',
            ],
            [
                'name' => 'Wahyu Sanjaya',
                'email' => 'wahyu@gmail.com',
                'password' => Hash::make('password'),
                'status' => 'Active',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
