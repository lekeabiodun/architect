<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

class SystemReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:reset
                            {--email= : Email for the super admin (prompted if omitted)}
                            {--password= : Password for the super admin (prompted if omitted)}
                            {--name=Super Admin : Display name for the super admin}
                            {--force : Skip the confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe all data and reset the system to a fresh state: all roles/permissions and a single super admin user.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('⚠️  This will DROP ALL TABLES and DELETE ALL DATA (users, projects, tasks, everything).');

        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to run in production without --force.');
            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Are you absolutely sure you want to reset the system?')) {
            $this->info('Aborted. No changes made.');
            return self::SUCCESS;
        }

        // Collect super admin credentials before we touch anything.
        $email = $this->option('email') ?: $this->ask('Super admin email', 'admin@projectarchitect.com');
        $name = $this->option('name') ?: 'Super Admin';
        $password = $this->option('password') ?: $this->secret('Super admin password (leave blank to generate one)');

        $generated = false;
        if (blank($password)) {
            $password = \Illuminate\Support\Str::password(16);
            $generated = true;
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email' => ['required', 'email'],
                'password' => ['required', Password::min(8)],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        // 1. Wipe and rebuild the schema.
        $this->info('🧨 Dropping all tables and re-running migrations...');
        Artisan::call('migrate:fresh', ['--force' => true], $this->getOutput());

        // 2. Seed roles and permissions only.
        $this->info('🔐 Seeding roles and permissions...');
        $this->callSilent('db:seed', [
            '--class' => RolesAndPermissionsSeeder::class,
            '--force' => true,
        ]);

        // 3. Create the single super admin user.
        $this->info('👤 Creating super admin user...');
        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('super_admin');

        $this->newLine();
        $this->info('✅ System reset complete. Fresh start ready.');
        $this->line('   Roles created: '.\Spatie\Permission\Models\Role::count());
        $this->line('   Users: 1 (super admin)');
        $this->newLine();
        $this->line('   📧 Email:    '.$email);

        if ($generated) {
            $this->warn('   🔑 Password: '.$password.'  (generated — save it now, it will not be shown again)');
        } else {
            $this->line('   🔑 Password: (as provided)');
        }

        return self::SUCCESS;
    }
}
