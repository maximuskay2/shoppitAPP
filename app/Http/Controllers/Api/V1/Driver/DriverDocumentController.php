<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\RuntimeConfig;
use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverDocumentUploadRequest;
use App\Modules\User\Models\DriverDocument;
use App\Modules\User\Models\User;
use App\Modules\User\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DriverDocumentController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinaryService) {}

    public function index(): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());

            $documents = $driver?->driverDocuments()
                ->latest()
                ->get()
                ->map(function (DriverDocument $document) {
                    return $this->mapDocument($document);
                }) ?? [];

            return ShopittPlus::response(true, 'Driver documents retrieved successfully', 200, $documents);
        } catch (\Exception $e) {
            Log::error('DRIVER DOCUMENTS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve documents', 500);
        }
    }

    public function store(DriverDocumentUploadRequest $request): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());

            if (!$driver) {
                return ShopittPlus::response(false, 'Driver not found', 404);
            }

            $documentType = $request->input('document_type');
            $existing = $driver->driverDocuments()
                ->where('document_type', $documentType)
                ->whereNotIn('status', ['REJECTED'])
                ->first();

            if ($existing) {
                return ShopittPlus::response(
                    false,
                    'Document already uploaded for this type',
                    422,
                    ['document_type' => ['A document of this type already exists.']]
                );
            }

            $fileUrl = '';
            $meta = null;

            if (RuntimeConfig::getCloudinaryConfig()['url']) {
                $upload = $this->cloudinaryService->uploadKycDocument(
                    $request->file('document'),
                    $driver->id,
                    $documentType
                );

                if (!$upload['success']) {
                    return ShopittPlus::response(false, $upload['message'] ?? 'Upload failed', 500);
                }

                $fileUrl = $upload['data']['secure_url'] ?? $upload['data']['url'] ?? '';
                $meta = $upload['data'] ?? null;
            } else {
                $path = $request->file('document')->store(
                    'driver-documents/' . $driver->id,
                    'public'
                );
                $fileUrl = Storage::disk('public')->url($path);
                $meta = ['path' => $path, 'storage' => 'local'];
            }

            $document = DriverDocument::create([
                'driver_id' => $driver->id,
                'document_type' => $documentType,
                'file_url' => $fileUrl,
                'status' => 'PENDING',
                'expires_at' => $request->input('expires_at'),
                'meta' => $meta,
            ]);

            return ShopittPlus::response(
                true,
                'Document uploaded successfully',
                201,
                $this->mapDocument($document)
            );
        } catch (\Exception $e) {
            Log::error('DRIVER DOCUMENT UPLOAD: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to upload document', 500);
        }
    }

    private function mapDocument(DriverDocument $document): array
    {
        return [
            'id' => $document->id,
            'document_type' => $document->document_type,
            'file_url' => $document->file_url,
            'status' => $document->status,
            'expires_at' => $document->expires_at,
            'verified_at' => $document->verified_at,
            'rejected_at' => $document->rejected_at,
            'rejection_reason' => $document->rejection_reason,
            'created_at' => $document->created_at,
        ];
    }
}
