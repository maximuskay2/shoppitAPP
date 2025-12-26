<?php

namespace App\Services\Admin;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Models\User\Wallet;
use App\Services\User\WalletService;
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
        $query = User::query();

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
            $query->where('status', $status);
        }

        // KYC status filter
        if ($kycStatus = $request->input('kyc_status')) {
            $query->where('kyc_status', $kycStatus);
        }

        // KYB status filter
        if ($kybStatus = $request->input('kyb_status')) {
            $query->where('kyb_status', $kybStatus);
        }

        // User type filter
        if ($userType = $request->input('user_type')) {
            $query->where('user_type', $userType);
        }

        // Account type filter
        if ($accountType = $request->input('account_type')) {
            $query->where('account_type', $accountType);
        }

        // Active status filter
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
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

        // Has transaction pin filter
        if ($request->has('has_transaction_pin')) {
            if ($request->boolean('has_transaction_pin')) {
                $query->whereNotNull('transaction_pin');
            } else {
                $query->whereNull('transaction_pin');
            }
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = [
            'created_at', 'name', 'email', 'username', 'status', 
            'kyc_status', 'kyb_status', 'user_type', 'country'
        ];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $perPage = min($perPage, 100); // Max 100 items per page

        $users = $query->with(['wallet', 'subscription'])->paginate($perPage);

        // Transform users data
        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'status' => $user->status,
                'avatar' => $user->avatar,
                'country' => $user->country,
                'user_type' => $user->user_type,
                'account_type' => $user->account_type,
                'kyc_status' => $user->kyc_status,
                'kyb_status' => $user->kyb_status,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
                'has_transaction_pin' => $user->has_transaction_pin,
                'has_wallet' => !is_null($user->wallet),
                'wallet_balance' => $user->wallet ? $user->wallet->amount->getAmount()->toFloat() : 0,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        return $users;
    }

    /**
     * Get user statistics
     */
    public function getUserStats(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $suspendedUsers = User::where('is_active', false)->count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $kycVerifiedUsers = User::where('kyc_status', 'SUCCESSFUL')->count();
        $usersWithWallet = User::has('wallet')->count();
        $usersCreatedToday = User::whereDate('created_at', today())->count();
        $usersCreatedThisWeek = User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $usersCreatedThisMonth = User::whereMonth('created_at', now()->month)->count();

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'suspended_users' => $suspendedUsers,
            'verified_users' => $verifiedUsers,
            'kyc_verified_users' => $kycVerifiedUsers,
            'users_with_wallet' => $usersWithWallet,
            'users_created_today' => $usersCreatedToday,
            'users_created_this_week' => $usersCreatedThisWeek,
            'users_created_this_month' => $usersCreatedThisMonth,
        ];
    }

    /**
     * Create a new user
     */
    public function createUser(array $data): User
    {
        // Check if user with email or username already exists
        if (User::where('email', $data['email'])->exists()) {
            throw new InvalidArgumentException('User with this email already exists');
        }

        if (User::where('username', $data['username'])->exists()) {
            throw new InvalidArgumentException('User with this username already exists');
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'status' => $data['status'] ?? UserStatusEnum::ACTIVE->value,
                'user_type' => $data['user_type'] ?? 'individual',
                'account_type' => $data['account_type'] ?? 'main',
                'country' => $data['country'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'email_verified_at' => $data['verify_email'] ?? false ? now() : null,
            ]);

            DB::commit();

            return $user->fresh();
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
            'wallet.virtualBankAccount',
            'transactions' => fn($q) => $q->latest()->limit(10),
            'linkedBankAccounts',
            'referrals',
            'referrer',
            'subscription'
        ])->find($userId);

        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'status' => $user->status,
            'avatar' => $user->avatar,
            'country' => $user->country,
            'referral_code' => $user->referral_code,
            'user_type' => $user->user_type,
            'account_type' => $user->account_type,
            'kyc_status' => $user->kyc_status,
            'kyb_status' => $user->kyb_status,
            'bvn_status' => $user->bvn_status,
            'nin_status' => $user->nin_status,
            'is_active' => $user->is_active,
            'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
            'has_transaction_pin' => $user->has_transaction_pin,
            'has_panic_pin' => $user->has_panic_pin,
            'last_logged_in_device' => $user->last_logged_in_device,
            'wallet' => $user->wallet ? [
                'balance' => $user->wallet->amount->getAmount()->toFloat(),
                'currency' => $user->wallet->currency,
                'account_number' => $user->wallet->virtualBankAccount?->account_number,
                'account_name' => $user->wallet->virtualBankAccount?->account_name,
                'bank_name' => $user->wallet->virtualBankAccount?->bank_name,
            ] : null,
            'total_transactions' => $user->transactions->count(),
            'linked_accounts_count' => $user->linkedBankAccounts->count(),
            'referrals_count' => $user->referrals->count(),
            'referred_by' => $user->referrer ? [
                'id' => $user->referrer->id,
                'name' => $user->referrer->name,
                'username' => $user->referrer->username,
            ] : null,
            'subscription' => $user->subscription,
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
        ];
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

        // Check username uniqueness if changed
        if (isset($data['username']) && $data['username'] !== $user->username) {
            if (User::where('username', $data['username'])->where('id', '!=', $userId)->exists()) {
                throw new InvalidArgumentException('Username already taken');
            }
        }

        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'username' => $data['username'] ?? null,
            'country' => $data['country'] ?? null,
            'status' => $data['status'] ?? null,
            'user_type' => $data['user_type'] ?? null,
            'is_active' => $data['is_active'] ?? null,
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
            if ($user->wallet) {
                resolve(WalletService::class)->destroy($user->wallet);
            }

            // Delete linked accounts
            if ($user->linkedBankAccounts) {
                $user->linkedBankAccounts()->delete();
            }

            // Delete sub accounts
            if ($user->subAccounts) {
                $user->subAccounts()->delete();
            }

            // Cancel subscription
            if ($user->subscription) {
                $user->subscription->update(['status' => 'CANCELLED']);
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

        $user->suspend();
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

        $user->activate();
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