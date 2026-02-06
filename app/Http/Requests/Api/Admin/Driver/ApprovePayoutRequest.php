<?php

namespace App\Http\Requests\Api\Admin\Driver;

use App\Helpers\ShopittPlus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApprovePayoutRequest extends FormRequest
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
            'APPROVE PAYOUT REQUEST: START',
            ["uid" => $this->request_uuid, "request" => $this->all()]
        );
    }

    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        $firstError = collect($errors)->flatten()->first();

        Log::channel('daily')->info(
            'APPROVE PAYOUT REQUEST: VALIDATION FAILED',
            ["uid" => $this->request_uuid, "response" => ['errors' => $errors]]
        );

        throw new HttpResponseException(
            ShopittPlus::response(false, $firstError, 422)
        );
    }
}
