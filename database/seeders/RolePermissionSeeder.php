<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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
        'customers.view',
        'customers.create',
        'customers.edit',
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
        // Spatie caches the permission list (in the app's default cache
        // store, so it can survive across requests/processes). Without
        // clearing it first, re-running this seeder after new slugs were
        // added to PERMISSIONS fails: syncPermissions() below would still
        // see the stale cached list and report the new ones as unknown.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (self::ROLES as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findByName('admin')->syncPermissions(self::PERMISSIONS);
    }
}
