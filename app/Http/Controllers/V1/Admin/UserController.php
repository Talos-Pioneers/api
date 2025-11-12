<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpgradeUserToModeratorRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Upgrade a user to moderator role.
     */
    public function upgradeToModerator(UpgradeUserToModeratorRequest $request, User $user): JsonResponse
    {
        $user->assignRole('Moderator');

        return response()->json([
            'message' => 'User upgraded to moderator successfully',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'roles' => $user->roles->pluck('name'),
            ],
        ]);
    }
}
