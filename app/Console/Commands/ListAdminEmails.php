<?php

namespace App\Console\Commands;

use App\Modules\User\Models\Admin;
use Illuminate\Console\Command;

class ListAdminEmails extends Command
{
    protected $signature = 'admin:list-emails';

    protected $description = 'List all admin user emails (for login verification)';

    public function handle(): int
    {
        $admins = Admin::all(['id', 'name', 'email']);
        $count = $admins->count();

        if ($count === 0) {
            $this->warn('No admin users found.');
            return 0;
        }

        $this->table(
            ['ID', 'Name', 'Email'],
            $admins->map(fn (Admin $a) => [$a->id, $a->name ?? '—', $a->email])->toArray()
        );
        $this->info("Total: {$count} admin(s). Use one of these emails to log in.");
        return 0;
    }
}
