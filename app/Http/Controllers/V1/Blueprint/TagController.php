<?php

namespace App\Http\Controllers\V1\Blueprint;

use App\Enums\TagType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\TagResource;
use Illuminate\Http\Request;
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
        if ($request->user()->cannot('create', Tag::class)) {
            abort(403, 'You are not authorized to create tags');
        }

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
        if ($request->user()->cannot('update', $tag)) {
            abort(403, 'You are not authorized to update this tag');
        }

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
    public function destroy(Request $request, Tag $tag): \Illuminate\Http\JsonResponse
    {
        if ($request->user()->cannot('delete', $tag)) {
            abort(403, 'You are not authorized to delete this tag');
        }

        $tag->delete();

        return response()->json(null, 204);
    }
}
