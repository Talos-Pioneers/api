<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Http\Requests\StoreBlueprintRequest;
use App\Http\Requests\UpdateBlueprintRequest;
use App\Http\Resources\BlueprintResource;
use App\Models\Blueprint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\Tags\Tag;

class BlueprintController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        if ($request->user()->cannot('viewAny', Blueprint::class)) {
            abort(403, 'You are not authorized to view any blueprints');
        }

        return BlueprintResource::collection(
            QueryBuilder::for(Blueprint::class)
                ->with(['creator', 'tags'])
                ->where('status', Status::PUBLISHED)
                ->allowedFilters(['title', 'region', 'version', 'is_anonymous'])
                ->allowedSorts(['created_at', 'updated_at', 'title'])
                ->defaultSort('created_at')
                ->paginate(25)->appends(request()->query()
                )
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBlueprintRequest $request): BlueprintResource
    {
        $validated = $request->validated();

        $blueprint = DB::transaction(function () use ($validated, $request) {
            /** @var User $user */
            $user = $request->user();

            $blueprint = Blueprint::create([
                'creator_id' => $user->id,
                'code' => $validated['code'],
                'title' => $validated['title'],
                'slug' => str($validated['title'])->slug(),
                'version' => $validated['version'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? \App\Enums\Status::DRAFT,
                'region' => $validated['region'] ?? null,
                'buildings' => $validated['buildings'] ?? null,
                'item_inputs' => $validated['item_inputs'] ?? null,
                'item_outputs' => $validated['item_outputs'] ?? null,
                'is_anonymous' => $validated['is_anonymous'] ?? false,
            ]);

            // Attach tags if provided
            if (isset($validated['tags']) && is_array($validated['tags'])) {
                $tags = Tag::whereIn('id', $validated['tags'])->get();
                $blueprint->syncTags($tags);
            }

            // Handle gallery uploads
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $file) {
                    $blueprint->addMedia($file)
                        ->usingName($file->getClientOriginalName())
                        ->usingFileName($file->getClientOriginalName())
                        ->toMediaCollection('gallery');
                }
            }

            return $blueprint->load(['creator', 'tags']);
        });

        return new BlueprintResource($blueprint);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Blueprint $blueprint): BlueprintResource
    {
        if ($request->user()->cannot('view', $blueprint)) {
            abort(403, 'You are not authorized to view this blueprint');
        }

        return new BlueprintResource($blueprint->load(['creator', 'tags']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBlueprintRequest $request, Blueprint $blueprint): BlueprintResource
    {
        $validated = $request->validated();

        $blueprint = DB::transaction(function () use ($blueprint, $validated, $request) {
            if (isset($validated['title'])) {
                $blueprint->title = $validated['title'];
                $blueprint->slug = str($validated['title'])->slug();
            }

            if (isset($validated['code'])) {
                $blueprint->code = $validated['code'];
            }

            if (isset($validated['version'])) {
                $blueprint->version = $validated['version'];
            }

            if (isset($validated['description'])) {
                $blueprint->description = $validated['description'];
            }

            if (isset($validated['status'])) {
                $blueprint->status = $validated['status'];
            }

            if (isset($validated['region'])) {
                $blueprint->region = $validated['region'];
            }

            if (isset($validated['buildings'])) {
                $blueprint->buildings = $validated['buildings'];
            }

            if (isset($validated['item_inputs'])) {
                $blueprint->item_inputs = $validated['item_inputs'];
            }

            if (isset($validated['item_outputs'])) {
                $blueprint->item_outputs = $validated['item_outputs'];
            }

            if (isset($validated['is_anonymous'])) {
                $blueprint->is_anonymous = $validated['is_anonymous'];
            }

            $blueprint->save();

            // Sync tags if provided
            if (isset($validated['tags']) && is_array($validated['tags'])) {
                $tags = Tag::whereIn('id', $validated['tags'])->get();
                $blueprint->syncTags($tags);
            }

            // Handle gallery uploads
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $file) {
                    $blueprint->addMedia($file)
                        ->usingName($file->getClientOriginalName())
                        ->usingFileName($file->getClientOriginalName())
                        ->toMediaCollection('gallery');
                }
            }

            return $blueprint->load(['creator', 'tags']);
        });

        return new BlueprintResource($blueprint);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Blueprint $blueprint): \Illuminate\Http\JsonResponse
    {
        if ($request->user()->cannot('delete', $blueprint)) {
            abort(403, 'You are not authorized to delete this blueprint');
        }

        $blueprint->delete();

        return response()->json(null, 204);
    }
}
