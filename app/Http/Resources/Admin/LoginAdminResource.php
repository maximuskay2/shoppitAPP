<?php

namespace App\Http\Resources\Admin;

use App\Modules\User\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this['admin']->name,
            'email' => $this['admin']->email,
            'role' => Role::find($this['admin']->role_id)->name,
            'is_super_admin' => $this['admin']->is_super_admin,
            'token' => $this['token'],
        ];
    }
}
