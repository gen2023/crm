<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permission slugs for every module active in Phase 1. Future modules
     * (Customers, Products, Orders, ...) add their own slugs here without
     * touching anything else in this seeder.
     *
     * @var list<string>
     */
    private const PERMISSIONS = [
        'users.view',
        'users.create',
        'users.edit',
        'users.delete',
        'roles.view',
        'roles.create',
        'roles.edit',
        'roles.delete',
    ];

    /**
     * Role names/slugs are not hardcoded elsewhere in the app — add, rename,
     * or remove roles here without any code change.
     *
     * @var list<string>
     */
    private const ROLES = ['admin', 'manager', 'user'];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (self::ROLES as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        Role::findByName('admin')->syncPermissions(self::PERMISSIONS);
    }
}
