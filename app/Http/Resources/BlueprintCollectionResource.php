<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class BlueprintCollectionResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status->value,
            'creator' => $this->is_anonymous ? null : [
                'id' => $this->creator->id,
                'name' => $this->creator->username,
            ],
            'blueprints' => $this->whenLoaded('blueprints', function () {
                return $this->blueprints->map(fn ($blueprint) => [
                    'id' => $blueprint->id,
                    'title' => $blueprint->title,
                    'slug' => $blueprint->slug,
                    'code' => $blueprint->code,
                ]);
            }),
            'blueprints_count' => $this->whenCounted('blueprints', $this->blueprints_count),
            'permissions' => [
                'can_edit' => $user ? Gate::forUser($user)->allows('update', $this->resource) : false,
                'can_delete' => $user ? Gate::forUser($user)->allows('delete', $this->resource) : false,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
