<?php

namespace App\Modules\Transaction\Services;

use App\Modules\Transaction\Events\FundWalletSuccessful;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Models\Wallet;
use App\Modules\Transaction\Models\WalletTransaction;
use App\Modules\User\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransactionService
{
    public function getTransactionDescription(string $type, string $currency): ?string
    {
        return match ($type) {
            'SEND_MONEY' => "Sent $currency",
            'SEND_MONEY_FEE' => "Charged $currency fee",
            'FUND_WALLET' => "Funded $currency wallet",
            'FUND_WALLET_FEE' => "Charged $currency fee",
            default => null,
        };
    }
    
    /**
     * Create and return a new pending transaction
     *
     * @param User $user
     * @param float $amount
     * @param string $currency
     * @param string $type
     * @param ?string $userIp
     * 
     * @return Transaction
     */
    public function createPendingTransaction(
        User $user,
        $amount,
        $currency = 'NGN',
        $type = "FUND_WALLET",
        $reference,
        $payload,
        $wallet_id,
        $narration = null,
        $userIp = null,
        $external_transaction_reference = null
    ) {

        $description = $this->getTransactionDescription($type, $currency);
        $transaction = Transaction::create([
            'id' => Str::uuid(),
            "user_id" => $user->id,
            "currency" => $currency,
            "wallet_id" => $wallet_id,
            "amount" => Money::of($amount, $currency),
            "reference" => $reference,
            "payload" => $payload,
            "status" => "PENDING",
            "type" => $type,
            "description" => $description,
            "narration" => $narration,
            "user_ip" => $userIp,
            "external_transaction_reference" => $external_transaction_reference,
        ]);
        
        return $transaction;
    }

        /**
     * Create and return a new pending fee transaction
     *
     * @param User $user
     * @param float $amount
     * @param string $currency
     * @param string $type
     * @param ?string $userIp
     * 
     * @return Transaction
     */
    public function createPendingFeeTransaction(
        User $user,
        $amount,
        $currency = 'NGN',
        $type = "SEND_MONEY_FEE",
        $reference,
        $wallet_id,
        $principal_transaction_id,
    ) {

        $description = $this->getTransactionDescription($type, $currency);
        $transaction = Transaction::create([
            'id' => Str::uuid(),
            "user_id" => $user->id,
            "currency" => $currency,
            "wallet_id" => $wallet_id,
            "principal_transaction_id" => $principal_transaction_id,
            "amount" => Money::of($amount, $currency),
            "reference" => $reference,
            "status" => "PENDING",
            "type" => $type,
            "description" => $description,
        ]);
        
        return $transaction;
    }


    /**
     * Create and return a new successful transaction
     *
     * @param User $user
     * @param float $amount
     * @param string $currency
     * @param string $type
     * @param string $wallet_id
     * @param ?string $userIp
     * @param ?string $external_transaction_reference
     * 
     * @return Transaction
     */
    public function createSuccessfulTransaction(
        User $user,
        $wallet_id,
        $amount,
        $currency = 'NGN',
        $type = "SEND_MONEY",
        $userIp = null,
        $external_transaction_reference = null,
        $payload = null,
        $reference = null,
        $narration = null,
    ) {

        $description = $this->getTransactionDescription($type, $currency);

        $transaction = Transaction::create([
            "user_id" => $user->id,
            "wallet_id" => $wallet_id,
            "currency" => $currency,
            "amount" => Money::of($amount, $currency),
            "reference" => isset($reference) ? $reference  : Str::uuid(),
            "external_transaction_reference" => $external_transaction_reference,
            "status" => "SUCCESSFUL",
            "type" => $type,
            "payload" => $payload,
            "description" => $description,
            "user_ip" => $userIp,
            "narration" => $narration,
        ]);

        if ($transaction->isFundWalletTransaction()) {
            event(new FundWalletSuccessful($transaction));
        }

        return $transaction;
    }

    public function createSuccessfulFeeTransaction(
        User $user,
        $wallet_id,
        $amount,
        $currency = 'NGN',
        $type = "SEND_MONEY_FEE",
        $principal_transaction_id,
    ) {

        $description = $this->getTransactionDescription($type, $currency);

        $transaction = Transaction::create([
            "user_id" => $user->id,
            "wallet_id" => $wallet_id,
            "currency" => $currency,
            "amount" => Money::of($amount, $currency),
            "reference" => Str::uuid(),
            "status" => "SUCCESSFUL",
            "type" => $type,
            "description" => $description,
            "principal_transaction_id" => $principal_transaction_id,
        ]);

        return $transaction;
    }

    /**
     * Update Transaction with associated Wallet Transaction
     *
     * @param Transaction $transaction
     * @param Wallet $wallet
     * @param string|null $walletTransactionId
     * @return void
     */
    public function attachWalletTransactionFor(Transaction $transaction, Wallet $wallet, ?string $walletTransactionId = null)
    {
        $walletTransaction = null;

        if (is_null($walletTransactionId)) {
            $walletTransaction = WalletTransaction::with('wallet')->latest()->first();
        } else {
            $walletTransaction = WalletTransaction::with('wallet')->find($walletTransactionId);
        }

        $walletTransactionAmountChange = $walletTransaction->amount_change->getMinorAmount()->toInt();
        $transactionAmount = $transaction->amount * 100;
        $feeAmount = $transaction->feeTransactions()->first()->amount * 100;
        
        Log::info('TransactionService.attachWalletTransactionFor() - walletTransactionAmountChange: ' . $walletTransactionAmountChange . ',$transaction->amount: ' . $transaction->amount . ', transactionAmount: ' . $transactionAmount . ', feeAmount: ' . $feeAmount);
        // Due diligence check to ensure that the transaction originates from the wallet
        if ($transaction->isFundWalletTransaction()) {
            if ($wallet->is($walletTransaction->wallet) && $wallet->is($transaction->wallet) && $walletTransactionAmountChange == $transactionAmount - $feeAmount) {
                $this->updateTransaction($transaction, ['wallet_transaction_id' => $walletTransaction->id]);
            }
        } else {
            if ($wallet->is($walletTransaction->wallet) && $wallet->is($transaction->wallet) && $walletTransactionAmountChange == $transactionAmount + $feeAmount) {
                $this->updateTransaction($transaction, ['wallet_transaction_id' => $walletTransaction->id]);
            }
        }
    }

    /**
     * Update a transaction with new data.
     *
     * @param Transaction $transaction
     * @param array $data
     * @return Transaction
     */
    public function updateTransaction(Transaction $transaction, array $data)
    {
        // Check if 'status' is in the data array and remove it
        $status = null;
        if (isset($data['status'])) {
            $status = $data['status'];
            unset($data['status']);
        }

        $transaction->update([
            'external_transaction_reference' => $data['external_transaction_reference'] ?? $transaction->external_transaction_reference,
            'reference' => $data['reference'] ?? $transaction->reference,
            'wallet_id' => $data['wallet_id'] ?? $transaction->wallet_id,
            'description' => $data['description'] ?? $transaction->description,
            'wallet_transaction_id' => $data['wallet_transaction_id'] ?? $transaction->wallet_transaction_id,
            'amount'  => isset($data['amount']) ? Money::of($data['amount'], $transaction->currency) : $transaction->amount,
        ]);

        if ($status !== null) {
            $this->updateTransactionStatus($transaction, $status);
        }

        return $transaction;
    }

    /**
     * Update a transaction's status.
     *
     * @param Transaction $transaction
     * @param string $status
     * @return Transaction
     */
    public function updateTransactionStatus(Transaction $transaction, $status)
    {

        if (!in_array($status, ["SUCCESSFUL", "FAILED", "PENDING", "PROCESSING", "REVERSED"])) {
            throw new \Exception("TransactionService.updateTransactionStatus(): Invalid status: $status.");
        }

        $oldTransactionStatus = $transaction->status;

        $transaction->update([
            'status' => $status,
        ]);

        if ($status === "SUCCESSFUL" && $oldTransactionStatus !== "SUCCESSFUL") {
            // transaction state is changing to successful
            if ($transaction->isFundWalletTransaction()) {
                // Event
            }

        }

        return $transaction;
    }

    // public function getTransactionReceipt(User $user, string $reference): array
    // {
    //     $transaction = Transaction::with(['wallet.virtualBankAccount', 'feeTransactions'])
    //         ->where('reference', $reference)
    //         ->where('user_id', $user->id)
    //         ->where('principal_transaction_id', null)
    //         ->first();

    //     if (!$transaction) {
    //         throw new InvalidArgumentException('Transaction not found');
    //     }

    //     $fee = $transaction->feeTransactions->sum(function ($t) {
    //         return $t->amount->getAmount()->toFloat();
    //     });

    //     $payload = is_array($transaction->payload) ? $transaction->payload : (array) json_decode(json_encode($transaction->payload), true);

    //     $sender = [
    //         'name' => $transaction->payload['sender_name'] ?? null,
    //         'account_number' => $transaction->payload['sender_account_number'] ?? null,
    //         'bank_name' => $transaction->payload['sender_bank_name'] ?? null,
    //     ];

    //     $recipient = null;
    //     $service = null;

    //     // Payment / transfer style recipient
    //     if (in_array($transaction->type, ['SEND_MONEY'])) {
    //         $recipient = [
    //             'name' => $payload['name'] ?? null,
    //             'username' => $payload['username'] ?? null,
    //             'email' => $payload['email'] ?? null,
    //             'account_number' => $payload['account_number'] ?? null,
    //             'bank_code' => $payload['bank_code'] ?? null,
    //             'bank_name' => $payload['bank_name'] ?? null,
    //             'account_name' => $payload['account_name'] ?? null,
    //             'type' => $payload['type'] ?? null,
    //         ];
    //     } elseif ($transaction->type === 'REQUEST_MONEY') {
    //         $recipient = [
    //             'requested_from_name' => $payload['name'] ?? null,
    //             'requested_from_username' => $payload['username'] ?? null,
    //             'requested_from_email' => $payload['email'] ?? null,
    //             'status' => $payload['status'] ?? null,
    //             'type' => $payload['type'] ?? null,
    //         ];
    //     }

    //     // Utilities / services (AIRTIME, DATA, CABLETV, UTILITY)
    //     $serviceTypes = ['AIRTIME','DATA','CABLETV','UTILITY'];
    //     if (in_array($transaction->type, $serviceTypes)) {
    //         // Whitelist likely payload keys for service display
    //         $possibleKeys = [
    //             'phone_number','network','vendType', 'token', 'units', 'validity',
    //             'package','package_name','id','iuc_number',
    //             'number','customer_name','address','company','provider',
    //             'product_code','service_type','biller_code','account_number',
    //         ];
    //         $extracted = [];
    //         foreach ($possibleKeys as $k) {
    //             if (array_key_exists($k, $payload)) {
    //                 $extracted[$k] = $payload[$k];
    //             }
    //         }
    //         $service = [
    //             'category' => $transaction->type,
    //             'details' => $extracted,
    //         ];
    //     }

    //     // Fee transaction flag
    //     $isFee = str_ends_with($transaction->type, '_FEE');

    //     // Compute total debited (only for outward debit types, exclude FUND_WALLET)
    //     $debitBaseTypes = array_merge(['SEND_MONEY','SUBSCRIPTION','TRANSACTION_SYNC'], $serviceTypes);
    //     $totalDebited = in_array($transaction->type, $debitBaseTypes)
    //         ? $transaction->amount->getAmount()->toFloat() + $fee
    //         : $transaction->amount->getAmount()->toFloat();
        
    //     return [
    //         'reference' => $transaction->reference,
    //         'external_reference' => $transaction->external_transaction_reference,
    //         'type' => $transaction->type,
    //         'is_fee' => $isFee,
    //         'status' => $transaction->status,
    //         'description' => $transaction->description,
    //         'narration' => $transaction->narration,
    //         'amount' => $transaction->amount->getAmount()->toFloat(),
    //         'currency' => $transaction->currency,
    //         'fee' => $fee,
    //         'total_debited' => $totalDebited,
    //         'date' => $transaction->created_at->toDateTimeString(),
    //         'date_human' => $transaction->created_at->format('F j, Y g:i:s A'),
    //         'sender' => $sender,
    //         'recipient' => $recipient,
    //         'service' => $service,
    //         'payload' => $payload,
    //     ];
    // }
}
