<?php

namespace Database\Seeders;

use App\Modules\User\Enums\UserKYBStatusEnum;
use App\Modules\User\Enums\UserStatusEnum;
use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestAndroidAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $vendor = User::firstOrCreate(
            ['email' => 'vendor.test@shopittplus.dev'],
            [
                'name' => 'Test Vendor',
                'phone' => '+2348000000002',
                'status' => UserStatusEnum::ACTIVE->value,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        if (!$vendor->vendor) {
            $vendor->vendor()->create([
                'business_name' => 'Test Vendor Store',
                'kyb_status' => UserKYBStatusEnum::PENDING->value,
            ]);
        }

        User::firstOrCreate(
            ['email' => 'customer.test@shopittplus.dev'],
            [
                'name' => 'Test Customer',
                'phone' => '+2348000000003',
                'status' => UserStatusEnum::ACTIVE->value,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );
    }
}
