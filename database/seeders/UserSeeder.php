<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'Admin')->firstOrFail();
        $customerRole = Role::where('name', 'Customer')->firstOrFail();

        User::firstOrCreate(
            ['email' => 'admin@minishop.com'],
            [
                'name' => 'Admin MiniShop',
                'phone' => '081234567890',
                'password' => Hash::make('AdminMiniShop1'),
                'email_verified_at' => now(),
                'role_id' => $adminRole->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'customer@minishop.com'],
            [
                'name' => 'Customer MiniShop',
                'phone' => '081234567891',
                'password' => Hash::make('CustomerMiniShop1'),
                'email_verified_at' => now(),
                'role_id' => $customerRole->id,
            ]
        );

        if (User::where('role_id', $customerRole->id)->count() < 10) {
            User::factory(10)->create(['role_id' => $customerRole->id]);
        }
    }
}
