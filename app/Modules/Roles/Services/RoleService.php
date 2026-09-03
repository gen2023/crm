<?php

namespace App\Modules\Roles\Services;

use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function paginate(): LengthAwarePaginator
    {
        return Role::withCount('permissions')->orderBy('name')->paginate(20);
    }

    public function allPermissions(): Collection
    {
        return Permission::orderBy('name')->get();
    }

    /**
     * @param  array{name: string, description?: string|null, permissions?: array<int, string>}  $data
     */
    public function create(array $data): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        $this->auditLogger->log('role.created', $role, [
            'permissions' => $data['permissions'] ?? [],
        ]);

        return $role;
    }

    /**
     * @param  array{name: string, description?: string|null, permissions?: array<int, string>}  $data
     */
    public function update(Role $role, array $data): Role
    {
        $previousPermissions = $role->permissions->pluck('name')->all();

        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $newPermissions = $data['permissions'] ?? [];
        $role->syncPermissions($newPermissions);

        $this->auditLogger->log('role.updated', $role, [
            'permissions' => ['before' => $previousPermissions, 'after' => $newPermissions],
        ]);

        return $role;
    }

    public function delete(Role $role): void
    {
        $this->auditLogger->log('role.deleted', $role, [
            'name' => $role->name,
        ]);

        $role->delete();
    }
}
