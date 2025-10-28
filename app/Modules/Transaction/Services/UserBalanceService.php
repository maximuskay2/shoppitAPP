<?php

namespace App\Modules\Blockchain\Services;

use App\Modules\Blockchain\Models\Asset;
use App\Modules\Blockchain\Models\UserBalance;
use App\Modules\Blockchain\Models\Wallet;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserBalanceService
{
    /**
     * Create or ensure a balance record exists for a user on a specific asset/wallet.
     * Initializes balances to 0.0 if the record doesn't exist.
     */
    public static function createBalance(User $user, Asset $asset, Wallet $wallet): bool
    {
        $existingBalance = UserBalance::where('user_id', $user->id)
            ->where('asset_id', $asset->id)
            ->where('wallets_id', $wallet->id) // Updated to use $wallet->id
            ->first();

        if ($existingBalance) {
            Log::info('Balance already exists for user, asset, and wallet', [
                'user_id' => $user->id,
                'asset' => $asset->symbol,
                'wallet_id' => $wallet->id, // Updated to use $wallet->id
            ]);
            return true; // Already exists
        }

        try {
            DB::transaction(function () use ($user, $asset, $wallet) {
                $balance = new UserBalance();
                $balance->id = Str::uuid()->toString();
                $balance->user_id = $user->id;
                $balance->asset_id = $asset->id;
                $balance->wallets_id = $wallet->id;
                $balance->save();
            });

            Log::info('Balance created for user, asset, and wallet', [
                'user_id' => $user->id,
                'asset' => $asset->symbol,
                'wallet_id' => $wallet->id, // Updated to use $wallet->id
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create balance', [
                'user_id' => $user->id,
                'asset' => $asset->symbol,
                'wallet_id' => $wallet->id, // Updated to use $wallet->id
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get the available balance for a user on a specific asset/wallet.
     */
    public static function getBalance(User $user, Asset $asset, Wallet $wallet): float
    {
        $balance = UserBalance::where('user_id', $user->id)
            ->where('asset_id', $asset->id)
            ->where('wallets_id', $wallet->id)
            ->first();

        $balance = UserBalance::where('user_id', $user->id)
            ->where('asset_id', $asset->id)
            ->where('wallets_id', $wallet->id) // Updated to use $wallet->id
            ->first();

        return $balance ? (float) $balance->available_balance : 0.0;
    }

    /**
     * Update the available balance for a user on a specific asset/wallet.
     * Type: 'deposit' or 'withdrawal'. Recalculates total_balance.
     */
    public static function updateBalance(User $user, Asset $asset, Wallet $wallet, float $amount, string $type): bool
    {
        if ($amount <= 0) {
            Log::warning('Invalid amount for balance update', [
                'user_id' => $user->id,
                'asset' => $asset->symbol,
                'wallet_id' => $wallet->id, // Updated to use $wallet->id
                'amount' => $amount,
            ]);
            return false;
        }

        $balance = UserBalance::where('user_id', $user->id)
            ->where('asset_id', $asset->id)
            ->where('wallets_id', $wallet->id) // Updated to use $wallet->id
            ->first();

        if (!$balance) {
            Log::error('Balance record not found for update', [
                'user_id' => $user->id,
                'asset' => $asset->symbol,
                'wallet_id' => $wallet->id, // Updated to use $wallet->id
            ]);
            return false;
        }

        DB::transaction(function () use ($balance, $amount, $type) {
            if ($type === 'deposit') {
                $balance->increment('available_balance', $amount);
            } elseif ($type === 'withdrawal') {
                if ($balance->available_balance < $amount) {
                    throw new \Exception('Insufficient available balance');
                }
                $balance->decrement('available_balance', $amount);
            } else {
                throw new \Exception('Invalid update type');
            }

            // Recalculate total_balance
            $balance->total_balance = $balance->available_balance + $balance->locked_balance + $balance->vault_balance;
            $balance->save();
        });

        return true;
    }

    /**
     * Check if the user has sufficient available balance for a transaction on a specific asset/wallet.
     */
    public static function hasSufficientBalance(User $user, Asset $asset, Wallet $wallet, float $amount): bool
    {
        $availableBalance = self::getBalance($user, $asset, $wallet);
        return $availableBalance >= $amount;
    }

    /**
     * Get all balances for a user across all assets/wallets.
     */
    public static function getAllBalances(User $user): array
    {
        $balances = UserBalance::with('asset')->where('user_id', $user->id)->get();
        $result = [];

        foreach ($balances as $balance) {
            $result[$balance->asset->symbol] = [
                'available' => (float) $balance->available_balance,
                'locked' => (float) $balance->locked_balance,
                'vault' => (float) $balance->vault_balance,
                'total' => (float) $balance->total_balance,
            ];
        }

        return $result;
    }

    /**
     * Lock/unlock balance for pending transactions (e.g., orders).
     * Type: 'lock' or 'unlock'.
     */
    public static function lockBalance(User $user, Asset $asset, Wallet $wallet, float $amount, string $type): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $balance = UserBalance::where('user_id', $user->id)
            ->where('asset_id', $asset->id)
            ->where('wallets_id', $wallet->id) // Updated to use $wallet->id
            ->first();

        if (!$balance) {
            return false;
        }

        DB::transaction(function () use ($balance, $amount, $type) {
            if ($type === 'lock') {
                if ($balance->available_balance < $amount) {
                    throw new \Exception('Insufficient available balance to lock');
                }
                $balance->decrement('available_balance', $amount);
                $balance->increment('locked_balance', $amount);
            } elseif ($type === 'unlock') {
                if ($balance->locked_balance < $amount) {
                    throw new \Exception('Insufficient locked balance to unlock');
                }
                $balance->increment('available_balance', $amount);
                $balance->decrement('locked_balance', $amount);
            }

            $balance->total_balance = $balance->available_balance + $balance->locked_balance + $balance->vault_balance;
            $balance->save();
        });

        return true;
    }

    /**
     * Sync balance from blockchain/third-party (placeholder; implement based on your microservice).
     */
    public static function syncBalance(User $user, Asset $asset, Wallet $wallet): bool
    {
        // Placeholder: Call your microservice to get balance
        // $response = Http::get("https://your-microservice.com/balance/{$user->id}/{$asset->symbol}/{$wallet->id}");
        // if ($response->successful()) {
        //     $realBalance = $response->json()['available_balance'];
        //     $balance = UserBalance::where('user_id', $user->id)->where('asset_id', $asset->id)->where('wallets_id', $wallet->id)->first();
        //     if ($balance) {
        //         $balance->update(['available_balance' => $realBalance]);
        //         $balance->total_balance = $balance->available_balance + $balance->locked_balance + $balance->vault_balance;
        //         $balance->save();
        //         return true;
        //     }
        // }
        Log::info('Balance sync not implemented yet', [
            'user_id' => $user->id,
            'asset' => $asset->symbol,
            'wallet_id' => $wallet->id,
        ]);
        return false;
    }
}