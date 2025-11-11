<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Http\Requests\StoreBlueprintCollectionRequest;
use App\Http\Requests\UpdateBlueprintCollectionRequest;
use App\Http\Resources\BlueprintCollectionResource;
use App\Models\Blueprint;
use App\Models\BlueprintCollection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BlueprintCollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        if ($request->user()->cannot('viewAny', BlueprintCollection::class)) {
            abort(403, 'You are not authorized to view any collections');
        }

        return BlueprintCollectionResource::collection(
            BlueprintCollection::query()
                ->with(['creator', 'blueprints'])
                ->where('status', Status::PUBLISHED)
                ->latest()
                ->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBlueprintCollectionRequest $request): BlueprintCollectionResource
    {
        $validated = $request->validated();

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
        if ($request->user()->cannot('view', $collection)) {
            abort(403, 'You are not authorized to view this collection');
        }

        return new BlueprintCollectionResource($collection->load(['creator', 'blueprints']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBlueprintCollectionRequest $request, BlueprintCollection $collection): BlueprintCollectionResource
    {
        $validated = $request->validated();

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
        if ($request->user()->cannot('delete', $collection)) {
            abort(403, 'You are not authorized to delete this collection');
        }

        $collection->delete();

        return response()->json(null, 204);
    }
}
