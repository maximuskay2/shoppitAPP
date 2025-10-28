<?php

namespace App\Modules\Blockchain\Services;

use App\Modules\Blockchain\Enums\WalletStatusEnum;
use App\Modules\Blockchain\Models\Asset;
use App\Modules\User\Models\User; // Add this import

class AssetService
{
    public function getAssets(User $user) // Add User parameter
    {
        $assets = Asset::where('is_active', true)
            ->with([
                'userBalances' => function ($query) use ($user) {
                    // $query->where('is_testnet', false)
                    //     ->select('id', 'name', 'compatibility_id') // Use correct column name (with typo)
                    //     ->with([
                    //         }
                    //     ]);
                }
            ])
            ->get();

        // // Flatten wallets to network for easier access
        // $assets->each(function ($asset) {
        //     if ($asset->network && $asset->network->compatibility) {
        //         $asset->network->wallets = $asset->network->compatibility->wallets;
        //     }
        // });

        return $assets;
    }

    public function getDepositAssets(User $user) // Add User parameter
    {
        $assets = Asset::where('is_active', true)
            ->with([
                'network' => function ($query) use ($user) {
                    $query->where('is_testnet', false)
                        ->select('id', 'name', 'compatibility_id') // Use correct column name (with typo)
                        ->with([
                            'compatibility' => function ($compQuery)  use ($user) {
                                $compQuery->select('id', 'name', 'slug')
                                    ->with([
                                        'wallets' => function ($walletQuery) use ($user) { // Pass user to closure
                                            $walletQuery->where('user_id', $user->id) // Filter by user
                                                ->where('status', WalletStatusEnum::ACTIVE)
                                                ->select('id', 'compatibility_id', 'address', 'memo');
                                        }
                                    ]);
                            }
                        ]);
                }
            ])
            ->get();

        // Flatten wallets to network for easier access
        $assets->each(function ($asset) {
            if ($asset->network && $asset->network->compatibility) {
                $asset->network->wallets = $asset->network->compatibility->wallets;
            }
        });

        return $assets;
    }
}