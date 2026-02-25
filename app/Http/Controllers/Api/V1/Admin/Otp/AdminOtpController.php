<?php

namespace App\Http\Controllers\Api\V1\Admin\Otp;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Otp\VerifyVerificationCodeRequest;
use App\Http\Requests\Api\V1\User\Otp\SendVerificationCodeRequest;
use App\Modules\User\Services\OTPService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AdminOtpController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        public OTPService $otpService
    ) {
    }  


    /**
     * This handles generation and sending of otp
     */
    public function send(SendVerificationCodeRequest $request): JsonResponse
    {
        try {

            $payload = $request->validated();

            $expiryMinutes = config('otp.expiry_minutes', 15);

            $expiry = now()->addMinutes($expiryMinutes);

            $this->otpService->generateAndSendOTP(
                $payload['phone'] ?? null,
                $payload['email'] ?? null,
                $expiryMinutes
            );

            return ShopittPlus::response(true, 'Verification code sent.', 200, (object)['expires_at' => $expiry]);
            // 
        } catch (InvalidArgumentException $e) {
            Log::error('SEND ADMIN VERIFICATION CODE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('SEND ADMIN VERIFICATION CODE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to send verification code', 500);
        }
    }

    /**
     * This handles generation and sending of otp for applied verifications
     */
    public function sendForVerification($email, $phone = null, $purpose = null)
    {
        try {
            $expiryMinutes = config('otp.expiry_minutes', 15);

            $expiry = now()->addMinutes($expiryMinutes);

            $this->otpService->generateAndSendOTP(
                $phone ?? null,
                $email ?? null,
                $expiryMinutes,
                $purpose = $purpose ?? 'admin-verification',
            );

            return (object)[
                'status' => true,
                'expires_at' => $expiry
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to send verification code');
        }
    }


    /**
     * This handles validation of otp
     */
    public function verify(VerifyVerificationCodeRequest $request): JsonResponse
    {
        try {

            $payload = $request->validated();

            $otp_identifier = $this->otpService->getVerificationCodeIdentifier(
                $payload['verification_code'],
                $payload['phone'] ?? null,
                $payload['email'] ?? null
            );

            $this->otpService->verifyOTP(
                $payload['verification_code'],
                $otp_identifier,
                $payload['phone'] ?? null,
                $payload['email'] ?? null
            );

            return ShopittPlus::response(true, 'Otp verified', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('VERIFY ADMIN VERIFICATION CODE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('VERIFY ADMIN VERIFICATION CODE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to verify verification code', 500);
        }
    }

    /**
     * This handles validation of otp
     */
    public function verifyAppliedCode($email, $code, $phone = null, $purpose = null)
    {
        try {
            $otp_identifier = $this->otpService->getVerificationCodeIdentifier(
                $code,
                $phone ?? null,
                $email ?? null
            );

            $verification_code = $this->otpService->verifyOTP(
                $code,
                $otp_identifier,
                $phone ?? null,
                $email ?? null
            );

            if ($verification_code?->purpose !== $purpose) {
                throw new InvalidArgumentException('Verification code does not match the purpose');
            }

            return (object)[
                'status' => true,
                'verification_code' => $verification_code
            ];
            // 
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
