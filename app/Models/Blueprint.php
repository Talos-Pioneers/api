<?php

namespace App\Models;

use App\Enums\GameVersion;
use App\Enums\Region;
use App\Enums\Status;
use BeyondCode\Comments\Traits\HasComments;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

class Blueprint extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\BlueprintFactory> */
    use HasComments, HasFactory, HasSlug, HasTags, HasUlids, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'creator_id',
        'title',
        'slug',
        'version',
        'description',
        'status',
        'region',
        'code',
        'is_anonymous',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'region' => Region::class,
            'version' => GameVersion::class,
            'is_anonymous' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(BlueprintCollection::class, 'blueprint_collection_blueprints');
    }

    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'blueprint_likes')
            ->withTimestamps();
    }

    public function copies(): HasMany
    {
        return $this->hasMany(BlueprintCopy::class);
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'blueprint_facilities')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function itemInputs(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'blueprint_item_inputs')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function itemOutputs(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'blueprint_item_outputs')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // If the relationship is already loaded, use it to avoid N+1 queries
        if ($this->relationLoaded('likes')) {
            return $this->likes->contains('id', $user->id);
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    #[Scope]
    public function scopeCreatedById(Builder $query, string|int $id): Builder
    {
        return $query->where('creator_id', $id)->where('is_anonymous', false);
    }

    #[Scope]
    public function withFacilitySlug(Builder $query, ...$slugs): Builder
    {
        return $query->whereHas('facilities', function ($q) use ($slugs) {
            $q->whereIn('slug', $slugs);
        });
    }

    #[Scope]
    public function withItemInputSlug(Builder $query, ...$slugs): Builder
    {
        return $query->whereHas('itemInputs', function ($q) use ($slugs) {
            $q->whereIn('slug', $slugs);
        });
    }

    #[Scope]
    public function withItemOutputSlug(Builder $query, ...$slugs): Builder
    {
        return $query->whereHas('itemOutputs', function ($q) use ($slugs) {
            $q->whereIn('slug', $slugs);
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
}
