<?php

namespace Database\Seeders;

use App\Modules\User\Enums\RoleEnum;
use App\Modules\User\Models\Admin;
use App\Modules\User\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Admin::where('is_super_admin', true)->exists()) {
            $this->command->info('Super admin already exists. Skipping creation.');
            return;
        }

        // Ensure the ADMIN role exists
        if (!Role::where('name', RoleEnum::ADMIN)->exists()) {
            Role::create([
                'id' => Str::uuid(),
                'name' => RoleEnum::ADMIN,
                'key' => 'admin',
                'description' => 'Administrator with full access',
            ]);
        }

        $adminRole = Role::where('name', RoleEnum::ADMIN)->first();
        // Create single super admin
          Admin::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Super Admin',
                'role_id' => $adminRole ? $adminRole->id : null,
                'password' => Hash::make('SecurePassword123!'), // Use a strong password
                'is_super_admin' => true,
                'email_verified_at' => now(),
                'permissions' => json_encode(['*']), // Wildcard for all permissions
            ]
        );

        $this->command->info('Super admin created:');
        $this->command->info('Email: superadmin@example.com');
        $this->command->info('Password: SecurePassword123!');
    }
}