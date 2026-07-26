<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'username'   => 'admin',
                'password'   => Hash::make('admin123'),
                'first_name' => 'Admin',
                'last_name'  => 'Manager',
                'role'       => 'admin',
                'is_active'  => true,
                'locale'     => 'ar-SA',
            ]
        );

        $this->command->info('Admin user created: admin@admin.com / admin123');
    }
}
