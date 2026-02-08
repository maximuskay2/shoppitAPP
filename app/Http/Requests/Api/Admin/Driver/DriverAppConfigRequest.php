<?php

namespace App\Http\Requests\Api\Admin\Driver;

use Illuminate\Foundation\Http\FormRequest;

class DriverAppConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'force_update' => ['nullable', 'boolean'],
            'min_version' => ['nullable', 'string', 'max:32'],
            'latest_version' => ['nullable', 'string', 'max:32'],
            'update_url' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:255'],
        ];
    }
}
