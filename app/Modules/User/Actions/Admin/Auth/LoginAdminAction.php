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

        if (!$admin || !Hash::check($loginAdminDto->password, $admin->password)) {
            return ShopittPlus::response(false, 'The provided credentials are incorrect.', 401);
        }

        // Force delete old tokens
        $admin->tokens()->delete();

        $token = $admin->createToken('AdminToken')->plainTextToken;


        Log::channel('daily')->info('ADMIN LOGIN: END', [
            "uid" => $loginAdminDto->request_uuid,
            "response" => [
                'data' => $admin,
            ],
        ]);

        return ['admin' => $admin, 'token' => $token];
    }
}
