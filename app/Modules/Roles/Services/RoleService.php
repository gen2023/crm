<?php

namespace App\Modules\Roles\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
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

        return $role;
    }

    /**
     * @param  array{name: string, description?: string|null, permissions?: array<int, string>}  $data
     */
    public function update(Role $role, array $data): Role
    {
        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
