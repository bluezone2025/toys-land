<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(StoreSeeder::class);

        $email = env('ADMIN_EMAIL', 'admin@toysland.com');
        if (! User::where('email', $email)->exists()) {
            $password = env('ADMIN_PASSWORD') ?: Str::password(14, symbols: false);
            User::create(['name' => 'Admin', 'email' => $email, 'password' => $password, 'is_admin' => true]);
            $this->command?->warn("Admin account: {$email} / {$password}  (change it after first login)");
        }
    }
}
