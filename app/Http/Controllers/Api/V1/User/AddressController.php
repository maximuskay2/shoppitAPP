<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\Addresses\CreateUserAddressRequest;
use App\Http\Requests\Api\V1\User\Addresses\UpdateUserAddressRequest;
use App\Http\Resources\User\AddressResource;
use App\Modules\User\Models\Address;
use App\Modules\User\Services\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;   

class AddressController extends Controller
{
    public function __construct(private readonly AddressService $addressService)
    {}

    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();

            return ShopittPlus::response(true, 'Addresses retrieved successfully', 200, AddressResource::collection($user->addresses));
        } catch (InvalidArgumentException $e) {
            Log::error('GET ADDRESSES: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('GET ADDRESSES: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve addresses', 500);
        }
    }

    public function store(CreateUserAddressRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $address = $this->addressService->store($user, $request->except('request_uuid'));
            return ShopittPlus::response(true, 'Address added successfully', 201, new AddressResource($address));
        } catch (InvalidArgumentException $e) {
            Log::error('ADD ADDRESS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('ADD ADDRESS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to add address', 500);
        }
    }

    public function update(string $id, UpdateUserAddressRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $address = $this->addressService->update($user, $id, $request->validated());
            return ShopittPlus::response(true, 'Address updated successfully', 200, new AddressResource($address));
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE ADDRESS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('UPDATE ADDRESS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update address', 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $this->addressService->destroy($user, $id);

            return ShopittPlus::response(true, 'Address deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('DELETE ADDRESS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DELETE ADDRESS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete address', 500);
        }
    }
}