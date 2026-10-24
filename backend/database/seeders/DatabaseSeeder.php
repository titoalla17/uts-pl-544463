<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Hanya membuat akun demo bila kredensial diberikan saat runtime.
        $email = env('DEMO_EMAIL');
        $password = env('DEMO_PASSWORD');
        if (!$email || !$password) {
            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            ['name' => env('DEMO_NAME', 'Admin UTS'), 'password' => $password],
        );
    }
}
