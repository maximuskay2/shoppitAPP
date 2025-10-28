<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegisterRequest extends FormRequest
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
     * Prepare the data for validation.
     */
    public function prepareForValidation(): void
    {
        $this->request_uuid = Str::uuid()->toString();

        Log::channel('daily')->info(
            'REGISTER REQUEST: START',
            ["uid" => $this->request_uuid, "request" => $this->except(['password', 'password_confirmation'])]
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users'],
        ];
    }

    /**
     * Modify the validated data.
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        return array_merge($data, [
            'request_uuid' => $this->request_uuid,
        ]);
    }

    /**
     * Handle a failed validation attempt.
     */
    public function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        $firstError = collect($errors)->flatten()->first();

        Log::channel('daily')->info(
            'REGISTER REQUEST: VALIDATION FAILED',
            ["uid" => $this->request_uuid, "response" => ['errors' => $errors]]
        );

        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => $firstError], 422)
        );
    }
}