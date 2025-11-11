<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlueprintResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'slug' => $this->slug,
            'version' => $this->version->value,
            'description' => $this->description,
            'status' => $this->status->value,
            'region' => $this->region?->value,
            'buildings' => $this->buildings,
            'item_inputs' => $this->item_inputs,
            'item_outputs' => $this->item_outputs,
            'creator' => $this->is_anonymous ? null : [
                'id' => $this->creator->id,
                'name' => $this->creator->username,
            ],
            'tags' => $this->tags->map(fn ($tag) => [
                'name' => $tag->name,
                'slug' => $tag->slug,
                'type' => $tag->type,
            ]),
            'gallery' => $this->getMedia('gallery')->map(fn ($media) => [
                'url' => $media->getUrl(),
                'name' => $media->name,
            ]),
            'likes_count' => $this->whenCounted('likes') ?? $this->likes()->count(),
            'copies_count' => $this->whenCounted('copies') ?? $this->copies()->count(),
            'is_liked' => $user ? $this->isLikedBy($user) : false,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
