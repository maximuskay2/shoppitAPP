<?php

namespace App\Modules\User\Services\Admin;

use App\Modules\User\Enums\UserKYBStatusEnum;
use App\Modules\User\Enums\UserStatusEnum;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class UserManagementService
{
    /**
     * List users with advanced filtering and sorting
     */
    public function listUsers(Request $request)
    {
        $query = User::query()->with('vendor');

        // User type filter - vendors or customers (mutually exclusive)
        if ($request->has('user_type')) {
            $userType = $request->input('user_type');
            
            if ($userType === 'vendor') {
                $query->whereHas('vendor');
            } elseif ($userType === 'customer') {
                $query->whereDoesntHave('vendor');
            }
        }

        if ($request->boolean('exclude_drivers')) {
            $query->whereDoesntHave('driver');
        }

        // Legacy support for vendors_only parameter
        if ($request->has('vendors_only') && $request->boolean('vendors_only')) {
            $query->whereHas('vendor');
        }

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            if ($status === 'new') {
                $status = UserStatusEnum::NEW->value;
            } elseif ($status === 'active') {
                $status = UserStatusEnum::ACTIVE->value;
            } elseif ($status === 'suspended') {
                $status = UserStatusEnum::SUSPENDED->value;
            } elseif ($status === 'inactive') {
                $status = UserStatusEnum::INACTIVE->value;
            } elseif ($status === 'blocked') {
                $status = UserStatusEnum::BLOCKED->value;
            }
        }

        // KYC status filter
        if ($kycStatus = $request->input('kyc_status')) {
            $query->where('kyc_status', $kycStatus);
        }

        // Email verified filter
        if ($request->has('email_verified')) {
            if ($request->boolean('email_verified')) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Country filter
        if ($country = $request->input('country')) {
            $query->where('country', $country);
        }

        // Date range filter
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Has wallet filter
        if ($request->has('has_wallet')) {
            if ($request->boolean('has_wallet')) {
                $query->has('wallet');
            } else {
                $query->doesntHave('wallet');
            }
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = [
            'created_at', 'name', 'email', 'username', 'status', 
            'kyc_status', 'country'
        ];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $perPage = min($perPage, 100); // Max 100 items per page

        // Load relationships based on user type
        $with = ['wallet'];
        if ($request->input('user_type') === 'vendor' || $request->boolean('vendors_only')) {
            $with[] = 'vendor';
        }

        $users = $query->with($with)->paginate($perPage);

        // Transform users data
        $users->getCollection()->transform(function ($user) use ($request) {
            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'status' => $user->status,
                'avatar' => $user->avatar,
                'country' => $user->country,
                'user_type' => $user->vendor ? 'vendor' : 'customer',
                'kyc_status' => $user->kyc_status,
                'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
                'wallet_balance' => $user->wallet ? $user->wallet->amount->getAmount()->toFloat() : 0,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            ];

            // Add vendor details if user is a vendor
            if ($user->vendor) {
                $data['vendor'] = [
                    'id' => $user->vendor->id,
                    'business_name' => $user->vendor->business_name,
                    'kyb_status' => $user->vendor->kyb_status,
                    'opening_time' => $user->vendor->opening_time?->format('g:i A'),
                    'closing_time' => $user->vendor->closing_time?->format('g:i A'),
                    'is_open' => $user->vendor->isOpen(),
                    'approximate_shopping_time' => $user->vendor->approximate_shopping_time . ' min' . ($user->vendor->approximate_shopping_time > 1 ? 's' : ''),
                    'delivery_fee' => $user->vendor->delivery_fee->getAmount()->toFloat(),
                    'average_rating' => $user->vendor->averageRating(),
                ];
            }

            return $data;
        });

        return $users;
    }

    /**
     * Get user statistics
     */
    public function getUserStats($request): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', UserStatusEnum::ACTIVE)->count();
        $suspendedUsers = User::where('status', UserStatusEnum::SUSPENDED)->count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $kycVerifiedVendors = Vendor::where('kyb_status', UserKYBStatusEnum::SUCCESSFUL)->count();
        $usersWithWallet = User::has('wallet')->count();
        $usersCreatedToday = User::whereDate('created_at', today())->count();
        $usersCreatedThisWeek = User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $usersCreatedThisMonth = User::whereMonth('created_at', now()->month)->count();

        // Get weekly growth data for customers and vendors in the specified or current month
        $targetDate = now();
        
        if ($request->has('month') && $request->has('year')) {
            $targetDate = now()->setYear(intval($request->input('year')))->setMonth(intval($request->input('month')));
        } elseif ($request->has('month')) {
            $targetDate = now()->setMonth(intval($request->input('month')));
        } elseif ($request->has('year')) {
            $targetDate = now()->setYear(intval($request->input('year')));
        }
        
        $startOfMonth = $targetDate->copy()->startOfMonth();
        $endOfMonth = $targetDate->copy()->endOfMonth();
        
        // Initialize weekly data arrays
        $customerData = [];
        $vendorData = [];
        
        // Calculate data for each week in the current month
        for ($week = 1; $week <= 5; $week++) {
            $weekStart = $startOfMonth->copy()->addWeeks($week - 1)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            
            // Ensure we don't go beyond the current month
            if ($weekStart->month != $startOfMonth->month) {
                break;
            }
            
            if ($weekEnd->month != $startOfMonth->month) {
                $weekEnd = $endOfMonth->copy();
            }
            
            // Count customers (users without vendor relationship) created in this week
            $customersCount = User::whereDoesntHave('vendor')
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->count();
            
            // Count vendors (users with vendor relationship) created in this week
            $vendorsCount = User::whereHas('vendor')
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->count();
            
            $customerData[] = [
                'week' => $week,
                'count' => $customersCount,
            ];
            
            $vendorData[] = [
                'week' => $week,
                'count' => $vendorsCount,
            ];
        }

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'suspended_users' => $suspendedUsers,
            'verified_users' => $verifiedUsers,
            'active_vendors' => $kycVerifiedVendors,
            'users_with_wallet' => $usersWithWallet,
            'users_created_today' => $usersCreatedToday,
            'users_created_this_week' => $usersCreatedThisWeek,
            'users_created_this_month' => $usersCreatedThisMonth,
            'customer_data' => $customerData,
            'vendor_data' => $vendorData,
        ];
    }

    /**
     * Create a new user
     */
    public function createUser(array $data): User
    {
        // Check if user with email or phone already exists
        if (User::where('email', $data['email'])->exists()) {
            throw new InvalidArgumentException('User with this email already exists');
        }

        if (User::where('phone', $data['phone'])->exists()) {
            throw new InvalidArgumentException('User with this phone already exists');
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => UserStatusEnum::NEW->value,
            ]);

            if ($data['user_type'] === 'vendor') {
                // Create associated vendor record
                $user->vendor()->create([
                    'business_name' => $data['business_name'] ?? $user->name . "'s Business",
                    'kyb_status' => UserKYBStatusEnum::PENDING->value,
                ]);
            }

            DB::commit();

            return $user->load('vendor')->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get user details
     */
    public function getUserDetails(string $userId)
    {
        $user = User::with([
            'wallet',
            'transactions' => fn($q) => $q->latest()->limit(10),
            'referrals',
            'referrer',
            'orders' => fn($q) => $q->whereIn('status', ['COMPLETED', 'DELIVERED'])->latest(),
            'vendor.subscription',
            'vendor.products',
            'vendor.orders' => fn($q) => $q->whereIn('status', ['COMPLETED', 'DELIVERED'])->latest(),
        ])->find($userId);

        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'status' => $user->status,
            'avatar' => $user->avatar,
            'country' => $user->country,
            'referral_code' => $user->referral_code,
            'kyc_status' => $user->kyc_status,
            'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
            'last_logged_in_device' => $user->last_logged_in_device,
            'wallet' => $user->wallet ? [
                'balance' => $user->wallet->amount->getAmount()->toFloat(),
                'currency' => $user->wallet->currency,
            ] : null,
            'total_transactions' => $user->transactions->count(),
            'total_orders' => $user->orders->count(),
            'total_spent' => $user->orders->sum(function ($order) {
                return $order->net_total_amount->getAmount()->toFloat() + max(0, $order->delivery_fee->getAmount()->toFloat());
            }),
            'referrals_count' => $user->referrals->count(),
            'referred_by' => $user->referrer ? [
                'id' => $user->referrer->id,
                'name' => $user->referrer->name,
                'username' => $user->referrer->username,
            ] : null,
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
        ];

          if ($user->vendor) {
                $data['vendor'] = [
                    'id' => $user->vendor->id,
                    'business_name' => $user->vendor->business_name,
                    'kyb_status' => $user->vendor->kyb_status,
                    'opening_time' => $user->vendor->opening_time?->format('g:i A'),
                    'closing_time' => $user->vendor->closing_time?->format('g:i A'),
                    'is_open' => $user->vendor->isOpen(),
                    'approximate_shopping_time' => $user->vendor->approximate_shopping_time . ' min' . ($user->vendor->approximate_shopping_time > 1 ? 's' : ''),
                    'delivery_fee' => $user->vendor->delivery_fee->getAmount()->toFloat(),
                    'average_rating' => $user->vendor->averageRating(),
                    'total_products' => $user->vendor->products->count(),
                    'total_orders' => $user->vendor->orders->count(),
                    'total_sales' => $user->vendor->orders->sum(function ($order) {
                        return $order->gross_total_amount->getAmount()->toFloat();
                    }),
                    'subscription' => $user->vendor->subscription,
                ];
            }

            return $data;
    }

    /**
     * Update user
     */
    public function updateUser(string $userId, array $data): User
    {
        $user = User::find($userId);

        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        // Check email uniqueness if changed
        if (isset($data['email']) && $data['email'] !== $user->email) {
            if (User::where('email', $data['email'])->where('id', '!=', $userId)->exists()) {
                throw new InvalidArgumentException('Email already taken');
            }
        }

        // Check phone uniqueness if changed
        if (isset($data['phone']) && $data['phone'] !== $user->phone) {
            if (User::where('phone', $data['phone'])->where('id', '!=', $userId)->exists()) {
                throw new InvalidArgumentException('Phone already taken');
            }
        }

        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn($value) => !is_null($value));

        if (isset($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return $user->fresh(['wallet']);
    }

    /**
     * Delete user
     */
    public function deleteUser(string $userId): void
    {
        $user = User::find($userId);

        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        // Check if user has wallet balance
        if ($user->wallet && $user->wallet->amount->getAmount()->toFloat() > 0) {
            throw new InvalidArgumentException('Cannot delete user with wallet balance. Please empty wallet first.');
        }

        DB::beginTransaction();

        try {
            // Delete wallet
            // if ($user->wallet) {
            //     resolve(WalletService::class)->destroy($user->wallet);
            // }

            if ($user->vendor) {
                // Delete vendor products
                $user->vendor->products()->delete();

                // Delete vendor orders
                $user->vendor->orders()->delete();

                // Delete vendor subscription
                if ($user->vendor->subscription) {
                    $user->vendor->subscription->delete();
                }

                // Delete vendor record
                $user->vendor->delete();
            } else {
                // Delete customer orders
                $user->orders()->delete();
            }

            $user->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Suspend user
     */
    public function suspendUser(string $userId): User
    {
        $user = User::find($userId);

        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        $user->update(['status' => UserStatusEnum::SUSPENDED]);

        return $user->fresh();
    }

    /**
     * Activate user
     */
    public function activateUser(string $userId): User
    {
        $user = User::find($userId);

        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        $user->update(['status' => UserStatusEnum::ACTIVE]);

        return $user->fresh();
    }

    /**
     * Change user status
     */
    public function changeUserStatus(string $userId, string $status): User
    {
        $user = User::find($userId);

        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        // Validate status against UserStatusEnum
        if (!in_array($status, UserStatusEnum::toArray())) {
            throw new InvalidArgumentException('Invalid status provided');
        }

        $user->update(['status' => $status]);

        return $user->fresh();
    }

    /**
     * Get user transactions
     */
    public function getUserTransactions(string $userId, Request $request)
    {
        $user = User::find($userId);

        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        $query = $user->transactions()->latest();

        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Date range
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $perPage = $request->input('per_page', 15);

        return $query->paginate($perPage);
    }

    /**
     * Get user wallet
     */
    public function getUserWallet(string $userId)
    {
        $user = User::with('wallet.virtualBankAccount')->find($userId);

        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        if (!$user->wallet) {
            throw new InvalidArgumentException('User does not have a wallet');
        }

        return [
            'balance' => $user->wallet->amount->getAmount()->toFloat(),
            'currency' => $user->wallet->currency,
            'account_number' => $user->wallet->virtualBankAccount->account_number,
            'account_name' => $user->wallet->virtualBankAccount->account_name,
            'bank_name' => $user->wallet->virtualBankAccount->bank_name,
            'created_at' => $user->wallet->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $user->wallet->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get user linked bank accounts
     */
    public function getUserLinkedBankAccounts(string $userId, array $filters = []): mixed
    {
        $user = User::find($userId);

        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        $query = $user->linkedBankAccounts();

        // Apply status filter
        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply provider filter
        if (isset($filters['provider']) && !empty($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        // Paginate results
        $perPage = $filters['per_page'] ?? 15;
        $linkedBankAccounts = $query->paginate($perPage);

        $formattedLinkedBankAccounts = $linkedBankAccounts->getCollection()->map(function ($lba) {
            return [
                'id' => $lba->id,
                'account_number' => $lba->account_number,
                'account_name' => $lba->account_name,
                'bank_name' => $lba->bank_name,
                'bank_code' => $lba->bank_code,
                'type' => $lba->type,
                'status' => $lba->status->value,
                'provider' => $lba->provider,
                'currency' => $lba->currency,
                'country' => $lba->country,
                'balance' => $lba->balance->getAmount()->toFloat(),
                'created_at' => $lba->created_at->format('Y-m-d H:i:s'),
            ];
        });

        $linkedBankAccounts->setCollection($formattedLinkedBankAccounts);

        return $linkedBankAccounts;
    }

    /**
     * Get user beneficiaries
     */
    public function getUserBeneficiaries(string $userId, array $filters = []): mixed
    {
        $user = User::find($userId);

        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        $query = $user->beneficiaries();

        // Apply service filter
        if (isset($filters['service']) && !empty($filters['service'])) {
            $query->where('service', $filters['service']);
        }

        // Paginate results
        $perPage = $filters['per_page'] ?? 15;
        $beneficiaries = $query->paginate($perPage);

        $formattedBeneficiaries = $beneficiaries->getCollection()->map(function ($beneficiary) {
            return [
                'id' => $beneficiary->id,
                'service' => $beneficiary->service,
                'account_number' => $beneficiary->payload['account_number'] ?? null,
                'account_name' => $beneficiary->payload['account_name'] ?? null,
                'bank_name' => $beneficiary->payload['bank_name'] ?? null,
                'bank_code' => $beneficiary->payload['bank_code'] ?? null,
                'phone_number' => $beneficiary->payload['phone_number'] ?? null,
                'network' => $beneficiary->payload['network'] ?? null,
                'created_at' => $beneficiary->created_at->format('Y-m-d H:i:s'),
            ];
        });

        $beneficiaries->setCollection($formattedBeneficiaries);

        return $beneficiaries;
    }
}