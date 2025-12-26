<?php

namespace App\Http\Controllers\v1\Admin\Auth;

use App\Actions\Admin\Auth\LoginAdminAction;
use App\Dtos\Admin\LoginAdminDto;
use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginAdminRequest;
use App\Http\Resources\Admin\LoginAdminResource;
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

            return TransactX::response(true, 'Login successful', 200, new LoginAdminResource($data));
        } catch (Exception $e) {
            Log::error('LOGIN ADMIN: Error Encountered: ' . $e->getMessage());

            return TransactX::response(false, $e->getMessage(), 500);
        }
    }
}

// curl -X POST \
//   http://your-domain.com/api/v1/admin/auth/login \
//   -H 'Content-Type: application/json' \
//   -H 'Accept: application/json' \
//   -d '{
//     "email": "superadmin@example.com",
//     "password": "SecurePassword123!"
//   }