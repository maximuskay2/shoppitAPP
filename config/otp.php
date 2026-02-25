<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OTP Expiry Minutes
    |--------------------------------------------------------------------------
    | How long (in minutes) a verification code remains valid. The code stays
    | usable for retries (wrong OTP, wrong password, user errors) until it
    | expires. Used for: forgot password, registration, login OTP, etc.
    */
    'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 15),
];
