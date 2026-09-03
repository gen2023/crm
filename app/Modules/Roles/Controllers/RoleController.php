<?php

namespace App\Modules\Roles\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Requests\StoreRoleRequest;
use App\Modules\Roles\Requests\UpdateRoleRequest;
use App\Modules\Roles\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roleService)
    {
    }

    public function index(): View
    {
        return view('roles.index', [
            'roles' => $this->roleService->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('roles.create', [
            'permissions' => $this->roleService->allPermissions(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roleService->create($request->validated());

        return redirect()->route('roles.index')->with('status', 'Роль создана.');
    }

    public function show(Role $role): View
    {
        return view('roles.show', [
            'role' => $role->load('permissions'),
        ]);
    }

    public function edit(Role $role): View
    {
        return view('roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => $this->roleService->allPermissions(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->roleService->update($role, $request->validated());

        return redirect()->route('roles.index')->with('status', 'Роль обновлена.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->roleService->delete($role);

        return redirect()->route('roles.index')->with('status', 'Роль удалена.');
    }
}
