<?php

namespace App\Http\Controllers\V1\Blueprint;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlueprintCollectionRequest;
use App\Http\Requests\UpdateBlueprintCollectionRequest;
use App\Http\Resources\BlueprintCollectionResource;
use App\Models\Blueprint;
use App\Models\BlueprintCollection;
use App\Models\User;
use App\Services\AutoMod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BlueprintCollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min($request->input('per_page', 25), 50);

        return BlueprintCollectionResource::collection(
            QueryBuilder::for(BlueprintCollection::class)
                ->with(['creator', 'blueprints'])
                ->where('status', Status::PUBLISHED)
                ->allowedFilters([
                    'is_anonymous',
                    AllowedFilter::scope('author_id', 'createdById'),
                ])
                ->allowedSorts(['created_at', 'updated_at', 'title'])
                ->defaultSort('created_at')
                ->paginate($perPage)->appends(request()->query()
                )
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBlueprintCollectionRequest $request): BlueprintCollectionResource
    {
        $validated = $request->validated();

        if (config('services.auto_mod.enabled')) {
            $autoMod = AutoMod::build()
                ->text($validated['title'] ?? null)
                ->text($validated['description'] ?? null);

            $moderationResult = $autoMod->validate();

            if ($autoMod->fails()) {
                throw ValidationException::withMessages([
                    'moderation' => ['Content moderation failed. Please review your content.'],
                    'flagged_texts' => $moderationResult['flagged_texts'],
                    'flagged_images' => $moderationResult['flagged_images'],
                ]);
            }
        }

        $collection = DB::transaction(function () use ($validated, $request) {
            /** @var User $user */
            $user = $request->user();

            $collection = BlueprintCollection::create([
                'creator_id' => $user->id,
                'title' => $validated['title'],
                'slug' => str($validated['title'])->slug(),
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? Status::DRAFT,
                'is_anonymous' => $validated['is_anonymous'] ?? false,
            ]);

            // Attach blueprints if provided
            if (isset($validated['blueprints']) && is_array($validated['blueprints'])) {
                $blueprints = Blueprint::whereIn('id', $validated['blueprints'])->get();
                $collection->blueprints()->sync($blueprints);
            }

            return $collection->load(['creator', 'blueprints']);
        });

        return new BlueprintCollectionResource($collection);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, BlueprintCollection $collection): BlueprintCollectionResource
    {
        Gate::authorize('view', $collection);

        return new BlueprintCollectionResource($collection->load(['creator', 'blueprints']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBlueprintCollectionRequest $request, BlueprintCollection $collection): BlueprintCollectionResource
    {
        $validated = $request->validated();

        // Run content moderation on updated fields
        if (config('services.auto_mod.enabled')) {
            $autoMod = AutoMod::build();

            if (isset($validated['title'])) {
                $autoMod->text($validated['title']);
            }

            if (isset($validated['description'])) {
                $autoMod->text($validated['description']);
            }

            $moderationResult = $autoMod->validate();

            if ($autoMod->fails()) {
                throw ValidationException::withMessages([
                    'moderation' => ['Content moderation failed. Please review your content.'],
                    'flagged_texts' => $moderationResult['flagged_texts'],
                    'flagged_images' => $moderationResult['flagged_images'],
                ]);
            }
        }

        $collection = DB::transaction(function () use ($collection, $validated) {
            if (isset($validated['title'])) {
                $collection->title = $validated['title'];
                $collection->slug = str($validated['title'])->slug();
            }

            if (isset($validated['description'])) {
                $collection->description = $validated['description'];
            }

            if (isset($validated['status'])) {
                $collection->status = $validated['status'];
            }

            if (isset($validated['is_anonymous'])) {
                $collection->is_anonymous = $validated['is_anonymous'];
            }

            $collection->save();

            // Sync blueprints if provided
            if (isset($validated['blueprints']) && is_array($validated['blueprints'])) {
                $blueprints = Blueprint::whereIn('id', $validated['blueprints'])->get();
                $collection->blueprints()->sync($blueprints);
            }

            return $collection->load(['creator', 'blueprints']);
        });

        return new BlueprintCollectionResource($collection);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, BlueprintCollection $collection): \Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $collection);

        $collection->delete();

        return response()->json(null, 204);
    }
}
