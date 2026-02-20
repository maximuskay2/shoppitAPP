<?php

namespace App\Console\Commands;

use App\Modules\User\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedSingleAdmin extends Command
{
    protected $signature = 'admin:seed-one
                            {email=superadmin@example.com : Admin email}
                            {password=SecurePassword123! : Admin password}
                            {--name=Super Admin : Admin display name}';

    protected $description = 'Delete all admins and create a single super admin with bcrypt password';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        $name = $this->option('name');

        // Delete all admins (including soft-deleted) and their tokens
        $admins = Admin::withTrashed()->get();
        foreach ($admins as $admin) {
            $admin->tokens()->delete();
            $admin->forceDelete();
        }
        $this->info('Removed all existing admin users.');

        Admin::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => null,
            'avatar' => null,
            'permissions' => null,
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->info("Created admin: {$email} (password encrypted with bcrypt).");
        return 0;
    }
}
