<?php

namespace App\Models;

use App\Enums\FacilityType;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Facility extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'type',
        'description',
        'range',
    ];

    protected function casts(): array
    {
        return [
            'range' => 'array',
            'type' => FacilityType::class,
        ];
    }
}
