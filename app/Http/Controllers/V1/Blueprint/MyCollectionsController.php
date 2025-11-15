<?php

namespace App\Http\Controllers\V1\Blueprint;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlueprintCollectionResource;
use App\Models\BlueprintCollection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;

class MyCollectionsController extends Controller
{
    /**
     * Display a listing of the authenticated user's collections.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return BlueprintCollectionResource::collection(
            QueryBuilder::for(BlueprintCollection::class)
                ->where('creator_id', $user->id)
                ->with(['creator', 'blueprints'])
                ->allowedFilters([
                    'status',
                    'is_anonymous',
                ])
                ->allowedSorts(['created_at', 'updated_at', 'title'])
                ->defaultSort('created_at')
                ->paginate(25)
                ->appends(request()->query())
        );
    }
}
