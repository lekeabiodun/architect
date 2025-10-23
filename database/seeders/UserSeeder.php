<?php

namespace Database\Seeders;

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
        // Create Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@projectarchitect.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'), // Change this in production!
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('super_admin');

        $this->command->info('✅ Super Admin created: admin@projectarchitect.com');
        $this->command->warn('⚠️  Default password is "password" - CHANGE THIS IN PRODUCTION!');

        // Optionally create other test users
        if (app()->environment('local')) {
            // Project Manager
            $pm = User::firstOrCreate(
                ['email' => 'pm@projectarchitect.com'],
                [
                    'name' => 'John Project Manager',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $pm->assignRole('project_manager');

            // Inspector
            $inspector = User::firstOrCreate(
                ['email' => 'inspector@projectarchitect.com'],
                [
                    'name' => 'Jane Inspector',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $inspector->assignRole('inspector');

            // Engineer
            $engineer = User::firstOrCreate(
                ['email' => 'engineer@projectarchitect.com'],
                [
                    'name' => 'Bob Engineer',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $engineer->assignRole('engineer');

            // Worker
            $worker = User::firstOrCreate(
                ['email' => 'worker@projectarchitect.com'],
                [
                    'name' => 'Mike Worker',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $worker->assignRole('worker');

            // Client
            $client = User::firstOrCreate(
                ['email' => 'client@projectarchitect.com'],
                [
                    'name' => 'Sarah Client',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $client->assignRole('client');

            $this->command->info('✅ Test users created for local environment');
            $this->command->line('   📧 pm@projectarchitect.com (Project Manager)');
            $this->command->line('   📧 inspector@projectarchitect.com (Inspector)');
            $this->command->line('   📧 engineer@projectarchitect.com (Engineer)');
            $this->command->line('   📧 worker@projectarchitect.com (Worker)');
            $this->command->line('   📧 client@projectarchitect.com (Client)');
        }
    }
}
