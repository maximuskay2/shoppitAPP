<?php
namespace App\Http\Requests\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Modules\User\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class KycInitializeRequest extends FormRequest
{
    private string $request_uuid;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->request_uuid = Str::uuid()->toString();

        Log::channel('daily')->info(
            'KYC LEVEL 1 INITIALIZATION: START',
            ["uid" => $this->request_uuid, "user_id" => $this->user()?->id, "request" => $this->all()]
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
            'first_name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'last_name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'date_of_birth' => 'required|date|before:-18 years|after:1900-01-01',
            'phone' => 'required|string|max:20|regex:/^[\+]?[0-9\-\(\)\s]+$/',
            'address' => 'required|string|max:500|min:10',
            'city' => 'required|string|max:100|regex:/^[a-zA-Z\s\-\']+$/',
            'state' => 'nullable|string|max:100|regex:/^[a-zA-Z\s\-\']+$/',
            'country' => 'required|string|max:100|regex:/^[a-zA-Z\s\-\']+$/',
            'postal_code' => 'required|string|max:20|min:3',
            'gender' => 'nullable|in:male,female,other',
            'nationality' => 'nullable|string|max:100|regex:/^[a-zA-Z\s\-\']+$/',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.regex' => 'First name can only contain letters and spaces.',
            'last_name.required' => 'Last name is required.',
            'last_name.regex' => 'Last name can only contain letters and spaces.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'You must be at least 18 years old.',
            'date_of_birth.after' => 'Please enter a valid date of birth.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Please enter a valid phone number.',
            'address.required' => 'Address is required.',
            'address.min' => 'Address must be at least 10 characters long.',
            'city.required' => 'City is required.',
            'city.regex' => 'City name can only contain letters, spaces, hyphens, and apostrophes.',
            'state.required' => 'State is required.',
            'state.regex' => 'State name can only contain letters, spaces, hyphens, and apostrophes.',
            'country.required' => 'Country is required.',
            'country.regex' => 'Country name can only contain letters, spaces, hyphens, and apostrophes.',
            'postal_code.required' => 'Postal code is required.',
            'postal_code.min' => 'Postal code must be at least 3 characters long.',
            'gender.in' => 'Gender must be male, female, or other.',
            'nationality.regex' => 'Nationality can only contain letters, spaces, hyphens, and apostrophes.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'date_of_birth' => 'date of birth',
            'phone' => 'phone number',
            'address' => 'address',
            'city' => 'city',
            'state' => 'state',
            'country' => 'country',
            'postal_code' => 'postal code',
            'gender' => 'gender',
            'nationality' => 'nationality',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  Validator  $validator
     * @return void
     *
     * @throws HttpResponseException
     */
    public function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        
        // Get the first validation error message
        $firstError = collect($errors)->flatten()->first();

        Log::channel('daily')->error(
            'KYC LEVEL 1 INITIALIZATION: VALIDATION FAILED',
            [
                "uid" => $this->request_uuid, 
                "user_id" => $this->user()?->id,
                "errors" => $errors
            ]
        );

        throw new HttpResponseException(
            ShopittPlus::response(false, $firstError, 422)
        );
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Additional custom validation logic can go here
            
            // Check if phone number already exists for another user
            if ($this->phone) {
                $existingUser = User::where('phone', $this->phone)
                    ->where('id', '!=', $this->user()->id)
                    ->first();
                
                if ($existingUser) {
                    $validator->errors()->add('phone', 'This phone number is already registered with another account.');
                }
            }

            // Validate date of birth is not in the future
            if ($this->date_of_birth && \Carbon\Carbon::parse($this->date_of_birth)->isFuture()) {
                $validator->errors()->add('date_of_birth', 'Date of birth cannot be in the future.');
            }
        });
    }
}