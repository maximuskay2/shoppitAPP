<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\CreateUserPasswordRequest;
use App\Http\Requests\Api\V1\User\SetUpUserProfileRequest;
use App\Http\Requests\Api\V1\User\SetUpVendorProfileRequest;
use App\Http\Requests\Api\V1\User\UpdateUserAvatarRequest;
use App\Http\Requests\Api\V1\User\UpdateUserProfileRequest;
use App\Http\Requests\Api\V1\User\UpdateVendorProfileRequest;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\User\VendorResource;
use App\Modules\Commerce\Services\SubscriptionService;
use App\Modules\Transaction\Enums\SubscriptionStatusEnum;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Services\WalletService;
use App\Modules\User\Enums\UserKYBStatusEnum;
use App\Modules\User\Events\UserProfileUpdatedEvent;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use App\Modules\User\Services\AddressService;
use App\Modules\User\Services\CloudinaryService;
use App\Modules\User\Services\IdentityService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Modules\User\Services\UserService;
use App\Modules\User\Services\VendorService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly VendorService $vendorService,
        private readonly CloudinaryService $cloudinaryService
    ) {}

    public function getAuthentictedUser(Request $request) {
        try{
            $user = $this->userService->getAuthenticatedUser($request);
            return ShopittPlus::response(true, 'User retrieved successfully', 200, $user);
        } catch (InvalidArgumentException $e) {
            Log::error('GET AUTHENTICATED USER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('GET AUTHENTICATED USER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve authenticated user', 500);
        }
    }

    public function setUpProfile(SetUpUserProfileRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();            
            $user = Auth::user();            

            $user = $this->userService->updateUserAccount($user, [
                'name' => $validatedData['full_name'],
                'phone' => '+234' . $validatedData['phone'],
                'state' => $validatedData['state'],
                'city' => $validatedData['city'],
                'address' => $validatedData['address'],
                'address_2' => isset($validatedData['address_2']) ? $validatedData['address_2'] : null,
            ]);

            app(AddressService::class)->store($user, [
                'address' => $validatedData['address'],
                'city' => $validatedData['city'],
                'state' => $validatedData['state'],
                'is_default' => true,
            ]);
            app(WalletService::class)->create($user);
            
            DB::commit();
            return ShopittPlus::response(true, 'User profile setup successfully', 200, (object) ["user" => new UserResource($user)]);
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('SETUP USER PROFILE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('SETUP USER PROFILE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to setup user profile', 500);
        }
    }

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

    public function createPassword(CreateUserPasswordRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $validatedData = $request->validated();            
            $user = Auth::user();

            $user = $this->userService->updateUserAccount($user, [
                'password' => $validatedData['password'],
            ]);

            DB::commit();
            return ShopittPlus::response(true, 'User password created successfully', 200);
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('CREATE USER PASSWORD: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CREATE USER PASSWORD: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create user password', 500);
        }
    }

    public function updateProfile(UpdateUserProfileRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();            
            $user = Auth::user();       

            $user = $this->userService->updateUserAccount($user, [
                'name' => isset($validatedData['full_name']) ? $validatedData['full_name'] : $user->name,
                'phone' => isset($validatedData['phone']) ? '+234' . $validatedData['phone'] : $user->phone,
                'state' => isset($validatedData['state']) ? $validatedData['state'] : $user->state,
                'city' => isset($validatedData['city']) ? $validatedData['city'] : $user->city,
                'address' => isset($validatedData['address']) ? $validatedData['address'] : $user->address,
                'address_2' => isset($validatedData['address_2']) ? $validatedData['address_2'] : $user->address_2,
            ]);
            
            DB::commit();
            return ShopittPlus::response(true, 'User profile updated successfully', 200, (object) ["user" => new UserResource($user)]);
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('UPDATE USER PROFILE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('UPDATE USER PROFILE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update user profile', 500);
        }
    }

    public function updateVendorProfile(UpdateVendorProfileRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $validatedData = $request->validated();            
            $user = Auth::user();
            $vendor = $user->vendor;

            if ($vendor->isKybVerified()) {
                throw new InvalidArgumentException('Vendor profile has already been approved and cannot be updated.');
            }

            $user = $this->userService->updateUserAccount($user, [
                'name' => isset($validatedData['full_name']) ? $validatedData['full_name'] : $user->name,
                'phone' => isset($validatedData['phone']) ? '+234' . $validatedData['phone'] : $user->phone,
                'state' => isset($validatedData['state']) ? $validatedData['state'] : $user->state,
                'city' => isset($validatedData['city']) ? $validatedData['city'] : $user->city,
                'address' => isset($validatedData['address']) ? $validatedData['address'] : $user->address,
                'address_2' => isset($validatedData['address_2']) ? $validatedData['address_2'] : $user->address_2,
            ]);
            
            DB::commit();
            return ShopittPlus::response(true, 'Vendor profile updated successfully', 200, (object) ["user" => new VendorResource($vendor)]);
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('UPDATE VENDOR PROFILE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('UPDATE VENDOR PROFILE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update vendor profile', 500);
        }
    }

    public function updateAvatar(UpdateUserAvatarRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            
            $user = Auth::user();
            
            $uploadedFile = $validatedData['avatar'];
            
            $result = $this->cloudinaryService->uploadUserAvatar($uploadedFile, $user->id);
            
            $data = [
                'avatar' =>  $result['data']['secure_url']
            ];

            $user = $this->userService->updateUserAccount($user, $data);
            
            return ShopittPlus::response(true, 'User avatar updated successfully', 200, (object) ["user" => new UserResource($user)]);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE USER AVATAR: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE USER AVATAR: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update user avatar', 500);
        }
    }
}
