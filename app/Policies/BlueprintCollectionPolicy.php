<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Status;
use App\Models\BlueprintCollection;
use App\Models\User;

class BlueprintCollectionPolicy
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
     * Public (published) collections can be viewed by anyone.
     * Private (draft) collections can only be viewed by the creator.
     */
    public function view(User $user, BlueprintCollection $blueprintCollection): bool
    {
        return $blueprintCollection->creator_id === $user->id || $blueprintCollection->status === Status::PUBLISHED;
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
    public function update(User $user, BlueprintCollection $blueprintCollection): bool
    {
        return $user->id === $blueprintCollection->creator_id
            || $user->hasPermissionTo(Permission::MANAGE_ALL_COLLECTIONS);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BlueprintCollection $blueprintCollection): bool
    {
        return $user->id === $blueprintCollection->creator_id
            || $user->hasPermissionTo(Permission::MANAGE_ALL_COLLECTIONS);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BlueprintCollection $blueprintCollection): bool
    {
        return $user->id === $blueprintCollection->creator_id
            || $user->hasPermissionTo(Permission::MANAGE_ALL_COLLECTIONS);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BlueprintCollection $blueprintCollection): bool
    {
        return $user->id === $blueprintCollection->creator_id
            || $user->hasPermissionTo(Permission::MANAGE_ALL_COLLECTIONS);
    }
}
