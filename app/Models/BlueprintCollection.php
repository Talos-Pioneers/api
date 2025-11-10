<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlueprintCollection extends Model
{
    /** @use HasFactory<\Database\Factories\BlueprintCollectionFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'creator_id',
        'title',
        'slug',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
