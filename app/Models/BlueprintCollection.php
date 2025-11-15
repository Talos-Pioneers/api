<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class BlueprintCollection extends Model
{
    /** @use HasFactory<\Database\Factories\BlueprintCollectionFactory> */
    use HasFactory, HasSlug, HasUlids, SoftDeletes;

    protected $fillable = [
        'creator_id',
        'title',
        'slug',
        'description',
        'status',
        'is_anonymous',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'is_anonymous' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function blueprints(): BelongsToMany
    {
        return $this->belongsToMany(Blueprint::class, 'blueprint_collection_blueprints');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    #[Scope]
    public function scopeCreatedById(Builder $query, string|int $id): Builder
    {
        return $query->where('creator_id', $id)->where('is_anonymous', false);
    }
}
