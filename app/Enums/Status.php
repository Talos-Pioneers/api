<?php

namespace App\Enums;

enum Status: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case REVIEW = 'review';
    case ARCHIVED = 'archived';
}
