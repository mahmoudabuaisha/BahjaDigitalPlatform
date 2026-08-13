<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $fillable = ['name', 'slug', 'sort_order'];

    public function shelterCenters(): HasMany
    {
        return $this->hasMany(ShelterCenter::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
