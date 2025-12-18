<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\SetUpVendorProfileRequest;
use App\Http\Requests\Api\V1\Vendor\UpdateVendorDetailsRequest;
use App\Http\Resources\User\VendorResource;
use App\Modules\Commerce\Services\SubscriptionService;
use App\Modules\Transaction\Enums\SubscriptionStatusEnum;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Services\WalletService;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use App\Modules\User\Services\IdentityService;
use App\Modules\User\Services\UserService;
use App\Modules\User\Services\VendorService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class VendorController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly VendorService $vendorService,
        // private readonly CloudinaryService $cloudinaryService
    ) {}

    public function setUpVendorProfile(SetUpVendorProfileRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $user = User::find(Auth::id());

            $validatedData = $request->validated();    
            $cac = $validatedData['cac'] ?? null;
            $tin = $validatedData['tin'] ?? null;
                        
            $user = $this->userService->updateUserAccount($user, [
                'name' => $validatedData['full_name'],
                'phone' => '+234' . $validatedData['phone'],
                'state' => $validatedData['state'],
                'city' => $validatedData['city'],
                'address' => $validatedData['address'],
                'address_2' => isset($validatedData['address_2']) ? $validatedData['address_2'] : null,
            ]);
            
            $vendor = $this->vendorService->createVendorAccount($user, [
                'business_name' => $validatedData['business_name'],
                'tin' => $validatedData['tin'],
                'cac' => $validatedData['cac'],
            ]);
            
            if ($vendor->isKybVerified()) {
                throw new InvalidArgumentException("Kyb has already been verified");
            }
            
            if (!is_null($cac) && Vendor::withCac($cac)->exists()) {
                throw new InvalidArgumentException("CAC already exists");
            }
            
            if (!is_null($tin) && Vendor::withTin($tin)->exists()) {
                throw new InvalidArgumentException("TIN already exists");
            }

            $verification_data = (object)[
                'state' => $validatedData['state'],
                'city' => $validatedData['city'],
                'business_name' => $validatedData['business_name'],
                'tin' => $validatedData['tin'],
                'cac' => $validatedData['cac'],
                'email' => $user->email,
                'vendor' => $vendor
            ];

            $identityService = resolve(IdentityService::class);
            $identityService->verifyBusiness($verification_data);
            
            $free_subscription = SubscriptionPlan::where('key', 1)
                ->where('status', SubscriptionStatusEnum::ACTIVE)
                ->first();
            resolve(SubscriptionService::class)->createSubscription($vendor, $free_subscription);

            app(WalletService::class)->create($user);
            DB::commit();
            return ShopittPlus::response(true, 'Vendor profile setup successfully', 201, (object) ["user" => new VendorResource($vendor)]);
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('SETUP VENDOR PROFILE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('SETUP VENDOR PROFILE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to setup vendor profile', 500);
        }
    }

    public function getVendorDetails(): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $vendor = $user->vendor;

            return ShopittPlus::response(true, 'Vendor details retrieved successfully', 200, new VendorResource($vendor));
        } catch (Exception $e) {
            Log::error('GET VENDOR DETAILS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve vendor details', 500);
        }
    }

    public function updateVendorDetails(UpdateVendorDetailsRequest $request): JsonResponse
    {
        try {
            $user = User::find(Auth::id());

            $vendor = $this->vendorService->updateVendorAccount($user->vendor, $request->validated());
            return ShopittPlus::response(true, 'Vendor details updated successfully', 200, new VendorResource($vendor));
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE VENDOR DETAILS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE VENDOR DETAILS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update vendor details', 500);
        }
    }
}