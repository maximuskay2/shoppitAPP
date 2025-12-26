<?php

namespace App\Modules\Transaction\Services;

use App\Modules\Commerce\Models\Settings;
use App\Modules\Transaction\Events\FundWalletProccessed;
use App\Modules\Transaction\Events\WithdrawalProccessed;
use App\Modules\Transaction\Models\Wallet;
use App\Modules\Transaction\Models\WalletTransaction;
use App\Modules\User\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    public $currency;

    public function __construct() {
        $this->currency = Settings::where('name', 'currency')->first()->value;
    }

    public function create(User $user): void
    {
        if ($user->wallet) {
            return;
        }
        
        $user->wallet()->create([
            'id' => Str::uuid(),
            'amount' => 0,
            'currency' => $this->currency,
            'is_active' => true,
        ]);
    }

    public function getBalance(User $user)
    {
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (!$wallet) {
            throw new \Exception("Wallet not found for user id: {$user->id}");
        }

        return $wallet->balance;
    }

    public function addFunds(User $user, int $amount, ?string $ipAddress = null)
    {
        try {
            $amount = Money::of($amount, Settings::getValue('currency'));
            
            $paymentMethod = $user->paymentMethods()
                ->where('provider', 'paystack')
                ->where('method', 'card')
                ->whereNotNull('authorization_code')
                ->where('is_active', true)
                ->first();
                
                
            $paymentService = app(PaymentService::class);            
            $response = $paymentService->addFunds($user, $amount->getMinorAmount()->toInt(), $paymentMethod);
                
            event(new FundWalletProccessed($user->wallet, $amount->getAmount()->toFloat(), 0.0, $amount->getCurrency(), Str::uuid(), $response['reference'], 'Wallet Funding', $ipAddress, null));           
            
            if (isset($response['authorization_url'])) {
                return [
                    'authorization_url' => $response['authorization_url']
                ];
            }
            // return null;
        } catch (\Exception $e) {
            throw new \Exception('Failed to add funds: ' . $e->getMessage());
        }
    }
    
    public function withdrawFunds(User $user, array $data, ?string $ipAddress = null)
    {
        try {
            $amount = Money::of($data['amount'], Settings::getValue('currency'));
            
            $paymentDetail = $user->vendor->paymentDetails()
                ->where('id', $data['payment_detail_id'])
                ->first();                
                
            $paymentService = app(PaymentService::class);            
            $response = $paymentService->withdrawFunds($amount->getMinorAmount()->toInt(), $paymentDetail);
                
            event(new WithdrawalProccessed($user->wallet, $amount->getAmount()->toFloat(), 0.0, $amount->getCurrency(), Str::uuid(), $response['reference'], 'Wallet Withdrawal', $ipAddress, null));           
        } catch (\Exception $e) {
            throw new \Exception('Failed to wallet funds: ' . $e->getMessage());
        }
    }

        /**
     * Deposit money into the wallet.
     *
     * @param Wallet $wallet
     * @param float $amount
     * @return void
     */
    public function deposit(Wallet $wallet, $amount)
    {
        DB::transaction(function () use ($wallet, $amount) {

            $type = 'CREDIT';

            $amount = Money::of($amount, $wallet->currency);

            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            if (!$wallet) {
                throw new \Exception("Wallet not found. id: $wallet->id");
            }

            if ($wallet->amount->getCurrency() !== $amount->getCurrency()) {
                throw new \Exception("deposit(): The currencies do not match. Wallet currency: {$wallet->amount->getCurrency()}, incoming amount currency: {$amount->getCurrency()}. User ID: {$wallet->user_id}, wallet->currency: {$wallet->currency}");
            }

            $previous_amount = $wallet->amount;
            $wallet->amount = $wallet->amount->plus($amount);
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'currency' => $wallet->currency,
                'type' => $type,
                'previous_balance' => $previous_amount,
                'new_balance' => $wallet->amount,
                'amount_change' => $amount
            ]);
        });
    }

    
    public function debit(Wallet $wallet, $amount)
    {
        DB::transaction(function () use ($wallet, $amount) {

            $type = 'DEBIT';

            $amount = Money::of($amount, $wallet->currency);
            
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            if (!$wallet) {
                throw new \Exception("Wallet not found. id: $wallet->id");
            }

            if ($wallet->amount->getCurrency() !== $amount->getCurrency()) {
                throw new \Exception("deposit(): The currencies do not match. Wallet currency: {$wallet->amount->getCurrency()}, incoming amount currency: {$amount->getCurrency()}. User ID: {$wallet->user_id}, wallet->currency: {$wallet->currency}");
            }

            $previous_amount = $wallet->amount;
            $wallet->amount = $wallet->amount->minus($amount);
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'currency' => $wallet->currency,
                'type' => $type,
                'previous_balance' => $previous_amount,
                'new_balance' => $wallet->amount,
                'amount_change' => $amount
            ]);
        });
    }

}