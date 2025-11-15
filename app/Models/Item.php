<?php

namespace App\Models;

use App\Enums\ItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Item extends Model
{
    /** @use HasFactory<\Database\Factories\ItemFactory> */
    use HasFactory, HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'type',
        'description',
        'output_facility_craft_table',
    ];

    protected function casts(): array
    {
        return [
            'output_facility_craft_table' => 'array',
            'type' => ItemType::class,
        ];
    }
}
