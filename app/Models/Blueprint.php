<?php

namespace App\Models;

use App\Enums\GameVersion;
use App\Enums\Region;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Tags\HasTags;

class Blueprint extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\BlueprintFactory> */
    use HasFactory, HasTags, HasUlids, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'creator_id',
        'title',
        'slug',
        'version',
        'description',
        'status',
        'region',
        'code',
        'buildings',
        'item_inputs',
        'item_outputs',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'region' => Region::class,
            'version' => GameVersion::class,
            'buildings' => 'array',
            'item_inputs' => 'array',
            'item_outputs' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery');
    }
}
