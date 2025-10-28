<?php

namespace App\Modules\User\Data\Admin\Auth;

use Spatie\LaravelData\Data;

class LoginAdminDto extends Data
{
    /**
     * Create's an instance of LoginAdminDto.
     *
     * @param string $request_uuid The UUID of the request.
     * @param string $email The email of the user.
     * @param string $password The password of the user.
     */
    public function __construct(
        public readonly string $request_uuid,
        public readonly string $email,
        public readonly string $password,
    ) {
    }
}
