<?php

namespace App\Services\Admin;

use App\Models\Beneficiary;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminBeneficiaryService
{
    /**
     * Get all beneficiaries with filters
     */
    public function getBeneficiaries(array $filters = []): mixed
    {
        try {
            $query = Beneficiary::with(['user'])
                ->orderBy('created_at', 'desc');

            // Apply search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('payload->account_number', 'LIKE', '%' . $search . '%')
                      ->orWhere('payload->account_name', 'LIKE', '%' . $search . '%')
                      ->orWhere('payload->bank_name', 'LIKE', '%' . $search . '%')
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'LIKE', '%' . $search . '%')
                                    ->orWhere('email', 'LIKE', '%' . $search . '%')
                                    ->orWhere('username', 'LIKE', '%' . $search . '%');
                      });
                });
            }

            // Apply service filter
            if (isset($filters['service']) && !empty($filters['service'])) {
                $query->where('service', $filters['service']);
            }

            // Apply date range filters
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            }

            // Paginate results
            $perPage = $filters['per_page'] ?? 15;
            $beneficiaries = $query->paginate($perPage);

            $formattedBeneficiaries = $beneficiaries->getCollection()->map(function ($beneficiary) {
                return [
                    'id' => $beneficiary->id,
                    'user' => $beneficiary->user ? [
                        'id' => $beneficiary->user->id,
                        'name' => $beneficiary->user->name,
                        'email' => $beneficiary->user->email,
                        'username' => $beneficiary->user->username,
                    ] : null,
                    'service' => $beneficiary->service,
                    'account_number' => $beneficiary->payload['account_number'] ?? null,
                    'account_name' => $beneficiary->payload['account_name'] ?? null,
                    'bank_name' => $beneficiary->payload['bank_name'] ?? null,
                    'bank_code' => $beneficiary->payload['bank_code'] ?? null,
                    'created_at' => $beneficiary->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $beneficiary->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            $beneficiaries->setCollection($formattedBeneficiaries);

            return $beneficiaries;
        } catch (Exception $e) {
            Log::error('ADMIN BENEFICIARY SERVICE - GET BENEFICIARIES: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single beneficiary with detailed information
     */
    public function getBeneficiary(string $id): array
    {
        try {
            $beneficiary = Beneficiary::with(['user'])->find($id);

            if (!$beneficiary) {
                throw new Exception('Beneficiary not found');
            }

            return [
                'id' => $beneficiary->id,
                'user' => $beneficiary->user ? [
                    'id' => $beneficiary->user->id,
                    'name' => $beneficiary->user->name,
                    'email' => $beneficiary->user->email,
                    'username' => $beneficiary->user->username,
                    'status' => $beneficiary->user->status,
                ] : null,
                'service' => $beneficiary->service,
                'payload' => $beneficiary->payload,
                'account_number' => $beneficiary->payload['account_number'] ?? null,
                'account_name' => $beneficiary->payload['account_name'] ?? null,
                'bank_name' => $beneficiary->payload['bank_name'] ?? null,
                'bank_code' => $beneficiary->payload['bank_code'] ?? null,
                'phone_number' => $beneficiary->payload['phone_number'] ?? null,
                'network' => $beneficiary->payload['network'] ?? null,
                'created_at' => $beneficiary->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $beneficiary->updated_at->format('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            Log::error('ADMIN BENEFICIARY SERVICE - GET BENEFICIARY: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all beneficiaries for a specific user
     */
    public function getUserBeneficiaries(string $userId, array $filters = []): mixed
    {
        try {
            $query = Beneficiary::where('user_id', $userId)
                ->orderBy('created_at', 'desc');

            // Apply service filter
            if (isset($filters['service']) && !empty($filters['service'])) {
                $query->where('service', $filters['service']);
            }

            // Paginate results
            $perPage = $filters['per_page'] ?? 15;
            $beneficiaries = $query->paginate($perPage);

            $formattedBeneficiaries = $beneficiaries->getCollection()->map(function ($beneficiary) {
                return [
                    'id' => $beneficiary->id,
                    'service' => $beneficiary->service,
                    'account_number' => $beneficiary->payload['account_number'] ?? null,
                    'account_name' => $beneficiary->payload['account_name'] ?? null,
                    'bank_name' => $beneficiary->payload['bank_name'] ?? null,
                    'bank_code' => $beneficiary->payload['bank_code'] ?? null,
                    'phone_number' => $beneficiary->payload['phone_number'] ?? null,
                    'network' => $beneficiary->payload['network'] ?? null,
                    'created_at' => $beneficiary->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $beneficiaries->setCollection($formattedBeneficiaries);

            return $beneficiaries;
        } catch (Exception $e) {
            Log::error('ADMIN BENEFICIARY SERVICE - GET USER BENEFICIARIES: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}