<?php

namespace App\Console\Commands;

use App\Modules\User\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EnsureOneAdmin extends Command
{
    protected $signature = 'admin:ensure-one
                            {--email=superadmin@example.com : Admin email}
                            {--password=SecurePassword123! : Admin password}
                            {--name=Super Admin : Admin display name}';

    protected $description = 'Create default super admin only if no admins exist (for Railway start script)';

    public function handle(): int
    {
        try {
            if (Admin::count() > 0) {
                $this->info('Admins already exist, skipping.');
                return 0;
            }

            $email = $this->option('email');
            $password = $this->option('password');
            $name = $this->option('name');

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

            $this->info("Created default admin: {$email} (bcrypt).");
            return 0;
        } catch (\Throwable $e) {
            $this->error('admin:ensure-one failed: ' . $e->getMessage());
            Log::error('admin:ensure-one failed', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return 1;
        }
    }
}
