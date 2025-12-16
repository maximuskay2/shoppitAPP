<?php

namespace App\Http\Requests\Api\V1\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCouponRequest extends FormRequest
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
            'code' => 'sometimes|string|unique:coupons,code,' . $this->route('id') . '|min:3|max:20|regex:/^[A-Z0-9]+$/',
            'discount_type' => 'sometimes|in:percent,flat',
            'discount_amount' => 'sometimes|numeric|min:0|max:999999.99',
            'percent' => 'sometimes|integer|min:1|max:100',
            'minimum_order_value' => 'sometimes|numeric|min:0|max:999999.99',
            'maximum_discount' => 'nullable|numeric|min:0|max:999999.99',
            'usage_per_customer' => 'sometimes|integer|min:1|max:100',
            'is_visible' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'Coupon code must contain only uppercase letters and numbers.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $discountType = $this->input('discount_type');

            // For percentage discounts, ensure maximum_discount is reasonable
            if ($discountType === 'percent' && $this->filled('maximum_discount')) {
                $maxDiscount = $this->input('maximum_discount');

                // Maximum discount should not be unreasonably high (e.g., not more than NGN 1,000,000)
                if ($maxDiscount > 1000000) {
                    $validator->errors()->add('maximum_discount', 'Maximum discount cannot exceed NGN1,000,000.');
                }
            }

            // Validate that required fields are present based on discount_type
            if ($discountType === 'flat' && !$this->filled('discount_amount')) {
                $validator->errors()->add('discount_amount', 'Discount amount is required for flat discount type.');
            }

            if ($discountType === 'percent' && !$this->filled('percent')) {
                $validator->errors()->add('percent', 'Percent is required for percentage discount type.');
            }
        });
    }
}
