<?php

namespace App\Console\Commands;

use App\Modules\User\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetAdminPasswords extends Command
{
    protected $signature = 'admin:reset-passwords
                            {password=SecurePassword123! : The new password to set for all admin users}';

    protected $description = 'Set the same bcrypt password for all admin users (e.g. for production reset)';

    public function handle(): int
    {
        $password = $this->argument('password');
        $hash = Hash::make($password);

        $admins = Admin::all();
        $count = $admins->count();

        if ($count === 0) {
            $this->warn('No admin users found.');
            return 0;
        }

        foreach ($admins as $admin) {
            $admin->password = $hash;
            $admin->save();
        }

        $this->info("Updated password for {$count} admin user(s).");
        return 0;
    }
}
