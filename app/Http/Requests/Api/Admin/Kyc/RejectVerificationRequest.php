<?php
namespace App\Http\Requests\Api\Admin\Kyc;

use App\Helpers\ShopittPlus;
use App\Modules\User\Models\Admin;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RejectVerificationRequest extends FormRequest
{
    private string $request_uuid;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has admin privileges for KYC rejection
        return $this->user() && Admin::where('id', $this->user()->id)->exists();
    }

    public function prepareForValidation(): void
    {
        $this->request_uuid = Str::uuid()->toString();

        Log::channel('daily')->info(
            'ADMIN KYC REJECTION: START',
            [
                "uid" => $this->request_uuid, 
                "admin_id" => $this->user()?->id,
                "verification_id" => $this->route('verification'),
                "request" => $this->except(['reason']) // Don't log sensitive rejection reason
            ]
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'max:255',
                'min:10',
                'in:invalid_document,poor_quality,expired_document,document_mismatch,incomplete_information,suspicious_activity,other'
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
                'min:5'
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'A rejection reason is required.',
            'reason.string' => 'Rejection reason must be a valid text.',
            'reason.max' => 'Rejection reason cannot exceed 255 characters.',
            'reason.min' => 'Rejection reason must be at least 10 characters long.',
            'reason.in' => 'Please select a valid rejection reason.',
            'notes.string' => 'Notes must be a valid text.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'notes.min' => 'Notes must be at least 5 characters long if provided.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reason' => 'rejection reason',
            'notes' => 'additional notes',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  Validator  $validator
     * @return void
     *
     * @throws HttpResponseException
     */
    public function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        
        // Get the first validation error message
        $firstError = collect($errors)->flatten()->first();

        Log::channel('daily')->error(
            'ADMIN KYC REJECTION: VALIDATION FAILED',
            [
                "uid" => $this->request_uuid, 
                "admin_id" => $this->user()?->id,
                "verification_id" => $this->route('verification'),
                "errors" => $errors
            ]
        );

        throw new HttpResponseException(
            ShopittPlus::response(false, $firstError, 422)
        );
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws HttpResponseException
     */
    public function failedAuthorization(): void
    {
        Log::channel('daily')->warning(
            'ADMIN KYC REJECTION: UNAUTHORIZED ACCESS ATTEMPT',
            [
                "uid" => $this->request_uuid,
                "user_id" => $this->user()?->id,
                "verification_id" => $this->route('verification'),
                "ip" => $this->ip(),
                "user_agent" => $this->userAgent()
            ]
        );

        throw new HttpResponseException(
            ShopittPlus::response(false, 'You are not authorized to reject KYC verifications.', 403)
        );
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Check if verification exists and is in a state that can be rejected
            $verification = \App\Modules\User\Models\KycVerification::find($this->route('verification'));
            
            if (!$verification) {
                $validator->errors()->add('verification', 'KYC verification not found.');
                return;
            }

            // Check if verification is in a valid state for rejection
            $validStatuses = ['PENDING', 'INPROGRESS', 'IN_PROGRESS'];
            if (!in_array($verification->status->value, $validStatuses)) {
                $validator->errors()->add('verification', 'This verification cannot be rejected in its current state.');
            }

            // If reason is 'other', notes should be required
            if ($this->reason === 'other' && empty($this->notes)) {
                $validator->errors()->add('notes', 'Additional notes are required when rejection reason is "other".');
            }

            // Validate reason against predefined list
            $validReasons = [
                'invalid_document' => 'Invalid or unreadable document',
                'poor_quality' => 'Poor image quality',
                'expired_document' => 'Document has expired',
                'document_mismatch' => 'Document information does not match profile',
                'incomplete_information' => 'Incomplete or missing information',
                'suspicious_activity' => 'Suspicious or fraudulent activity detected',
                'other' => 'Other reason (see notes)'
            ];

            if (!array_key_exists($this->reason, $validReasons)) {
                $validator->errors()->add('reason', 'Invalid rejection reason selected.');
            }
        });
    }

    /**
     * Get the display name for the rejection reason
     *
     * @return string
     */
    public function getReasonDisplayName(): string
    {
        $reasonMap = [
            'invalid_document' => 'Invalid or unreadable document',
            'poor_quality' => 'Poor image quality',
            'expired_document' => 'Document has expired',
            'document_mismatch' => 'Document information does not match profile',
            'incomplete_information' => 'Incomplete or missing information',
            'suspicious_activity' => 'Suspicious or fraudulent activity detected',
            'other' => 'Other reason'
        ];

        return $reasonMap[$this->reason] ?? $this->reason;
    }
}