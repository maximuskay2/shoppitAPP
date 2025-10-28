<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Modules\User\Actions\RegisterAction;
use App\Modules\User\Data\Auth\RegisterDTO;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request)
    {
        Log::info('RegisterController invoked', [
            'request' => $request->validated()
        ]);
        $dto = RegisterDTO::fromRequest($request);
        return RegisterAction::execute($dto);
    }
}