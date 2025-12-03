<?php

namespace App\Listeners;

use App\Mail\AutoModFlaggedMail;
use App\Models\User;
use App\Services\AutoMod;
use BeyondCode\Comments\Events\CommentAdded;
use Illuminate\Support\Facades\Mail;

class AutoApproveComment
{
    /**
     * Handle the event.
     */
    public function handle(CommentAdded $event): void
    {
        $comment = $event->comment;

        // Only auto-approve if AutoMod is enabled
        if (! config('services.auto_mod.enabled')) {
            return;
        }

        // Run AutoMod validation on the comment text
        $autoMod = AutoMod::build()
            ->text($comment->comment, 'Comment');

        $moderationResult = $autoMod->validate();

        // If AutoMod passes, approve the comment
        if ($autoMod->passes()) {
            $comment->approve();
        } else {
            // Notify all admins about flagged comment
            $author = $comment->user;
            $commentable = $comment->commentable;
            $contentTitle = method_exists($commentable, 'getTitle')
                ? $commentable->getTitle()
                : ($commentable->title ?? 'Comment on '.class_basename($commentable));

            $admins = User::role('Admin')->get();

            foreach ($admins as $admin) {
                Mail::to($admin)->queue(new AutoModFlaggedMail(
                    contentType: 'comment',
                    contentTitle: $contentTitle,
                    author: $author,
                    flaggedTexts: $moderationResult['flagged_texts'] ?? [],
                    flaggedImages: $moderationResult['flagged_images'] ?? [],
                ));
            }
        }
    }
}
