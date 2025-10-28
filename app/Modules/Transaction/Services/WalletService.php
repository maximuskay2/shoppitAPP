<?php

namespace App\Modules\Blockchain\Services;

use App\Modules\Blockchain\Models\Wallet;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletService
{
    public $microserviceUrl;
    public function __construct()
    {
        $this->microserviceUrl = config('app.node_microservice_url') . '/api/wallet/create-wallet';
    }
    /**
     * Retrieve all wallets for the authenticated user.
     */
    public function getUserWallets()
    {
        $user = Auth::user();

        $wallets = Wallet::with('network')
            ->where('user_id', $user->id)
            ->get();

        return $wallets;
    }

    /**
     * Create wallets for all supported networks by calling the Node.js microservice.
     */
    public function createWalletsForUser(User $user): array
    {
        try {
            DB::beginTransaction();
            // Call the microservice
            $response = Http::post($this->microserviceUrl);

            if (!$response->successful()) {
                Log::error('Microservice wallet creation failed', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to create wallets from microservice');
            }

            $data = $response->json();
            if (!$data['success'] || !isset($data['wallets'])) {
                Log::error('Invalid microservice response', ['user_id' => $user->id, 'data' => $data]);
                throw new \Exception('Invalid response from microservice');
            }

            $walletsCreated = [];
            foreach ($data['wallets'] as $compatibilitySlug => $walletData) {
                // Eager load networks and their assets to avoid lazy loading
                $compatibility = \App\Modules\Blockchain\Models\Compatibility::with('networks.assets')
                    ->where('slug', $compatibilitySlug)
                    ->first();
                if (!$compatibility) {
                    Log::warning("Compatibility not found for slug: {$compatibilitySlug}", ['user_id' => $user->id]);
                    continue;
                }

                // Check if wallet already exists for this user and compatibility
                $existingWallet = Wallet::where('user_id', $user->id)
                    ->where('compatibility_id', $compatibility->id)
                    ->first();
                if ($existingWallet) {
                    continue; // Skip if already exists
                }

                // Map data from microservice response
                $wallet = new Wallet();
                $wallet->id = Str::uuid()->toString();
                $wallet->user_id = $user->id;
                $wallet->compatibility_id = $compatibility->id;
                $wallet->address = $walletData['address'] ?? null;
                $wallet->publicKey = $walletData['publicKey'] ?? null;
                $wallet->privateKey = $walletData['privateKey'] ?? null;
                $wallet->mnemonics = is_array($walletData['mnemonic'] ?? null)
                    ? json_encode($walletData['mnemonic'])
                    : ($walletData['mnemonic'] ?? null);
                $wallet->keys = json_encode($walletData); // Store full data for reference
                $wallet->memo = $walletData['memo'] ?? null; // Set if needed
                $wallet->save();
                $walletsCreated[] = $wallet;

                // Create UserBalance record for this wallet and all assets in the compatibility
                foreach ($compatibility->networks as $network) {
                    foreach ($network->assets as $asset) {
                        // Create UserBalance
                        $userBalanceService = new UserBalanceService();
                        $createdUserBalance = $userBalanceService->createBalance($user, $asset, $wallet);
                        if (!$createdUserBalance) {
                            DB::rollBack();
                            Log::error('Failed to create user balance', [
                                'user_id' => $user->id,
                                'asset_id' => $asset->id,
                                'wallet_id' => $wallet->id,
                            ]);
                            throw new \Exception('Failed to create user balance');  
                        }
                    }
                }
            }

            DB::commit();
            return [
                'wallets' => $walletsCreated,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating wallets for user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Error creating wallets: ' . $e->getMessage());
        }
    }
}