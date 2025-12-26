<?php

namespace App\Services\Admin;

use App\Models\Role;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AdminRoleService
{
    /**
     * Get all roles with filters
     */
    public function getRoles(array $filters = []): mixed
    {
        try {
            $query = Role::query()->orderBy('created_at', 'desc');

            // Apply search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%')
                      ->orWhere('description', 'LIKE', '%' . $search . '%');
                });
            }

            // Paginate results
            $perPage = $filters['per_page'] ?? 15;
            $roles = $query->paginate($perPage);

            $formattedRoles = $roles->getCollection()->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                    'users_count' => $role->users()->count(),
                    'admins_count' => $role->admins()->count(),
                    'created_at' => $role->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $role->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            $roles->setCollection($formattedRoles);

            return $roles;
        } catch (Exception $e) {
            Log::error('ADMIN ROLE SERVICE - GET ROLES: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single role with detailed information
     */
    public function getRole(string $id): array
    {
        try {
            $role = Role::with(['users', 'admins'])->find($id);

            if (!$role) {
                throw new Exception('Role not found');
            }

            return [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'users_count' => $role->users->count(),
                'admins_count' => $role->admins->count(),
                'users' => $role->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'username' => $user->username,
                    ];
                }),
                'admins' => $role->admins->map(function ($admin) {
                    return [
                        'id' => $admin->id,
                        'name' => $admin->name,
                        'email' => $admin->email,
                    ];
                }),
                'created_at' => $role->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $role->updated_at->format('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            Log::error('ADMIN ROLE SERVICE - GET ROLE: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new role
     */
    public function createRole(array $data): Role
    {
        try {
            // Check if role with name already exists
            if (Role::where('name', $data['name'])->exists()) {
                throw new InvalidArgumentException('Role with this name already exists');
            }

            $role = Role::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            return $role;
        } catch (Exception $e) {
            Log::error('ADMIN ROLE SERVICE - CREATE ROLE: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a role
     */
    public function updateRole(string $id, array $data): Role
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                throw new InvalidArgumentException('Role not found');
            }

            // Check name uniqueness if changed
            if (isset($data['name']) && $data['name'] !== $role->name) {
                if (Role::where('name', $data['name'])->where('id', '!=', $id)->exists()) {
                    throw new InvalidArgumentException('Role with this name already exists');
                }
            }

            $role->update([
                'name' => $data['name'] ?? $role->name,
                'description' => $data['description'] ?? $role->description,
            ]);

            return $role->fresh();
        } catch (Exception $e) {
            Log::error('ADMIN ROLE SERVICE - UPDATE ROLE: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a role
     */
    public function deleteRole(string $id): void
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                throw new InvalidArgumentException('Role not found');
            }

            // Check if role has users or admins assigned
            if ($role->users()->count() > 0 || $role->admins()->count() > 0) {
                throw new InvalidArgumentException('Cannot delete role that has users or admins assigned');
            }

            $role->delete();
        } catch (Exception $e) {
            Log::error('ADMIN ROLE SERVICE - DELETE ROLE: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}