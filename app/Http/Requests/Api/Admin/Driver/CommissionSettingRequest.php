<?php

namespace App\Http\Requests\Api\Admin\Driver;

use App\Helpers\ShopittPlus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CommissionSettingRequest extends FormRequest
{
    private string $request_uuid;

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->request_uuid = Str::uuid()->toString();

        Log::channel('daily')->info(
            'COMMISSION SETTING REQUEST: START',
            ["uid" => $this->request_uuid, "request" => $this->all()]
        );
    }

    public function rules(): array
    {
        return [
            'driver_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'delivery_fee_commission' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_withdrawal' => ['nullable', 'numeric', 'min:0'],
            'driver_match_radius_km' => ['nullable', 'numeric', 'min:1', 'max:10000'],
            'driver_match_radius_active' => ['nullable', 'boolean'],
        ];
    }

    public function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        $firstError = collect($errors)->flatten()->first();

        Log::channel('daily')->info(
            'COMMISSION SETTING REQUEST: VALIDATION FAILED',
            ["uid" => $this->request_uuid, "response" => ['errors' => $errors]]
        );

        throw new HttpResponseException(
            ShopittPlus::response(false, $firstError, 422)
        );
    }
}
