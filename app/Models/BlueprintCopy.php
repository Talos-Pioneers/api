<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlueprintCopy extends Model
{
    /** @use HasFactory<\Database\Factories\BlueprintCopyFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'blueprint_id',
        'ip_address',
        'copied_at',
    ];

    protected function casts(): array
    {
        return [
            'copied_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class);
    }
}
