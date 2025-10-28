<?php

namespace App\Modules\User\Data\Auth;

use App\Http\Requests\Api\V1\Auth\RegisterRequest;

class RegisterDTO
{
    public string $email;

    public static function fromRequest(RegisterRequest $request): self
    {
        $dto = new self();
        $body = $request->validated();
        $dto->email = $body['email'];
        return $dto;
    }
}