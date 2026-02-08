<?php

namespace App\Http\Requests\Admin\Settings;

use App\Helpers\ShopittPlus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @param  Validator  $validator
     */
    public function failedValidation(Validator $validator): void
    {
        $firstError = collect($validator->errors()->toArray())->flatten()->first();

        throw new HttpResponseException(
            ShopittPlus::response(false, $firstError ?? 'Validation error', 422)
        );
    }
}
