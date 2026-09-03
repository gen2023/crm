<?php

namespace App\Modules\Users\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Deactivating (the app's equivalent of "deleting") a user is blocked
     * when it would remove your own access, or strip the system of its
     * last active administrator.
     */
    public function deactivate(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        return ! $this->isLastActiveAdmin($target);
    }

    /**
     * Blocks taking the "admin" role away from a user when they are the
     * last active administrator — the role-assignment equivalent of the
     * same last-administrator protection.
     */
    public function removeAdminRole(User $actor, User $target): bool
    {
        return ! $this->isLastActiveAdmin($target);
    }

    private function isLastActiveAdmin(User $target): bool
    {
        if (! $target->hasRole('admin')) {
            return false;
        }

        return User::role('admin')
            ->where('status', 'active')
            ->where('id', '!=', $target->id)
            ->doesntExist();
    }
}
