<?php

namespace App\Helpers;

/**
 * OTP (One-Time Password) generation and validation utility
 */
class OTPHelper
{
    /**
     * Minimum OTP length
     */
    const MIN_LENGTH = 4;

    /**
     * Maximum OTP length
     */
    const MAX_LENGTH = 10;

    /**
     * Default OTP length
     */
    const DEFAULT_LENGTH = 6;

    /**
     * OTP validity duration in minutes
     */
    const VALIDITY_MINUTES = 15;

    /**
     * Generate a random OTP
     *
     * @param int $length Length of OTP (default 6)
     * @return string Generated OTP
     */
    public static function generate(int $length = self::DEFAULT_LENGTH): string
    {
        $length = max(self::MIN_LENGTH, min(self::MAX_LENGTH, $length));
        
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= random_int(0, 9);
        }
        
        return $otp;
    }

    /**
     * Validate OTP length
     *
     * @param string $otp OTP to validate
     * @return bool True if valid length
     */
    public static function validateLength(string $otp): bool
    {
        $length = strlen($otp);
        return $length >= self::MIN_LENGTH && $length <= self::MAX_LENGTH;
    }

    /**
     * Validate OTP format (numeric only)
     *
     * @param string $otp OTP to validate
     * @return bool True if valid format
     */
    public static function validateFormat(string $otp): bool
    {
        return preg_match('/^\d+$/', $otp) === 1;
    }

    /**
     * Validate complete OTP
     *
     * @param string $otp OTP to validate
     * @return bool True if valid
     */
    public static function validate(string $otp): bool
    {
        return self::validateFormat($otp) && self::validateLength($otp);
    }

    /**
     * Compare OTP with stored OTP
     *
     * @param string $providedOtp OTP provided by user
     * @param string $storedOtp Stored OTP in database
     * @return bool True if OTPs match
     */
    public static function compare(string $providedOtp, string $storedOtp): bool
    {
        // Constant time comparison to prevent timing attacks
        return hash_equals($providedOtp, $storedOtp);
    }
}
