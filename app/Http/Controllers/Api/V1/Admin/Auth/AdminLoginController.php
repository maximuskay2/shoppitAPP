<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\LoginAdminRequest;
use App\Http\Resources\Admin\LoginAdminResource;
use App\Modules\User\Actions\Admin\Auth\LoginAdminAction;
use App\Modules\User\Data\Admin\Auth\LoginAdminDto;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdminLoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginAdminRequest $request)
    {
        try {
            $login_data = LoginAdminDto::from($request->validated());

            $data = LoginAdminAction::handle($login_data, $request);

            if ($data instanceof JsonResponse) {
                return $data;
            }

            return ShopittPlus::response(true, 'Login successful', 200, new LoginAdminResource($data));
        } catch (\Throwable $e) {
            try {
                Log::error('LOGIN ADMIN: Error Encountered: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            } catch (\Throwable $logErr) {
                // Ignore if logging fails
            }
            $message = $e instanceof Exception ? $e->getMessage() : 'Login failed. Please try again.';
            return ShopittPlus::response(false, $message, 500);
        }
    }
}