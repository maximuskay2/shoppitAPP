<?php

namespace App\Modules\User\Data\Auth;

use App\Http\Requests\Api\V1\Auth\LoginRequest;

class LoginDTO
{
    public string $email;
    public string $password;
    public ?string $fcm_device_token;

    public static function fromRequest(LoginRequest $request): self
    {
        $dto = new self();
        $body = $request->validated();
        $dto->email = $body['email'];
        $dto->password = $body['password'];
        if (array_key_exists('fcm_device_token', $body)) {
            $dto->fcm_device_token = $body['fcm_device_token'];
        } else {
            $dto->fcm_device_token = null;
        }
        return $dto;
    }
}