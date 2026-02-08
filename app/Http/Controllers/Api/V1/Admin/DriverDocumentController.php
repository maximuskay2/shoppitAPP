<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\User\Models\DriverDocument;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DriverDocumentController extends Controller
{
    public function index(string $driverId): JsonResponse
    {
        try {
            $driver = User::findOrFail($driverId);

            $documents = $driver->driverDocuments()
                ->latest()
                ->get()
                ->map(function (DriverDocument $document) {
                    return $this->mapDocument($document);
                });

            return ShopittPlus::response(true, 'Driver documents retrieved successfully', 200, $documents);
        } catch (\Exception $e) {
            Log::error('ADMIN DRIVER DOCUMENTS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve driver documents', 500);
        }
    }

    public function approve(string $documentId): JsonResponse
    {
        try {
            $document = DriverDocument::findOrFail($documentId);
            $document->status = 'VERIFIED';
            $document->verified_at = now();
            $document->rejected_at = null;
            $document->rejection_reason = null;
            $document->save();

            return ShopittPlus::response(true, 'Document approved successfully', 200, $this->mapDocument($document));
        } catch (\Exception $e) {
            Log::error('ADMIN DRIVER DOCUMENT APPROVE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to approve document', 500);
        }
    }

    public function reject(Request $request, string $documentId): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $document = DriverDocument::findOrFail($documentId);
            $document->status = 'REJECTED';
            $document->rejected_at = now();
            $document->rejection_reason = $data['reason'] ?? null;
            $document->save();

            return ShopittPlus::response(true, 'Document rejected successfully', 200, $this->mapDocument($document));
        } catch (\Exception $e) {
            Log::error('ADMIN DRIVER DOCUMENT REJECT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to reject document', 500);
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
