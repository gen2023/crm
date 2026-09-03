<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class UserService
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

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

        $this->auditLogger->log('user.created', $user, [
            'status' => $user->status,
            'roles' => $user->getRoleNames()->all(),
        ]);

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

        // Never audit the password itself — only that it changed, if it did.
        $changes = collect($user->getChanges())->except(['password', 'updated_at'])->all();
        $passwordChanged = $user->wasChanged('password');

        $previousRoles = $user->getRoleNames()->all();
        $user->syncRoles($newRoles);

        $this->auditLogger->log('user.updated', $user, [
            'changes' => $changes,
            'password_changed' => $passwordChanged,
            'roles' => ['before' => $previousRoles, 'after' => $newRoles],
        ]);

        return $user->fresh('roles');
    }

    public function deactivate(User $user): void
    {
        Gate::authorize('deactivate', $user);

        $user->update(['status' => 'inactive']);

        $this->auditLogger->log('user.deactivated', $user);
    }

    public function activate(User $user): void
    {
        $user->update(['status' => 'active']);

        $this->auditLogger->log('user.activated', $user);
    }
}
