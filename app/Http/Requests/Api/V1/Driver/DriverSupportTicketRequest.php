<?php

namespace App\Http\Requests\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DriverSupportTicketRequest extends FormRequest
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
            'DRIVER SUPPORT TICKET REQUEST: START',
            ["uid" => $this->request_uuid, "request" => $this->all()]
        );
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:3', 'max:100'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
            'priority' => ['nullable', 'string', 'in:LOW,NORMAL,HIGH'],
            'meta' => ['nullable', 'array'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        return array_merge($data, [
            'request_uuid' => $this->request_uuid,
        ]);
    }

    public function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        $firstError = collect($errors)->flatten()->first();

        Log::channel('daily')->info(
            'DRIVER SUPPORT TICKET REQUEST: VALIDATION',
            ["uid" => $this->request_uuid, "response" => ['errors' => $errors]]
        );

        throw new HttpResponseException(
            ShopittPlus::response(false, $firstError, 422)
        );
    }
}
