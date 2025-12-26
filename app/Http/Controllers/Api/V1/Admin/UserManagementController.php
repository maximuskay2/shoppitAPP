<?php

namespace App\Http\Controllers\v1\Admin;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserManagement\CreateUserRequest;
use App\Http\Requests\Admin\UserManagement\UpdateUserRequest;
use App\Http\Requests\Admin\UserManagement\ChangeUserStatusRequest;
use App\Services\Admin\UserManagementService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UserManagementController extends Controller
{
    /**
     * Create a new UserManagementController instance.
     */
    public function __construct(
        private readonly UserManagementService $userManagementService
    ) {
    }

    /**
     * Get list of users with advanced filtering and sorting
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $users = $this->userManagementService->listUsers($request);

            return TransactX::response(true, 'Users retrieved successfully', 200, $users);
        } catch (InvalidArgumentException $e) {
            Log::error('LIST USERS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('LIST USERS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve users', 500);
        }
    }

    /**
     * Get user statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->userManagementService->getUserStats();

            return TransactX::response(true, 'User statistics retrieved successfully', 200, $stats);
        } catch (Exception $e) {
            Log::error('GET USER STATS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve user statistics', 500);
        }
    }

    /**
     * Create a new user
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $user = $this->userManagementService->createUser($validatedData);

            return TransactX::response(true, 'User created successfully', 201, (object) ['user' => $user]);
        } catch (InvalidArgumentException $e) {
            Log::error('CREATE USER: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('CREATE USER: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to create user', 500);
        }
    }

    /**
     * Get user details
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = $this->userManagementService->getUserDetails($id);

            return TransactX::response(true, 'User details retrieved successfully', 200, (object) ['user' => $user]);
        } catch (InvalidArgumentException $e) {
            Log::error('GET USER DETAILS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('GET USER DETAILS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve user details', 500);
        }
    }

    /**
     * Update user
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $user = $this->userManagementService->updateUser($id, $validatedData);

            return TransactX::response(true, 'User updated successfully', 200, (object) ['user' => $user]);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE USER: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE USER: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to update user', 500);
        }
    }

    /**
     * Delete user
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->userManagementService->deleteUser($id);

            return TransactX::response(true, 'User deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('DELETE USER: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('DELETE USER: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to delete user', 500);
        }
    }

    /**
     * Suspend user
     */
    public function suspend(string $id): JsonResponse
    {
        try {
            $user = $this->userManagementService->suspendUser($id);

            return TransactX::response(true, 'User suspended successfully', 200, (object) ['user' => $user]);
        } catch (InvalidArgumentException $e) {
            Log::error('SUSPEND USER: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('SUSPEND USER: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to suspend user', 500);
        }
    }

    /**
     * Activate user
     */
    public function activate(string $id): JsonResponse
    {
        try {
            $user = $this->userManagementService->activateUser($id);

            return TransactX::response(true, 'User activated successfully', 200, (object) ['user' => $user]);
        } catch (InvalidArgumentException $e) {
            Log::error('ACTIVATE USER: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('ACTIVATE USER: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to activate user', 500);
        }
    }

    /**
     * Change user status
     */
    public function changeStatus(ChangeUserStatusRequest $request, string $id): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $user = $this->userManagementService->changeUserStatus($id, $validatedData['status']);

            return TransactX::response(true, 'User status changed successfully', 200, (object) ['user' => $user]);
        } catch (InvalidArgumentException $e) {
            Log::error('CHANGE USER STATUS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('CHANGE USER STATUS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to change user status', 500);
        }
    }

    /**
     * Get user transactions
     */
    public function transactions(Request $request, string $id): JsonResponse
    {
        try {
            $transactions = $this->userManagementService->getUserTransactions($id, $request);

            return TransactX::response(true, 'User transactions retrieved successfully', 200, $transactions);
        } catch (InvalidArgumentException $e) {
            Log::error('GET USER TRANSACTIONS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('GET USER TRANSACTIONS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve user transactions', 500);
        }
    }

    /**
     * Get user wallet details
     */
    public function wallet(string $id): JsonResponse
    {
        try {
            $wallet = $this->userManagementService->getUserWallet($id);

            return TransactX::response(true, 'User wallet retrieved successfully', 200, (object) ['wallet' => $wallet]);
        } catch (InvalidArgumentException $e) {
            Log::error('GET USER WALLET: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('GET USER WALLET: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve user wallet', 500);
        }
    }

    /**
     * Get user linked bank accounts
     */
    public function linkedBankAccounts(Request $request, string $id): JsonResponse
    {
        try {
            $linkedBankAccounts = $this->userManagementService->getUserLinkedBankAccounts($id, $request->all());

            return TransactX::response(true, 'User linked bank accounts retrieved successfully', 200, $linkedBankAccounts);
        } catch (InvalidArgumentException $e) {
            Log::error('GET USER LINKED BANK ACCOUNTS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('GET USER LINKED BANK ACCOUNTS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve user linked bank accounts', 500);
        }
    }

    /**
     * Get user beneficiaries
     */
    public function beneficiaries(Request $request, string $id): JsonResponse
    {
        try {
            $beneficiaries = $this->userManagementService->getUserBeneficiaries($id, $request->all());

            return TransactX::response(true, 'User beneficiaries retrieved successfully', 200, $beneficiaries);
        } catch (InvalidArgumentException $e) {
            Log::error('GET USER BENEFICIARIES: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('GET USER BENEFICIARIES: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve user beneficiaries', 500);
        }
    }
}