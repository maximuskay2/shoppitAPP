<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Modules\User\Actions\LoginAction;
use App\Modules\User\Data\Auth\LoginDTO;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request)
    {
        $dto = LoginDTO::fromRequest($request);
        return LoginAction::execute($dto);
    }
}