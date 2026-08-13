<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'color', 'sort_order'];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
