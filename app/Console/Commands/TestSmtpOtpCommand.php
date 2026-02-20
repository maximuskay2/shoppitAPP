<?php

namespace App\Console\Commands;

use App\Helpers\RuntimeConfig;
use App\Modules\User\Services\OTPService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestSmtpOtpCommand extends Command
{
    protected $signature = 'mail:test-otp {email : Email address to send test OTP}';

    protected $description = 'Send a test OTP email to verify SMTP configuration (dev/production)';

    public function handle(OTPService $otpService): int
    {
        $email = $this->argument('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address.');
            return 1;
        }

        $config = RuntimeConfig::getSmtpConfig();
        $hasHost = !empty($config['host']) || !empty(env('MAIL_HOST'));

        if (!$hasHost) {
            $this->warn('SMTP host not configured. Set MAIL_HOST in .env or configure via Admin Settings.');
            return 1;
        }

        $this->info('SMTP config: mailer=' . ($config['mailer'] ?? 'smtp') . ', host=' . ($config['host'] ?: '(env)'));
        $this->info("Sending test OTP to: {$email}");

        try {
            // Use sync queue so we get immediate feedback (bypasses queued notifications)
            $originalQueue = config('queue.default');
            config(['queue.default' => 'sync']);

            $otpService->generateAndSendOTP(null, $email, 10);

            config(['queue.default' => $originalQueue]);

            $this->info('Test OTP email sent successfully. Check inbox (and spam).');
            return 0;
        } catch (\Throwable $e) {
            $this->error('Failed to send: ' . $e->getMessage());
            return 1;
        }
    }
}
