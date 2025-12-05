<?php

namespace App\Http\Requests\Api\V1\User\Cart;

use Illuminate\Foundation\Http\FormRequest;

class ProcessCartRequest extends FormRequest
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
            'receiver_delivery_address' => 'required|string|max:500',
            'receiver_name' => 'nullable|string|max:255',
            'receiver_email' => 'nullable|email|max:255',
            'receiver_phone' => 'nullable|string|max:20',
            'order_notes' => 'nullable|string|max:1000',
            'is_gift' => 'boolean',
            'coupon_code' => 'nullable|string|exists:coupons,code',
        ];
    }
}