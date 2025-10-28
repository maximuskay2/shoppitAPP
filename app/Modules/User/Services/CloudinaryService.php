<?php

namespace App\Modules\User\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    /**
     * Upload KYC document to Cloudinary
     *
     * @param UploadedFile $file
     * @param string $userId
     * @param string $documentType
     * @param string $kycVerificationId
     * @return array
     */
    public function uploadKycDocument(UploadedFile $file, string $userId, string $documentType = 'cac'): array
    {
        try {
            // Generate unique public ID
            $publicId = "{$userId}/{$documentType}_" . time();
            
            // Upload to Cloudinary
            $result = cloudinary()->uploadApi()->upload($file->getRealPath(), [
                'public_id' => $publicId,
                'folder' => 'shopittplus/kyc-documents',
                'resource_type' => 'auto',
                // 'access_control' => 'authenticated', // Private access
                "access_control" => [
                    ["access_type" => "token"],
                    ["access_type" => "anonymous", "start" => now()->toISOString(), "end" => now()->addMinutes(10)->toISOString()],
                ],
                'type' => 'authenticated',
                'tags' => ['kyc', 'document', $documentType, $userId],
                'context' => [
                    'user_id' => $userId,
                    'document_type' => $documentType,
                    'uploaded_at' => now()->toISOString(),
                ],
                'transformation' => [
                    'quality' => 'auto:good',
                    'fetch_format' => 'auto',
                ],
                // Add image-specific transformations
                'eager' => [
                    [
                        'width' => 2000,
                        'height' => 2000,
                        'crop' => 'limit',
                        'quality' => 'auto:good',
                        'fetch_format' => 'auto'
                    ]
                ],
            ]);

            // Get the uploaded file information
            $publicId = $result['public_id'];
            $secureUrl = $result['secure_url'];
            $version = $result['version'];
            $format = $result['format'];
            $bytes = $result['bytes'];
            $width = $result['width'];
            $height = $result['height'];

            return [
                'success' => true,
                'data' => [
                    'public_id' => $publicId,
                    'secure_url' => $secureUrl,
                    'url' => str_replace('https://', 'http://', $secureUrl),
                    'version' => $version,
                    'format' => $format,
                    'resource_type' => $this->isImage($file) ? 'image' : 'raw',
                    'bytes' => $bytes,
                    'width' => $width,
                    'height' => $height,
                    'created_at' => now()->toISOString(),
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Cloudinary upload failed', [
                'user_id' => $userId,
                'document_type' => $documentType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to upload document to cloud storage',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function uploadUserAvatar(UploadedFile $file, string $userId): array
    {
        try {
            // Generate unique public ID
            $publicId = "{$userId}/avatar_" . time();
            // Upload to Cloudinary
            $result = cloudinary()->uploadApi()->upload($file->getRealPath(), [
                'public_id' => $publicId,
                'folder' => 'shopittplus/user-avatars',
                'resource_type' => 'auto',
                'tags' => ['user_avatar', $userId],
                'context' => [
                    'user_id' => $userId,
                    'uploaded_at' => now()->toISOString(),
                ],
                'transformation' => [
                    'quality' => 'auto:good',
                    'fetch_format' => 'auto',
                ],
                // Add image-specific transformations
                'eager' => [
                    [
                        'width' => 2000,
                        'height' => 2000,
                        'crop' => 'limit',
                        'quality' => 'auto:good',
                        'fetch_format' => 'auto'
                    ]
                ],
            ]);

            // Get the uploaded file information
            $publicId = $result['public_id'];
            $secureUrl = $result['secure_url'];
            $version = $result['version'];
            $format = $result['format'];
            $bytes = $result['bytes'];
            $width = $result['width'];
            $height = $result['height'];

            Log::info('User avatar uploaded to Cloudinary', [
                'user_id' => $userId,
                'public_id' => $publicId,
                'secure_url' => $secureUrl,
                'bytes' => $bytes,
            ]);

            return [
                'success' => true,
                'data' => [
                    'public_id' => $publicId,
                    'secure_url' => $secureUrl,
                    'url' => str_replace('https://', 'http://', $secureUrl),
                    'version' => $version,
                    'format' => $format,
                    'resource_type' => $this->isImage($file) ? 'image' : 'raw',
                    'bytes' => $bytes,
                    'width' => $width,
                    'height' => $height,
                    'created_at' => now()->toISOString(),
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Cloudinary upload failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => 'Failed to upload avatar to cloud storage',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete document from Cloudinary
     *
     * @param string $publicId
     * @return array
     */
    public function deleteDocument(string $publicId): array
    {
        try {
            $result = Cloudinary::destroy($publicId, [
                'type' => 'authenticated',
                'invalidate' => true,
            ]);

            Log::info('Document deleted from Cloudinary', [
                'public_id' => $publicId,
                'result' => $result,
            ]);

            return [
                'success' => isset($result['result']) && $result['result'] === 'ok',
                'data' => $result,
            ];

        } catch (\Exception $e) {
            Log::error('Cloudinary delete failed', [
                'public_id' => $publicId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete document from cloud storage',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate authenticated URL for document access
     *
     * @param string $publicId
     * @param int $expiresIn Expiration time in seconds (default: 1 hour)
     * @return string
     */
    public function getAuthenticatedUrl(string $publicId, int $expiresIn = 3600): string
    {
        try {
            // Generate authenticated URL using Cloudinary Laravel package
            $authenticatedUrl = Cloudinary::getUrl($publicId, [
                'type' => 'authenticated',
                'sign_url' => true,
                'auth_token' => [
                    'duration' => $expiresIn,
                    'start_time' => now()->timestamp,
                ]
            ]);

            return $authenticatedUrl;

        } catch (\Exception $e) {
            Log::error('Failed to generate authenticated URL', [
                'public_id' => $publicId,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Generate a signed download URL for admin access
     *
     * @param string $publicId
     * @param string $filename
     * @return string
     */
    public function getDownloadUrl(string $publicId, string $filename = null): string
    {
        try {
            $options = [
                'type' => 'authenticated',
                'sign_url' => true,
                'flags' => 'attachment'
            ];

            if ($filename) {
                $options['public_id'] = $publicId;
                $options['attachment'] = $filename;
            }

            return Cloudinary::getUrl($publicId, $options);

        } catch (\Exception $e) {
            Log::error('Failed to generate download URL', [
                'public_id' => $publicId,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Get file information from Cloudinary
     *
     * @param string $publicId
     * @return array
     */
    public function getFileInfo(string $publicId): array
    {
        try {
            $result = Cloudinary::admin()->asset($publicId, [
                'type' => 'authenticated'
            ]);

            return [
                'success' => true,
                'data' => $result,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get file info from Cloudinary', [
                'public_id' => $publicId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve file information',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate thumbnail URL for images
     *
     * @param string $publicId
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getThumbnailUrl(string $publicId, int $width = 300, int $height = 300)
    {
        try {
            // $result = cloudinary()->uploadApi()->upload
            return cloudinary()->uploadApi()->explicit($publicId, [
                'type' => 'authenticated',
                'sign_url' => true,
                'transformation' => [
                    'width' => $width,
                    'height' => $height,
                    'crop' => 'fill',
                    'quality' => 'auto:good',
                    'fetch_format' => 'auto',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate thumbnail URL', [
                'public_id' => $publicId,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Check if file is an image
     *
     * @param UploadedFile $file
     * @return bool
     */
    private function isImage(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
        ]);
    }

    /**
     * Validate document before upload
     *
     * @param UploadedFile $file
     * @param string $documentType
     * @return bool
     */
    public function validateDocument(UploadedFile $file, string $documentType = 'cac'): bool
    {
        // File size check (max 10MB)
        if ($file->getSize() > 10485760) {
            return false;
        }

        // MIME type check
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return false;
        }

        // Additional validation for images
        if ($this->isImage($file)) {
            $imageInfo = @getimagesize($file->getRealPath());
            if (!$imageInfo) {
                return false;
            }

            // Check minimum dimensions (e.g., 200x200 pixels)
            if ($imageInfo[0] < 200 || $imageInfo[1] < 200) {
                return false;
            }
        }

        return true;
    }
}