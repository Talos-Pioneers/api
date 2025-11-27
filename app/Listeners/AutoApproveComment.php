<?php

namespace App\Listeners;

use App\Services\AutoMod;
use BeyondCode\Comments\Events\CommentAdded;

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
        }
    }
}
