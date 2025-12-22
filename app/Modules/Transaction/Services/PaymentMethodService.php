<?php

namespace App\Modules\Transaction\Services;

use App\Modules\Transaction\Models\PaymentMethod;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentMethodService
{
    public function index (User $user)
    {
        return $user->paymentMethods;
    }

    public function initialize (User $user)
    {
        try {
            DB::beginTransaction();
            $paymentService = app(PaymentService::class);

            $response = $paymentService->initializePaymentMethod($user);

            $user->paymentMethods()->create([
                'provider' => 'paystack',
                'method' => 'card',
                'access_code' => $response['access_code'],
                'reference' => $response['reference'],
                'is_default' => false,
                'is_active' => true,
            ]);
            DB::commit();
            return [
                'authorization_url' => $response['authorization_url']
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to initialize payment method: ' . $e->getMessage());
        }
    }

    public function add (PaymentMethod $paymentMethod, array $data)
    {
        $paymentMethod->update($data);
    }

    public function destroy (User $user, string $paymentMethodId): void
    {
        $paymentMethod = $user->paymentMethods()->where('id', $paymentMethodId)->first();

        if (!$paymentMethod) {
            throw new \InvalidArgumentException('Payment method not found.');
        }

        $paymentMethod->delete();
    }
}