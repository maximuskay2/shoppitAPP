<?php

namespace App\Http\Requests\Api\V1\User\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Helpers\ShopittPlus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProcessCartRequest extends FormRequest
{
    private string $request_uuid;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    /**
     * @return void
     */
    public function prepareForValidation(): void
    {
        $this->request_uuid = Str::uuid()->toString();

        Log::channel('daily')->info(
            'PROCESS CART: START',
            ["uid" => $this->request_uuid, "request" => $this->all()]
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'uuid', 'exists:vendors,id'],
            'payment_method_id' => ['nullable', 'sometimes', 'uuid', 'exists:payment_methods,id'],
            'receiver_delivery_address' => ['nullable', 'sometimes', 'string', 'max:500'],
            'receiver_name' => ['nullable', 'sometimes', 'string', 'max:255'],
            'receiver_email' => ['nullable', 'sometimes', 'email', 'max:255'],
            'receiver_phone' => ['nullable', 'sometimes', 'string', 'max:20'],
            'order_notes' => ['nullable', 'sometimes', 'string', 'max:1000' ],
            'wallet_usage' => ['boolean'],
            'is_gift' => ['boolean'], 
        ];
    }


    /**
     * @param $key
     * @param $default
     *
     * @return array
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        return array_merge($data, [
            'request_uuid' => $this->request_uuid,
        ]);
    }

   /**
     * @param  Validator  $validator
     *
     * @return void
     */
    public function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        
        // Get the first validation error message
        $firstError = collect($errors)->flatten()->first();

        Log::channel('daily')->info(
            'PROCESS CART: VALIDATION',
            ["uid" => $this->request_uuid, "response" => ['errors' => $errors]]
        );

       throw new HttpResponseException(
            ShopittPlus::response(false, $firstError, 422)
        );
    }
}