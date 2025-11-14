<?php

namespace App\Http\Controllers\V1\Blueprint;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Blueprint;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\QueryBuilder\QueryBuilder;

class BlueprintCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Blueprint $blueprint): AnonymousResourceCollection
    {
        $comments = QueryBuilder::for(Comment::class)
            ->where('commentable_type', Blueprint::class)
            ->where('commentable_id', $blueprint->id)
            ->where('is_approved', true)
            ->with(['commentator', 'comments.commentator'])
            ->withCount('comments')
            ->defaultSort('-created_at')
            ->paginate(25)
            ->appends(request()->query());

        return CommentResource::collection($comments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommentRequest $request, Blueprint $blueprint): CommentResource|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Rate limiting: 1 comment per minute
        $rateLimitKey = "comment:user:{$user->id}";

        $canComment = RateLimiter::attempt(
            $rateLimitKey,
            1,
            function () use ($blueprint, $user, $request) {
                return $blueprint->commentAsUser($user, $request->validated()['comment']);
            },
            60 // 1 minute in seconds
        );

        if (! $canComment) {
            return response()->json([
                'message' => 'You can only post 1 comment per minute. Please try again later.',
            ], 429);
        }

        $comment = Comment::where('commentable_type', Blueprint::class)
            ->where('commentable_id', $blueprint->id)
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        return new CommentResource($comment->load(['commentator']));
    }

    /**
     * Display the specified resource.
     */
    public function show(Blueprint $blueprint, Comment $comment): CommentResource
    {
        return new CommentResource($comment->load(['commentator', 'comments.commentator'])->loadCount('comments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommentRequest $request, Blueprint $blueprint, Comment $comment): CommentResource
    {
        $comment->update([
            'comment' => $request->validated()['comment'],
            'is_edited' => true,
        ]);

        return new CommentResource($comment->load(['commentator'])->loadCount('comments'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Blueprint $blueprint, Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json(null, 204);
    }
}
