<?php

namespace App\Modules\User\Services;

use App\Modules\User\Enums\KYCLevelEnum;
use App\Modules\User\Enums\KYCStatusEnum;
use App\Modules\User\Enums\KYCDocumentStatusEnum;
use App\Modules\User\Models\Admin;
use App\Modules\User\Models\User;
use App\Modules\User\Models\KycVerification;
use App\Modules\User\Models\KycDocument;
use App\Modules\User\Models\Role;
use App\Modules\User\Notifications\KycVerificationSubmittedNotification;
use App\Modules\User\Notifications\KycDocumentSubmittedNotification;
use App\Modules\User\Notifications\KycApprovedNotification;
use App\Modules\User\Notifications\KycDocumentReceivedNotification;
use App\Modules\User\Notifications\KycRejectedNotification;
use App\Modules\User\Notifications\KycVerificationReceivedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KycService
{
    // KYC Limits Configuration
    private const KYC_LIMITS = [
        'LEVEL_0' => ['daily' => 100, 'monthly' => 1000, 'withdrawal' => false],
        'LEVEL_1' => ['daily' => 50000, 'monthly' => 50000000, 'withdrawal' => true],
        'LEVEL_2' => ['daily' => 1000000, 'monthly' => 500000000, 'withdrawal' => true],
        // 'LEVEL_3' => ['daily' => 1000000, 'monthly' => 500000000, 'withdrawal' => true],
    ];

    public function __construct(
        private readonly CloudinaryService $cloudinaryService
    ) {}

    public function initializeLevel1Kyc(User $user, array $personalInfo): array
    {
        try {
            DB::beginTransaction();

            // Check if user already has pending or approved KYC
            $existingKyc = $user->kycVerifications()
                ->whereIn('status', [KYCStatusEnum::PENDING, KYCStatusEnum::INPROGRESS, KYCStatusEnum::VERIFIED])
                ->first();

            if ($existingKyc) {
                return [
                    'success' => false,
                    'message' => 'KYC verification already in progress or completed',
                    'data' => $existingKyc
                ];
            }

            // Create KYC verification record
            $kycVerification = KycVerification::create([
                'user_id' => $user->id,
                'level' => KYCLevelEnum::LEVEL_1,
                'status' => KYCStatusEnum::PENDING,
                'personal_info' => $personalInfo,
                'submitted_at' => now(),
            ]);

            // Update user KYC status
            $user->update([
                'kyc_status' => KYCStatusEnum::PENDING,
                'kyc_level' => KYCLevelEnum::LEVEL_0, // Keep at 0 until approved
            ]);

            DB::commit();

            $admins = Admin::where('role_id', Role::where('key', 'kyc_officer')->first()->id)->get();


            foreach ($admins as $admin) {
                $admin->notify(new KycVerificationReceivedNotification($kycVerification));
            }

            // Send notification
            $user->notify(new KycVerificationSubmittedNotification($kycVerification));

            return [
                'success' => true,
                'message' => 'Level 1 KYC initiated successfully',
                'data' => $kycVerification
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('KYC Level 1 initiation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initiate KYC verification',
                'error' => $e->getMessage()
            ];
        }
    }

    public function uploadDocument(User $user, string $documentType, UploadedFile $file): array
    {
        try {
            DB::beginTransaction();

            // Validate file using CloudinaryService
            if (!$this->cloudinaryService->validateDocument($file, $documentType)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Invalid document file. Please ensure it meets the requirements.'
                ];
            }

            // Get current KYC verification
            $kycVerification = $user->currentKycVerification;
            if (!$kycVerification) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'No active KYC verification found. Please start KYC process first.'
                ];
            }

            // Check for existing document of same type
            $existingDocument = $user->kycDocuments()
                ->where('document_type', $documentType)
                ->where('kyc_verification_id', $kycVerification->id)
                ->whereNotIn('status', [KYCDocumentStatusEnum::REJECTED])
                ->first();

            if ($existingDocument) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'A document of this type has already been uploaded for your current verification.'
                ];
            }

            // Upload to Cloudinary
            $uploadResult = $this->cloudinaryService->uploadKycDocument(
                $file,
                $user->id,
                $documentType,
                $kycVerification->id
            );

            if (!$uploadResult['success']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => $uploadResult['message'],
                    'error' => $uploadResult['error'] ?? null
                ];
            }

            $cloudinaryData = $uploadResult['data'];

            // Create document record
            $document = KycDocument::create([
                'user_id' => $user->id,
                'kyc_verification_id' => $kycVerification->id,
                'document_type' => $documentType,
                'file_path' => $cloudinaryData['secure_url'],
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $cloudinaryData['bytes'],
                'status' => KYCDocumentStatusEnum::PENDING,
                'cloudinary_public_id' => $cloudinaryData['public_id'],
            ]);

            // Update KYC status to in progress
            $kycVerification->update(['status' => KYCStatusEnum::INPROGRESS]);
            $user->update(['kyc_status' => KYCStatusEnum::INPROGRESS]);

            DB::commit();

            $admins = Admin::where('role_id', Role::where('key', 'kyc_officer')->first()->id)->get();


            foreach ($admins as $admin) {
                $admin->notify(new KycDocumentReceivedNotification($kycVerification));
            }

            // Send notification
            $user->notify(new KycDocumentSubmittedNotification($document));

            Log::info('KYC Document uploaded successfully', [
                'user_id' => $user->id,
                'document_id' => $document->id,
                'document_type' => $documentType,
                'cloudinary_public_id' => $cloudinaryData['public_id'],
            ]);
            
            return [
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => $document
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Document upload failed', [
                'user_id' => $user->id,
                'document_type' => $documentType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to upload document. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }

    public function approveKyc(KycVerification $kycVerification, Admin $reviewer, string $notes = null): array
    {
        try {
            DB::beginTransaction();

            $user = $kycVerification->user;
            $limits = self::KYC_LIMITS[$kycVerification->level->value];

            // Update KYC verification
            $kycVerification->update([
                'status' => KYCStatusEnum::VERIFIED,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'admin_notes' => $notes,
            ]);

            $userService = app(UserService::class);
            $userService->updateUserAccount($user, [
                'name' => $kycVerification->personal_info['first_name'] . ' ' . $kycVerification->personal_info['last_name'],
                'date_of_birth' => $kycVerification->personal_info['date_of_birth'],
                'phone' => $kycVerification->personal_info['phone'],
                'address' => $kycVerification->personal_info['address'] . ', ' . $kycVerification->personal_info['city'] . ', ' . $kycVerification->personal_info['state'],
                'country' => $kycVerification->personal_info['country'],
                'postal_code' => $kycVerification->personal_info['postal_code'],
                'kyc_status' => KYCStatusEnum::VERIFIED,
                'kyc_level' => $kycVerification->level,
                'kyc_approved_at' => now(),
                'daily_withdrawal_limit' => $limits['daily'],
                'monthly_withdrawal_limit' => $limits['monthly'],
                'withdrawal_enabled' => true,
                'deposit_enabled' => true,
            ]);

            // Approve all related documents
            $kycVerification->documents()->update([
                'status' => KYCDocumentStatusEnum::APPROVED,
                'verified_at' => now(),
                'verified_by' => $reviewer->id,
            ]);

            DB::commit();

            // Send notification
            $user->notify(new KycApprovedNotification($kycVerification));

            return [
                'success' => true,
                'message' => 'KYC approved successfully',
                'data' => $kycVerification->fresh()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('KYC approval failed', [
                'kyc_id' => $kycVerification->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to approve KYC',
                'error' => $e->getMessage()
            ];
        }
    }

    public function rejectKyc(KycVerification $kycVerification, Admin $reviewer, string $reason, string $notes = null): array
    {
        try {
            DB::beginTransaction();

            $user = $kycVerification->user;

            // Update KYC verification
            $kycVerification->update([
                'status' => KYCStatusEnum::FAILED,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'rejection_reason' => $reason,
                'admin_notes' => $notes,
            ]);

            // Update user
            $user->update([
                'kyc_status' => KYCStatusEnum::FAILED,
            ]);

            $kycVerification->documents()->update([
                'status' => KYCDocumentStatusEnum::REJECTED,
                'verified_at' => now(),
                'verified_by' => $reviewer->id,
            ]);

            DB::commit();

            // Send notification
            $user->notify(new KycRejectedNotification($kycVerification));

            return [
                'success' => true,
                'message' => 'KYC rejected successfully',
                'data' => $kycVerification->fresh()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('KYC rejection failed', [
                'kyc_id' => $kycVerification->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reject KYC',
                'error' => $e->getMessage()
            ];
        }
    }

    public function getKycStatus(User $user): array
    {
        $currentKyc = $user->currentKycVerification;
        $documents = $user->kycDocuments()->latest()->get();

        return [
            'user_kyc_level' => $user->kyc_level,
            'user_kyc_status' => $user->kyc_status,
            'current_verification' => $currentKyc,
            'documents' => $documents,
            'limits' => [
                'daily_withdrawal_limit' => $user->daily_withdrawal_limit,
                'monthly_withdrawal_limit' => $user->monthly_withdrawal_limit,
                'remaining_daily_limit' => $user->getRemainingDailyLimit(),
                'remaining_monthly_limit' => $user->getRemainingMonthlyLimit(),
                'withdrawal_enabled' => $user->withdrawal_enabled,
                'deposit_enabled' => $user->deposit_enabled,
            ]
        ];
    }

    private function validateDocument(UploadedFile $file, string $documentType): bool
    {
        // File size check (max 5MB)
        if ($file->getSize() > 5242880) {
            return false;
        }

        // MIME type check
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return false;
        }

        return true;
    }
}