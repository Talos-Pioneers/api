<?php

namespace App\Http\Controllers;

use App\Enums\TagType;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\TagResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Tags\Tag;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $tags = Tag::query()
            ->latest()
            ->get();

        return TagResource::collection($tags);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTagRequest $request): TagResource
    {
        $validated = $request->validated();

        $tag = Tag::create([
            'name' => $validated['name'],
            'slug' => str($validated['name'])->slug(),
            'type' => $validated['type'] ?? TagType::BLUEPRINT_TAGS,
        ]);

        return new TagResource($tag);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tag $tag): TagResource
    {
        return new TagResource($tag);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTagRequest $request, Tag $tag): TagResource
    {
        $validated = $request->validated();

        if (isset($validated['name'])) {
            $tag->name = $validated['name'];
            $tag->slug = str($validated['name'])->slug();
        }

        if (isset($validated['type'])) {
            $tag->type = $validated['type'];
        }

        $tag->save();

        return new TagResource($tag);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tag $tag): \Illuminate\Http\JsonResponse
    {
        $tag->delete();

        return response()->json(null, 204);
    }
}
