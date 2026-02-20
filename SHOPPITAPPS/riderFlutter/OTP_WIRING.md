# OTP Wiring – riderFlutter + Laravel API

## Overview

The riderFlutter app uses **one backend OTP flow** for both **email** and **SMS**:

- **Email OTP** → Laravel **Mail** (SMTP / `MAIL_*` config).
- **SMS OTP** → **EbulkSMS** API (`config/services.ebulksms`).

All OTP generation, storage, and verification happen in the API; the app only calls the endpoints below.

---

## 1. Flutter (riderFlutter) – OTP screens and API calls

### API paths (from `lib/core/network/api_paths.dart`)

| Purpose              | Path                       | Used for                          |
|----------------------|----------------------------|-----------------------------------|
| Send OTP             | `POST /auth/send-code`     | Generic send (email or phone)     |
| Verify OTP           | `POST /auth/verify-code`   | Generic verify (email or phone)   |
| Resend register OTP  | `POST /auth/resend-register-otp` | After driver signup, email only |
| Verify register OTP | `POST /auth/verify-register-otp` | Verify after driver signup   |
| Driver OTP login     | `POST /driver/auth/login-otp`    | Login with OTP (email or phone) |

### OTP verify screen (`lib/features/auth/presentation/otp_verify_screen.dart`)

- **Params:** `email`, `phone`, `useRegisterEndpoint` (bool).
- **Verify:**
  - If `useRegisterEndpoint == true` → `AuthService.verifyRegisterOtp(RegisterOtpVerifyRequest(email, code))` → **POST `/auth/verify-register-otp`**.
  - If `useRegisterEndpoint == false` → `AuthService.verifyOtp(OtpVerifyRequest(email?, phone?, code))` → **POST `/auth/verify-code`**.
- **Resend:**
  - If `useRegisterEndpoint == true` → `AuthService.resendRegisterOtp(RegisterOtpResendRequest(email))` → **POST `/auth/resend-register-otp`** (email only).
  - If `useRegisterEndpoint == false` → `AuthService.sendOtp(OtpSendRequest(email?, phone?))` → **POST `/auth/send-code`** (email or phone).

### Register screen → OTP after signup

- User signs up as driver → **POST `/driver/auth/register`** (API sends email OTP automatically after creating user).
- Then:
  - **Email verification:** Navigate to `OtpVerifyScreen(email: email)` with `useRegisterEndpoint: true` → verify via `/auth/verify-register-otp`, resend via `/auth/resend-register-otp`.
  - **Phone verification (optional flow):** After signup, app can call `sendOtp(OtpSendRequest(phone: phone))` → **POST `/auth/send-code`** with `phone`, then navigate to `OtpVerifyScreen(phone: phone, useRegisterEndpoint: false)` → verify via **POST `/auth/verify-code`**.

So:

- **Email OTP (register)** → sent by API on register; verify/resend use `/auth/verify-register-otp` and `/auth/resend-register-otp`.
- **SMS OTP** → app sends **POST `/auth/send-code`** with `phone`; verify with **POST `/auth/verify-code`** with `phone` + `verification_code`.

---

## 2. Backend – who sends what

### Single OTP service: `App\Modules\User\Services\OTPService`

- **Email:** `Notification::route('mail', $email)->notify(new VerificationCodeNotification($code, $expiryMinutes))`  
  → Uses Laravel **Mail** (SMTP / driver from `config/mail.php` and `.env`).

- **SMS:** `$this->smsService->sendOtp($phone, $message, ...)`  
  → Uses **EbulkSmsService** (EbulkSMS HTTP API).

### Email (SMTP)

- **Config:** `config/mail.php` + `.env`:
  - `MAIL_MAILER` (e.g. `smtp`),
  - `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`,
  - `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.
- **Notification:** `App\Modules\User\Notifications\Otp\VerificationCodeNotification`  
  - Channel: `mail`.  
  - Template: `email.user.otp.verification-code` (markdown).

So **email OTP is wired to whatever SMTP/mailer you configure** (e.g. SMTP server, Mailtrap, SendGrid, etc.) via Laravel’s mail config.

### SMS (EbulkSMS)

- **Config:** `config/services.php` → `services.ebulksms` from `.env`:
  - `EBULKSMS_BASE_URL` (default `https://api.ebulksms.com/sendsms.json`),
  - `EBULKSMS_USERNAME`,
  - `EBULKSMS_API_KEY`,
  - `EBULKSMS_SENDER` (e.g. `ShopittPlus`),
  - `EBULKSMS_DNDSENDER`, `EBULKSMS_COUNTRY_CODE` (e.g. `234`).
- **Service:** `App\Modules\User\Services\EbulkSmsService::sendOtp($phone, $message, ...)`  
  - Sends JSON POST to EbulkSMS API.  
  - If `EBULKSMS_*` are missing, OTP is still generated and stored; only SMS send is skipped (and logged).

So **SMS OTP is wired to EbulkSMS**; no other SMS provider is used in this flow.

---

## 3. Request/response shapes (for reference)

- **POST `/auth/send-code`**  
  - Body: `{ "email"?: string, "phone"?: string }` (at least one).  
  - Backend: `UserOtpController::send` → `OTPService::generateAndSendOTP($phone, $email, 10)` → email via Mail, SMS via EbulkSmsService.

- **POST `/auth/verify-code`**  
  - Body: `{ "verification_code": string, "email"?: string, "phone"?: string }`.  
  - Backend: `UserOtpController::verify` → `OTPService::getVerificationCodeIdentifier` + `verifyOTP`.

- **POST `/auth/resend-register-otp`**  
  - Body: `{ "email": string }`.  
  - Backend: `ResendRegisterOtp` → `UserOtpController::sendForVerification($email, null)` → **email only** (Mail).

- **POST `/auth/verify-register-otp`**  
  - Body: `{ "email": string, "verification_code": string }`.  
  - Backend: `VerifyRegisterOtp` → `UserOtpController::verifyAppliedCode` → marks user email verified, etc.

---

## 4. Summary table

| Channel | Service / driver      | Config / env                                      | Used when                    |
|---------|------------------------|---------------------------------------------------|-------------------------------|
| Email   | Laravel Mail (SMTP)   | `config/mail.php`, `MAIL_*` in `.env`            | Register verification, send-code with `email` |
| SMS     | EbulkSMS API          | `config/services.php` → `ebulksms`, `EBULKSMS_*` in `.env` | Send-code with `phone` only   |

The **OTP screen in riderFlutter** is wired to these endpoints only; it does not choose SMTP or EbulkSMS directly—the API does that based on whether the app sends `email` or `phone` to the send/verify endpoints above.
