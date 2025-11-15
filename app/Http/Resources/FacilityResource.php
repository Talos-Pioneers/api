<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacilityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'icon' => $this->icon,
            'type' => $this->type?->value,
            'type_display' => $this->type?->displayName(),
            'description' => $this->description,
            'range' => $this->range,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
