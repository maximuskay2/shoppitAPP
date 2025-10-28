<?php
namespace App\Http\Requests\Api\Admin\Kyc;

use App\Helpers\ShopittPlus;
use App\Modules\User\Models\Admin;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApproveVerificationRequest extends FormRequest
{
    private string $request_uuid;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has admin privileges for KYC approval
        return $this->user() && Admin::where('id', $this->user()->id)->exists();
    }

    public function prepareForValidation(): void
    {
        $this->request_uuid = Str::uuid()->toString();

        Log::channel('daily')->info(
            'ADMIN KYC APPROVAL: START',
            [
                "uid" => $this->request_uuid, 
                "admin_id" => $this->user()?->id,
                "verification_id" => $this->route('verification'),
                "request" => $this->all()
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
            'notes' => [
                'nullable',
                'string',
                'max:1000',
                'min:3'
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
            'notes.string' => 'Notes must be a valid text.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'notes.min' => 'Notes must be at least 3 characters long if provided.',
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
            'notes' => 'approval notes',
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
            'ADMIN KYC APPROVAL: VALIDATION FAILED',
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
            'ADMIN KYC APPROVAL: UNAUTHORIZED ACCESS ATTEMPT',
            [
                "uid" => $this->request_uuid,
                "user_id" => $this->user()?->id,
                "verification_id" => $this->route('verification'),
                "ip" => $this->ip(),
                "user_agent" => $this->userAgent()
            ]
        );

        throw new HttpResponseException(
            ShopittPlus::response(false, 'You are not authorized to approve KYC verifications.', 403)
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
            // Check if verification exists and is in a state that can be approved
            $verification = \App\Modules\User\Models\KycVerification::find($this->route('verification'));
            
            if (!$verification) {
                $validator->errors()->add('verification', 'KYC verification not found.');
                return;
            }

            // Check if verification is in a valid state for approval
            $validStatuses = ['PENDING', 'IN_PROGRESS', 'INPROGRESS'];
            if (!in_array($verification->status->value, $validStatuses)) {
                $validator->errors()->add('verification', 'This verification cannot be approved in its current state.');
            }

            // Check if required documents are uploaded
            $uploadedDocuments = $verification->documents()->pluck('document_type')->toArray();
            
            // Check for identity document (government_id OR passport - either one is acceptable)
            $hasIdentityDocument = in_array('government_id', $uploadedDocuments) || in_array('passport', $uploadedDocuments);
            if (!$hasIdentityDocument) {
                $validator->errors()->add('documents', 'Identity document is required: either Government ID or Passport must be uploaded.');
            }
            
            // Check for selfie (always required)
            if (!in_array('selfie', $uploadedDocuments)) {
                $validator->errors()->add('documents', 'Selfie document is required for verification.');
            }

            // Optional: Check if documents are in approved status
            $rejectedDocuments = $verification->documents()
                ->whereIn('document_type', ['government_id', 'passport', 'selfie'])
                ->where('status', 'REJECTED')
                ->pluck('document_type')
                ->toArray();
            
            if (!empty($rejectedDocuments)) {
                $validator->errors()->add('documents', 'Some required documents have been rejected and need to be re-uploaded: ' . implode(', ', $rejectedDocuments));
            }
        });
    }
}