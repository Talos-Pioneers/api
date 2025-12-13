<?php

namespace App\Http\Controllers\V1\Blueprint;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlueprintRequest;
use App\Http\Requests\UpdateBlueprintRequest;
use App\Http\Resources\BlueprintResource;
use App\Mail\AutoModFlaggedMail;
use App\Models\Blueprint;
use App\Models\BlueprintCopy;
use App\Models\User;
use App\Services\AutoMod;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\Tags\Tag;

class BlueprintController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(HandlePrecognitiveRequests::class, only: ['store', 'update', 'destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min($request->input('per_page', 25), 50);

        return BlueprintResource::collection(
            QueryBuilder::for(Blueprint::class)
                ->with(['creator', 'tags', 'facilities', 'itemInputs', 'itemOutputs'])
                ->withCount(['likes', 'copies', 'comments'])
                ->where('status', Status::PUBLISHED)
                ->allowedFilters([
                    'region',
                    'server_region',
                    'version',
                    'is_anonymous',
                    AllowedFilter::scope('author_id', 'createdById'),
                    AllowedFilter::scope('facility', 'withFacilitySlug', arrayValueDelimiter: ','),
                    AllowedFilter::scope('item_input', 'withItemInputSlug', arrayValueDelimiter: ','),
                    AllowedFilter::scope('item_output', 'withItemOutputSlug', arrayValueDelimiter: ','),
                    'likes_count',
                    'copies_count',
                    'width',
                    'height',
                    AllowedFilter::exact('tags.id', arrayValueDelimiter: ','),
                ])
                ->allowedSorts(['created_at', 'updated_at', 'title', 'likes_count', 'copies_count'])
                ->defaultSort('-created_at')
                ->paginate($perPage)
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
        $needsReview = false;
        $moderationResult = null;
        if (config('services.auto_mod.enabled')) {
            $autoMod = AutoMod::build()
                ->text($validated['title'] ?? null, 'title')
                ->text($validated['description'] ?? null, 'description');

            if ($request->hasFile('gallery')) {
                $autoMod->images($request->file('gallery'));
            }

            $moderationResult = $autoMod->validate();

            if ($autoMod->fails()) {
                $needsReview = true;

                // Notify all admins about flagged content
                /** @var User $author */
                $author = $request->user();
                $admins = User::role('Admin')->get();

                foreach ($admins as $admin) {
                    Mail::to($admin)->queue(new AutoModFlaggedMail(
                        contentType: 'blueprint',
                        contentTitle: $validated['title'] ?? 'Untitled',
                        author: $author,
                        flaggedTexts: $moderationResult['flagged_texts'] ?? [],
                        flaggedImages: $moderationResult['flagged_images'] ?? [],
                    ));
                }
            }
        }

        $blueprint = DB::transaction(function () use ($validated, $request, $needsReview) {
            /** @var User $user */
            $user = $request->user();

            $blueprint = Blueprint::create([
                'creator_id' => $user->id,
                'code' => $validated['code'],
                'title' => $validated['title'],
                'slug' => str($validated['title'])->slug(),
                'version' => $validated['version'],
                'description' => $validated['description'] ?? null,
                'status' => $needsReview ? Status::REVIEW : ($validated['status'] ?? Status::DRAFT),
                'region' => (empty($validated['region']) || $validated['region'] === \App\Enums\Region::ANY->value) ? null : $validated['region'],
                'server_region' => $validated['server_region'] ?? null,
                'is_anonymous' => $validated['is_anonymous'] ?? false,
                'width' => $validated['width'] ?? null,
                'height' => $validated['height'] ?? null,
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
                foreach ($request->file('gallery') as $index => $file) {
                    $media = $blueprint->addMedia($file)
                        ->usingName($file->getClientOriginalName())
                        ->usingFileName($file->getClientOriginalName())
                        ->toMediaCollection('gallery');
                    $media->order_column = $index;
                    $media->save();
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
        $needsReview = false;
        $moderationResult = null;
        if (config('services.auto_mod.enabled')) {
            $autoMod = AutoMod::build();

            if (isset($validated['title'])) {
                $autoMod->text($validated['title'], 'title');
            }

            if (isset($validated['description'])) {
                $autoMod->text($validated['description'], 'description');
            }

            if ($request->hasFile('gallery')) {
                $autoMod->images($request->file('gallery'));
            }

            $moderationResult = $autoMod->validate();

            if ($autoMod->fails()) {
                $needsReview = true;

                // Notify all admins about flagged content
                /** @var User $author */
                $author = $request->user();
                $admins = User::role('Admin')->get();

                foreach ($admins as $admin) {
                    Mail::to($admin)->queue(new AutoModFlaggedMail(
                        contentType: 'blueprint',
                        contentTitle: $validated['title'] ?? $blueprint->title,
                        author: $author,
                        flaggedTexts: $moderationResult['flagged_texts'] ?? [],
                        flaggedImages: $moderationResult['flagged_images'] ?? [],
                    ));
                }
            }
        }

        $blueprint = DB::transaction(function () use ($blueprint, $validated, $request, $needsReview) {
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
            } elseif ($needsReview) {
                $blueprint->status = Status::REVIEW;
            }

            if (isset($validated['region'])) {
                $blueprint->region = (empty($validated['region']) || $validated['region'] === \App\Enums\Region::ANY->value) ? null : $validated['region'];
            } else {
                $blueprint->region = null;
            }

            if (isset($validated['server_region'])) {
                $blueprint->server_region = $validated['server_region'];
            }

            if (isset($validated['is_anonymous'])) {
                $blueprint->is_anonymous = $validated['is_anonymous'];
            }

            if (isset($validated['width'])) {
                $blueprint->width = $validated['width'];
            }

            if (isset($validated['height'])) {
                $blueprint->height = $validated['height'];
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

            // Handle gallery image deletion - delete images not in keep list
            $keepIds = $validated['gallery_keep_ids'] ?? [];
            $keepIds = array_map('intval', $keepIds);
            $currentMedia = $blueprint->getMedia('gallery');
            foreach ($currentMedia as $media) {
                if (! in_array((int) $media->id, $keepIds, true)) {
                    $media->delete();
                }
            }

            // Process gallery order if provided
            $galleryOrder = $validated['gallery_order'] ?? [];
            $newImageIndex = 0;
            $newMediaItems = [];

            // First, upload new images and collect them
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $file) {
                    $mediaItem = $blueprint->addMedia($file)
                        ->usingName($file->getClientOriginalName())
                        ->usingFileName($file->getClientOriginalName())
                        ->toMediaCollection('gallery');
                    $newMediaItems[] = $mediaItem;
                }
            }

            // Now update order for all images based on gallery_order
            if (! empty($galleryOrder)) {
                $newImageCounter = 0;
                foreach ($galleryOrder as $order => $identifier) {
                    if (str_starts_with($identifier, 'new_') && isset($newMediaItems[$newImageCounter])) {
                        $newMediaItems[$newImageCounter]->order_column = $order;
                        $newMediaItems[$newImageCounter]->save();
                        $newImageCounter++;

                        continue;
                    }

                    if (! str_starts_with($identifier, 'new_')) {
                        $mediaId = (int) $identifier;
                        $existingMedia = $blueprint->getMedia('gallery')->firstWhere('id', $mediaId);

                        if ($existingMedia) {
                            $existingMedia->order_column = $order;
                            $existingMedia->save();
                        }
                    }
                }
            }

            if (empty($galleryOrder) && ! empty($newMediaItems)) {
                // Fallback: if no order provided, set order for new images based on existing count
                $existingCount = count($keepIds);
                foreach ($newMediaItems as $index => $mediaItem) {
                    $mediaItem->order_column = $existingCount + $index;
                    $mediaItem->save();
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
