<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

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
            'partner_url' => $this->partner_url,
            'title' => $this->title,
            'slug' => $this->slug,
            'version' => $this->version->value,
            'description' => $this->description,
            'status' => $this->status->value,
            'region' => $this->region?->value,
            'server_region' => $this->server_region?->value,
            'facilities' => $this->whenLoaded('facilities', fn () => $this->facilities->map(fn ($facility) => [
                'id' => $facility->id,
                'slug' => $facility->slug,
                'name' => $facility->name,
                'icon' => $facility->icon,
                'quantity' => $facility->pivot->quantity,
            ])),
            'item_inputs' => $this->whenLoaded('itemInputs', fn () => $this->itemInputs->map(fn ($item) => [
                'id' => $item->id,
                'slug' => $item->slug,
                'name' => $item->name,
                'icon' => $item->icon,
                'quantity' => $item->pivot->quantity,
            ])),
            'item_outputs' => $this->whenLoaded('itemOutputs', fn () => $this->itemOutputs->map(fn ($item) => [
                'id' => $item->id,
                'slug' => $item->slug,
                'name' => $item->name,
                'icon' => $item->icon,
                'quantity' => $item->pivot->quantity,
            ])),
            'width' => $this->width,
            'height' => $this->height,
            'creator' => $this->is_anonymous ? null : [
                'id' => $this->creator->id,
                'name' => $this->creator->username,
            ],
            'tags' => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'type' => $tag->type,
            ]),
            'gallery' => $this->getMedia('gallery')
                ->map(fn ($media) => [
                    'id' => (string) $media->id,
                    'thumbnail' => $media->getTemporaryUrl(now()->addMinutes(5), 'thumb'),
                    'url' => $media->getTemporaryUrl(now()->addMinutes(5), 'optimized'),
                    'name' => $media->name,
                ]),
            'likes_count' => $this->whenCounted('likes') ?? $this->likes()->count(),
            'copies_count' => $this->whenCounted('copies') ?? $this->copies()->count(),
            'comments_count' => $this->whenCounted('comments') ?? $this->comments()->count(),
            'is_liked' => $user ? $this->isLikedBy($user) : false,
            'permissions' => [
                'can_edit' => $user ? Gate::forUser($user)->allows('update', $this->resource) : false,
                'can_delete' => $user ? Gate::forUser($user)->allows('delete', $this->resource) : false,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
