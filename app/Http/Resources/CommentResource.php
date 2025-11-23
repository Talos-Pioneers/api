<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class CommentResource extends JsonResource
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
            'comment' => $this->comment,
            'is_approved' => $this->is_approved,
            'is_edited' => $this->is_edited ?? false,
            'user' => $this->when($this->commentator, function () {
                return [
                    'id' => $this->commentator->id,
                    'username' => $this->commentator->username,
                ];
            }),
            'commentable' => [
                'type' => $this->commentable_type,
                'id' => $this->commentable_id,
            ],
            'replies' => $this->when($this->relationLoaded('comments'), function () {
                return CommentResource::collection($this->comments);
            }),
            'replies_count' => $this->whenCounted('comments', $this->comments_count),
            'permissions' => [
                'can_edit' => $user ? Gate::forUser($user)->allows('update', $this->resource) : false,
                'can_delete' => $user ? Gate::forUser($user)->allows('delete', $this->resource) : false,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
