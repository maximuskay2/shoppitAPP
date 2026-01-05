<?php

namespace App\Http\Requests\Api\Admin\UserManagement;

use App\Helpers\ShopittPlus;
use App\Modules\User\Enums\UserStatusEnum;
use App\Rules\FullNameRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'UPDATE USER: START',
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
        $userId = $this->route('id');

        return [
            'name' => ['nullable', 'sometimes', 'string', 'max:255', new FullNameRule()],
            'email' => ['nullable', 'sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'sometimes', 'max:255', Rule::unique('users', 'phone')->ignore($userId)],
            'password' => ['nullable', 'sometimes', 'string', 'min:8'],
            'address' => ['nullable', 'sometimes', 'string'],
            'city' => ['nullable', 'sometimes', 'string'],
            'state' => ['nullable', 'sometimes', 'string'],
            'country' => ['nullable', 'sometimes', 'string'],
            'status' => ['nullable', 'sometimes', 'string', Rule::in(UserStatusEnum::toArray())],
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
            'UPDATE USER: VALIDATION',
            ["uid" => $this->request_uuid, "response" => ['errors' => $errors]]
        );

        throw new HttpResponseException(
            ShopittPlus::response(false, $firstError, 422)
        );
    }
}