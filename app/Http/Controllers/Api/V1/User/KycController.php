<?php
namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\KycDocumentUploadRequest;
use App\Modules\User\Services\KycService;
use App\Http\Requests\Api\V1\User\KycInitializeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KycController extends Controller
{
    public function __construct(
        private readonly KycService $kycService
    ) {}

    public function getStatus(Request $request)
    {
        $user = $request->user();
        $status = $this->kycService->getKycStatus($user);

        return ShopittPlus::response(true, 'KYC status retrieved successfully', 200, $status);
    }

    public function initializeLevel1(KycInitializeRequest $request)
    {
        $user = $request->user();
        $result = $this->kycService->initializeLevel1Kyc($user, $request->validated());

        return ShopittPlus::response(
            $result['success'],
            $result['message'],
            $result['success'] ? 201 : 400,
            $result['data'] ?? null
        );
    }

    public function uploadDocument(KycDocumentUploadRequest $request)
    {
        $user = $request->user();
        $result = $this->kycService->uploadDocument(
            $user,
            $request->document_type,
            $request->file('document')
        );

        return ShopittPlus::response(
            $result['success'],
            $result['message'],
            $result['success'] ? 201 : 400,
            $result['data'] ?? null
        );
    }

    public function getDocuments(Request $request)
    {
        $user = $request->user();
        $documents = $user->kycDocuments()->latest()->get();

        return ShopittPlus::response(true, 'Documents retrieved successfully', 200, $documents);
    }
}