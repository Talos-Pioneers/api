<?php

namespace App\Http\Controllers\V1\Blueprint;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlueprintResource;
use App\Models\Blueprint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MyBlueprintsController extends Controller
{
    /**
     * Display a listing of the authenticated user's blueprints.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return BlueprintResource::collection(
            QueryBuilder::for(Blueprint::class)
                ->where('creator_id', $user->id)
                ->with(['creator', 'tags', 'facilities', 'itemInputs', 'itemOutputs'])
                ->withCount(['likes', 'copies'])
                ->allowedFilters([
                    'status',
                    'region',
                    'server_region',
                    'version',
                    'is_anonymous',
                    AllowedFilter::scope('facility', 'withFacilitySlug', arrayValueDelimiter: ','),
                    AllowedFilter::scope('item_input', 'withItemInputSlug', arrayValueDelimiter: ','),
                    AllowedFilter::scope('item_output', 'withItemOutputSlug', arrayValueDelimiter: ','),
                    'likes_count',
                    'copies_count',
                    AllowedFilter::exact('tags.id', arrayValueDelimiter: ','),
                ])
                ->allowedSorts(['created_at', 'updated_at', 'title'])
                ->defaultSort('created_at')
                ->paginate(25)
                ->appends(request()->query())
        );
    }
}
