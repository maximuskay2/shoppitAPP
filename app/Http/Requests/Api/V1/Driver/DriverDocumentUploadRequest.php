<?php

namespace App\Http\Requests\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DriverDocumentUploadRequest extends FormRequest
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
            'DRIVER DOCUMENT UPLOAD REQUEST: START',
            [
                'uid' => $this->request_uuid,
                'driver_id' => $this->user()?->id,
                'document_type' => $this->input('document_type'),
            ]
        );
    }

    public function rules(): array
    {
        return [
            'document_type' => [
                'required',
                'string',
                'in:drivers_license,vehicle_registration,insurance,government_id',
            ],
            'document' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,pdf',
                'max:5120',
            ],
            'expires_at' => ['nullable', 'date'],
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
            'DRIVER DOCUMENT UPLOAD REQUEST: VALIDATION FAILED',
            [
                'uid' => $this->request_uuid,
                'response' => ['errors' => $errors],
            ]
        );

        throw new HttpResponseException(
            ShopittPlus::response(false, $firstError, 422, $errors)
        );
    }
}
