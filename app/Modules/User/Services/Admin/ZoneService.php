<?php

namespace App\Modules\User\Services\Admin;

use App\Modules\User\Models\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

class ZoneService
{
    /**
     * Get all zones with optional pagination
     *
     * @param bool $paginate
     * @param int $perPage
     * @return mixed
     */
    public function getAllZones(bool $paginate = true, int $perPage = 20)
    {
        $query = Zone::query()
            ->orderBy('continent')
            ->orderBy('country')
            ->orderBy('name');

        if ($paginate) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * Get a single zone by ID
     *
     * @param string $zoneId
     * @return array
     */
    public function getZoneById(string $zoneId): array
    {
        try {
            $zone = Zone::find($zoneId);
            // $zone = Zone::with(['admins', 'users'])->find($zoneId);

            if (!$zone) {
                return [
                    'success' => false,
                    'message' => 'Zone not found',
                ];
            }

            return [
                'success' => true,
                'message' => 'Zone retrieved successfully',
                'data' => $zone,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get zone by ID', [
                'zone_id' => $zoneId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve zone',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get zone by unique key
     *
     * @param string $key
     * @return array
     */
    public function getZoneByKey(string $key): array
    {
        try {
            $zone = Zone::where('key', $key)->first();

            if (!$zone) {
                return [
                    'success' => false,
                    'message' => 'Zone not found',
                ];
            }

            return [
                'success' => true,
                'message' => 'Zone retrieved successfully',
                'data' => $zone,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get zone by key', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve zone',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Filter zones by various criteria
     *
     * @param array $filters
     * @param bool $paginate
     * @param int $perPage
     * @return mixed
     */
    public function filterZones(array $filters = [], bool $paginate = true, int $perPage = 20)
    {
        $query = Zone::query();

        // Filter by continent
        if (!empty($filters['continent'])) {
            $query->where('continent', 'LIKE', '%' . $filters['continent'] . '%');
        }

        // Filter by country
        if (!empty($filters['country'])) {
            $query->where('country', 'LIKE', '%' . $filters['country'] . '%');
        }

        // Filter by name
        if (!empty($filters['name'])) {
            $query->where('name', 'LIKE', '%' . $filters['name'] . '%');
        }

        // Search across all fields
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('continent', 'LIKE', "%{$search}%")
                    ->orWhere('country', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%")
                    ->orWhere('key', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        
        $allowedSortFields = ['continent', 'country', 'name', 'key', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        if ($paginate) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * Get zones by continent
     *
     * @param string $continent
     * @return Collection
     */
    public function getZonesByContinent(string $continent): Collection
    {
        return Zone::where('continent', $continent)
            ->orderBy('country')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get zones by country
     *
     * @param string $country
     * @return Collection
     */
    public function getZonesByCountry(string $country): Collection
    {
        return Zone::where('country', $country)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get zones by continent and country
     *
     * @param string $continent
     * @param string $country
     * @return Collection
     */
    public function getZonesByContinentAndCountry(string $continent, string $country): Collection
    {
        return Zone::where('continent', $continent)
            ->where('country', $country)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all unique continents
     *
     * @return array
     */
    public function getAllContinents(): array
    {
        return Zone::select('continent')
            ->distinct()
            ->orderBy('continent')
            ->pluck('continent')
            ->toArray();
    }

    /**
     * Get all unique countries
     *
     * @param string|null $continent
     * @return array
     */
    public function getAllCountries(?string $continent = null): array
    {
        $query = Zone::select('country')->distinct();

        if ($continent) {
            $query->where('continent', $continent);
        }

        return $query->orderBy('country')
            ->pluck('country')
            ->toArray();
    }

    /**
     * Create a new zone
     *
     * @param array $data
     * @return array
     */
    public function createZone(array $data): array
    {
        try {
            DB::beginTransaction();

            // Generate unique key if not provided
            if (empty($data['key'])) {
                $data['key'] = $this->generateZoneKey($data['continent'], $data['country'], $data['name']);
            }

            // Check if zone already exists
            $existingZone = Zone::where('key', $data['key'])->first();
            if ($existingZone) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'A zone with this key already exists',
                ];
            }

            // Check compound unique constraint
            $duplicateZone = Zone::where('continent', $data['continent'])
                ->where('country', $data['country'])
                ->where('name', $data['name'])
                ->first();

            if ($duplicateZone) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'A zone with this continent, country, and name combination already exists',
                ];
            }

            $zone = Zone::create([
                'continent' => $data['continent'],
                'country' => $data['country'],
                'name' => $data['name'],
                'key' => $data['key'],
                'description' => $data['description'] ?? null,
            ]);

            DB::commit();

            Log::info('Zone created successfully', [
                'zone_id' => $zone->id,
                'key' => $zone->key,
            ]);

            return [
                'success' => true,
                'message' => 'Zone created successfully',
                'data' => $zone,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create zone', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create zone',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update an existing zone
     *
     * @param string $zoneId
     * @param array $data
     * @return array
     */
    public function updateZone(string $zoneId, array $data): array
    {
        try {
            DB::beginTransaction();

            $zone = Zone::find($zoneId);

            if (!$zone) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Zone not found',
                ];
            }

            // Check if updating key would cause conflict
            if (isset($data['key']) && $data['key'] !== $zone->key) {
                $existingZone = Zone::where('key', $data['key'])
                    ->where('id', '!=', $zoneId)
                    ->first();

                if ($existingZone) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'A zone with this key already exists',
                    ];
                }
            }

            // Check compound unique constraint if updating continent, country, or name
            if (isset($data['continent']) || isset($data['country']) || isset($data['name'])) {
                $duplicateZone = Zone::where('continent', $data['continent'] ?? $zone->continent)
                    ->where('country', $data['country'] ?? $zone->country)
                    ->where('name', $data['name'] ?? $zone->name)
                    ->where('id', '!=', $zoneId)
                    ->first();

                if ($duplicateZone) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'A zone with this continent, country, and name combination already exists',
                    ];
                }
            }

            // Update zone
            $zone->update(array_filter($data, function ($value) {
                return $value !== null;
            }));

            DB::commit();

            Log::info('Zone updated successfully', [
                'zone_id' => $zone->id,
                'updated_fields' => array_keys($data),
            ]);

            return [
                'success' => true,
                'message' => 'Zone updated successfully',
                'data' => $zone->fresh(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update zone', [
                'zone_id' => $zoneId,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update zone',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a zone
     *
     * @param string $zoneId
     * @param bool $forceDelete
     * @return array
     */
    public function deleteZone(string $zoneId, bool $forceDelete = false): array
    {
        try {
            DB::beginTransaction();

            $zone = Zone::find($zoneId);

            if (!$zone) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Zone not found',
                ];
            }

            // Check if zone has associated admins or users
            $adminsCount = $zone->admins()->count();
            $usersCount = $zone->users()->count();

            if (($adminsCount > 0 || $usersCount > 0) && !$forceDelete) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => "Cannot delete zone. It has {$adminsCount} admin(s) and {$usersCount} user(s) associated with it.",
                    'data' => [
                        'admins_count' => $adminsCount,
                        'users_count' => $usersCount,
                    ]
                ];
            }

            $zoneName = $zone->name;
            $zone->delete();

            DB::commit();

            Log::info('Zone deleted successfully', [
                'zone_id' => $zoneId,
                'zone_name' => $zoneName,
                'force_delete' => $forceDelete,
            ]);

            return [
                'success' => true,
                'message' => 'Zone deleted successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete zone', [
                'zone_id' => $zoneId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete zone',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Bulk create zones
     *
     * @param array $zonesData
     * @return array
     */
    public function bulkCreateZones(array $zonesData): array
    {
        try {
            DB::beginTransaction();

            $createdZones = [];
            $errors = [];

            foreach ($zonesData as $index => $data) {
                // Generate unique key if not provided
                if (empty($data['key'])) {
                    $data['key'] = $this->generateZoneKey($data['continent'], $data['country'], $data['name']);
                }

                // Check if zone already exists
                $existingZone = Zone::where('key', $data['key'])->first();
                if ($existingZone) {
                    $errors[] = "Zone at index {$index} with key '{$data['key']}' already exists";
                    continue;
                }

                try {
                    $zone = Zone::create([
                        'continent' => $data['continent'],
                        'country' => $data['country'],
                        'name' => $data['name'],
                        'key' => $data['key'],
                        'description' => $data['description'] ?? null,
                    ]);

                    $createdZones[] = $zone;
                } catch (\Exception $e) {
                    $errors[] = "Failed to create zone at index {$index}: {$e->getMessage()}";
                }
            }

            DB::commit();

            Log::info('Bulk zone creation completed', [
                'total_zones' => count($zonesData),
                'created' => count($createdZones),
                'errors' => count($errors),
            ]);

            return [
                'success' => count($errors) === 0,
                'message' => count($createdZones) . ' zones created successfully' . (count($errors) > 0 ? ' with ' . count($errors) . ' errors' : ''),
                'data' => [
                    'created' => $createdZones,
                    'errors' => $errors,
                    'total' => count($zonesData),
                    'success_count' => count($createdZones),
                    'error_count' => count($errors),
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Bulk zone creation failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Bulk zone creation failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get zone statistics
     *
     * @return array
     */
    public function getZoneStatistics(): array
    {
        try {
            $stats = [
                'total_zones' => Zone::count(),
                'continents' => Zone::select('continent')->distinct()->count(),
                'countries' => Zone::select('country')->distinct()->count(),
                'zones_by_continent' => Zone::select('continent', DB::raw('count(*) as count'))
                    ->groupBy('continent')
                    ->orderBy('count', 'desc')
                    ->get()
                    ->pluck('count', 'continent')
                    ->toArray(),
                'zones_by_country' => Zone::select('country', DB::raw('count(*) as count'))
                    ->groupBy('country')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get()
                    ->pluck('count', 'country')
                    ->toArray(),
                'zones_with_admins' => Zone::has('admins')->count(),
                'zones_with_users' => Zone::has('users')->count(),
            ];

            return [
                'success' => true,
                'message' => 'Zone statistics retrieved successfully',
                'data' => $stats,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get zone statistics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve zone statistics',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get admins in a specific zone
     *
     * @param string $zoneId
     * @param bool $paginate
     * @param int $perPage
     * @return array
     */
    public function getZoneAdmins(string $zoneId, bool $paginate = true, int $perPage = 20): array
    {
        try {
            $zone = Zone::find($zoneId);

            if (!$zone) {
                return [
                    'success' => false,
                    'message' => 'Zone not found',
                ];
            }

            $query = $zone->admins();

            $admins = $paginate ? $query->paginate($perPage) : $query->get();

            return [
                'success' => true,
                'message' => 'Zone admins retrieved successfully',
                'data' => [
                    'zone' => $zone,
                    'admins' => $admins,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get zone admins', [
                'zone_id' => $zoneId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve zone admins',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get users in a specific zone
     *
     * @param string $zoneId
     * @param bool $paginate
     * @param int $perPage
     * @return array
     */
    public function getZoneUsers(string $zoneId, bool $paginate = true, int $perPage = 20): array
    {
        try {
            $zone = Zone::find($zoneId);

            if (!$zone) {
                return [
                    'success' => false,
                    'message' => 'Zone not found',
                ];
            }

            $query = $zone->users();

            $users = $paginate ? $query->paginate($perPage) : $query->get();

            return [
                'success' => true,
                'message' => 'Zone users retrieved successfully',
                'data' => [
                    'zone' => $zone,
                    'users' => $users,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get zone users', [
                'zone_id' => $zoneId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve zone users',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate a unique zone key
     *
     * @param string $continent
     * @param string $country
     * @param string $name
     * @return string
     */
    private function generateZoneKey(string $continent, string $country, string $name): string
    {
        $base = Str::slug($name . '_' . $country, '_');
        $key = $base;
        $counter = 1;

        while (Zone::where('key', $key)->exists()) {
            $key = $base . '_' . $counter;
            $counter++;
        }

        return $key;
    }

    /**
     * Validate zone data
     *
     * @param array $data
     * @param bool $isUpdate
     * @return array
     */
    public function validateZoneData(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        if (!$isUpdate || isset($data['continent'])) {
            if (empty($data['continent'])) {
                $errors['continent'] = 'Continent is required';
            }
        }

        if (!$isUpdate || isset($data['country'])) {
            if (empty($data['country'])) {
                $errors['country'] = 'Country is required';
            }
        }

        if (!$isUpdate || isset($data['name'])) {
            if (empty($data['name'])) {
                $errors['name'] = 'Name is required';
            }
        }

        if (isset($data['key'])) {
            if (strlen($data['key']) < 3) {
                $errors['key'] = 'Key must be at least 3 characters long';
            }
            if (!preg_match('/^[a-z0-9_]+$/', $data['key'])) {
                $errors['key'] = 'Key can only contain lowercase letters, numbers, and underscores';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}