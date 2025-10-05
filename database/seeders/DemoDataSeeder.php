<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Contact;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Create a demo user
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Demo User', 'password' => Hash::make('password')]
        );

        // 2) Create 25 demo contacts for that user
        Contact::factory()
            ->count(25)
            ->create(['user_id' => $user->id]);

        // 3) Create a Sanctum token and print it for you to copy
        $token = $user->createToken('dev')->plainTextToken;

        // Print to console when seeding runs
        $this->command->info('==============================');
        $this->command->info('Demo login email : demo@example.com');
        $this->command->info('Demo password    : password');
        $this->command->info('API Bearer Token : ' . $token);
        $this->command->info('==============================');
    }
}
