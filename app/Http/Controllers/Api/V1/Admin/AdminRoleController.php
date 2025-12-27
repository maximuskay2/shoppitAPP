<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Role\CreateRoleRequest;
use App\Http\Requests\Admin\Role\UpdateRoleRequest;
use App\Services\Admin\AdminRoleService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AdminRoleController extends Controller
{
    /**
     * Create a new AdminRoleController instance.
     */
    public function __construct(
        private readonly AdminRoleService $adminRoleService
    ) {}

    /**
     * Get list of roles with advanced filtering and sorting
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $roles = $this->adminRoleService->getRoles($request->all());

            return ShopittPlus::response(true, 'Roles retrieved successfully', 200, $roles);
        } catch (Exception $e) {
            Log::error('LIST ROLES: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve roles', 500);
        }
    }

    /**
     * Create a new role
     */
    public function store(CreateRoleRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $role = $this->adminRoleService->createRole($validatedData);

            return ShopittPlus::response(true, 'Role created successfully', 201, (object) ['role' => $role]);
        } catch (InvalidArgumentException $e) {
            Log::error('CREATE ROLE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('CREATE ROLE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create role', 500);
        }
    }

    /**
     * Get role details
     */
    public function show(string $id): JsonResponse
    {
        try {
            $role = $this->adminRoleService->getRole($id);

            return ShopittPlus::response(true, 'Role details retrieved successfully', 200, (object) ['role' => $role]);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Role not found') {
                return ShopittPlus::response(false, $e->getMessage(), 404);
            }
            Log::error('GET ROLE DETAILS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve role details', 500);
        }
    }

    /**
     * Update role
     */
    public function update(UpdateRoleRequest $request, string $id): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $role = $this->adminRoleService->updateRole($id, $validatedData);

            return ShopittPlus::response(true, 'Role updated successfully', 200, (object) ['role' => $role]);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE ROLE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE ROLE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update role', 500);
        }
    }

    /**
     * Delete role
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->adminRoleService->deleteRole($id);

            return ShopittPlus::response(true, 'Role deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('DELETE ROLE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('DELETE ROLE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete role', 500);
        }
    }
}