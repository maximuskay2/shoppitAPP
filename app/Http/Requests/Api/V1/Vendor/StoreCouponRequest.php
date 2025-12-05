<?php

namespace App\Http\Requests\Api\V1\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
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
            'code' => 'nullable|string|unique:coupons,code|min:3|max:20|regex:/^[A-Z0-9]+$/',
            'discount_type' => 'required|in:percent,flat',
            'discount_amount' => 'required_if:discount_type,flat|numeric|min:0|max:999999.99',
            'percent' => 'required_if:discount_type,percent|integer|min:1|max:100',
            'minimum_order_value' => 'numeric|min:0|max:999999.99',
            'maximum_discount' => 'nullable|numeric|min:0|max:999999.99',
            'usage_per_customer' => 'integer|min:1|max:100',
            'is_visible' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'Coupon code must contain only uppercase letters and numbers.',
            'discount_amount.required_if' => 'Discount amount is required for flat discount type.',
            'percent.required_if' => 'Percent is required for percentage discount type.',
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

                // Maximum discount should not be unreasonably high (e.g., not more than ₦1,000,000)
                if ($maxDiscount > 1000000) {
                    $validator->errors()->add('maximum_discount', 'Maximum discount cannot exceed ₦1,000,000.');
                }
            }
        });
    }
}
