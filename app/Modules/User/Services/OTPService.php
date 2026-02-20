<?php

namespace App\Modules\User\Services;

use App\Helpers\RuntimeConfig;
use App\Modules\User\Models\VerificationCode;
use App\Modules\User\Notifications\Otp\VerificationCodeNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OTPService
{
    public function __construct(private readonly EbulkSmsService $smsService) {}


    /**
     * This function runs analysis to detect spam on the otp module
     * 
     * @param string|null $phone
     * @param string|null $email
     */
    private function runSecurityChecks($phone, $email)
    {

        /**
         * pick the user IP, 
         * if it has requested phone OTP more than 5 times in the last 48 hours
         * throw InvalidArgumentException
         */

        $user_ip = request()->ip();

        $count = VerificationCode::where('user_ip', $user_ip)
            ->where('created_at', '>', now()->subDays(2))
            ->count();

        if ($count > 5) {
            logger()->info("$count OTP requests in the last 48 hours from IP $user_ip.. possible spam");
            throw new InvalidArgumentException("You have reached the maximum number of OTP requests for this device.");
        }
    }



    /**
     * This function generates and send otp
     * 
     * @param string|null $phone
     * @param string|null $email
     * @param int $expiryMinutes
     * @return string
     */
    public function generateAndSendOTP($phone = null, $email = null, $expiryMinutes = 10, $purpose = 'verification')
    {
        if (is_null($phone) && is_null($email)) {
            throw new InvalidArgumentException("Either phone or email must be provided.");
        }

        $code = rand(100000, 999999); // Generate a random 6-digit OTP

        $identifier = (string) Str::uuid();

        $user_ip = request()->ip();

        $this->runSecurityChecks($phone, $email); // Uncommenting the security checks
        
        VerificationCode::create([
            'identifier' => $identifier,
            'phone' => $phone,
            'email' => $email,
            'code' => $code,
            'purpose' => $purpose,
            'user_ip' => $user_ip,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        if (!is_null($email)) {
            Notification::route('mail', $email)->notify(new VerificationCodeNotification($code, $expiryMinutes));
        }

        if (!is_null($phone)) {
            $message = "Your verification code is {$code}. Expires in {$expiryMinutes} minutes.";
            $config = RuntimeConfig::getEbulksmsConfig();
            $username = $config['username'] ?? null;
            $apiKey = $config['api_key'] ?? null;
            $sender = $config['sender'] ?? null;

            if ($username && $apiKey && $sender) {
                try {
                    $sent = $this->smsService->sendOtp(
                        $phone,
                        $message,
                        0,
                        (int) ($config['dndsender'] ?? 0)
                    );

                    if (!$sent) {
                        logger()->warning('SMS delivery failed for OTP', [
                            'phone' => $phone,
                        ]);
                    }
                } catch (\Throwable $e) {
                    logger()->warning('SMS delivery error for OTP', [
                        'phone' => $phone,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                logger()->info('OTP generated for phone verification. SMS credentials missing.', [
                    'phone' => $phone,
                    'expires_at' => now()->addMinutes($expiryMinutes)->toISOString(),
                ]);
            }
        }

        return $identifier;
    }


    /**
     * This function gets the identifier of a verification code
     *
     * @param string $code
     * @param string|null $phone
     * @param string|null $email
     * @return string
     */
    public function getVerificationCodeIdentifier(string $code, ?string $phone = null, ?string $email = null)
    {

        if (is_null($code)) {
            throw new InvalidArgumentException("Verification code must be provided.");
        }

        $query = VerificationCode::where('code', $code)
            ->where('expires_at', '>', now());

        if (!is_null($phone) || !is_null($email)) {
            $query->where(function ($q) use ($phone, $email) {
                if (!is_null($phone)) {
                    $q->orWhere('phone', $phone);
                }
                if (!is_null($email)) {
                    $q->orWhere('email', $email);
                }
            });
        } else {
            throw new InvalidArgumentException("Either phone or email must be provided.");
        }

        $otp = $query->latest()->first();

        if (!$otp) {
            throw new InvalidArgumentException("Verification code not found or has expired.");
        }

        return $otp->identifier;
    }



    /**
     * This function verifies the otp code supplied
     *
     * @param string $code
     * @param string $identifier
     * @param string|null $phone
     * @param string|null $email
     * @return VerificationCode
     */
    public function verifyOTP(string $code, string $identifier, ?string $phone = null, ?string $email = null): VerificationCode
    {
        $id = $this->getVerificationCodeIdentifier($code, $phone, $email);

        if ($id !== $identifier) {
            throw new InvalidArgumentException("Invalid verification code identifier.");
        }

        $verification_code = VerificationCode::where('identifier', $identifier)->first();

        $verification_code->update(['expires_at' => now(), 'is_verified' => true]);

        if (!$verification_code) {
            throw new InvalidArgumentException("Verification code not found.");
        }
       
        return $verification_code;
    }
}
