<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Status;
use App\Models\Blueprint;
use App\Models\User;

class BlueprintPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Blueprint $blueprint): bool
    {
        return $user?->id === $blueprint->creator_id
            || $user?->hasPermissionTo(Permission::MANAGE_ALL_BLUEPRINTS)
            || $blueprint->status === Status::PUBLISHED;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Blueprint $blueprint): bool
    {
        return $user->id === $blueprint->creator_id
            || $user->hasPermissionTo(Permission::MANAGE_ALL_BLUEPRINTS);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Blueprint $blueprint): bool
    {
        return $user->id === $blueprint->creator_id
            || $user->hasPermissionTo(Permission::MANAGE_ALL_BLUEPRINTS);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Blueprint $blueprint): bool
    {
        return $user->id === $blueprint->creator_id
            || $user->hasPermissionTo(Permission::MANAGE_ALL_BLUEPRINTS);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Blueprint $blueprint): bool
    {
        return $user->id === $blueprint->creator_id
            || $user->hasPermissionTo(Permission::MANAGE_ALL_BLUEPRINTS);
    }
}
