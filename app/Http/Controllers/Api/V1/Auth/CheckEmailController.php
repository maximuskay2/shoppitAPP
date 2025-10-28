<?php
namespace App\Http\Controllers\Api\V1\Auth;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CheckEmailController extends Controller
{
    public function __invoke(Request $request)
    {
        // Manually validate the request
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        // If validation fails, return a custom response
        if ($validator->fails()) {
            return ShopittPlus::response(false, $validator->errors()->first(), 422);
        }

        // Check if the email exists in the database
        $emailExists = User::where('email', $request->input('email'))->exists();

        if ($emailExists) {
            return ShopittPlus::response(false, 'Email is already registered.', 200);
        } else {
            return ShopittPlus::response(true, 'Email is available.', 200);
        }
    }
}