<?php

namespace App\Modules\User\Actions\Admin\Auth;

use App\Helpers\ShopittPlus;
use App\Modules\User\Data\Admin\Auth\LoginAdminDto;
use App\Modules\User\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginAdminAction
{
    /**
     * Handle the registration process for a new admin.
     *
     * @param LoginAdminDto $loginAdminDto The data transfer object containing admin login information.
     * @param Request $request The request
     * @return array|JsonResponse Returns an array containing the admin object and token or an error json response.
     */
    public static function handle(LoginAdminDto $loginAdminDto, Request $request): array|JsonResponse
    {
        $admin = Admin::where('email', $loginAdminDto->email)->first();

        if (!$admin) {
            return ShopittPlus::response(false, 'The provided credentials are incorrect.', 401);
        }

        $passwordValid = false;
        try {
            $passwordValid = Hash::check($loginAdminDto->password, $admin->password);
        } catch (\Throwable $e) {
            // Stored password may be plain text or non-bcrypt (e.g. after migration). One-time upgrade.
            if ($admin->password === $loginAdminDto->password) {
                $admin->password = Hash::make($loginAdminDto->password);
                $admin->save();
                $passwordValid = true;
            }
            if (!$passwordValid) {
                Log::warning('ADMIN LOGIN: Stored password is not bcrypt or invalid', [
                    'admin_id' => $admin->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if (!$passwordValid) {
            return ShopittPlus::response(false, 'The provided credentials are incorrect.', 401);
        }

        // Force delete old tokens
        $admin->tokens()->delete();

        $token = $admin->createToken('AdminToken')->plainTextToken;


        try {
            Log::channel('daily')->info('ADMIN LOGIN: END', [
                "uid" => $loginAdminDto->request_uuid,
                "response" => ['admin_id' => $admin->id],
            ]);
        } catch (\Throwable $e) {
            // Do not fail login if logging fails
        }

        return ['admin' => $admin, 'token' => $token];
    }
}
