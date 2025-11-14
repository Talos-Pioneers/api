<?php

namespace App\Models;

use BeyondCode\Comments\Comment as BaseComment;

class Comment extends BaseComment
{
    protected $fillable = [
        'comment',
        'user_id',
        'is_approved',
        'is_edited',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts() ?? [], [
            'is_approved' => 'boolean',
            'is_edited' => 'boolean',
        ]);
    }
}
