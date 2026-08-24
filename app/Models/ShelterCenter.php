<?php

namespace App\Models;

use App\Enums\LocationVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShelterCenter extends Model
{
    protected $fillable = ['area_id', 'name', 'type', 'address', 'visibility', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'visibility' => LocationVisibility::class,
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
