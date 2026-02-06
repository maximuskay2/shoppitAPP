<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Driver\VerifyDriverRequest;
use App\Http\Requests\Api\Admin\Driver\ReassignOrderRequest;
use App\Modules\User\Services\Admin\DriverManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class DriverManagementController extends Controller
{
    public function __construct(private readonly DriverManagementService $driverManagementService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $drivers = $this->driverManagementService->listDrivers($request);

            return ShopittPlus::response(true, 'Drivers retrieved successfully', 200, $drivers);
        } catch (InvalidArgumentException $e) {
            Log::error('LIST DRIVERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('LIST DRIVERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve drivers', 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $driver = $this->driverManagementService->getDriverDetails($id);

            return ShopittPlus::response(true, 'Driver details retrieved successfully', 200, $driver);
        } catch (InvalidArgumentException $e) {
            Log::error('GET DRIVER DETAILS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 404);
        } catch (\Exception $e) {
            Log::error('GET DRIVER DETAILS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve driver details', 500);
        }
    }

    public function verify(VerifyDriverRequest $request, string $id): JsonResponse
    {
        try {
            $driver = $this->driverManagementService->verifyDriver(
                $id,
                $request->boolean('approved'),
                $request->input('reason')
            );

            return ShopittPlus::response(true, 'Driver verification updated successfully', 200, $driver);
        } catch (InvalidArgumentException $e) {
            Log::error('VERIFY DRIVER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('VERIFY DRIVER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update driver verification', 500);
        }
    }

    public function block(string $id): JsonResponse
    {
        try {
            $driver = $this->driverManagementService->blockDriver($id, null);

            return ShopittPlus::response(true, 'Driver blocked successfully', 200, $driver);
        } catch (InvalidArgumentException $e) {
            Log::error('BLOCK DRIVER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('BLOCK DRIVER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to block driver', 500);
        }
    }

    public function unblock(string $id): JsonResponse
    {
        try {
            $driver = $this->driverManagementService->unblockDriver($id);

            return ShopittPlus::response(true, 'Driver unblocked successfully', 200, $driver);
        } catch (InvalidArgumentException $e) {
            Log::error('UNBLOCK DRIVER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('UNBLOCK DRIVER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to unblock driver', 500);
        }
    }

    public function locations(): JsonResponse
    {
        try {
            $locations = $this->driverManagementService->getLatestLocations();

            return ShopittPlus::response(true, 'Driver locations retrieved successfully', 200, $locations);
        } catch (\Exception $e) {
            Log::error('DRIVER LOCATIONS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve driver locations', 500);
        }
    }

    public function reassignOrder(ReassignOrderRequest $request, string $id): JsonResponse
    {
        try {
            $order = $this->driverManagementService->reassignOrder(
                $id,
                $request->validated()['driver_id'],
                $request->input('reason')
            );

            return ShopittPlus::response(true, 'Order reassigned successfully', 200, $order);
        } catch (InvalidArgumentException $e) {
            Log::error('REASSIGN ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('REASSIGN ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to reassign order', 500);
        }
    }
}
