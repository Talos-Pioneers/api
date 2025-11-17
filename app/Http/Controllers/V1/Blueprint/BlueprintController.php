<?php

namespace App\Http\Controllers\V1\Blueprint;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlueprintRequest;
use App\Http\Requests\UpdateBlueprintRequest;
use App\Http\Resources\BlueprintResource;
use App\Models\Blueprint;
use App\Models\BlueprintCopy;
use App\Models\User;
use App\Services\AutoMod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\Tags\Tag;

class BlueprintController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return BlueprintResource::collection(
            QueryBuilder::for(Blueprint::class)
                ->with(['creator', 'tags', 'facilities', 'itemInputs', 'itemOutputs'])
                ->withCount(['likes', 'copies'])
                ->where('status', Status::PUBLISHED)
                ->allowedFilters([
                    'region',
                    'version',
                    'is_anonymous',
                    AllowedFilter::scope('author_id', 'createdById'),
                    AllowedFilter::scope('facility', 'withFacilitySlug', arrayValueDelimiter: ','),
                    AllowedFilter::scope('item_input', 'withItemInputSlug', arrayValueDelimiter: ','),
                    AllowedFilter::scope('item_output', 'withItemOutputSlug', arrayValueDelimiter: ','),
                    'likes_count',
                    'copies_count',
                    AllowedFilter::exact('tags.id', arrayValueDelimiter: ','),
                ])
                ->allowedSorts(['created_at', 'updated_at', 'title', 'likes_count', 'copies_count'])
                ->defaultSort('created_at')
                ->paginate(25)
                ->appends(request()->query())
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBlueprintRequest $request): BlueprintResource
    {
        $validated = $request->validated();

        // Run content moderation
        if (config('services.auto_mod.enabled')) {
            $autoMod = AutoMod::build()
                ->text($validated['title'] ?? null)
                ->text($validated['description'] ?? null);

            if ($request->hasFile('gallery')) {
                $autoMod->images($request->file('gallery'));
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
                'is_anonymous' => $validated['is_anonymous'] ?? false,
            ]);

            // Sync facilities if provided
            if (isset($validated['facilities']) && is_array($validated['facilities'])) {
                $facilitiesSync = [];
                foreach ($validated['facilities'] as $facility) {
                    $facilitiesSync[$facility['id']] = ['quantity' => $facility['quantity']];
                }
                $blueprint->facilities()->sync($facilitiesSync);
            }

            // Sync item inputs if provided
            if (isset($validated['item_inputs']) && is_array($validated['item_inputs'])) {
                $itemInputsSync = [];
                foreach ($validated['item_inputs'] as $itemInput) {
                    $itemInputsSync[$itemInput['id']] = ['quantity' => $itemInput['quantity']];
                }
                $blueprint->itemInputs()->sync($itemInputsSync);
            }

            // Sync item outputs if provided
            if (isset($validated['item_outputs']) && is_array($validated['item_outputs'])) {
                $itemOutputsSync = [];
                foreach ($validated['item_outputs'] as $itemOutput) {
                    $itemOutputsSync[$itemOutput['id']] = ['quantity' => $itemOutput['quantity']];
                }
                $blueprint->itemOutputs()->sync($itemOutputsSync);
            }

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

            return $blueprint->load(['creator', 'tags', 'facilities', 'itemInputs', 'itemOutputs']);
        });

        return new BlueprintResource($blueprint);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Blueprint $blueprint): BlueprintResource
    {
        Gate::authorize('view', $blueprint);

        return new BlueprintResource($blueprint->load(['creator', 'tags', 'facilities', 'itemInputs', 'itemOutputs'])->loadCount(['likes', 'copies']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBlueprintRequest $request, Blueprint $blueprint): BlueprintResource
    {
        $validated = $request->validated();

        // Run content moderation on updated fields
        $autoMod = AutoMod::build();

        if (isset($validated['title'])) {
            $autoMod->text($validated['title']);
        }

        if (isset($validated['description'])) {
            $autoMod->text($validated['description']);
        }

        if ($request->hasFile('gallery')) {
            $autoMod->images($request->file('gallery'));
        }

        $moderationResult = $autoMod->validate();

        if ($autoMod->fails()) {
            throw ValidationException::withMessages([
                'moderation' => ['Content moderation failed. Please review your content.'],
                'flagged_texts' => $moderationResult['flagged_texts'],
                'flagged_images' => $moderationResult['flagged_images'],
            ]);
        }

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

            if (isset($validated['is_anonymous'])) {
                $blueprint->is_anonymous = $validated['is_anonymous'];
            }

            $blueprint->save();

            // Sync facilities if provided
            if (isset($validated['facilities']) && is_array($validated['facilities'])) {
                $facilitiesSync = [];
                foreach ($validated['facilities'] as $facility) {
                    $facilitiesSync[$facility['id']] = ['quantity' => $facility['quantity']];
                }
                $blueprint->facilities()->sync($facilitiesSync);
            }

            // Sync item inputs if provided
            if (isset($validated['item_inputs']) && is_array($validated['item_inputs'])) {
                $itemInputsSync = [];
                foreach ($validated['item_inputs'] as $itemInput) {
                    $itemInputsSync[$itemInput['id']] = ['quantity' => $itemInput['quantity']];
                }
                $blueprint->itemInputs()->sync($itemInputsSync);
            }

            // Sync item outputs if provided
            if (isset($validated['item_outputs']) && is_array($validated['item_outputs'])) {
                $itemOutputsSync = [];
                foreach ($validated['item_outputs'] as $itemOutput) {
                    $itemOutputsSync[$itemOutput['id']] = ['quantity' => $itemOutput['quantity']];
                }
                $blueprint->itemOutputs()->sync($itemOutputsSync);
            }

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

            return $blueprint->load(['creator', 'tags', 'facilities', 'itemInputs', 'itemOutputs']);
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

    /**
     * Toggle like status for a blueprint.
     */
    public function like(Request $request, Blueprint $blueprint): \Illuminate\Http\JsonResponse
    {
        if ($request->user()->cannot('view', $blueprint)) {
            abort(403, 'You are not authorized to like this blueprint');
        }

        /** @var User $user */
        $user = $request->user();

        $isLiked = $blueprint->isLikedBy($user);

        if ($isLiked) {
            $blueprint->likes()->detach($user->id);
            $liked = false;
        } else {
            $blueprint->likes()->attach($user->id);
            $liked = true;
        }

        $blueprint->refresh();

        return response()->json([
            'liked' => $liked,
            'likes_count' => $blueprint->likes()->count(),
        ]);
    }

    /**
     * Track a blueprint copy with rate limiting.
     */
    public function copy(Request $request, Blueprint $blueprint): \Illuminate\Http\JsonResponse
    {
        if ($request->user()->cannot('view', $blueprint)) {
            abort(403, 'You are not authorized to copy this blueprint');
        }

        /** @var User|null $user */
        $user = $request->user();
        $ipAddress = $request->ip();

        // Check rate limiting: once per day per user or per IP
        $rateLimitKey = $user
            ? "blueprint_copy:{$blueprint->id}:user:{$user->id}"
            : "blueprint_copy:{$blueprint->id}:ip:{$ipAddress}";

        $canCopy = RateLimiter::attempt(
            $rateLimitKey,
            1,
            function () use ($blueprint, $user, $ipAddress) {
                BlueprintCopy::create([
                    'blueprint_id' => $blueprint->id,
                    'user_id' => $user?->id,
                    'ip_address' => $ipAddress,
                    'copied_at' => now(),
                ]);
            },
            86400 // 24 hours in seconds
        );

        if (! $canCopy) {
            return response()->json([
                'message' => 'You have already copied this blueprint today. Please try again tomorrow.',
            ], 429);
        }

        $blueprint->refresh();

        return response()->json([
            'message' => 'Copy tracked successfully',
            'copies_count' => $blueprint->copies()->count(),
        ]);
    }
}
