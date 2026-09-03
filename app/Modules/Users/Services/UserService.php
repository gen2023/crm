<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class UserService
{
    public function paginate(): LengthAwarePaginator
    {
        return User::with('roles')->orderBy('name')->paginate(20);
    }

    public function allRoles(): Collection
    {
        return Role::orderBy('name')->get();
    }

    /**
     * @param  array{name: string, email: string, password: string, status: string, roles?: array<int, string>}  $data
     */
    public function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => $data['status'],
        ]);

        $user->syncRoles($data['roles'] ?? []);

        return $user;
    }

    /**
     * @param  array{name: string, email: string, password?: string|null, status: string, roles?: array<int, string>}  $data
     */
    public function update(User $user, array $data): User
    {
        $newRoles = $data['roles'] ?? [];

        if ($user->hasRole('admin') && ! in_array('admin', $newRoles, true)) {
            Gate::authorize('removeAdminRole', $user);
        }

        if ($data['status'] === 'inactive' && $user->status !== 'inactive') {
            Gate::authorize('deactivate', $user);
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->status = $data['status'];

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();
        $user->syncRoles($newRoles);

        return $user->fresh('roles');
    }

    public function deactivate(User $user): void
    {
        Gate::authorize('deactivate', $user);

        $user->update(['status' => 'inactive']);
    }

    public function activate(User $user): void
    {
        $user->update(['status' => 'active']);
    }
}
