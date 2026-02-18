<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
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
            'LOGIN REQUEST: START',
            ["uid" => $this->request_uuid, "request" => $this->except(['password'])]
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'otp_code' => ['sometimes', 'nullable', 'string', 'min:4', 'max:10'],
            'fcm_device_token' => ['bail', 'sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    public function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        $firstError = collect($errors)->flatten()->first();

        Log::channel('daily')->info(
            'LOGIN REQUEST: VALIDATION FAILED',
            ["uid" => $this->request_uuid, "response" => ['errors' => $errors]]
        );

        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => $firstError], 422)
        );
    }
}