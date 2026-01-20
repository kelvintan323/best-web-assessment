<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Admin::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt(12345678),
        ]);
    }
}
