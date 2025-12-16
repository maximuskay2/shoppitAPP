<?php

namespace App\Modules\User\Services;

use App\Modules\User\Models\User;
use InvalidArgumentException;

class AddressService 
{
    public function store (User $user, array $data)
    {
        if ($user->addresses) {
            $user->addresses()->where('is_active', true)->update(['is_active' => false]);
        }
        return $user->addresses()->create($data);
    }

    public function update (User $user, string $id, array $data)
    {
        $address = $user->addresses()->where('id', $id)->first();

        if (!$address) {
            throw new InvalidArgumentException('Address not found');
        }

        if (isset($data['is_default']) && $data['is_default'] == true) {
            $user->addresses()->where('is_default', true)->update(['is_default' => false]);
        }

        if (isset($data['is_active']) && $data['is_active'] == true) {
            $user->addresses()->where('is_active', true)->update(['is_active' => false]);
        }

        $address->update([
            'address' => $data['address'] ?? $address->address,
            'city' => $data['city'] ?? $address->city,
            'state' => $data['state'] ?? $address->state,
            'country' => $data['country'] ?? $address->country,
            'is_default' => $data['is_default'] ?? $address->is_default,
            'is_active' => $data['is_active'] ?? $address->is_active,
        ]);
        return $address;
    }

    public function destroy (User $user, string $id): void
    {
        $address = $user->addresses()->where('id', $id)->first();

        if (!$address) {
            throw new InvalidArgumentException('Address not found');
        }

        $address->delete();
    }
}