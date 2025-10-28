<?php

namespace App\Http\Requests\Api\V1\User;

use App\Helpers\ShopittPlus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class KycDocumentUploadRequest extends FormRequest
{
    private string $request_uuid;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->request_uuid = Str::uuid()->toString();

        Log::channel('daily')->info(
            'KYC DOCUMENT UPLOAD: START',
            [
                "uid" => $this->request_uuid, 
                "user_id" => $this->user()?->id, 
                "document_type" => $this->document_type,
                "file_info" => $this->file('document') ? [
                    'original_name' => $this->file('document')->getClientOriginalName(),
                    'size' => $this->file('document')->getSize(),
                    'mime_type' => $this->file('document')->getMimeType()
                ] : null
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
            'document_type' => [
                'required',
                'string',
                'in:government_id,passport,drivers_license,utility_bill,selfie,bank_statement'
            ],
            'document' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg',
                'max:2048', // 2MB max
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
            'document_type.required' => 'Document type is required.',
            'document_type.in' => 'Invalid document type. Allowed types: Government ID, Passport, Driver\'s License, Utility Bill, Selfie, Bank Statement.',
            'document.required' => 'Document file is required.',
            'document.file' => 'The uploaded document must be a valid file.',
            'document.mimes' => 'Document must be a JPEG, PNG, JPG, or PDF file.',
            'document.max' => 'Document file size cannot exceed 5MB.',
            'document.min' => 'Document file is too small. Please upload a valid document.',
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
            'KYC DOCUMENT UPLOAD: VALIDATION FAILED',
            [
                "uid" => $this->request_uuid, 
                "user_id" => $this->user()?->id,
                "document_type" => $this->document_type,
                "errors" => $errors
            ]
        );

        throw new HttpResponseException(
            ShopittPlus::response(false, $firstError, 422)
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
            // Check if user has an active KYC verification
            $user = $this->user();
            if ($user && !$user->currentKycVerification) {
                $validator->errors()->add('document', 'Please initialize KYC verification before uploading documents.');
                return;
            }

            // Check if document type already exists for current verification
            if ($user && $this->document_type) {
                $existingDocument = $user->kycDocuments()
                    ->where('document_type', $this->document_type)
                    ->where('kyc_verification_id', $user->currentKycVerification?->id)
                    ->whereNotIn('status', ['REJECTED'])
                    ->first();

                if ($existingDocument) {
                    $validator->errors()->add('document_type', 'A document of this type has already been uploaded for your current verification.');
                }
            }

            // Validate file content type matches extension
            if ($this->file('document')) {
                $file = $this->file('document');
                $extension = strtolower($file->getClientOriginalExtension());
                $mimeType = $file->getMimeType();

                $validMimes = [
                    'jpg' => ['image/jpeg', 'image/jpg'],
                    'jpeg' => ['image/jpeg', 'image/jpg'],
                    'png' => ['image/png'],
                    'pdf' => ['application/pdf']
                ];

                if (isset($validMimes[$extension]) && !in_array($mimeType, $validMimes[$extension])) {
                    $validator->errors()->add('document', 'File content does not match the file extension.');
                }

                // Additional security check for image files
                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $imageInfo = @getimagesize($file->getRealPath());
                    if (!$imageInfo) {
                        $validator->errors()->add('document', 'Invalid image file.');
                    }
                }
            }
        });
    }

    /**
     * Get the document type display name.
     *
     * @return string
     */
    public function getDocumentTypeDisplayName(): string
    {
        $displayNames = [
            'government_id' => 'Government ID',
            'passport' => 'Passport',
            'drivers_license' => 'Driver\'s License',
            'utility_bill' => 'Utility Bill',
            'selfie' => 'Selfie',
            'bank_statement' => 'Bank Statement'
        ];

        return $displayNames[$this->document_type] ?? $this->document_type;
    }
}