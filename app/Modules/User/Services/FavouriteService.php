<?php

namespace App\Modules\User\Services;

class FavouriteService
{
    public function getFavouriteVendors($user)
    {
        return $user->favourites()->where('favouritable_type', 'App\Modules\User\Models\Vendor')->with('favouritable')->get()->pluck('favouritable');
    }

    public function addFavouriteVendor($user, $vendorId)
    {
        $existingFavourite = $user->favourites()
            ->where('favouritable_type', 'App\Modules\User\Models\Vendor')
            ->where('favouritable_id', $vendorId)
            ->first();

        if ($existingFavourite) {
            throw new \InvalidArgumentException('Vendor is already in favourites.');
        }

        $favourite = new \App\Modules\User\Models\Favourite();
        $favourite->user_id = $user->id;
        $favourite->favouritable_type = 'App\Modules\User\Models\Vendor';
        $favourite->favouritable_id = $vendorId;
        $favourite->save();
    }

    public function removeFavouriteVendor($user, $vendorId)
    {
        $favourite = $user->favourites()
            ->where('favouritable_type', 'App\Modules\User\Models\Vendor')
            ->where('favouritable_id', $vendorId)
            ->first();

        if (!$favourite) {
            throw new \InvalidArgumentException('Vendor not found in favourites.');
        }

        $favourite->delete();
    }

    public function getFavouriteProducts($user)
    {
        return $user->favourites()->where('favouritable_type', 'App\Modules\Commerce\Models\Product')->with('favouritable')->get()->pluck('favouritable');
    }

    public function addFavouriteProduct($user, $productId)
    {
        $existingFavourite = $user->favourites()
            ->where('favouritable_type', 'App\Modules\Commerce\Models\Product')
            ->where('favouritable_id', $productId)
            ->first();

        if ($existingFavourite) {
            throw new \InvalidArgumentException('Product is already in favourites.');
        }

        $favourite = new \App\Modules\User\Models\Favourite();
        $favourite->user_id = $user->id;
        $favourite->favouritable_type = 'App\Modules\Commerce\Models\Product';
        $favourite->favouritable_id = $productId;
        $favourite->save();
    }

    public function removeFavouriteProduct($user, $productId)
    {
        $favourite = $user->favourites()
            ->where('favouritable_type', 'App\Modules\Commerce\Models\Product')
            ->where('favouritable_id', $productId)
            ->first();

        if (!$favourite) {
            throw new \InvalidArgumentException('Product not found in favourites.');
        }

        $favourite->delete();
    }
}